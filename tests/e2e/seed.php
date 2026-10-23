<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = realpath((string) config('database.connections.sqlite.database'));
$testDirectory = realpath(storage_path('framework/testing'));
if (! app()->environment('testing') || config('database.default') !== 'sqlite' || ! $database || ! $testDirectory || ! str_starts_with($database, $testDirectory.DIRECTORY_SEPARATOR.'e2e-')) {
    fwrite(STDERR, "Browser fixtures require a dedicated e2e SQLite database under storage/framework/testing.\n");
    exit(1);
}
if (in_array('--check', $argv, true)) {
    exit(0);
}

foreach (['admin', 'manager', 'recipient', 'auditor'] as $name) {
    $user = User::create(['name' => ucfirst($name), 'email' => $name.'@endgame.test', 'password' => Hash::make('browser-password-123')]);
    $user->forceFill(['email_verified_at' => now(), 'is_system_admin' => $name === 'admin'])->save();
}
$w = Workspace::create(['name' => 'Browser team', 'slug' => 'browser-team']);
$manager = $w->roles()->create(['name' => 'Manager', 'code' => 'manager', 'permissions' => array_keys(config('access.permissions')), 'is_system' => true]);
$member = $w->roles()->create(['name' => 'Member', 'code' => 'member', 'permissions' => [], 'is_system' => true]);
$w->members()->create(['user_id' => User::where('email', 'manager@endgame.test')->first()->id, 'role_id' => $manager->id]);
$w->members()->create(['user_id' => User::where('email', 'auditor@endgame.test')->firstOrFail()->id, 'role_id' => $member->id]);
$w->invitations()->create(['email' => 'recipient@endgame.test', 'role_id' => $member->id, 'token_hash' => hash('sha256', str_repeat('a', 64)), 'expires_at' => now()->addDays(7), 'invited_by' => User::where('email', 'manager@endgame.test')->first()->id]);
