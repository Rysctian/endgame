<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function permitted(User $user, Project $project, string $permission): bool
    {
        return $user->hasWorkspacePermission($project->workspace, $permission) && Project::visibleTo($user, $project->workspace)->whereKey($project->id)->exists();
    }

    public function viewAny(User $user, Project $project): bool
    {
        return $this->permitted($user, $project, 'tickets.view');
    }

    public function create(User $user, Project $project): bool
    {
        return $project->status === 'active' && $this->permitted($user, $project, 'tickets.create');
    }

    public function editAny(User $user, Project $project): bool
    {
        return $project->status === 'active' && $this->permitted($user, $project, 'tickets.edit');
    }

    public function deleteAny(User $user, Project $project): bool
    {
        return $project->status === 'active' && $this->permitted($user, $project, 'tickets.delete');
    }

    public function setup(User $user, Project $project): bool
    {
        return $project->status === 'active' && $this->permitted($user, $project, 'tickets.setup');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->editAny($user, $ticket->project);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $this->deleteAny($user,$ticket->project);
    }
}
