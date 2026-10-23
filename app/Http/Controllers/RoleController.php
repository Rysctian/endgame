<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\ActivityLog;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function store(RoleRequest $request, Workspace $workspace): RedirectResponse
    {
        DB::transaction(function () use ($request, $workspace) {
            Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('permission', [$workspace, 'roles.create']);
            $data = $request->validated();
            if ($data['permissions'] !== []) {
                Gate::authorize('permission', [$workspace, 'roles.setup']);
            }
            $this->checkGrants($request, $workspace, $data['permissions']);
            $role = $workspace->roles()->create($data);
            ActivityLog::record($request->user(), 'role.created', $role, $workspace->id, $data);
        });

        return back()->with('status', 'Role created.');
    }

    public function update(RoleRequest $request, Workspace $workspace, string $role): RedirectResponse
    {
        DB::transaction(function () use ($request, $workspace, $role) {
            Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            $record = $workspace->roles()->findOrFail($role);
            abort_if($record->is_system || ! $record->assignableBy($request->user()), 403);
            $data = $request->validated();
            $detailsChanged = $record->name !== $data['name'] || $record->code !== $data['code'];
            $before = $record->only('name', 'code', 'permissions');
            $existing = $record->permissions;
            $proposed = $data['permissions'];
            sort($existing);
            sort($proposed);
            if ($detailsChanged) {
                Gate::authorize('permission', [$workspace, 'roles.edit']);
            }
            if ($existing !== $proposed) {
                Gate::authorize('permission', [$workspace, 'roles.setup']);
            }
            // Recheck even no-op requests after acquiring the workspace lock.
            abort_unless($request->user()->hasWorkspacePermission($workspace, 'roles.edit') || $request->user()->hasWorkspacePermission($workspace, 'roles.setup'), 403);
            $this->checkGrants($request, $workspace, $data['permissions']);
            $record->update($data);
            ActivityLog::record($request->user(), 'role.updated', $record, $workspace->id, ['before' => $before, 'after' => $data]);
        });

        return back()->with('status', 'Role updated.');
    }

    public function destroy(Request $request, Workspace $workspace, string $role): RedirectResponse
    {
        DB::transaction(function () use ($request, $workspace, $role) {
            Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('permission', [$workspace, 'roles.delete']);
            $record = $workspace->roles()->findOrFail($role);
            abort_if($record->is_system || ! $record->assignableBy($request->user()), 403);
            if ($workspace->members()->where('role_id', $record->id)->exists() || $workspace->invitations()->where('role_id', $record->id)->exists()) {
                throw ValidationException::withMessages(['role' => 'This role is still referenced by members or invitations.']);
            }
            ActivityLog::record($request->user(), 'role.deleted', $record, $workspace->id, $record->only('name', 'code', 'permissions'));
            $record->delete();
        });

        return back()->with('status', 'Role deleted.');
    }

    private function checkGrants(Request $request, Workspace $workspace, array $permissions): void
    {
        foreach ($permissions as $key) {
            abort_unless($request->user()->hasWorkspacePermission($workspace,$key),403);
        }
    }
}
