<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(): Workspace
    {
        $admin = User::factory()->create(['is_system_admin' => true]);
        $this->actingAs($admin)->post('/workspaces', ['name' => 'Product', 'slug' => 'product-'.fake()->unique()->numberBetween(1, 999999)])->assertRedirect();

        return Workspace::latest('id')->firstOrFail();
    }

    private function member(Workspace $workspace, string $code = 'member', ?array $permissions = null): User
    {
        $user = User::factory()->create();
        $role = $permissions === null ? $workspace->roles()->where('code', $code)->firstOrFail() : $workspace->roles()->create(['name' => 'Custom '.$user->id, 'code' => 'custom-'.$user->id, 'permissions' => $permissions]);
        $workspace->members()->create(['user_id' => $user->id, 'role_id' => $role->id]);

        return $user;
    }

    private function project(Workspace $workspace, User $actor, string $key = 'APP'): Project
    {
        $this->actingAs($actor)->post($this->base($workspace), ['name' => 'Application', 'key' => $key, 'description' => 'A project.'])->assertSessionHasNoErrors()->assertRedirect();

        return $workspace->projects()->where('key', strtoupper($key))->firstOrFail();
    }

    private function base(Workspace $workspace, ?Project $project = null): string
    {
        return '/workspaces/'.$workspace->id.'/projects'.($project ? '/'.$project->id : '');
    }

    public function test_create_normalizes_key_derives_ownership_and_records_creator_membership(): void
    {
        $workspace = $this->workspace();
        $manager = $this->member($workspace, 'manager');
        $this->actingAs($manager)->post($this->base($workspace), ['name' => '  Launch  ', 'key' => ' web1 ', 'description' => '  Notes  '])->assertRedirect();
        $this->assertDatabaseHas('projects', ['workspace_id' => $workspace->id, 'name' => 'Launch', 'key' => 'WEB1', 'description' => 'Notes', 'created_by' => $manager->id, 'status' => 'active']);
        $project = Project::firstOrFail();
        $this->assertDatabaseHas('project_members', ['project_id' => $project->id, 'user_id' => $manager->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'project.created', 'subject_id' => $project->id, 'actor_id' => $manager->id]);
    }

    public function test_keys_are_workspace_unique_and_protected_fields_are_rejected(): void
    {
        $a = $this->workspace();
        $b = $this->workspace();
        $admin = User::factory()->create(['is_system_admin' => true]);
        $this->project($a, $admin, 'APP');
        $this->project($b, $admin, 'APP');
        $this->actingAs($admin)->post($this->base($a), ['name' => 'Duplicate', 'key' => 'app'])->assertSessionHasErrors('key');
        foreach (['workspace_id', 'created_by', 'status', 'is_system_admin'] as $field) {
            $this->post($this->base($a), ['name' => 'Invalid', 'key' => 'NEW', $field => null])->assertSessionHasErrors($field);
        }
        foreach (['a-b', '1APP', 'A', 'TOOLONGKEY123', ['nested']] as $key) {
            $this->post($this->base($a), ['name' => 'Invalid', 'key' => $key])->assertSessionHasErrors('key');
        }
        $this->post($this->base($a), ['name' => ['bad'], 'key' => 'NEW'])->assertSessionHasErrors('name');
        $this->assertDatabaseCount('projects', 2);
    }

    public function test_visibility_search_counts_and_custom_roles_never_leak_unassigned_projects(): void
    {
        $w = $this->workspace();
        $manager = $this->member($w, 'manager');
        $a = $this->project($w, $manager, 'ONE');
        $b = $this->project($w, $manager, 'TWO');
        $member = $this->member($w);
        $custom = $this->member($w, permissions: ['projects.view', 'projects.edit', 'projects.setup']);
        $a->members()->create(['user_id' => $member->id]);
        $a->members()->create(['user_id' => $custom->id]);
        foreach ([$member, $custom] as $user) {
            $this->actingAs($user)->get($this->base($w))->assertInertia(fn (Assert $page) => $page->component('projects/index')->has('projects.data', 1)->where('projects.total', 1)->where('projects.data.0.id', $a->id));
            $this->get($this->base($w, $b))->assertForbidden();
            $this->patch($this->base($w, $b), ['name' => 'Stolen', 'description' => ''])->assertForbidden();
            $this->get($this->base($w).'?search=TWO')->assertInertia(fn (Assert $page) => $page->where('projects.total', 0));
        }
        $this->actingAs($manager)->get($this->base($w))->assertInertia(fn (Assert $page) => $page->where('projects.total', 2));
        $this->actingAs(User::factory()->create(['is_system_admin' => true]))->get($this->base($w, $b))->assertOk();
    }

    public function test_create_and_view_permissions_are_independent_and_scope_requires_workspace_membership(): void
    {
        $w = $this->workspace();
        $creator = $this->member($w, permissions: ['projects.create']);
        $this->actingAs($creator)->post($this->base($w), ['name' => 'Private', 'key' => 'NEW'])->assertRedirect('/workspaces/'.$w->id);
        $p = Project::firstOrFail();
        $this->get($this->base($w, $p))->assertForbidden();
        $member = $this->member($w);
        $this->actingAs($member)->post($this->base($w), ['name' => 'Denied', 'key' => 'NO'])->assertForbidden();
        $foreign = $this->workspace();
        $this->actingAs($creator)->get($this->base($foreign))->assertForbidden();
        $this->post($this->base($foreign), ['name' => 'Denied', 'key' => 'NO'])->assertForbidden();
        $creator->memberships()->delete();
        $this->get($this->base($w, $p))->assertForbidden();
    }

    public function test_update_preserves_key_and_audits_only_meaningful_changes(): void
    {
        $w = $this->workspace();
        $manager = $this->member($w, 'manager');
        $p = $this->project($w, $manager);
        $this->actingAs($manager)->patch($this->base($w, $p), ['name' => 'Renamed', 'description' => 'Updated'])->assertSessionHasNoErrors();
        $this->assertSame('Renamed', $p->fresh()->name);
        $log = ActivityLog::where('action', 'project.updated')->firstOrFail();
        $this->assertSame('Application', $log->changes['before']['name']);
        $this->assertSame('Renamed', $log->changes['after']['name']);
        $this->patch($this->base($w, $p), ['name' => 'Renamed', 'description' => 'Updated'])->assertSessionHasNoErrors();
        $this->assertSame(1, ActivityLog::where('action', 'project.updated')->count());
        $this->patch($this->base($w, $p), ['name' => 'Renamed', 'key' => 'CHANGED'])->assertSessionHasErrors('key');
        $this->assertSame('APP', $p->fresh()->key);
    }

    public function test_eligible_options_and_submitted_members_are_scoped_active_and_unique(): void
    {
        $w = $this->workspace();
        $manager = $this->member($w, 'manager');
        $p = $this->project($w, $manager);
        $eligible = $this->member($w);
        $suspended = $this->member($w);
        $suspended->forceFill(['status' => 'suspended'])->save();
        $outsider = User::factory()->create();
        $this->actingAs($manager)->get($this->base($w, $p))->assertInertia(fn (Assert $page) => $page->component('projects/show')->has('memberOptions', 1)->where('memberOptions.0.value', $eligible->id)->where('fieldStates.key.readOnly', true)->where('fieldStates.name.required', true)->has('validationRules')->where('defaults.status', 'active'));
        foreach ([$suspended, $outsider] as $invalid) {
            $this->post($this->base($w, $p).'/members', ['user_id' => $invalid->id])->assertSessionHasErrors('user_id');
        }
        $this->post($this->base($w, $p).'/members', ['user_id' => $eligible->id])->assertSessionHasNoErrors();
        $this->post($this->base($w, $p).'/members', ['user_id' => $eligible->id])->assertSessionHasErrors('user_id');
        $this->assertDatabaseHas('activity_logs', ['action' => 'project.member_added', 'subject_id' => $p->id]);
        $this->get($this->base($w, $p))->assertInertia(fn (Assert $page) => $page->has('memberOptions', 0));
    }

    public function test_member_removal_revokes_next_request_and_foreign_member_routes_are_rejected(): void
    {
        $w = $this->workspace();
        $manager = $this->member($w, 'manager');
        $a = $this->project($w, $manager, 'ONE');
        $b = $this->project($w, $manager, 'TWO');
        $member = $this->member($w);
        $am = $a->members()->create(['user_id' => $member->id]);
        $bm = $b->members()->create(['user_id' => $member->id]);
        $this->actingAs($member)->get($this->base($w, $a))->assertOk();
        $this->actingAs($manager)->delete($this->base($w, $a).'/members/'.$bm->id)->assertNotFound();
        $this->delete($this->base($w, $a).'/members/'.$am->id)->assertRedirect();
        $this->actingAs($member)->get($this->base($w, $a))->assertForbidden();
        $this->get($this->base($w, $b))->assertOk();
        $this->assertDatabaseHas('activity_logs', ['action' => 'project.member_removed', 'subject_id' => $a->id]);
    }

    public function test_archive_preserves_data_and_rejects_all_edits_until_authorized_restore(): void
    {
        $w = $this->workspace();
        $manager = $this->member($w, 'manager');
        $p = $this->project($w, $manager);
        $member = $this->member($w);
        $this->actingAs($manager)->post($this->base($w, $p).'/archive')->assertSessionHasNoErrors();
        $this->assertSame('archived', $p->fresh()->status);
        $this->get($this->base($w, $p))->assertInertia(fn (Assert $page) => $page->where('can.edit', false)->where('can.manageMembers', false)->where('can.restore', true)->where('memberOptions', []));
        $this->patch($this->base($w, $p), ['name' => 'No'])->assertForbidden();
        $this->post($this->base($w, $p).'/members', ['user_id' => $member->id])->assertForbidden();
        $this->delete($this->base($w, $p).'/members/'.$p->members()->first()->id)->assertForbidden();
        $this->post($this->base($w, $p).'/archive')->assertForbidden();
        $this->delete($this->base($w, $p))->assertMethodNotAllowed();
        $this->post($this->base($w, $p).'/restore')->assertSessionHasNoErrors();
        $this->assertSame('active', $p->fresh()->status);
        $this->assertSame(1, $p->members()->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'project.archived']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'project.restored']);
    }

    public function test_custom_permission_changes_take_effect_without_reauthentication(): void
    {
        $w = $this->workspace();
        $manager = $this->member($w, 'manager');
        $p = $this->project($w, $manager);
        $custom = $this->member($w, permissions: ['projects.view', 'projects.edit']);
        $p->members()->create(['user_id' => $custom->id]);
        $this->actingAs($custom)->patch($this->base($w, $p), ['name' => 'Allowed'])->assertSessionHasNoErrors();
        $this->post($this->base($w, $p).'/archive')->assertForbidden();
        $this->post($this->base($w, $p).'/members', ['user_id' => $manager->id])->assertForbidden();
        $custom->memberships()->first()->role->update(['permissions' => ['projects.view']]);
        $this->patch($this->base($w, $p), ['name' => 'Denied'])->assertForbidden();
        $this->get($this->base($w, $p))->assertInertia(fn (Assert $page) => $page->where('can.edit', false)->where('memberOptions', []));
    }

    public function test_foreign_workspace_routes_and_removed_workspace_memberships_cannot_reuse_project_access(): void
    {
        $a = $this->workspace();
        $b = $this->workspace();
        $manager = $this->member($a, 'manager');
        $p = $this->project($a, $manager);
        $member = $this->member($a);
        $p->members()->create(['user_id' => $member->id]);
        $admin = User::factory()->create(['is_system_admin' => true]);
        $this->actingAs($admin)->get($this->base($b, $p))->assertNotFound();
        $this->patch($this->base($b, $p), ['name' => 'Wrong'])->assertNotFound();
        $this->post($this->base($b, $p).'/archive')->assertNotFound();
        $membership = $a->members()->where('user_id', $member->id)->firstOrFail();
        $this->actingAs($manager)->delete('/workspaces/'.$a->id.'/members/'.$membership->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('project_members', ['project_id' => $p->id, 'user_id' => $member->id]);
        $a->members()->create(['user_id' => $member->id, 'role_id' => $a->roles()->where('code', 'member')->firstOrFail()->id]);
        $this->actingAs($member)->get($this->base($a, $p))->assertForbidden();
    }
}
