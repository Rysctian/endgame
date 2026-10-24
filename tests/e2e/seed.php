<?php

use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectWorkflowService;
use App\Services\TicketService;
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

$projectWorkspace = Workspace::create(['name' => 'Project team', 'slug' => 'project-team']);
$projectManagerRole = $projectWorkspace->roles()->create(['name' => 'Manager', 'code' => 'manager', 'permissions' => array_keys(config('access.permissions')), 'is_system' => true]);
$projectMemberRole = $projectWorkspace->roles()->create(['name' => 'Member', 'code' => 'member', 'permissions' => ['projects.view'], 'is_system' => true]);
foreach (['projectmanager', 'projectmember'] as $name) {
    $user = User::create(['name' => ucfirst($name), 'email' => $name.'@endgame.test', 'password' => Hash::make('browser-password-123')]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $projectWorkspace->members()->create(['user_id' => $user->id, 'role_id' => $name === 'projectmanager' ? $projectManagerRole->id : $projectMemberRole->id]);
}

$ticketWorkspace = Workspace::create(['name' => 'Ticket team', 'slug' => 'ticket-team']);
$ticketRole = $ticketWorkspace->roles()->create(['name' => 'Manager', 'code' => 'manager', 'permissions' => array_keys(config('access.permissions')), 'is_system' => true]);
$ticketUser = User::create(['name' => 'Ticket manager', 'email' => 'ticketmanager@endgame.test', 'password' => Hash::make('browser-password-123')]);
$ticketUser->forceFill(['email_verified_at' => now()])->save();
$ticketWorkspace->members()->create(['user_id' => $ticketUser->id, 'role_id' => $ticketRole->id]);
$ticketProject = $ticketWorkspace->projects()->create(['name' => 'Product launch', 'key' => 'SHIP', 'description' => 'Deliver a thoughtful first release.', 'created_by' => $ticketUser->id, 'status' => 'active']);
$ticketProject->members()->create(['user_id' => $ticketUser->id]);
app(ProjectWorkflowService::class)->initialize($ticketProject);
foreach (['Map the onboarding journey', 'Polish the navigation', 'Review keyboard accessibility'] as $title) {
    app(TicketService::class)->create($ticketUser, $ticketWorkspace, (string) $ticketProject->id, ['title' => $title, 'description' => 'Keep the experience clear and considered.', 'type' => 'task', 'priority' => 'medium', 'assignee_id' => $ticketUser->id]);
}

foreach (['Mobile app' => 'MOB', 'Design system' => 'DSGN'] as $name => $key) {
    $project = $ticketWorkspace->projects()->create(['name' => $name, 'key' => $key, 'created_by' => $ticketUser->id, 'status' => $key === 'DSGN' ? 'archived' : 'active']);
    app(ProjectWorkflowService::class)->initialize($project);
}
