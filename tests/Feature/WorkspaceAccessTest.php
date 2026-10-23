<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkspaceAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_system_admin' => true]);
    }

    private function workspace(): Workspace
    {
        $this->actingAs($this->admin())->post('/workspaces', ['name' => 'Product', 'slug' => 'product-'.fake()->unique()->numberBetween(1, 99999)])->assertRedirect();

        return Workspace::latest('id')->firstOrFail();
    }

    private function member(Workspace $workspace, string $role = 'Manager'): User
    {
        $user = User::factory()->create();
        WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role_id' => $workspace->roles()->where('name', $role)->firstOrFail()->id]);

        return $user;
    }

    public function test_only_admins_create_workspaces_and_defaults_are_seeded(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/workspaces', ['name' => 'Denied', 'slug' => 'denied'])->assertForbidden();
        $workspace = $this->workspace();
        $this->assertSame(['Manager', 'Member'], $workspace->roles()->orderBy('name')->pluck('name')->all());
        $this->assertDatabaseHas('activity_logs', ['workspace_id' => $workspace->id, 'action' => 'workspace.created']);
        $this->actingAs($this->admin())->post('/workspaces', ['name' => 'Duplicate', 'slug' => $workspace->slug])->assertSessionHasErrors('slug');
    }

    public function test_workspace_lists_and_details_are_scoped(): void
    {
        $a = $this->workspace();
        $b = $this->workspace();
        $user = $this->member($a, 'Member');
        $this->actingAs($user)->get('/workspaces')->assertInertia(fn (Assert $page) => $page->component('workspaces/index')->has('workspaces.data', 1)->where('workspaces.data.0.id', $a->id));
        $this->get('/workspaces/'.$b->id)->assertForbidden();
        $this->get('/workspaces/'.$a->id)->assertInertia(fn (Assert $page) => $page->component('workspaces/show')->where('members', null)->where('roles', [])->where('invitations', null));
    }

    public function test_manager_can_manage_custom_roles_but_cannot_escalate(): void
    {
        $w = $this->workspace();
        $manager = $this->member($w);
        $this->actingAs($manager)->post('/workspaces/'.$w->id.'/roles', ['name' => 'Inviter', 'code' => 'inviter', 'permissions' => ['members.create']])->assertSessionHasNoErrors()->assertRedirect();
        $role = $w->roles()->where('name', 'Inviter')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', ['action' => 'role.created', 'subject_id' => $role->id]);
        $this->post('/workspaces/'.$w->id.'/roles', ['name' => 'Root', 'code' => 'root', 'permissions' => ['system.admin']])->assertSessionHasErrors('permissions.0');
        $this->patch('/workspaces/'.$w->id.'/roles/'.$w->roles()->where('name', 'Manager')->first()->id, ['name' => 'Root', 'code' => 'root', 'permissions' => []])->assertForbidden();
        $limited = $w->roles()->create(['name' => 'Role editor', 'code' => 'role-editor', 'permissions' => ['roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.setup']]);
        WorkspaceMember::where('user_id', $manager->id)->update(['role_id' => $limited->id]);
        $this->patch('/workspaces/'.$w->id.'/roles/'.$role->id, ['name' => 'Changed', 'code' => 'changed', 'permissions' => []])->assertForbidden();
        $this->post('/workspaces/'.$w->id.'/roles', ['name' => 'Power', 'code' => 'power', 'permissions' => ['members.delete']])->assertForbidden();
    }

    public function test_membership_changes_reject_foreign_roles_and_preserve_account(): void
    {
        $a = $this->workspace();
        $b = $this->workspace();
        $manager = $this->member($a);
        $member = $this->member($a, 'Member');
        $membership = $a->members()->where('user_id', $member->id)->first();
        $this->actingAs($manager)->patch('/workspaces/'.$a->id.'/members/'.$membership->id, ['role_id' => $b->roles()->first()->id])->assertSessionHasErrors('role_id');
        $foreign = $b->members()->create(['user_id' => User::factory()->create()->id, 'role_id' => $b->roles()->first()->id]);
        $this->delete('/workspaces/'.$a->id.'/members/'.$foreign->id)->assertNotFound();
        $this->delete('/workspaces/'.$a->id.'/members/'.$membership->id)->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $member->id]);
        $this->assertDatabaseMissing('workspace_members', ['id' => $membership->id]);
        $this->actingAs($member)->get('/workspaces/'.$a->id)->assertForbidden();
    }

    public function test_managers_cannot_change_self_or_system_admin_memberships(): void
    {
        $w = $this->workspace();
        $manager = $this->member($w);
        $admin = $this->admin();
        $am = $w->members()->create(['user_id' => $admin->id, 'role_id' => $w->roles()->where('name', 'Member')->first()->id]);
        $self = $w->members()->where('user_id', $manager->id)->first();
        $this->actingAs($manager)->delete('/workspaces/'.$w->id.'/members/'.$self->id)->assertForbidden();
        $this->delete('/workspaces/'.$w->id.'/members/'.$am->id)->assertForbidden();
        $this->actingAs($admin)->delete('/workspaces/'.$w->id.'/members/'.$self->id)->assertSessionHasErrors('member');
    }

    public function test_global_account_administration_is_protected_and_logged(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $this->actingAs($user)->patch('/admin/users/'.$admin->id, ['status' => 'suspended'])->assertForbidden();
        $this->actingAs($admin)->patch('/admin/users/'.$admin->id, ['status' => 'suspended'])->assertForbidden();
        $this->patch('/admin/users/'.$user->id, ['status' => 'suspended', 'is_system_admin' => true])->assertRedirect();
        $this->assertSame('suspended', $user->fresh()->status);
        $this->assertFalse($user->fresh()->is_system_admin);
        $this->assertDatabaseHas('activity_logs', ['action' => 'account.status_updated', 'subject_id' => $user->id]);
        $this->patch('/admin/users/'.$user->id, ['status' => 'active'])->assertRedirect();
        $this->assertSame('active', $user->fresh()->status);
    }

    public function test_referenced_and_system_roles_cannot_be_deleted_but_unused_custom_roles_can(): void
    {
        $w = $this->workspace();
        $manager = $this->member($w);
        $custom = $w->roles()->create(['name' => 'Custom', 'code' => 'custom', 'permissions' => []]);
        $this->actingAs($manager)->delete('/workspaces/'.$w->id.'/roles/'.$custom->id)->assertRedirect();
        $this->assertDatabaseMissing('roles', ['id' => $custom->id]);
        $this->delete('/workspaces/'.$w->id.'/roles/'.$w->roles()->where('name', 'Member')->first()->id)->assertForbidden();
        $used = $w->roles()->create(['name' => 'Used', 'code' => 'used', 'permissions' => []]);
        $w->members()->create(['user_id' => User::factory()->create()->id, 'role_id' => $used->id]);
        $this->delete('/workspaces/'.$w->id.'/roles/'.$used->id)->assertSessionHasErrors('role');
    }

    public function test_member_can_never_invoke_workspace_administration(): void
    {
        $w = $this->workspace();
        $member = $this->member($w, 'Member');
        $role = $w->roles()->first();
        $this->actingAs($member)->patch('/workspaces/'.$w->id, ['name' => 'Hacked', 'slug' => 'hacked'])->assertForbidden();
        $this->post('/workspaces/'.$w->id.'/roles', ['name' => 'Hacked', 'code' => 'hacked', 'permissions' => []])->assertForbidden();
        $this->post('/workspaces/'.$w->id.'/invitations', ['email' => 'outsider@example.com', 'role_id' => $role->id])->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_admin_can_update_workspace_and_manager_can_change_a_members_role(): void
    {
        $w = $this->workspace();
        $manager = $this->member($w);
        $member = $this->member($w, 'Member');
        $this->actingAs($this->admin())->patch('/workspaces/'.$w->id, ['name' => 'Renamed', 'slug' => 'renamed'])->assertRedirect();
        $this->assertSame('Renamed', $w->fresh()->name);
        $custom = $w->roles()->create(['name' => 'Contributor', 'code' => 'contributor', 'permissions' => ['members.view']]);
        $membership = $w->members()->where('user_id', $member->id)->first();
        $this->actingAs($manager)->patch('/workspaces/'.$w->id.'/members/'.$membership->id, ['role_id' => $custom->id])->assertRedirect();
        $this->assertSame($custom->id, $membership->fresh()->role_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'membership.role_updated', 'subject_id' => $membership->id]);
    }
}
