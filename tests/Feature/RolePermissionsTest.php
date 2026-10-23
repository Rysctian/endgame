<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(Workspace $w, array $permissions): User
    {
        $role = $w->roles()->create(['name' => 'Operator '.uniqid(), 'code' => 'operator-'.uniqid(), 'permissions' => $permissions]);
        $user = User::factory()->create();
        $w->members()->create(['user_id' => $user->id, 'role_id' => $role->id]);

        return $user;
    }

    public function test_custom_role_code_crud_and_audit(): void
    {
        $w = Workspace::factory()->create();
        $admin = User::factory()->create(['is_system_admin' => true]);
        $this->actingAs($admin)->post('/workspaces/'.$w->id.'/roles', ['name' => 'Support', 'code' => 'SUPPORT', 'permissions' => ['members.view', 'activity.view']])->assertSessionHasNoErrors()->assertRedirect();
        $role = $w->roles()->where('name', 'Support')->firstOrFail();
        $this->assertSame('support', $role->code);
        $this->patch('/workspaces/'.$w->id.'/roles/'.$role->id, ['name' => 'Support desk', 'code' => 'support-desk', 'permissions' => ['activity.view']])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('support-desk', $role->fresh()->code);
        $this->assertSame(['activity.view'], $role->fresh()->permissions);
        $log = DB::table('activity_logs')->where('action', 'role.updated')->first();
        $changes = json_decode($log->changes, true);
        $this->assertSame(['members.view', 'activity.view'], $changes['before']['permissions']);
        $this->assertSame(['activity.view'], $changes['after']['permissions']);
        $this->delete('/workspaces/'.$w->id.'/roles/'.$role->id)->assertRedirect();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_codes_are_required_valid_and_unique_within_workspace(): void
    {
        $a = Workspace::factory()->create();
        $b = Workspace::factory()->create();
        $admin = User::factory()->create(['is_system_admin' => true]);
        $this->actingAs($admin)->post('/workspaces/'.$a->id.'/roles', ['name' => 'Missing', 'permissions' => []])->assertSessionHasErrors('code');
        $this->post('/workspaces/'.$a->id.'/roles', ['name' => 'Invalid', 'code' => 'with spaces', 'permissions' => []])->assertSessionHasErrors('code');
        foreach ([$a, $b] as $w) {
            $this->post('/workspaces/'.$w->id.'/roles', ['name' => 'Support', 'code' => 'support', 'permissions' => []])->assertSessionHasNoErrors()->assertRedirect();
        }
        $this->post('/workspaces/'.$a->id.'/roles', ['name' => 'Other', 'code' => 'SUPPORT', 'permissions' => []])->assertSessionHasErrors('code');
        $this->post('/workspaces/'.$a->id.'/roles', ['name' => 'System Admin', 'code' => 'system-admin', 'permissions' => []])->assertSessionHasErrors(['name', 'code']);
    }

    public function test_protected_fields_and_future_permission_keys_are_rejected(): void
    {
        $w = Workspace::factory()->create();
        $this->actingAs(User::factory()->create(['is_system_admin' => true]));
        $this->post('/workspaces/'.$w->id.'/roles', ['name' => 'Root', 'code' => 'root', 'permissions' => ['tickets.view'], 'is_system' => true, 'is_system_admin' => true, 'workspace_id' => $w->id])->assertSessionHasErrors(['permissions.0', 'is_system', 'is_system_admin', 'workspace_id']);
    }

    public function test_role_actions_and_permission_setup_are_authorized_independently(): void
    {
        $w = Workspace::factory()->create();
        $creator = $this->actor($w, ['roles.view', 'roles.create']);
        $this->actingAs($creator)->post('/workspaces/'.$w->id.'/roles', ['name' => 'Empty', 'code' => 'empty', 'permissions' => []])->assertSessionHasNoErrors()->assertRedirect();
        $role = $w->roles()->where('code', 'empty')->firstOrFail();
        $this->post('/workspaces/'.$w->id.'/roles', ['name' => 'Grants', 'code' => 'grants', 'permissions' => ['roles.view']])->assertForbidden();
        $this->patch('/workspaces/'.$w->id.'/roles/'.$role->id, ['name' => 'Denied', 'code' => 'denied', 'permissions' => []])->assertForbidden();
        $this->delete('/workspaces/'.$w->id.'/roles/'.$role->id)->assertForbidden();
        $editor = $this->actor($w, ['roles.view', 'roles.edit']);
        $this->actingAs($editor)->patch('/workspaces/'.$w->id.'/roles/'.$role->id, ['name' => 'Renamed', 'code' => 'renamed', 'permissions' => []])->assertSessionHasNoErrors()->assertRedirect();
        $this->patch('/workspaces/'.$w->id.'/roles/'.$role->id, ['name' => 'Renamed', 'code' => 'renamed', 'permissions' => ['roles.view']])->assertForbidden();
        $setup = $this->actor($w, ['roles.view', 'roles.setup', 'members.view']);
        $this->actingAs($setup)->patch('/workspaces/'.$w->id.'/roles/'.$role->id, ['name' => 'Renamed', 'code' => 'renamed', 'permissions' => ['members.view']])->assertSessionHasNoErrors()->assertRedirect();
        $this->patch('/workspaces/'.$w->id.'/roles/'.$role->id, ['name' => 'No metadata', 'code' => 'renamed', 'permissions' => ['members.view']])->assertForbidden();
    }

    public function test_assignment_and_permission_changes_take_effect_without_new_login(): void
    {
        $w = Workspace::factory()->create();
        $manager = $this->actor($w, array_keys(config('access.permissions')));
        $user = User::factory()->create();
        $role = $w->roles()->create(['name' => 'Reader', 'code' => 'reader', 'permissions' => ['members.view']]);
        $member = $w->members()->create(['user_id' => $user->id, 'role_id' => $w->roles()->where('name', 'Member')->first()->id]);
        $this->actingAs($manager)->patch('/workspaces/'.$w->id.'/members/'.$member->id, ['role_id' => $role->id])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue($user->hasWorkspacePermission($w, 'members.view'));
        $this->actingAs($user)->get('/workspaces/'.$w->id)->assertInertia(fn (Assert $p) => $p->has('members.data')->where('activities', null));
        $this->actingAs($manager)->patch('/workspaces/'.$w->id.'/roles/'.$role->id, ['name' => 'Reader', 'code' => 'reader', 'permissions' => ['activity.view']])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertFalse($user->hasWorkspacePermission($w, 'members.view'));
        $this->assertTrue($user->hasWorkspacePermission($w, 'activity.view'));
        $this->actingAs($user)->get('/workspaces/'.$w->id)->assertInertia(fn (Assert $p) => $p->where('members', null)->has('activities.data'));
    }

    public function test_foreign_roles_protected_defaults_and_escalation_are_denied(): void
    {
        $w = Workspace::factory()->create();
        $other = Workspace::factory()->create();
        $user = $this->actor($w, ['roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.setup', 'members.edit']);
        $target = $other->roles()->create(['name' => 'Other', 'code' => 'other', 'permissions' => []]);
        $data = ['name' => 'Changed', 'code' => 'changed', 'permissions' => []];
        $this->actingAs($user)->patch('/workspaces/'.$w->id.'/roles/'.$target->id, $data)->assertNotFound();
        foreach (['Manager', 'Member'] as $name) {
            $default = $w->roles()->where('name', $name)->first();
            $this->delete('/workspaces/'.$w->id.'/roles/'.$default->id)->assertForbidden();
            $this->patch('/workspaces/'.$w->id.'/roles/'.$default->id, $data)->assertForbidden();
        }
        $this->post('/workspaces/'.$w->id.'/roles', ['name' => 'Elevated', 'code' => 'elevated', 'permissions' => ['members.delete']])->assertForbidden();
        $this->assertFalse($user->hasWorkspacePermission($w, 'projects.view'));
    }

    public function test_protected_fields_are_rejected_even_when_empty_or_false(): void
    {
        $w = Workspace::factory()->create();
        $this->actingAs(User::factory()->create(['is_system_admin' => true]));
        foreach (['is_system' => null, 'is_system_admin' => false, 'workspace_id' => null] as $key => $value) {
            $this->post('/workspaces/'.$w->id.'/roles', ['name' => 'Protected', 'code' => 'protected', 'permissions' => [], $key => $value])->assertSessionHasErrors($key);
        }
    }

    public function test_malformed_role_fields_return_validation_errors(): void
    {
        $w = Workspace::factory()->create();
        $this->actingAs(User::factory()->create(['is_system_admin' => true]));
        foreach (['name' => ['unexpected'], 'code' => ['unexpected'], 'permissions' => [['unexpected']]] as $key => $value) {
            $this->post('/workspaces/'.$w->id.'/roles', array_replace(['name' => 'Malformed', 'code' => 'malformed', 'permissions' => []], [$key => $value]))->assertSessionHasErrors($key === 'permissions' ? 'permissions.0' : $key);
        }
    }
}
