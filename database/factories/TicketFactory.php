<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return ['project_id' => Project::factory(), 'title' => fake()->sentence(5), 'description' => fake()->paragraph(), 'type' => 'task', 'priority' => 'medium', 'assignee_id' => null, 'due_date' => now()->addDays(7)->toDateString()];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Ticket $ticket) {
            DB::transaction(function () use ($ticket) {
                $project = Project::whereKey($ticket->project_id)->lockForUpdate()->firstOrFail();
                $project->increment('last_ticket_number');
                $project->increment('board_version');
                $status = $project->statuses()->where('is_initial', true)->firstOrFail();
                $ticket->forceFill(['number' => $project->last_ticket_number, 'reporter_id' => $project->created_by, 'status_id' => $ticket->status_id ?? $status->id, 'sort_order' => $project->last_ticket_number, 'version' => 1]);
            });
        });
    }
}
