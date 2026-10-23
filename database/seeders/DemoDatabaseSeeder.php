<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data is available only in local/testing environments.');
        }

        DB::transaction(function (): void {
            $users = $this->seedUsers();
            $permissionKeys = array_keys(config('access.permissions'));
            $workspaceNames = [
                ['Endgame HQ', 'endgame-hq'],
                ['Product Design', 'product-design'],
                ['Engineering', 'engineering'],
                ['Customer Success', 'customer-success'],
                ['Operations', 'operations'],
            ];

            foreach ($workspaceNames as [$name, $slug]) {
                $workspace = Workspace::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name],
                );

                $manager = $this->role($workspace, 'Manager', 'manager', $permissionKeys, true);
                $member = $this->role($workspace, 'Member', 'member', [], true);
                $auditor = $this->role($workspace, 'Auditor', 'auditor', ['activity.view'], false);
                $contributor = $this->role($workspace, 'Contributor', 'contributor', ['members.view', 'members.create', 'members.edit'], false);
                $roleDesigner = $this->role($workspace, 'Role designer', 'role-designer', ['roles.view', 'roles.create', 'roles.edit', 'roles.setup'], false);

                $roles = [$manager, $member, $auditor, $contributor, $roleDesigner];

                foreach ($users as $index => $user) {
                    WorkspaceMember::firstOrCreate(
                        ['workspace_id' => $workspace->id, 'user_id' => $user->id],
                        ['role_id' => $roles[$index % count($roles)]->id],
                    );
                }

                foreach (range(1, 5) as $index) {
                    $email = sprintf('invite-%d@%s.example.test', $index, $slug);

                    WorkspaceInvitation::firstOrCreate(
                        ['workspace_id' => $workspace->id, 'email' => $email],
                        [
                            'role_id' => $index % 2 === 0 ? $contributor->id : $member->id,
                            'token_hash' => hash('sha256', 'demo-'.$slug.'-'.$index),
                            'expires_at' => now()->addDays(7 + $index),
                            'invited_by' => $users[0]->id,
                        ],
                    );
                }

                $subjects = [$workspace, $manager, $member, $auditor, $contributor];
                foreach ($subjects as $index => $subject) {
                    ActivityLog::firstOrCreate(
                        [
                            'workspace_id' => $workspace->id,
                            'action' => 'demo.seeded.'.($index + 1),
                            'subject_type' => $subject->getMorphClass(),
                            'subject_id' => $subject->getKey(),
                        ],
                        [
                            'actor_id' => $users[0]->id,
                            'changes' => ['source' => 'DemoDatabaseSeeder', 'workspace' => $slug],
                        ],
                    );
                }
            }
        });
    }

    /**
     * @return array<int, User>
     */
    private function seedUsers(): array
    {
        $users = [];

        foreach (range(1, 5) as $index) {
            $user = User::firstOrCreate(
                ['email' => 'demo-user-'.$index.'@example.test'],
                [
                    'name' => 'Demo User '.$index,
                    'password' => Hash::make('demo-password-123'),
                ],
            );

            $user->forceFill([
                'email_verified_at' => $user->email_verified_at ?? now(),
                'status' => 'active',
                'is_system_admin' => false,
            ])->save();

            $users[] = $user;
        }

        return $users;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function role(Workspace $workspace, string $name, string $code, array $permissions, bool $system): Role
    {
        return $workspace->roles()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'permissions' => $permissions,
                'is_system' => $system,
            ],
        );
    }
}
