<?php

namespace App\Queries;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

class ProjectNavigation
{
    public function forUser(?User $user): array
    {
        if (! $user || $user->status !== 'active' || ! $user->hasVerifiedEmail()) {
            return [];
        }

        $workspaces = Workspace::query()
            ->when(! $user->is_system_admin, fn ($query) => $query->whereHas('members', fn ($members) => $members->where('user_id', $user->id)))
            ->with(['members' => fn ($members) => $members->where('user_id', $user->id)->with('role')])
            ->orderBy('name')->orderBy('id')->get(['id', 'name']);
        $groups = $workspaces->map(function ($workspace) use ($user) {
            $role = $workspace->members->first()?->role;
            $permissions = $user->is_system_admin ? array_keys(config('access.permissions')) : ($role?->permissions ?? []);
            $has = fn ($key) => in_array($key, $permissions, true);

            return [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'href' => $has('projects.view') || $has('projects.create') ? route('projects.index', $workspace, false) : null,
                'can' => ['overview' => $has('projects.view'), 'tickets' => $has('tickets.view'), 'workflow' => $has('tickets.setup')],
                'allProjects' => $user->is_system_admin || ($role?->is_system && $role->code === 'manager'),
            ];
        })->filter(fn ($group) => $group['href'] || in_array(true, $group['can'], true))->values();

        $unrestricted = $groups->where('allProjects', true)->pluck('id');
        $projects = Project::query()->whereIn('workspace_id', $groups->pluck('id'))
            ->where(fn ($query) => $query->whereIn('workspace_id', $unrestricted)
                ->orWhereHas('members', fn ($members) => $members->where('user_id', $user->id)))
            ->orderBy('name')->orderBy('id')->get(['id', 'workspace_id', 'name', 'key', 'status'])->groupBy('workspace_id');

        return $groups->map(function ($group) use ($projects) {
            $items = $projects->get($group['id'], collect())->map(function ($project) use ($group) {
                $can = [...$group['can'], 'workflow' => $group['can']['workflow'] && $project->status === 'active'];
                if (! in_array(true, $can, true)) {
                    return null;
                }
                $base = route('projects.show', [$group['id'], $project->id], false);

                return [
                    ...$project->only('id', 'name', 'key', 'status'),
                    'href' => $can['overview'] ? $base : ($can['tickets'] ? $base.'/tickets?view=board' : $base.'/workflow'),
                    'base' => $base,
                    'can' => $can,
                ];
            })->filter()->values()->all();

            return ['id' => $group['id'], 'name' => $group['name'], 'href' => $group['href'], 'projects' => $items];
        })->all();
    }
}
