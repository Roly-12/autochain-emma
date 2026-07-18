<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BootstrapAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_bootstraps_the_hosted_super_admin_idempotently(): void
    {
        config([
            'autochain.bootstrap_admin' => [
                'name' => 'Admin Emma',
                'email' => 'admin@example.com',
                'password' => 'SecurePassword2026',
            ],
        ]);

        $this->artisan('autochain:bootstrap-admin')->assertSuccessful();
        $this->artisan('autochain:bootstrap-admin')->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $admin = User::firstOrFail();

        $this->assertSame(UserRole::SuperAdmin, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('SecurePassword2026', $admin->password));
    }
}
