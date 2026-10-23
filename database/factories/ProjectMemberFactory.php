<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectMemberFactory extends Factory
{
    public function definition(): array
    {
        return ['project_id' => Project::factory(), 'user_id' => User::factory()];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ProjectMember $member): void {
            $workspace = $member->project->workspace;
            $role = $workspace->roles()->firstOrCreate(['code' => 'member'], [
                'name' => 'Member', 'permissions' => ['projects.view'], 'is_system' => true,
            ]);
            $workspace->members()->firstOrCreate(['user_id' => $member->user_id], ['role_id' => $role->id]);
        });
    }
}
