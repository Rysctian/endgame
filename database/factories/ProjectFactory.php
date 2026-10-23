<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'created_by' => User::factory()->state(['is_system_admin' => true]),
            'name' => fake()->catchPhrase(),
            'key' => 'P'.fake()->unique()->numerify('######'),
            'description' => fake()->paragraph(),
            'status' => 'active',
        ];
    }
}
