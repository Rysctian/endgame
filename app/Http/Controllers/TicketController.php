<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketRequest;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\Workspace;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request, Workspace $workspace, string $project, TicketService $service): Response
    {
        $record = $workspace->projects()->findOrFail($project);
        Gate::authorize('viewAny', [Ticket::class, $record]);
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['board', 'list', 'table'])], 'search' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(array_keys(config('tickets.priorities')))], 'type' => ['nullable', Rule::in(array_keys(config('tickets.types')))],
            'status_id' => ['nullable', 'integer', Rule::exists('project_statuses', 'id')->where('project_id', $record->id)],
            'assignee_id' => ['nullable', 'integer', 'min:0'], 'assignee_search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['number', 'title', 'due_date', 'created_at'])], 'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'], 'ticket' => ['nullable', 'integer'], 'column_pages' => ['nullable', 'array', 'max:20'], 'column_pages.*' => ['integer', 'min:1', 'max:100000'],
        ]);
        $view = $filters['view'] ?? 'board';
        $search = trim($filters['search'] ?? '');
        $query = $record->tickets()->with(['assignee:id,name', 'status:id,name,category']);
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('number', preg_replace('/^'.preg_quote($record->key, '/').'-/i', '', $search)));
        }
        foreach (['priority', 'type', 'status_id'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }
        if (isset($filters['assignee_id'])) {
            $filters['assignee_id'] == 0 ? $query->whereNull('assignee_id') : $query->where('assignee_id', $filters['assignee_id']);
        }
        $statuses = $record->statuses()->orderBy('position')->get();
        $columns = [];
        $tickets = null;
        if ($view === 'table') {
            $tickets = (clone $query)->orderBy($filters['sort'] ?? 'number', $filters['direction'] ?? 'asc')->orderBy('id')->paginate(30)->withQueryString()->through(fn ($t) => $this->ticketData($t, $record));
        } else {
            foreach ($statuses as $status) {
                $page = (int) ($filters['column_pages'][$status->id] ?? 1);
                $column = (clone $query)->where('status_id', $status->id);
                $total = $column->count();
                $page = min($page, max(1, (int) ceil($total / 30)));
                $columns[] = ['status_id' => $status->id, 'total' => $total, 'page' => $page, 'hasMore' => $page * 30 < $total, 'next_id' => (clone $column)->orderBy('sort_order')->orderBy('id')->skip($page * 30)->value('id'), 'tickets' => $column->orderBy('sort_order')->orderBy('id')->skip(($page - 1) * 30)->take(30)->get()->map(fn ($t) => $this->ticketData($t, $record))];
            }
        }
        $selected = null;
        if (isset($filters['ticket'])) {
            $ticket = $record->tickets()->with(['assignee:id,name', 'reporter:id,name', 'status:id,name,category'])->findOrFail($filters['ticket']);
            $selected = [...$this->ticketData($ticket, $record), 'reporter' => $ticket->reporter->only('id', 'name'), 'created_at' => $ticket->created_at->toISOString()];
            $selected['activity'] = ActivityLog::where('workspace_id', $workspace->id)->where('subject_type', $ticket->getMorphClass())->where('subject_id', $ticket->id)->leftJoin('users', 'users.id', '=', 'activity_logs.actor_id')->orderByDesc('activity_logs.id')->limit(30)->get(['activity_logs.id', 'action', 'changes', 'activity_logs.created_at', 'users.name as actor']);
        }
        $assignees = $service->eligibleAssignees($record)
            ->when(! empty($filters['assignee_search']), fn ($q) => $q->where('name', 'like', '%'.$filters['assignee_search'].'%'))
            ->orderBy('name')->orderBy('id')->limit(50)->get(['id', 'name'])->map(fn ($u) => ['value' => $u->id, 'label' => $u->name]);

        return Inertia::render('tickets/index', [
            'workspace' => $workspace->only('id', 'name'), 'project' => $record->only('id', 'name', 'key', 'status', 'board_version', 'workflow_version'),
            'statuses' => $statuses->map->only(['id', 'code', 'name', 'category', 'position', 'is_initial']),
            'transitions' => $record->transitions()->get(['from_status_id', 'to_status_id']),
            'columns' => $columns, 'tickets' => $tickets, 'selectedTicket' => $selected,
            'filters' => [...$filters, 'page' => (int) ($filters['page'] ?? 1), 'search' => $search, 'view' => $view],
            'can' => ['view' => true, 'create' => Gate::allows('create', [Ticket::class, $record]), 'edit' => Gate::allows('editAny', [Ticket::class, $record]), 'delete' => Gate::allows('deleteAny', [Ticket::class, $record]), 'setup' => Gate::allows('setup', [Ticket::class, $record]), 'project' => Gate::allows('view', $record)],
            'workTypes' => $this->options('types'), 'priorities' => $this->options('priorities'), 'assignees' => $assignees,
            'fieldLabels' => config('tickets.fieldLabels'), 'defaults' => config('tickets.defaults'), 'validationRules' => config('tickets.validationRules'),
            'fieldStates' => collect(config('tickets.fieldLabels'))->mapWithKeys(fn ($label, $field) => [$field => ['required' => in_array($field, ['title', 'type', 'priority'], true), 'readOnly' => $record->status !== 'active']]),
        ]);
    }

    private function options(string $key)
    {
        return collect(config('tickets.'.$key))->map(fn ($label, $value) => compact('value', 'label'))->values();
    }

    private function ticketData(Ticket $ticket, Project $project): array
    {
        return [...$ticket->only('id', 'number', 'title', 'description', 'type', 'priority', 'status_id', 'sort_order', 'version', 'assignee_id'), 'due_date' => $ticket->due_date?->format('Y-m-d'), 'key' => $project->key.'-'.$ticket->number, 'assignee' => $ticket->assignee?->only('id', 'name'), 'status' => $ticket->status->only('id', 'name', 'category')];
    }

    public function store(TicketRequest $request, Workspace $workspace, string $project, TicketService $service): RedirectResponse
    {
        $service->create($request->user(), $workspace, $project, $request->validated());

        return $this->destination($workspace, $project)->with('status', 'Ticket created.');
    }

    public function update(TicketRequest $request, Workspace $workspace, string $project, string $ticket, TicketService $service): RedirectResponse
    {
        $service->update($request->user(), $workspace, $project, $ticket, $request->validated());

        return $this->destination($workspace, $project)->with('status', 'Ticket updated.');
    }

    public function destroy(Request $request, Workspace $workspace, string $project, string $ticket, TicketService $service): RedirectResponse
    {
        $record = $workspace->projects()->findOrFail($project);
        Gate::authorize('delete', $record->tickets()->findOrFail($ticket));
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $service->delete($request->user(), $workspace, $project, $ticket, (int) $data['version']);
        // Remove a deleted ticket from the drawer URL while preserving its current view and filters.
        $query = parse_url(url()->previous(), PHP_URL_QUERY);
        parse_str($query ?? '', $values);
        unset($values['ticket']);

        return redirect()->route('tickets.index', [$workspace, $project, ...$values])->with('status', 'Ticket deleted.');
    }

    public function move(Request $request, Workspace $workspace, string $project, string $ticket, TicketService $service): RedirectResponse
    {
        $record = $workspace->projects()->findOrFail($project);
        Gate::authorize('update', $record->tickets()->findOrFail($ticket));
        $data = $request->validate(['status_id' => ['required', 'integer'], 'expected_status_id' => ['required', 'integer'], 'version' => ['required', 'integer', 'min:1'], 'board_version' => ['required', 'integer', 'min:0'], 'before_id' => ['nullable', 'integer']]);
        $service->move($request->user(), $workspace, $project, $ticket, $data);

        return $this->destination($workspace, $project)->with('status', 'Ticket moved.');
    }

    private function destination(Workspace $workspace, string $project): RedirectResponse
    {
        $record = $workspace->projects()->findOrFail($project);
        if (! Gate::allows('viewAny',[Ticket::class, $record])) {
            return redirect()->route('workspaces.show',$workspace);
        }
        $path = parse_url(url()->previous(),PHP_URL_PATH);

        return $path === route('tickets.index',[$workspace, $record],false) ? back() : redirect()->route('tickets.index',[$workspace, $record]);
    }
}
