<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request, Workspace $workspace): Response
    {
        Gate::authorize('view', $workspace);
        $canView = Gate::allows('viewAny', [Project::class, $workspace]);
        $canCreate = Gate::allows('create', [Project::class, $workspace]);
        abort_unless($canView || $canCreate, 403);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(config('projects.statuses')))],
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? '';
        $projects = Project::visibleTo($request->user(), $workspace)
            ->when(! $canView, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('key', 'like', '%'.$search.'%'));
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')->orderBy('id')
            ->paginate(20, ['id', 'name', 'key', 'description', 'status'])->withQueryString();

        return Inertia::render('projects/index', [
            'workspace' => $workspace->only('id', 'name'),
            'projects' => $projects,
            'filters' => ['search' => $search, 'status' => $status],
            'statusOptions' => collect(config('projects.statuses'))->map(fn ($label, $value) => compact('value', 'label'))->values(),
            'can' => ['create' => $canCreate, 'view' => $canView],
            ...$this->formData(),
        ]);
    }

    public function store(ProjectRequest $request, Workspace $workspace): RedirectResponse
    {
        $project = DB::transaction(function () use ($request, $workspace) {
            Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('create', [Project::class, $workspace]);
            if ($workspace->projects()->where('key', $request->validated('key'))->exists()) {
                throw ValidationException::withMessages(['key' => 'The project key has already been taken.']);
            }
            $project = $workspace->projects()->create([
                ...$request->safe()->only(['name', 'key', 'description']),
                'created_by' => $request->user()->id,
                'status' => 'active',
            ]);
            app(ProjectWorkflowService::class)->initialize($project);
            ActivityLog::record($request->user(), 'project.created', $project, $workspace->id, $project->only('name', 'key', 'description', 'status'));
            if ($workspace->members()->where('user_id', $request->user()->id)->exists()) {
                $project->members()->create(['user_id' => $request->user()->id]);
                ActivityLog::record($request->user(), 'project.member_added', $project, $workspace->id, ['user_id' => $request->user()->id]);
            }

            return $project;
        });

        return $this->destination($workspace, $project)->with('status', 'Project created.');
    }

    public function show(Request $request, Workspace $workspace, string $project): Response
    {
        Gate::authorize('view', $workspace);
        $record = $workspace->projects()->findOrFail($project);
        Gate::authorize('view', $record);
        $filters = $request->validate(['member_search' => ['nullable', 'string', 'max:255']]);
        $canManage = Gate::allows('manageMembers', $record);
        $search = trim($filters['member_search'] ?? '');
        $options = [];
        if ($canManage) {
            $options = User::query()->where('status', 'active')
                ->whereHas('memberships', fn ($q) => $q->where('workspace_id', $workspace->id))
                ->whereNotIn('id', $record->members()->select('user_id'))
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
                ->orderBy('name')->orderBy('id')->limit(50)->get(['id', 'name', 'email'])
                ->map(fn ($user) => ['value' => $user->id, 'label' => $user->name.' ('.$user->email.')'])->all();
        }

        return Inertia::render('projects/show', [
            'workspace' => $workspace->only('id', 'name'),
            'project' => $record->only('id', 'name', 'key', 'description', 'status', 'created_at'),
            'statusLabel' => config('projects.statuses.'.$record->status),
            'members' => $record->members()->with('user:id,name,email,status')->orderBy('id')
                ->paginate(20, ['id', 'project_id', 'user_id'])->withQueryString()
                ->through(fn ($member) => ['id' => $member->id, 'user' => $member->user->only('id', 'name', 'email', 'status')]),
            'memberOptions' => $options,
            'memberSearch' => $search,
            'can' => [
                'tickets' => Gate::allows('viewAny', [Ticket::class, $record]),
                'workflow' => Gate::allows('setup', [Ticket::class, $record]),
                'edit' => Gate::allows('update', $record),
                'archive' => Gate::allows('archive', $record),
                'restore' => Gate::allows('restore', $record),
                'manageMembers' => $canManage,
            ],
            ...$this->formData($record),
        ]);
    }

    public function update(ProjectRequest $request, Workspace $workspace, string $project): RedirectResponse
    {
        $record = DB::transaction(function () use ($request, $workspace, $project) {
            $record = $this->lockedProject($workspace, $project);
            Gate::authorize('update', $record);
            $before = $record->only('name', 'description');
            $record->fill($request->safe()->only(['name', 'description']));
            if ($record->isDirty()) {
                $record->save();
                ActivityLog::record($request->user(), 'project.updated', $record, $workspace->id, ['before' => $before, 'after' => $record->only('name', 'description')]);
            }

            return $record;
        });

        return $this->destination($workspace, $record)->with('status', 'Project updated.');
    }

    public function archive(Request $request, Workspace $workspace, string $project): RedirectResponse
    {
        return $this->changeStatus($request, $workspace, $project, 'archive', 'archived');
    }

    public function restore(Request $request, Workspace $workspace, string $project): RedirectResponse
    {
        return $this->changeStatus($request, $workspace, $project, 'restore', 'active');
    }

    private function changeStatus(Request $request, Workspace $workspace, string $project, string $ability, string $status): RedirectResponse
    {
        $record = DB::transaction(function () use ($request, $workspace, $project, $ability, $status) {
            $record = $this->lockedProject($workspace, $project);
            Gate::authorize($ability, $record);
            $before = $record->status;
            $record->update(['status' => $status]);
            ActivityLog::record($request->user(), $status === 'active' ? 'project.restored' : 'project.archived', $record, $workspace->id, ['from' => $before, 'to' => $status]);

            return $record;
        });

        return $this->destination($workspace, $record)->with('status', $status === 'active' ? 'Project restored.' : 'Project archived.');
    }

    private function lockedProject(Workspace $workspace, string $project): Project
    {
        Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();

        return $workspace->projects()->lockForUpdate()->findOrFail($project);
    }

    private function destination(Workspace $workspace, Project $project): RedirectResponse
    {
        return Gate::allows('view', $project)
            ? redirect()->route('projects.show', [$workspace, $project])
            : redirect()->route('workspaces.show', $workspace);
    }

    private function formData(?Project $project = null): array
    {
        $rules = config('projects.validationRules');
        $readOnly = $project && $project->status === 'archived';

        return [
            'fieldLabels' => config('projects.fieldLabels'),
            'fieldStates' => [
                'name' => ['required' => $rules['name']['required'], 'readOnly' => (bool) $readOnly],
                'key' => ['required' => $rules['key']['required'], 'readOnly' => $project !== null],
                'description' => ['required' => $rules['description']['required'], 'readOnly' => (bool) $readOnly],
            ],
            'defaults' => $project
                ? [...config('projects.defaults'), ...$project->only('name', 'key', 'description', 'status')]
                : config('projects.defaults'),
            'validationRules' => $rules,
        ];
    }
}
