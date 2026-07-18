<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DocumentAccessLog;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Services\IpfsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', VehicleDocument::class);

        $documents = VehicleDocument::with(['vehicle', 'uploader'])
            ->when(
                ! $request->user()->roleEnum()->canManageFleet(),
                fn ($query) => $query->where('is_public', true)
            )
            ->when($request->vehicle_id, fn ($q, $id) => $q->where('vehicle_id', $id))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'vehicles' => Vehicle::orderBy('license_plate')->get(['id', 'license_plate', 'brand', 'model']),
            'filters' => $request->only('vehicle_id'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', VehicleDocument::class);

        return Inertia::render('Documents/Create', [
            'vehicles' => Vehicle::orderBy('license_plate')->get(['id', 'license_plate', 'brand', 'model']),
        ]);
    }

    public function store(Request $request, IpfsService $ipfs): RedirectResponse
    {
        $this->authorize('create', VehicleDocument::class);

        $data = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'type' => 'required|in:carte_grise,assurance,facture,controle_technique,certificat_inspection,autre',
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'is_public' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        $file = $request->file('file');
        $hash = hash_file('sha256', $file->getRealPath());
        $path = $file->store('documents/'.$data['vehicle_id'], 'local');

        $cid = null;
        if ($request->boolean('is_public') || $data['type'] === 'certificat_inspection') {
            $cid = $ipfs->add($file);

            if (! $cid) {
                Storage::disk('local')->delete($path);

                return back()->withErrors([
                    'file' => 'La publication IPFS a échoué. Le document n’a pas été enregistré comme public.',
                ])->withInput();
            }
        }

        $document = VehicleDocument::create([
            'vehicle_id' => $data['vehicle_id'],
            'uploaded_by' => $request->user()->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'content_hash' => $hash,
            'ipfs_cid' => $cid,
            'is_public' => (bool) $cid,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $vehicle = Vehicle::find($data['vehicle_id']);
        if ($data['type'] === 'carte_grise') {
            $vehicle?->update(['registration_hash' => $hash]);
        }
        if ($data['type'] === 'assurance') {
            $vehicle?->update(['insurance_contract_hash' => $hash]);
        }

        return redirect()->route('documents.index')->with('success', 'Document enregistré (hash : '.substr($hash, 0, 12).'…).');
    }

    public function download(Request $request, VehicleDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        $valid = Storage::disk('local')->exists($document->file_path)
            && hash_equals(
                $document->content_hash,
                hash_file('sha256', Storage::disk('local')->path($document->file_path))
            );

        DocumentAccessLog::create([
            'vehicle_document_id' => $document->id,
            'user_id' => $request->user()->id,
            'action' => 'download',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'integrity_valid' => $valid,
        ]);

        abort_unless($valid, 409, 'Le document ne correspond plus à son empreinte SHA-256.');

        return Storage::disk('local')->download($document->file_path, $document->original_name ?? $document->title);
    }

    public function verify(Request $request, VehicleDocument $document): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $document);

        $valid = Storage::disk('local')->exists($document->file_path)
            && hash_equals(
                $document->content_hash,
                hash_file('sha256', Storage::disk('local')->path($document->file_path))
            );

        DocumentAccessLog::create([
            'vehicle_document_id' => $document->id,
            'user_id' => $request->user()->id,
            'action' => 'verify',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'integrity_valid' => $valid,
        ]);

        return response()->json([
            'valid' => $valid,
            'hash' => $document->content_hash,
            'ipfs_cid' => $document->ipfs_cid,
            'gateway_url' => $document->ipfs_cid
                ? rtrim(config('ipfs.gateway_url'), '/').'/'.$document->ipfs_cid
                : null,
        ], $valid ? 200 : 409);
    }

    public function destroy(VehicleDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document supprimé.');
    }
}
