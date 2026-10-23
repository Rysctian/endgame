<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WorkspaceFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->company(), 'slug' => fake()->unique()->slug().'-'.fake()->numberBetween(100, 99999)];
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($workspace) {
            $workspace->roles()->create(['name' => 'Manager', 'code' => 'manager', 'permissions' => array_keys(config('access.permissions')), 'is_system' => true]);
            $workspace->roles()->create(['name' => 'Member', 'code' => 'member', 'permissions' => ['projects.view'], 'is_system' => true]);
        });
    }
}
