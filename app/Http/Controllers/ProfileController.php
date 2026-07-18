<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ImageUploadService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request, ImageUploadService $images): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        try {
            if ($request->hasFile('avatar')) {
                if ($user->avatar_path) {
                    Storage::disk('public')->delete($user->avatar_path);
                }
                $data['avatar_path'] = $images->storeAsJpeg($request->file('avatar'), 'avatars', 512);
            }

            if ($request->hasFile('company_logo')) {
                if ($user->company_logo_path) {
                    Storage::disk('public')->delete($user->company_logo_path);
                }
                $data['company_logo_path'] = $images->storeAsJpeg($request->file('company_logo'), 'branding', 800);
            }
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['avatar' => $e->getMessage()]);
        }

        unset($data['avatar'], $data['company_logo']);

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Profil mis à jour.');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme_preference' => ['required', 'in:light,dark,system'],
        ]);

        $request->user()->update($data);

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
