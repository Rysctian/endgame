<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkspaceInvitationFactory extends Factory
{
    public function definition(): array
    {
        return ['workspace_id' => Workspace::factory(), 'email' => fake()->unique()->safeEmail(), 'role_id' => fn (array $attributes) => Workspace::findOrFail($attributes['workspace_id'])->roles()->where('name', 'Member')->firstOrFail()->id, 'token_hash' => hash('sha256', Str::random(64)), 'expires_at' => now()->addDays(7), 'invited_by' => User::factory()];
    }
}
