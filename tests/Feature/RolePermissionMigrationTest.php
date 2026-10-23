<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RolePermissionMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_forward_migration_preserves_references_and_translates_legacy_grants(): void
    {
        $w = Workspace::factory()->create();
        $role = $w->roles()->create(['name' => 'Support Team', 'code' => 'support-team', 'permissions' => []]);
        $collision = $w->roles()->create(['name' => 'Support-Team', 'code' => 'support-team-2', 'permissions' => []]);
        $user = User::factory()->create();
        $member = $w->members()->create(['user_id' => $user->id, 'role_id' => $role->id]);
        $invitation = $w->invitations()->create(['email' => 'preserved@example.test', 'role_id' => $role->id, 'token_hash' => hash('sha256', 'preserved-token'), 'expires_at' => now()->addDays(7), 'invited_by' => $user->id]);
        if (Schema::hasColumn('roles', 'code')) {
            Schema::table('roles', function (Blueprint $t) {
                $t->dropUnique(['workspace_id', 'code']);
                $t->dropColumn('code');
            });
        }
        DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(['members.invite', 'members.update', 'members.remove', 'members.view', 'roles.manage'])]);
        $migration = require database_path('migrations/2026_10_23_000002_add_role_codes_and_module_permissions.php');
        $migration->up();
        $this->assertSame($role->id, $member->fresh()->role_id);
        $this->assertSame($role->id, $invitation->fresh()->role_id);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        $this->assertSame('support-team', $role->fresh()->code);
        $this->assertNotSame($role->fresh()->code, $collision->fresh()->code);
        $this->assertEqualsCanonicalizing(['members.create', 'members.edit', 'members.delete', 'members.view', 'activity.view', 'roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.setup'], $role->fresh()->permissions);
    }
}
