<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProjectMemberController extends Controller
{
    public function store(Request $request, Workspace $workspace, string $project): RedirectResponse
    {
        $record = DB::transaction(function () use ($request, $workspace, $project) {
            $record = $this->lockedProject($workspace, $project);
            Gate::authorize('manageMembers', $record);
            $data = $request->validate(['user_id' => ['required', 'integer'], 'role_id' => ['missing'], 'project_id' => ['missing'], 'workspace_id' => ['missing']]);
            $user = User::whereKey($data['user_id'])->lockForUpdate()->first();
            if (! $user || $user->status !== 'active'
                || ! $workspace->members()->where('user_id', $user->id)->exists()
                || $record->members()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['user_id' => 'Choose an active workspace member who is not already in this project.']);
            }
            $record->members()->create(['user_id' => $user->id]);
            ActivityLog::record($request->user(), 'project.member_added', $record, $workspace->id, ['user_id' => $user->id]);

            return $record;
        });

        return (Gate::allows('view', $record)
            ? redirect()->route('projects.show', [$workspace, $record])
            : redirect()->route('workspaces.show', $workspace))->with('status', 'Project member added.');
    }

    public function destroy(Request $request, Workspace $workspace, string $project, string $member): RedirectResponse
    {
        $record = DB::transaction(function () use ($request, $workspace, $project, $member) {
            $record = $this->lockedProject($workspace, $project);
            Gate::authorize('manageMembers', $record);
            $membership = $record->members()->findOrFail($member);
            ActivityLog::record($request->user(), 'project.member_removed', $record, $workspace->id, ['user_id' => $membership->user_id]);
            $membership->delete();

            return $record;
        });

        return (Gate::allows('view', $record)
            ? redirect()->route('projects.show', [$workspace, $record])
            : redirect()->route('workspaces.show', $workspace))->with('status', 'Project member removed.');
    }

    private function lockedProject(Workspace $workspace, string $project): Project
    {
        Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();

        return $workspace->projects()->lockForUpdate()->findOrFail($project);
    }
}
