<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class BootstrapAdmin extends Command
{
    protected $signature = 'autochain:bootstrap-admin';

    protected $description = 'Crée ou actualise le Super Admin depuis les secrets d’hébergement';

    public function handle(): int
    {
        $data = config('autochain.bootstrap_admin');

        if (empty($data['email']) || empty($data['password'])) {
            $this->warn('AUTOCHAIN_ADMIN_EMAIL ou AUTOCHAIN_ADMIN_PASSWORD absent : administrateur non créé.');

            return self::SUCCESS;
        }

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()],
        ])->validate();

        User::updateOrCreate(
            ['email' => strtolower($validated['email'])],
            [
                'name' => $validated['name'],
                'password' => $validated['password'],
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->info('Super Admin AutoChain prêt.');

        return self::SUCCESS;
    }
}
