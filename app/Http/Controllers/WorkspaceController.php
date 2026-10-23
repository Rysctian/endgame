<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Workspace::query();
        if (! $request->user()->is_system_admin) {
            $query->whereHas('members', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        return Inertia::render('workspaces/index', ['workspaces' => $query->orderBy('name')->paginate(20), 'canCreate' => Gate::allows('create', Workspace::class)]);
    }

    private function validated(Request $request, ?Workspace $workspace = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('workspaces')->ignore($workspace?->id)]]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Workspace::class);
        $data = $this->validated($request);
        $workspace = DB::transaction(function () use ($request, $data) {
            $workspace = Workspace::create($data);
            $workspace->roles()->create(['name' => 'Manager', 'code' => 'manager', 'permissions' => array_keys(config('access.permissions')), 'is_system' => true]);
            $workspace->roles()->create(['name' => 'Member', 'code' => 'member', 'permissions' => ['projects.view'], 'is_system' => true]);
            ActivityLog::record($request->user(), 'workspace.created', $workspace, $workspace->id, $data);

            return $workspace;
        });

        return redirect()->route('workspaces.show', $workspace)->with('status', 'Workspace created.');
    }

    public function update(Request $request, Workspace $workspace)
    {
        Gate::authorize('update', $workspace);
        $data = $this->validated($request, $workspace);
        DB::transaction(function () use ($workspace, $data, $request) {
            $workspace->update($data);
            ActivityLog::record($request->user(), 'workspace.updated', $workspace, $workspace->id, $data);
        });

        return back()->with('status', 'Workspace updated.');
    }

    public function show(Request $request, Workspace $workspace)
    {
        Gate::authorize('view', $workspace);
        $user = $request->user();
        $can = collect(config('access.permissions'))->mapWithKeys(fn ($label, $key) => [$key => $user->hasWorkspacePermission($workspace, $key)])->all();
        $roles = $workspace->roles()->orderBy('name')->get();
        $grants = array_keys(array_filter($can));
        $assignable = $roles->filter(fn ($role) => $user->is_system_admin || array_diff($role->permissions, $grants) === [])->values();
        $members = null;
        if ($can['members.view']) {
            $members = $workspace->members()->with(['user:id,name,email,status,is_system_admin', 'role:id,name,permissions'])->orderBy('id')->paginate(20, ['*'], 'members_page');
            $members->through(function ($member) use ($user, $can, $assignable) {
                $protected = ! $user->is_system_admin && ($member->user_id === $user->id || $member->user->is_system_admin || ! $assignable->contains('id', $member->role_id));

                return ['id' => $member->id, 'user' => $member->user, 'role' => $member->role->only('id', 'name'), 'canUpdate' => $can['members.edit'] && ! $protected, 'canRemove' => $can['members.delete'] && ! $protected];
            });
        }

        return Inertia::render('workspaces/show', [
            'workspace' => $workspace, 'can' => $can, 'canEdit' => Gate::allows('update', $workspace),
            'members' => $members,
            'roles' => $can['roles.view'] ? $roles->map(fn ($role) => [
                ...$role->only('id', 'name', 'code', 'permissions', 'is_system'),
                'canEdit' => ! $role->is_system && $assignable->contains('id', $role->id) && ($can['roles.edit'] || $can['roles.setup']),
                'canDelete' => ! $role->is_system && $assignable->contains('id', $role->id) && $can['roles.delete'],
            ])->all() : [],
            'roleOptions' => ($can['members.create'] || $can['members.edit']) ? $assignable->map->only('id', 'name')->all() : [],
            'permissionModules' => collect(config('access.modules'))->map(function ($module, $key) use ($can) {
                return ['key' => $key, 'label' => $module['label'], 'actions' => collect($module['actions'])->mapWithKeys(fn ($label, $action) => [$action => ['key' => $key.'.'.$action, 'label' => $label, 'canGrant' => $can[$key.'.'.$action]]])->all()];
            })->values()->all(),
            'invitations' => $can['members.create'] ? $workspace->invitations()->with('role:id,name')->whereNull('accepted_at')->whereNull('cancelled_at')->orderByDesc('id')->paginate(10, ['id', 'workspace_id', 'email', 'role_id', 'expires_at'], 'invitations_page')->through(fn ($invitation) => [...$invitation->only('id', 'email', 'expires_at'), 'role' => $invitation->role->only('id', 'name'), 'canCancel' => $assignable->contains('id', $invitation->role_id)]) : null,
            'activities' => $can['activity.view'] ? $workspace->activities()->orderByDesc('id')->paginate(15, ['id', 'action', 'actor_id', 'changes', 'created_at'], 'activity_page') : null,
        ]);
    }
}
