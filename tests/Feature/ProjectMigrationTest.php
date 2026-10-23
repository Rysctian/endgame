<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ProjectMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_project_upgrade_extends_only_protected_defaults_and_preserves_existing_references(): void
    {
        $tickets = require database_path('migrations/2026_10_24_000001_create_ticket_workflow_tables.php');
        $tickets->down();
        $migration = require database_path('migrations/2026_10_23_000003_create_project_tables.php');
        $migration->down();
        $workspace = Workspace::create(['name' => 'Legacy workspace', 'slug' => 'legacy-workspace']);
        $manager = $workspace->roles()->create(['name' => 'Manager', 'code' => 'manager', 'permissions' => ['members.view'], 'is_system' => true]);
        $member = $workspace->roles()->create(['name' => 'Member', 'code' => 'member', 'permissions' => [], 'is_system' => true]);
        $custom = $workspace->roles()->create(['name' => 'Custom', 'code' => 'custom', 'permissions' => ['activity.view']]);
        $user = User::factory()->create(['is_system_admin' => true]);
        $membership = $workspace->members()->create(['user_id' => $user->id, 'role_id' => $manager->id]);
        $password = $user->password;
        $migration->up();
        $this->assertEqualsCanonicalizing(['members.view', 'projects.view', 'projects.create', 'projects.edit', 'projects.delete', 'projects.setup'], $manager->fresh()->permissions);
        $this->assertSame(['projects.view'], $member->fresh()->permissions);
        $this->assertSame(['activity.view'], $custom->fresh()->permissions);
        $this->assertSame($manager->id, $membership->fresh()->role_id);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertTrue($user->fresh()->is_system_admin);
        $this->assertDatabaseCount('projects', 0);
        $tickets->up();
    }
}
