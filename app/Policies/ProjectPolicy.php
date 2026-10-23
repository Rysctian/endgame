<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

class ProjectPolicy
{
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $user->hasWorkspacePermission($workspace, 'projects.view');
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $user->hasWorkspacePermission($workspace, 'projects.create');
    }

    private function permitted(User $user, Project $project, string $permission): bool
    {
        return $user->hasWorkspacePermission($project->workspace, $permission)
            && Project::visibleTo($user, $project->workspace)->whereKey($project->id)->exists();
    }

    public function view(User $user, Project $project): bool
    {
        return $this->permitted($user, $project, 'projects.view');
    }

    public function update(User $user, Project $project): bool
    {
        return $project->status === 'active' && $this->permitted($user, $project, 'projects.edit');
    }

    public function archive(User $user, Project $project): bool
    {
        return $project->status === 'active' && $this->permitted($user, $project, 'projects.delete');
    }

    public function restore(User $user, Project $project): bool
    {
        return $project->status === 'archived' && $this->permitted($user, $project, 'projects.setup');
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $project->status === 'active' && $this->permitted($user, $project, 'projects.setup');
    }
}
