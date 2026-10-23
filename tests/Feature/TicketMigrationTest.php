<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class TicketMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_upgrade_preserves_projects_and_backfills_default_workflows_without_elevating_custom_roles(): void
    {
        $migration = require database_path('migrations/2026_10_24_000001_create_ticket_workflow_tables.php');
        $migration->down();
        $w = Workspace::create(['name' => 'Legacy tickets', 'slug' => 'legacy-tickets']);
        $u = User::factory()->create();
        $p = $w->projects()->create(['name' => 'Existing', 'key' => 'LEGACY', 'created_by' => $u->id, 'status' => 'archived']);
        $manager = $w->roles()->create(['name' => 'Manager', 'code' => 'manager', 'permissions' => ['projects.view'], 'is_system' => true]);
        $member = $w->roles()->create(['name' => 'Member', 'code' => 'member', 'permissions' => ['projects.view'], 'is_system' => true]);
        $custom = $w->roles()->create(['name' => 'Custom', 'code' => 'custom', 'permissions' => ['activity.view'], 'is_system' => false]);
        $migration->up();
        $this->assertSame('archived', $p->fresh()->status);
        $this->assertSame('LEGACY', $p->fresh()->key);
        $this->assertSame(4, $p->statuses()->count());
        $this->assertSame(1, $p->statuses()->where('is_initial', true)->count());
        $this->assertSame(6, $p->transitions()->count());
        $this->assertContains('tickets.setup', $manager->fresh()->permissions);
        $this->assertContains('tickets.edit', $member->fresh()->permissions);
        $this->assertNotContains('tickets.setup', $member->fresh()->permissions);
        $this->assertSame(['activity.view'], $custom->fresh()->permissions);
    }
}
