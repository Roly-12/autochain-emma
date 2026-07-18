<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Notifications\MfaCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user?->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Ce compte est désactivé.',
            ]);
        }

        if ($user?->requiresMfa()) {
            $code = random_int(100000, 999999);
            $user->notify(new MfaCodeNotification((string) $code));
            $request->session()->put('mfa_code', (string) $code);
            $request->session()->put('mfa_user_id', $user->getKey());
            $request->session()->put('mfa_expires_at', now()->addMinutes(10)->timestamp);
            Auth::logout();

            return redirect()->route('mfa.verify');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
