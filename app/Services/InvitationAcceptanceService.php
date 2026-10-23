<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Support\Facades\DB;

class InvitationAcceptanceService
{
    public function validate(WorkspaceInvitation $invitation, User $user, string $token): void
    {
        abort_unless(hash_equals($invitation->token_hash, hash('sha256', $token)), 404);
        abort_unless($user->status === 'active' && $user->hasVerifiedEmail() && strcasecmp($user->email, $invitation->email) === 0, 403);
        abort_if($invitation->cancelled_at, 410, 'This invitation was cancelled.');
        if (! $invitation->accepted_at) {
            abort_if($invitation->expires_at->isPast(), 410, 'This invitation has expired.');
        }
    }

    public function accept(WorkspaceInvitation $invitation, User $user, string $token): void
    {
        DB::transaction(function () use ($invitation, $user, $token) {
            $workspace = Workspace::whereKey($invitation->workspace_id)->lockForUpdate()->firstOrFail();
            $invitation = WorkspaceInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            $this->validate($invitation, $user, $token);
            if ($invitation->accepted_at) {
                return;
            }
            $workspace->members()->firstOrCreate(['user_id' => $user->id], ['role_id' => $invitation->role_id]);
            $invitation->update(['accepted_at' => now()]);
            ActivityLog::record($user, 'invitation.accepted', $invitation, $workspace->id, ['user_id' => $user->id, 'role_id' => $invitation->role_id]);
        });
    }
}
