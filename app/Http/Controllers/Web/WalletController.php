<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Blockchain\WalletSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class WalletController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Wallet/Connect', [
            'wallet' => [
                'address' => auth()->user()->wallet_address,
                'verified_at' => auth()->user()->wallet_verified_at,
            ],
            'chain' => [
                'id' => (int) config('blockchain.chain_id'),
                'name' => config('blockchain.network'),
                'contract' => config('blockchain.contract_address'),
            ],
        ]);
    }

    public function challenge(Request $request): JsonResponse
    {
        $nonce = Str::random(48);
        $expiresAt = now()->addMinutes(5);

        $request->user()->forceFill([
            'wallet_nonce' => hash('sha256', $nonce),
            'wallet_nonce_expires_at' => $expiresAt,
        ])->save();

        $message = $this->message(
            $request->user()->id,
            $request->getHost(),
            $nonce,
            $expiresAt->toIso8601String()
        );

        $request->session()->put('wallet_challenge_nonce', $nonce);
        $request->session()->put('wallet_challenge_expires', $expiresAt->timestamp);

        return response()->json([
            'message' => $message,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function verify(Request $request, WalletSignatureService $signatures): JsonResponse
    {
        $data = $request->validate([
            'address' => ['required', 'string', 'max:42'],
            'signature' => ['required', 'string', 'max:132'],
        ]);

        $nonce = (string) $request->session()->pull('wallet_challenge_nonce');
        $expiresTimestamp = (int) $request->session()->pull('wallet_challenge_expires');
        $user = $request->user();

        if (! $nonce || now()->timestamp > $expiresTimestamp || $user->wallet_nonce_expires_at?->isPast()) {
            return response()->json(['message' => 'Le challenge wallet a expiré.'], 422);
        }

        if (! hash_equals((string) $user->wallet_nonce, hash('sha256', $nonce))) {
            return response()->json(['message' => 'Challenge wallet invalide.'], 422);
        }

        $message = $this->message(
            $user->id,
            $request->getHost(),
            $nonce,
            $user->wallet_nonce_expires_at->toIso8601String()
        );

        try {
            $claimed = $signatures->normalizeAddress($data['address']);
            $recovered = $signatures->recoverAddress($message, $data['signature']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! hash_equals($claimed, $recovered)) {
            return response()->json(['message' => 'La signature ne correspond pas au wallet.'], 422);
        }

        $alreadyUsed = DB::table('users')
            ->whereRaw('LOWER(wallet_address) = ?', [$claimed])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($alreadyUsed) {
            return response()->json(['message' => 'Ce wallet est déjà lié à un autre compte.'], 422);
        }

        $user->forceFill([
            'wallet_address' => $claimed,
            'wallet_verified_at' => now(),
            'wallet_nonce' => null,
            'wallet_nonce_expires_at' => null,
        ])->save();

        return response()->json([
            'message' => 'Wallet vérifié et lié au compte.',
            'address' => $claimed,
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        if ($request->user()->is_verified_onchain) {
            return response()->json([
                'message' => 'Révoquez d’abord le rôle on-chain associé à ce wallet.',
            ], 409);
        }

        $request->user()->forceFill([
            'wallet_address' => null,
            'wallet_verified_at' => null,
        ])->save();

        return response()->json(['message' => 'Wallet dissocié.']);
    }

    private function message(int $userId, string $host, string $nonce, string $expiresAt): string
    {
        return implode("\n", [
            'AutoChain Emma+ demande la liaison de ce wallet.',
            'Domaine: '.$host,
            'Utilisateur: '.$userId,
            'Nonce: '.$nonce,
            'Expiration: '.$expiresAt,
            'Cette signature ne déclenche aucune transaction.',
        ]);
    }
}
