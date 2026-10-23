<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use Database\Seeders\DemoDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_command_creates_an_owner_with_a_prompted_password(): void
    {
        $this->artisan('app:make-admin', ['email' => 'owner@example.com', '--name' => 'Owner'])->expectsQuestion('Password (at least 12 characters)', 'owner-password-123')->assertSuccessful();
        $user = User::where('email', 'owner@example.com')->firstOrFail();
        $this->assertTrue($user->is_system_admin);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('owner-password-123', $user->password));
    }

    public function test_demo_seed_is_repeatable_and_preserves_existing_credentials(): void
    {
        $user = User::factory()->create(['email' => 'manager@example.test']);
        $password = $user->password;
        $this->seed(DemoDatabaseSeeder::class);
        $this->seed(DemoDatabaseSeeder::class);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertDatabaseCount('workspaces', 5);
        $this->assertDatabaseCount('workspace_members', 25);
    }

    public function test_factories_create_scoped_relations_and_hide_tokens(): void
    {
        $member = WorkspaceMember::factory()->create();
        $invitation = WorkspaceInvitation::factory()->create();
        $activity = ActivityLog::factory()->create();
        $this->assertSame($member->workspace_id, $member->role->workspace_id);
        $this->assertSame($invitation->workspace_id, $invitation->role->workspace_id);
        $this->assertArrayNotHasKey('token_hash', $invitation->toArray());
        $this->assertNotNull($activity->created_at);
    }
}
