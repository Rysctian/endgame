<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowRequest;
use App\Models\Ticket;
use App\Models\Workspace;
use App\Services\ProjectWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ProjectWorkflowController extends Controller
{
    public function edit(Request $request, Workspace $workspace, string $project)
    {
        $record = $workspace->projects()->findOrFail($project);
        Gate::authorize('setup', [Ticket::class, $record]);
        $statuses = $record->statuses()->orderBy('position')->get();

        return Inertia::render('tickets/workflow', ['workspace' => $workspace->only('id', 'name'), 'project' => $record->only('id', 'name', 'key', 'status', 'workflow_version'),
            'statuses' => $statuses->map->only(['id', 'code', 'name', 'category', 'is_initial']),
            'transitions' => $record->transitions()->get()->map(fn ($t) => ['from' => $statuses->find($t->from_status_id)->code, 'to' => $statuses->find($t->to_status_id)->code]),
            'categories' => collect(config('tickets.categories'))->map(fn ($label, $value) => compact('value', 'label'))->values(),
            'canView' => Gate::allows('viewAny', [Ticket::class, $record])]);
    }

    public function update(WorkflowRequest $request, Workspace $workspace, string $project, ProjectWorkflowService $service)
    {
        $service->save($request->user(), $workspace, $project, $request->validated());

        return back()->with('status','Workflow saved.');
    }
}
