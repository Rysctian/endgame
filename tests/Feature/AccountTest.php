<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_ignores_privileges_and_sends_verification(): void
    {
        Notification::fake();
        $this->post('/register', ['name' => 'Alice', 'email' => 'Alice@Example.com', 'password' => 'strong-password', 'password_confirmation' => 'strong-password', 'is_system_admin' => true, 'status' => 'suspended'])->assertRedirect('/email/verify');
        $user = User::firstOrFail();
        $this->assertSame('alice@example.com', $user->email);
        $this->assertFalse($user->is_system_admin);
        $this->assertSame('active', $user->status);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('strong-password', $user->password));
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertDatabaseHas('activity_logs', ['action' => 'account.registered', 'actor_id' => $user->id]);
    }

    public function test_login_logout_and_throttling(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/workspaces');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_accounts_cannot_login_or_continue_sessions(): void
    {
        $user = User::factory()->create(['status' => 'suspended']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($user)->get('/workspaces')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_verification_requires_signed_url_and_marks_matching_user(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get('/workspaces')->assertRedirect('/email/verify');
        $this->get('/email/verify/'.$user->id.'/'.sha1($user->email))->assertForbidden();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect('/workspaces');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_profile_and_password_changes_are_validated(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->patch('/profile', ['name' => 'Alice', 'email' => 'new@example.com', 'is_system_admin' => true])->assertRedirect();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertFalse($user->fresh()->is_system_admin);
        $this->put('/profile/password', ['current_password' => 'wrong', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertSessionHasErrors('current_password');
        $this->put('/profile/password', ['current_password' => 'password', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_password_broker_resets_password_without_disclosing_unknown_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });
        $this->post('/reset-password', ['email' => $user->email, 'token' => $token, 'password' => 'reset-password-123', 'password_confirmation' => 'reset-password-123'])->assertRedirect('/login');
        $this->assertTrue(Hash::check('reset-password-123', $user->fresh()->password));
        $this->post('/forgot-password', ['email' => 'unknown@example.com'])->assertSessionHas('status');
    }
}
