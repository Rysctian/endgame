<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvitationRequest;
use App\Models\ActivityLog;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use App\Services\InvitationAcceptanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function store(InvitationRequest $request, Workspace $workspace)
    {
        $token = Str::random(64);
        $invitation = DB::transaction(function () use ($request, $workspace, $token) {
            Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('permission', [$workspace, 'members.create']);
            $data = $request->validated();
            $role = $workspace->roles()->findOrFail($data['role_id']);
            abort_unless($role->assignableBy($request->user()), 403);
            if ($workspace->members()->whereHas('user', fn ($q) => $q->where('email', $data['email']))->exists()) {
                throw ValidationException::withMessages(['email' => 'This user is already a member.']);
            }
            if ($workspace->invitations()->where('email', $data['email'])->whereNull('accepted_at')->whereNull('cancelled_at')->where('expires_at', '>', now())->exists()) {
                throw ValidationException::withMessages(['email' => 'A pending invitation already exists.']);
            }
            $invitation = $workspace->invitations()->create([...$data, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(7), 'invited_by' => $request->user()->id]);
            ActivityLog::record($request->user(), 'invitation.created', $invitation, $workspace->id, $data);

            return $invitation;
        });
        try {
            Notification::route('mail', $invitation->email)->notify(new WorkspaceInvitationNotification($invitation, $token));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['invitation' => 'The invitation was saved, but email delivery failed. Cancel it and try again.']);
        }

        return back()->with('status', 'Invitation sent.');
    }

    public function destroy(Request $request, Workspace $workspace, string $invitation)
    {
        DB::transaction(function () use ($request, $workspace, $invitation) {
            Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('permission', [$workspace, 'members.create']);
            $record = $workspace->invitations()->with('role')->findOrFail($invitation);
            abort_unless($record->role->assignableBy($request->user()), 403);
            abort_if($record->accepted_at, 409, 'This invitation has already been accepted.');
            if (! $record->cancelled_at) {
                $record->update(['cancelled_at' => now()]);
                ActivityLog::record($request->user(), 'invitation.cancelled', $record, $workspace->id, ['email' => $record->email]);
            }
        });

        return back()->with('status', 'Invitation cancelled.');
    }

    public function show(Request $request, WorkspaceInvitation $invitation, string $token, InvitationAcceptanceService $service)
    {
        $service->validate($invitation, $request->user(), $token);

        return Inertia::render('invitations/show', ['invitation' => ['id' => $invitation->id, 'email' => $invitation->email, 'workspace' => $invitation->workspace->only('name'), 'role' => $invitation->role->only('name'), 'accepted' => (bool) $invitation->accepted_at], 'token' => $token]);
    }

    public function accept(Request $request, WorkspaceInvitation $invitation, string $token, InvitationAcceptanceService $service)
    {
        $service->accept($invitation, $request->user(), $token);

        return redirect()->route('workspaces.show', $invitation->workspace_id)->with('status', 'Invitation accepted.');
    }
}
