<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TicketService
{
    public function eligibleAssignees(Project $project): Builder
    {
        return User::where('status', 'active')->whereNotNull('email_verified_at')
            ->whereIn('id', $project->members()->select('user_id'))
            ->whereHas('memberships', fn ($q) => $q->where('workspace_id', $project->workspace_id)->whereHas('role', fn ($q) => $q->whereJsonContains('permissions', 'tickets.view')));
    }

    private function lockedProject(Workspace $workspace, string $id): Project
    {
        Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();

        return $workspace->projects()->lockForUpdate()->findOrFail($id);
    }

    private function assignee(Project $project, ?int $id, ?Ticket $ticket = null): void
    {
        if ($id === null || ($ticket && $ticket->assignee_id === $id)) {
            return;
        }
        // Synchronize with account suspension as well as workspace/project membership writes.
        User::whereKey($id)->lockForUpdate()->first();
        if (! $this->eligibleAssignees($project)->whereKey($id)->exists()) {
            throw ValidationException::withMessages(['assignee_id' => 'Choose an active project member with ticket access.']);
        }
    }

    private function version(Ticket $ticket, int $version, string $field = 'version'): void
    {
        if ($ticket->version !== $version) {
            throw ValidationException::withMessages([$field => 'This ticket changed. Reload before trying again.']);
        }
    }

    public function create(User $actor, Workspace $workspace, string $id, array $data): Ticket
    {
        return DB::transaction(function () use ($actor, $workspace, $id, $data) {
            $project = $this->lockedProject($workspace, $id);
            $actor = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [Ticket::class, $project]);
            $this->assignee($project, isset($data['assignee_id']) ? (int) $data['assignee_id'] : null);
            $status = $project->statuses()->where('is_initial', true)->firstOrFail();
            $project->increment('last_ticket_number');
            $project->increment('board_version');
            $ticket = new Ticket($data);
            $ticket->forceFill(['project_id' => $project->id, 'number' => $project->last_ticket_number, 'reporter_id' => $actor->id, 'status_id' => $status->id, 'sort_order' => (int) $project->tickets()->where('status_id', $status->id)->max('sort_order') + 1, 'version' => 1])->save();
            ActivityLog::record($actor, 'ticket.created', $ticket, $workspace->id, ['project_id' => $project->id, 'key' => $project->key.'-'.$ticket->number, ...$ticket->only('title', 'type', 'priority', 'assignee_id', 'status_id')]);

            return $ticket;
        }, 3);
    }

    public function update(User $actor, Workspace $workspace, string $id, string $ticketId, array $data): void
    {
        DB::transaction(function () use ($actor, $workspace, $id, $ticketId, $data) {
            $project = $this->lockedProject($workspace, $id);
            $actor = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $ticket = $project->tickets()->lockForUpdate()->findOrFail($ticketId);
            $ticket->setRelation('project', $project);
            Gate::forUser($actor)->authorize('update', $ticket);
            $this->version($ticket, (int) $data['version']);
            $this->assignee($project, isset($data['assignee_id']) ? (int) $data['assignee_id'] : null, $ticket);
            $fields = ['title', 'description', 'type', 'priority', 'assignee_id', 'due_date'];
            $before = $ticket->only($fields);
            $ticket->fill(collect($data)->only($fields)->all());
            if ($ticket->isDirty()) {
                $ticket->version++;
                $ticket->save();
                $project->increment('board_version');
                ActivityLog::record($actor, 'ticket.updated', $ticket, $workspace->id, ['project_id' => $project->id, 'before' => $before, 'after' => $ticket->only($fields)]);
            }
        }, 3);
    }

    public function delete(User $actor, Workspace $workspace, string $id, string $ticketId, int $version): void
    {
        DB::transaction(function () use ($actor, $workspace, $id, $ticketId, $version) {
            $project = $this->lockedProject($workspace, $id);
            $actor = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $ticket = $project->tickets()->lockForUpdate()->findOrFail($ticketId);
            $ticket->setRelation('project', $project);
            Gate::forUser($actor)->authorize('delete', $ticket);
            $this->version($ticket, $version);
            $ticket->delete();
            $project->increment('board_version');
            ActivityLog::record($actor, 'ticket.deleted', $ticket, $workspace->id, ['project_id' => $project->id, 'title' => $ticket->title]);
        }, 3);
    }

    public function move(User $actor, Workspace $workspace, string $id, string $ticketId, array $data): void
    {
        DB::transaction(function () use ($actor, $workspace, $id, $ticketId, $data) {
            $project = $this->lockedProject($workspace, $id);
            $actor = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $ticket = $project->tickets()->lockForUpdate()->findOrFail($ticketId);
            $ticket->setRelation('project', $project);
            Gate::forUser($actor)->authorize('update', $ticket);
            $this->version($ticket, (int) $data['version'], 'move');
            if ($project->board_version !== (int) $data['board_version'] || $ticket->status_id !== (int) $data['expected_status_id']) {
                throw ValidationException::withMessages(['move' => 'The board changed. Reload before moving this ticket.']);
            }
            $target = $project->statuses()->find($data['status_id']);
            if (! $target || ($target->id !== $ticket->status_id && ! $project->transitions()->where('from_status_id', $ticket->status_id)->where('to_status_id', $target->id)->exists())) {
                throw ValidationException::withMessages(['status_id' => 'This workflow transition is not allowed.']);
            }
            $ordered = $project->tickets()->where('status_id', $target->id)->whereKeyNot($ticket->id)->orderBy('sort_order')->orderBy('id')->get();
            $beforeId = $data['before_id'] ?? null;
            $index = $beforeId === null ? $ordered->count() : $ordered->search(fn ($t) => $t->id === (int) $beforeId);
            if ($index === false) {
                throw ValidationException::withMessages(['before_id' => 'The target position is no longer available.']);
            }
            $before = ['status_id' => $ticket->status_id, 'status' => $ticket->status->name, 'sort_order' => $ticket->sort_order];
            $ordered->splice($index, 0, [$ticket]);
            foreach ($ordered as $position => $item) {
                $item->sort_order = $position + 1;
                if ($item->id === $ticket->id) {
                    $item->status_id = $target->id;
                }
                if ($item->isDirty()) {
                    $item->version++;
                    $item->save();
                }
            }
            $project->increment('board_version');
            ActivityLog::record($actor,$before['status_id'] === $target->id ? 'ticket.reordered' : 'ticket.transitioned',$ticket,$workspace->id,['project_id' => $project->id, 'before' => $before, 'after' => ['status_id' => $target->id, 'status' => $target->name, 'sort_order' => $ticket->sort_order]]);
        }, 3);
    }
}
