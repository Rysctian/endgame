<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return ['workspace_id' => Workspace::factory(), 'actor_id' => User::factory(), 'action' => 'workspace.updated', 'subject_type' => Workspace::class, 'subject_id' => fn (array $attributes) => $attributes['workspace_id'], 'changes' => ['name' => fake()->company()], 'created_at' => now()];
    }
}
