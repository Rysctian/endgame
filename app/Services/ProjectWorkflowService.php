<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProjectWorkflowService
{
    public function initialize(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $project = Project::whereKey($project->id)->lockForUpdate()->firstOrFail();
            if ($project->statuses()->exists()) {
                return;
            }
            $statuses = [];
            foreach (config('tickets.workflow') as $position => $definition) {
                $statuses[$definition['code']] = $project->statuses()->create([...collect($definition)->except('next')->all(), 'position' => $position]);
            }
            foreach (config('tickets.workflow') as $definition) {
                foreach ($definition['next'] as $next) {
                    $project->transitions()->create(['from_status_id' => $statuses[$definition['code']]->id, 'to_status_id' => $statuses[$next]->id]);
                }
            }
        });
    }

    public function save(User $actor, Workspace $workspace, string $id, array $data): void
    {
        DB::transaction(function () use ($actor, $workspace, $id, $data) {
            Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            $project = $workspace->projects()->lockForUpdate()->findOrFail($id);
            $actor = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('setup', [Ticket::class, $project]);
            if ($project->workflow_version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'The workflow changed. Reload before saving.']);
            }
            $codes = array_column($data['statuses'], 'code');
            if (count(array_filter($data['statuses'], fn ($s) => (bool) $s['is_initial'])) !== 1) {
                throw ValidationException::withMessages(['statuses' => 'Choose exactly one initial status.']);
            }
            $pairs = [];
            foreach ($data['transitions'] as $t) {
                $pair = $t['from'].'>'.$t['to'];
                if ($t['from'] === $t['to'] || ! in_array($t['from'], $codes, true) || ! in_array($t['to'], $codes, true) || in_array($pair, $pairs, true)) {
                    throw ValidationException::withMessages(['transitions' => 'Choose distinct transitions between statuses in this project.']);
                }
                $pairs[] = $pair;
            }
            $removed = $project->statuses()->whereNotIn('code', $codes)->pluck('id');
            if (Ticket::withTrashed()->where('project_id', $project->id)->whereIn('status_id', $removed)->exists()) {
                throw ValidationException::withMessages(['statuses' => 'A status used by a ticket, including deleted tickets, cannot be removed.']);
            }
            $before = ['statuses' => $project->statuses()->orderBy('position')->get()->toArray(), 'transitions' => $project->transitions()->get()->toArray()];
            $project->transitions()->delete();
            $project->statuses()->whereIn('id', $removed)->delete();
            $statuses = [];
            foreach ($data['statuses'] as $position => $status) {
                $statuses[$status['code']] = $project->statuses()->updateOrCreate(['code' => $status['code']], [...$status, 'position' => $position]);
            }
            foreach ($data['transitions'] as $transition) {
                $project->transitions()->create(['from_status_id' => $statuses[$transition['from']]->id, 'to_status_id' => $statuses[$transition['to']]->id]);
            }
            $project->increment('workflow_version');
            $project->increment('board_version');
            ActivityLog::record($actor, 'workflow.updated', $project, $workspace->id, ['before' => $before, 'after' => ['statuses' => $data['statuses'], 'transitions' => $data['transitions']]]);
        }, 3);
    }
}
