<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Ticket;
use App\Services\ProjectWorkflowService;
use App\Services\TicketService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoTicketSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data is available only in local/testing environments.');
        }
        $titles = ['Map the onboarding journey', 'Build the project navigation', 'Review keyboard accessibility', 'Polish the release checklist', 'Verify invitation delivery'];
        $service = app(TicketService::class);
        Project::whereIn('key', ['DEMO1', 'DEMO2', 'DEMO3', 'DEMO4', 'DEMO5'])->whereHas('workspace', fn ($q) => $q->whereIn('slug', ['endgame-hq', 'product-design', 'engineering', 'customer-success', 'operations']))->where('status', 'active')->each(function ($project) use ($titles, $service) {
            DB::transaction(function () use ($project, $titles, $service) {
                $workspace = $project->workspace()->lockForUpdate()->firstOrFail();
                $project = Project::whereKey($project->id)->lockForUpdate()->firstOrFail();
                app(ProjectWorkflowService::class)->initialize($project);
                $actor = $project->creator;
                if (! $actor->hasWorkspacePermission($workspace, 'tickets.create')) {
                    return;
                }
                // Seed a demo project only once; edited or soft-deleted demo tickets stay untouched.
                if (Ticket::withTrashed()->where('project_id', $project->id)->exists()) {
                    return;
                }
                $assignee = $service->eligibleAssignees($project)->orderBy('id')->first();
                foreach ($titles as $i => $title) {
                    $ticket = $service->create($actor, $workspace, (string) $project->id, ['title' => $title, 'description' => 'Coordinate the next delivery step for '.$project->name.'.', 'type' => ['story', 'task', 'task', 'task', 'bug'][$i], 'priority' => ['medium', 'high', 'medium', 'low', 'urgent'][$i], 'assignee_id' => $assignee?->id, 'due_date' => now()->addDays($i - 1)->toDateString()]);
                    if ($i > 0 && $actor->hasWorkspacePermission($workspace, 'tickets.edit')) {
                        $path = array_slice(['in_progress', 'in_review', 'done'], 0, min($i, 3));
                        foreach ($path as $code) {
                            $target = $project->statuses()->where('code', $code)->first();
                            if (! $target || ! $project->transitions()->where('from_status_id', $ticket->status_id)->where('to_status_id', $target->id)->exists()) {
                                break;
                            }
                            $service->move($actor, $workspace, (string) $project->id, (string) $ticket->id, ['status_id' => $target->id, 'expected_status_id' => $ticket->status_id, 'version' => $ticket->version, 'board_version' => $project->fresh()->board_version, 'before_id' => null]);
                            $ticket->refresh();
                        }
                    }
                }
            });
        });
    }
}
