<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return Inertia::render('settings/profile');
    }

    public function update(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)]]);
        $user = $request->user();
        $changed = $user->email !== $data['email'];
        DB::transaction(function () use ($user, $data, $changed) {
            $user->fill($data);
            if ($changed) {
                $user->email_verified_at = null;
            } $user->save();
            ActivityLog::record($user, 'account.profile_updated', $user, null, $data);
        });
        if ($changed) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Profile updated.');
    }

    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(12)]]);
        DB::transaction(function () use ($request, $data) {
            $request->user()->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
            ActivityLog::record($request->user(), 'account.password_updated', $request->user());
        });
        $request->session()->regenerate();

        return back()->with('status', 'Password updated.');
    }
}
