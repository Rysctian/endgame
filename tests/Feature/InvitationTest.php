<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private function setupInvitation(string $email = 'recipient@example.com'): array
    {
        Notification::fake();
        $admin = User::factory()->create(['is_system_admin' => true]);
        $this->actingAs($admin)->post('/workspaces', ['name' => 'Team', 'slug' => 'team'])->assertRedirect();
        $w = Workspace::firstOrFail();
        $role = $w->roles()->where('name', 'Member')->firstOrFail();
        $this->post('/workspaces/'.$w->id.'/invitations', ['email' => $email, 'role_id' => $role->id])->assertRedirect()->assertSessionHasNoErrors();
        $token = null;
        Notification::assertSentOnDemand(WorkspaceInvitationNotification::class, function ($notification, $channels, $notifiable) use (&$token, $email) {
            $token = $notification->token;

            return $notifiable->routes['mail'] === $email;
        });

        return [$w, WorkspaceInvitation::firstOrFail(), $token, $admin];
    }

    public function test_tokens_are_hashed_and_acceptance_is_transactional_and_idempotent(): void
    {
        [$w,$invitation,$token] = $this->setupInvitation();
        $this->assertSame(hash('sha256', $token), $invitation->token_hash);
        $this->assertArrayNotHasKey('token_hash', $invitation->toArray());
        $user = User::factory()->create(['email' => 'recipient@example.com']);
        $url = '/invitations/'.$invitation->id.'/'.$token;
        $this->actingAs($user)->post($url)->assertRedirect('/workspaces/'.$w->id);
        $this->assertDatabaseHas('workspace_members', ['workspace_id' => $w->id, 'user_id' => $user->id]);
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->post($url)->assertRedirect();
        $this->assertDatabaseCount('workspace_members', 1);
        $this->assertDatabaseCount('activity_logs', 3);
        WorkspaceMember::where('user_id', $user->id)->delete();
        $this->post($url)->assertRedirect();
        $this->assertDatabaseCount('workspace_members', 0);
    }

    public function test_acceptance_rejects_wrong_email_unverified_expired_or_invalid_tokens(): void
    {
        [$w,$invitation,$token] = $this->setupInvitation();
        $url = '/invitations/'.$invitation->id.'/'.$token;
        $this->actingAs(User::factory()->create())->post($url)->assertForbidden();
        $user = User::factory()->unverified()->create(['email' => 'recipient@example.com']);
        $this->actingAs($user)->post($url)->assertRedirect('/email/verify');
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->post('/invitations/'.$invitation->id.'/invalid')->assertNotFound();
        $invitation->update(['expires_at' => now()->subSecond()]);
        $this->post($url)->assertStatus(410);
        $this->assertDatabaseCount('workspace_members', 0);
    }

    public function test_duplicate_and_existing_member_invitations_are_rejected_and_cancellation_revokes_link(): void
    {
        [$w,$invitation,$token] = $this->setupInvitation();
        $data = ['email' => $invitation->email, 'role_id' => $invitation->role_id];
        $this->post('/workspaces/'.$w->id.'/invitations', $data)->assertSessionHasErrors('email');
        $this->delete('/workspaces/'.$w->id.'/invitations/'.$invitation->id)->assertRedirect();
        $user = User::factory()->create(['email' => $invitation->email]);
        $this->actingAs($user)->post('/invitations/'.$invitation->id.'/'.$token)->assertStatus(410);
        $w->members()->create(['user_id' => $user->id, 'role_id' => $invitation->role_id]);
        $this->actingAs(User::where('is_system_admin', true)->first())->post('/workspaces/'.$w->id.'/invitations', $data)->assertSessionHasErrors('email');
    }

    public function test_invitation_role_must_be_scoped_and_within_actor_authority(): void
    {
        [$w,$invitation,$token,$admin] = $this->setupInvitation();
        $this->post('/workspaces', ['name' => 'Other', 'slug' => 'other']);
        $other = Workspace::latest('id')->first();
        $this->post('/workspaces/'.$w->id.'/invitations', ['email' => 'next@example.com', 'role_id' => $other->roles()->first()->id])->assertSessionHasErrors('role_id');
        $limited = $w->roles()->create(['name' => 'Inviter', 'code' => 'inviter', 'permissions' => ['members.create', 'projects.view', 'tickets.view', 'tickets.create', 'tickets.edit']]);
        $user = User::factory()->create();
        $w->members()->create(['user_id' => $user->id, 'role_id' => $limited->id]);
        $this->actingAs($user)->post('/workspaces/'.$w->id.'/invitations', ['email' => 'next@example.com', 'role_id' => $w->roles()->where('name', 'Manager')->first()->id])->assertForbidden();
        $this->post('/workspaces/'.$w->id.'/invitations', ['email' => 'next@example.com', 'role_id' => $invitation->role_id])->assertRedirect()->assertSessionHasNoErrors();
        $this->delete('/workspaces/'.$other->id.'/invitations/'.$invitation->id)->assertForbidden();
    }

    public function test_invitation_link_preserves_intended_url_through_login(): void
    {
        [$w,$invitation,$token] = $this->setupInvitation();
        $this->post('/logout');
        $url = '/invitations/'.$invitation->id.'/'.$token;
        $this->get($url)->assertRedirect('/login')->assertSessionHas('url.intended', url($url));
        $user = User::factory()->create(['email' => $invitation->email]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect($url);
    }

    public function test_limited_inviters_do_not_receive_cancel_actions_for_more_powerful_roles(): void
    {
        [$workspace,$invitation,$token,$admin] = $this->setupInvitation();
        $invitation->update(['role_id' => $workspace->roles()->where('name', 'Manager')->firstOrFail()->id]);
        $limited = $workspace->roles()->create(['name' => 'Limited inviter', 'code' => 'limited-inviter', 'permissions' => ['members.create']]);
        $user = User::factory()->create();
        $workspace->members()->create(['user_id' => $user->id, 'role_id' => $limited->id]);
        $this->actingAs($user)->get('/workspaces/'.$workspace->id)->assertInertia(fn (AssertableInertia $page) => $page->where('invitations.data.0.canCancel', false));
    }
}
