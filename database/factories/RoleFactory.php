<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    public function definition(): array
    {
        return ['workspace_id' => Workspace::factory(), 'name' => fake()->unique()->jobTitle(), 'code' => 'role-'.fake()->unique()->numberBetween(1, 9999999), 'permissions' => ['members.view'], 'is_system' => false];
    }
}
