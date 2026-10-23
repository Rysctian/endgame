<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $user->status === 'active' && $user->hasVerifiedEmail() && ($user->is_system_admin || $workspace->members()->where('user_id', $user->id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->status === 'active' && $user->hasVerifiedEmail() && $user->is_system_admin;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $this->create($user);
    }

    public function permission(User $user, Workspace $workspace, string $permission): bool
    {
        return $user->hasWorkspacePermission($workspace, $permission);
    }
}
