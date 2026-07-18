<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Blockchain\BlockchainTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()?->roleEnum()->canManageUsers(), 403);

        return Inertia::render('Users/Index', [
            'users' => User::orderBy('name')->paginate(20),
            'roles' => collect(UserRole::cases())->map(fn (UserRole $r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->roleEnum()->canManageUsers(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'email_verified_at' => now(),
        ]);

        return back()->with('success', 'Utilisateur créé.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->roleEnum()->canManageUsers(), 403);

        $data = $request->validate([
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active' => 'boolean',
        ]);

        if ($user->is_verified_onchain
            && ($data['role'] !== UserRole::GaragisteAgree->value || ! ($data['is_active'] ?? false))
        ) {
            return back()->withErrors([
                'role' => 'Révoquez d’abord la certification du garage sur la blockchain.',
            ]);
        }

        $user->update($data);

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    public function setGarageCertification(
        Request $request,
        User $user,
        BlockchainTransactionService $transactions
    ): RedirectResponse {
        abort_unless($request->user()->roleEnum()->canManageUsers(), 403);
        abort_unless($user->hasRole(UserRole::GaragisteAgree), 422, 'Ce compte n’est pas un garagiste agréé.');
        abort_unless($user->wallet_verified_at && $user->wallet_address, 422, 'Le garagiste doit vérifier son wallet.');
        try {
            $transactions->assertReady($request->user(), 'certify_garage');
        } catch (\RuntimeException $exception) {
            return redirect()->route('wallet.show')->with('error', $exception->getMessage());
        }

        $data = $request->validate(['certified' => ['required', 'boolean']]);
        $transaction = $transactions->prepare(
            $user,
            $request->user(),
            'certify_garage',
            'GarageCertificationUpdated',
            'setGarageCertification',
            [$user->wallet_address, (bool) $data['certified']],
            ['certified' => (bool) $data['certified']]
        );

        return redirect()->route('blockchain.transactions.show', $transaction);
    }
}
