<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\MfaCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MfaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_mfa_enabled_are_redirected_to_verification_step_after_login(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => bcrypt('password'),
            'mfa_enabled' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/mfa/verify');
        $this->assertGuest();
        Notification::assertSentTo($user, MfaCodeNotification::class);
    }

    public function test_profile_can_store_theme_and_notification_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->patch('/profile', [
            'name' => 'Alice Example',
            'email' => 'alice@example.com',
            'phone_number' => '+33601020304',
            'company_name' => 'AutoChain Labs',
            'bio' => 'Responsable flotte',
            'theme_preference' => 'dark',
            'notification_email' => false,
        ]);

        $response->assertRedirect('/profile');
        $this->assertSame('dark', $user->fresh()->theme_preference);
        $this->assertFalse($user->fresh()->notification_email);
    }
}
