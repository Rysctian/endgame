<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class MakeAdmin extends Command
{
    protected $signature = 'app:make-admin {email} {--name=}';

    protected $description = 'Create or promote a System Admin without changing existing credentials';

    public function handle(): int
    {
        $email = Str::lower(trim($this->argument('email')));
        $input = ['email' => $email];
        $rules = ['email' => ['required', 'email', 'max:255']];
        $user = User::where('email', $email)->first();
        if (! $user) {
            $input['name'] = $this->option('name') ?: $this->ask('Name');
            $input['password'] = $this->secret('Password (at least 12 characters)');
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['password'] = ['required', Password::min(12)];
        }
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        DB::transaction(function () use ($user, $input) {
            if (! $user) {
                $user = User::create($input);
                $user->email_verified_at = now();
            }
            $user->is_system_admin = true;
            $user->save();
            ActivityLog::record(null, 'account.admin_provisioned', $user);
        });
        $this->info('System Admin provisioned. Existing account status and credentials are preserved.');

        return self::SUCCESS;
    }
}
