<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('administer-users');

        return Inertia::render('admin/users', ['users' => User::orderBy('name')->paginate(20, ['id', 'name', 'email', 'status', 'is_system_admin', 'email_verified_at']),
            'activities' => ActivityLog::whereNull('workspace_id')->orderByDesc('id')->paginate(15, ['id', 'action', 'actor_id', 'subject_id', 'changes', 'created_at'], 'activity_page')]);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('administer-users');
        abort_if($user->is_system_admin || $user->id === $request->user()->id, 403);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'suspended'])]]);
        DB::transaction(function () use ($user, $request, $data) {
            $before = $user->status;
            $user->forceFill($data)->save();
            if ($user->status === 'suspended') {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            ActivityLog::record($request->user(), 'account.status_updated', $user, null, ['from' => $before, 'to' => $user->status]);
        });

        return back()->with('status', 'Account updated.');
    }
}
