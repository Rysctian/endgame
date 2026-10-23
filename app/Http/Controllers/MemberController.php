<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ProjectMember;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MemberController extends Controller
{
    public function update(Request $request, Workspace $workspace, string $member)
    {
        DB::transaction(function () use ($request, $workspace, $member) {
            Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('permission', [$workspace, 'members.edit']);
            $record = $workspace->members()->with(['user', 'role'])->findOrFail($member);
            $this->protect($request, $record);
            $data = $request->validate(['role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('workspace_id', $workspace->id)]]);
            $role = $workspace->roles()->findOrFail($data['role_id']);
            abort_unless($role->assignableBy($request->user()), 403);
            if ($record->role_id !== $role->id && $role->name !== 'Manager') {
                $this->protectLastManager($workspace, $record);
            }
            $before = $record->role_id;
            $record->update(['role_id' => $role->id]);
            ActivityLog::record($request->user(), 'membership.role_updated', $record, $workspace->id, ['user_id' => $record->user_id, 'from' => $before, 'to' => $role->id]);
        });

        return back()->with('status', 'Member role updated.');
    }

    public function destroy(Request $request, Workspace $workspace, string $member)
    {
        DB::transaction(function () use ($request, $workspace, $member) {
            Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('permission', [$workspace, 'members.delete']);
            $record = $workspace->members()->with(['user', 'role'])->findOrFail($member);
            $this->protect($request, $record);
            $this->protectLastManager($workspace, $record);
            ActivityLog::record($request->user(), 'membership.removed', $record, $workspace->id, ['user_id' => $record->user_id, 'role_id' => $record->role_id]);
            $projectMemberships = ProjectMember::where('user_id', $record->user_id)
                ->whereHas('project', fn ($q) => $q->where('workspace_id', $workspace->id))->with('project')->get();
            foreach ($projectMemberships as $membership) {
                ActivityLog::record($request->user(), 'project.member_removed', $membership->project, $workspace->id, ['user_id' => $record->user_id, 'reason' => 'workspace_membership_removed']);
                $membership->delete();
            }
            $record->delete();
        });

        return back()->with('status', 'Member removed.');
    }

    private function protect(Request $request, WorkspaceMember $member): void
    {
        if (! $request->user()->is_system_admin) {
            abort_if($member->user_id === $request->user()->id || $member->user->is_system_admin || ! $member->role->assignableBy($request->user()), 403);
        }
    }

    private function protectLastManager(Workspace $workspace, WorkspaceMember $member): void
    {
        if ($member->role->is_system && $member->role->name === 'Manager' && $member->user->status === 'active' &&
            ! $workspace->members()->where('id', '!=', $member->id)->whereHas('role', fn ($q) => $q->where('is_system', true)->where('name', 'Manager'))->whereHas('user', fn ($q) => $q->where('status', 'active'))->exists()) {
            throw ValidationException::withMessages(['member' => 'Keep at least one active Manager.']);
        }
    }
}
