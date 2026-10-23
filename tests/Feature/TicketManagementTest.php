<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\DemoDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketManagementTest extends TestCase
{
    use RefreshDatabase;

    private function setupProject(): array
    {
        $admin = User::factory()->create(['is_system_admin' => true]);
        $this->actingAs($admin)->post('/workspaces', ['name' => 'Tickets', 'slug' => 'tickets-'.fake()->unique()->numberBetween(1, 999999)])->assertRedirect();
        $w = Workspace::latest('id')->firstOrFail();
        $this->post('/workspaces/'.$w->id.'/projects', ['name' => 'Delivery', 'key' => 'APP'])->assertRedirect();
        $p = $w->projects()->firstOrFail();

        return [$w, $p, $admin, '/workspaces/'.$w->id.'/projects/'.$p->id];
    }

    private function createTicket(string $base, array $extra = []): Ticket
    {
        $this->post($base.'/tickets', [...['title' => 'Ship the feature', 'type' => 'task', 'priority' => 'medium'], ...$extra])->assertSessionHasNoErrors()->assertRedirect();

        return Ticket::latest('id')->firstOrFail();
    }

    private function moveData(Project $p, Ticket $t, int $target): array
    {
        return ['status_id' => $target, 'expected_status_id' => $t->status_id, 'version' => $t->version, 'board_version' => $p->fresh()->board_version, 'before_id' => null];
    }

    public function test_new_projects_receive_workflow_and_creation_owns_identity_and_number(): void
    {
        [$w,$p,$u,$base] = $this->setupProject();
        $this->assertDatabaseCount('project_statuses', 4);
        $this->assertDatabaseCount('project_status_transitions', 6);
        $a = $this->createTicket($base, ['title' => '  Launch  ']);
        $b = $this->createTicket($base);
        $this->assertSame(1, $a->number);
        $this->assertSame(2, $b->number);
        $this->assertSame('Launch', $a->title);
        $this->assertSame($u->id, $a->reporter_id);
        $this->assertSame($p->statuses()->where('is_initial', true)->first()->id, $a->status_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'ticket.created', 'subject_id' => $a->id]);
        $this->get($base.'/tickets')->assertOk()->assertInertia(fn (Assert $page) => $page->component('tickets/index')->has('workTypes', 3)->has('priorities', 4)->has('columns', 4)->where('columns.0.total', 2)->where('can.create', true));
    }

    public function test_permissions_scope_and_workspace_activity_do_not_leak_tickets(): void
    {
        [$w,$p,$admin,$base] = $this->setupProject();
        $t = $this->createTicket($base);
        $user = User::factory()->create();
        $role = $w->roles()->create(['code' => 'custom', 'name' => 'Custom', 'permissions' => ['tickets.view', 'tickets.create', 'tickets.edit', 'activity.view']]);
        $w->members()->create(['user_id' => $user->id, 'role_id' => $role->id]);
        $this->actingAs($user)->get($base.'/tickets')->assertForbidden();
        $this->post($base.'/tickets', ['title' => 'Invalid', 'type' => 'task', 'priority' => 'low'])->assertForbidden();
        $this->get('/workspaces/'.$w->id)->assertInertia(fn (Assert $page) => $page->where('activities.total', 1));
        $p->members()->create(['user_id' => $user->id]);
        $this->get($base.'/tickets')->assertOk();
        $this->get('/workspaces/'.$w->id)->assertInertia(fn (Assert $page) => $page->has('ticketProjects.data', 1)->where('ticketProjects.data.0.id', $p->id));
        $this->get($base.'/tickets?ticket='.$t->id)->assertInertia(fn (Assert $page) => $page->where('selectedTicket.id', $t->id));
        $role->update(['permissions' => ['tickets.create']]);
        $this->get($base.'/tickets')->assertForbidden();
        $this->post($base.'/tickets', ['title' => 'Created without view', 'type' => 'task', 'priority' => 'low'])->assertRedirect('/workspaces/'.$w->id);
        $this->actingAs($admin)->get('/workspaces/'.Workspace::factory()->create()->id.'/projects/'.$p->id.'/tickets')->assertNotFound();
    }

    public function test_protected_fields_and_invalid_choices_are_rejected(): void
    {
        [$w,$p,$u,$base] = $this->setupProject();
        foreach (['number', 'project_id', 'reporter_id', 'status_id', 'sort_order', 'workspace_id'] as $field) {
            $this->post($base.'/tickets', ['title' => 'No', 'type' => 'task', 'priority' => 'low', $field => 1])->assertSessionHasErrors($field);
        }
        $this->post($base.'/tickets', ['title' => '  ', 'type' => 'epic', 'priority' => 'unknown', 'due_date' => 'bad'])->assertSessionHasErrors(['title', 'type', 'priority', 'due_date']);
        $t = $this->createTicket($base);
        $this->patch($base.'/tickets/'.$t->id, ['title' => 'Edit', 'type' => 'task', 'priority' => 'low', 'version' => $t->version, 'status_id' => $t->status_id])->assertSessionHasErrors('status_id');
    }

    public function test_assignments_require_active_verified_eligible_project_members(): void
    {
        [$w,$p,$u,$base] = $this->setupProject();
        $member = User::factory()->create();
        $role = $w->roles()->where('code', 'member')->firstOrFail();
        $w->members()->create(['user_id' => $member->id, 'role_id' => $role->id]);
        $this->post($base.'/tickets', ['title' => 'No', 'type' => 'task', 'priority' => 'low', 'assignee_id' => $member->id])->assertSessionHasErrors('assignee_id');
        $p->members()->create(['user_id' => $member->id]);
        $t = $this->createTicket($base, ['assignee_id' => $member->id]);
        $this->get($base.'/tickets')->assertInertia(fn (Assert $page) => $page->has('assignees', 1)->where('assignees.0.value', $member->id));
        $member->forceFill(['status' => 'suspended'])->save();
        $this->post($base.'/tickets', ['title' => 'No', 'type' => 'task', 'priority' => 'low', 'assignee_id' => $member->id])->assertSessionHasErrors('assignee_id');
        $this->patch($base.'/tickets/'.$t->id, ['title' => 'Historical assignee', 'type' => 'task', 'priority' => 'low', 'version' => $t->version, 'assignee_id' => $member->id])->assertSessionHasNoErrors();
        $this->get($base.'/tickets')->assertInertia(fn (Assert $page) => $page->has('assignees', 0));
    }

    public function test_transition_reorder_and_stale_move_are_atomic(): void
    {
        [$w,$p,$u,$base] = $this->setupProject();
        $a = $this->createTicket($base);
        $b = $this->createTicket($base);
        $active = $p->statuses()->where('code', 'in_progress')->first()->id;
        $done = $p->statuses()->where('code', 'done')->first()->id;
        $this->post($base.'/tickets/'.$a->id.'/move', $this->moveData($p, $a, $done))->assertSessionHasErrors('status_id');
        $this->assertSame($a->status_id, $a->fresh()->status_id);
        $reorder = [...$this->moveData($p, $b, $b->status_id), 'before_id' => $a->id];
        $this->post($base.'/tickets/'.$b->id.'/move', $reorder)->assertSessionHasNoErrors();
        $this->assertSame([$b->id, $a->id], $p->tickets()->orderBy('sort_order')->pluck('id')->all());
        $this->post($base.'/tickets/'.$b->id.'/move', $reorder)->assertSessionHasErrors('move');
        $a->refresh();
        $move = $this->moveData($p, $a, $active);
        $this->post($base.'/tickets/'.$a->id.'/move', $move)->assertSessionHasNoErrors();
        $this->assertSame($active, $a->fresh()->status_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'ticket.transitioned', 'subject_id' => $a->id]);
        $this->post($base.'/tickets/'.$a->id.'/move', $move)->assertSessionHasErrors('move');
        $this->assertSame(1, ActivityLog::where('action', 'ticket.transitioned')->count());
    }

    public function test_updates_deletion_stale_versions_and_archived_projects(): void
    {
        [$w,$p,$u,$base] = $this->setupProject();
        $t = $this->createTicket($base);
        $data = ['title' => 'Renamed', 'type' => 'bug', 'priority' => 'high', 'version' => $t->version];
        $this->patch($base.'/tickets/'.$t->id, $data)->assertSessionHasNoErrors();
        $this->patch($base.'/tickets/'.$t->id, $data)->assertSessionHasErrors('version');
        $t->refresh();
        $p->update(['status' => 'archived']);
        $this->get($base.'/tickets')->assertInertia(fn (Assert $page) => $page->where('can.edit', false));
        $this->post($base.'/tickets', ['title' => 'No', 'type' => 'task', 'priority' => 'low'])->assertForbidden();
        $this->patch($base.'/tickets/'.$t->id, [...$data, 'version' => $t->version])->assertForbidden();
        $this->delete($base.'/tickets/'.$t->id, ['version' => $t->version])->assertForbidden();
        $this->post($base.'/tickets/'.$t->id.'/move', $this->moveData($p, $t, $t->status_id))->assertForbidden();
        $this->put($base.'/workflow', [])->assertForbidden();
        $p->update(['status' => 'active']);
        $this->delete($base.'/tickets/'.$t->id, ['version' => $t->version])->assertSessionHasNoErrors();
        $this->assertSoftDeleted('tickets', ['id' => $t->id]);
        $this->get($base.'/tickets?ticket='.$t->id)->assertNotFound();
        $this->assertSame(2, $this->createTicket($base)->number);
    }

    private function workflow(Project $p): array
    {
        return ['version' => $p->fresh()->workflow_version, 'statuses' => $p->statuses()->orderBy('position')->get()->map->only(['code', 'name', 'category', 'is_initial'])->all(), 'transitions' => [['from' => 'todo', 'to' => 'in_progress']]];
    }

    public function test_workflow_preserves_status_ids_and_rejects_invalid_or_stale_configuration(): void
    {
        [$w,$p,$u,$base] = $this->setupProject();
        $t = $this->createTicket($base);
        $data = $this->workflow($p);
        $data['statuses'][0]['name'] = 'Ready';
        $this->put($base.'/workflow', $data)->assertSessionHasNoErrors();
        $this->assertSame($t->status_id, $t->fresh()->status_id);
        $this->assertSame('Ready', $t->fresh()->status->name);
        $this->put($base.'/workflow', $data)->assertSessionHasErrors('version');
        $data = $this->workflow($p);
        $data['statuses'][0]['is_initial'] = false;
        $this->put($base.'/workflow', $data)->assertSessionHasErrors('statuses');
        $data = $this->workflow($p);
        $data['transitions'] = [['from' => 'todo', 'to' => 'todo']];
        $this->put($base.'/workflow', $data)->assertSessionHasErrors('transitions');
        $data = $this->workflow($p);
        $data['transitions'] = [['from' => 'todo', 'to' => 'foreign']];
        $this->put($base.'/workflow', $data)->assertSessionHasErrors('transitions');
        $this->delete($base.'/tickets/'.$t->id, ['version' => $t->version]);
        $data = $this->workflow($p);
        array_shift($data['statuses']);
        $data['statuses'][0]['is_initial'] = true;
        $data['transitions'] = [];
        $this->put($base.'/workflow', $data)->assertSessionHasErrors('statuses');
    }

    public function test_board_is_bounded_searchable_and_table_is_paginated(): void
    {
        [$w,$p,$u,$base] = $this->setupProject();
        foreach (range(1, 32) as $i) {
            $this->createTicket($base, ['title' => 'Task '.$i]);
        }
        $this->get($base.'/tickets')->assertInertia(fn (Assert $page) => $page->has('columns.0.tickets', 30)->where('columns.0.total', 32)->where('columns.0.hasMore', true)->where('columns.0.next_id', 31));
        $this->get($base.'/tickets?view=table')->assertInertia(fn (Assert $page) => $page->has('tickets.data', 30)->where('tickets.total', 32));
        $this->get($base.'/tickets?view=table&page=2&ticket=32')->assertInertia(fn (Assert $page) => $page->where('filters.page', 2)->where('tickets.current_page', 2)->where('selectedTicket.id', 32));
        $this->get($base.'/tickets?search=Task%2032')->assertInertia(fn (Assert $page) => $page->where('columns.0.total', 1));
        $this->get($base.'/tickets?priority=bad')->assertSessionHasErrors('priority');
    }

    public function test_each_mutation_requires_its_permission_and_scope(): void
    {
        [$w,$p,$admin,$base] = $this->setupProject();
        $ticket = $this->createTicket($base);
        $user = User::factory()->create();
        $role = $w->roles()->create(['name' => 'Reader', 'code' => 'reader', 'permissions' => ['tickets.view']]);
        $w->members()->create(['user_id' => $user->id, 'role_id' => $role->id]);
        $p->members()->create(['user_id' => $user->id]);
        $this->actingAs($user)->get($base.'/tickets')->assertOk();
        $this->post($base.'/tickets', ['title' => 'No', 'type' => 'task', 'priority' => 'low'])->assertForbidden();
        $this->patch($base.'/tickets/'.$ticket->id, ['title' => 'No', 'type' => 'task', 'priority' => 'low', 'version' => $ticket->version])->assertForbidden();
        $this->delete($base.'/tickets/'.$ticket->id, ['version' => $ticket->version])->assertForbidden();
        $this->post($base.'/tickets/'.$ticket->id.'/move', $this->moveData($p, $ticket, $ticket->status_id))->assertForbidden();
        $this->get($base.'/workflow')->assertForbidden();
        $this->put($base.'/workflow', $this->workflow($p))->assertForbidden();
        $role->update(['permissions' => ['tickets.view', 'tickets.edit', 'tickets.delete', 'tickets.setup']]);
        $p->members()->delete();
        $this->patch($base.'/tickets/'.$ticket->id, ['title' => 'No', 'type' => 'task', 'priority' => 'low', 'version' => $ticket->version])->assertForbidden();
        $this->delete($base.'/tickets/'.$ticket->id, ['version' => $ticket->version])->assertForbidden();
        $this->put($base.'/workflow', $this->workflow($p))->assertForbidden();
    }

    public function test_foreign_ticket_status_and_anchor_cannot_be_submitted(): void
    {
        [$w,$p,$admin,$base] = $this->setupProject();
        $a = $this->createTicket($base);
        $other = Project::factory()->create(['workspace_id' => $w->id]);
        $foreign = Ticket::factory()->create(['project_id' => $other->id]);
        $this->get($base.'/tickets?ticket='.$foreign->id)->assertNotFound();
        $this->patch($base.'/tickets/'.$foreign->id, ['title' => 'No', 'type' => 'task', 'priority' => 'low', 'version' => 1])->assertNotFound();
        $this->delete($base.'/tickets/'.$foreign->id, ['version' => 1])->assertNotFound();
        $this->post($base.'/tickets/'.$a->id.'/move', $this->moveData($p, $a, $foreign->status_id))->assertSessionHasErrors('status_id');
        $this->post($base.'/tickets/'.$a->id.'/move', [...$this->moveData($p, $a, $a->status_id), 'before_id' => $foreign->id])->assertSessionHasErrors('before_id');
        $this->assertSame(0, ActivityLog::whereIn('action', ['ticket.reordered', 'ticket.transitioned'])->count());
    }

    public function test_assignment_choices_are_bounded_searchable_and_removed_members_cannot_be_assigned(): void
    {
        [$w,$p,$admin,$base] = $this->setupProject();
        $role = $w->roles()->where('code', 'member')->firstOrFail();
        foreach (range(1, 52) as $i) {
            $user = User::factory()->create(['name' => sprintf('Person %02d', $i)]);
            $w->members()->create(['user_id' => $user->id, 'role_id' => $role->id]);
            $p->members()->create(['user_id' => $user->id]);
        }
        $this->get($base.'/tickets')->assertInertia(fn (Assert $page) => $page->has('assignees', 50));
        $this->get($base.'/tickets?assignee_search=Person%2052')->assertInertia(fn (Assert $page) => $page->has('assignees', 1)->where('assignees.0.label', 'Person 52'));
        $p->members()->where('user_id', $user->id)->delete();
        $this->post($base.'/tickets', ['title' => 'No', 'type' => 'task', 'priority' => 'low', 'assignee_id' => $user->id])->assertSessionHasErrors('assignee_id');
    }

    public function test_demo_tickets_and_factories_are_repeatable_and_preserve_edits_and_soft_deletion(): void
    {
        $this->seed(DemoDatabaseSeeder::class);
        $this->assertDatabaseCount('tickets', 125);
        $ticket = Ticket::firstOrFail();
        $ticket->update(['title' => 'Keep my edit']);
        $deleted = Ticket::findOrFail($ticket->id + 1);
        $deleted->delete();
        $this->seed(DemoDatabaseSeeder::class);
        $this->assertDatabaseCount('tickets', 125);
        $this->assertSame('Keep my edit', $ticket->fresh()->title);
        $this->assertSoftDeleted('tickets',['id' => $deleted->id]);
        $p = Project::factory()->create();
        $tickets = Ticket::factory()->count(5)->create(['project_id' => $p->id]);
        $this->assertSame([1, 2, 3, 4, 5],$tickets->pluck('number')->all());
        foreach ($tickets as $t) {
            $this->assertSame($p->id,$t->status->project_id);
        }
    }
}
