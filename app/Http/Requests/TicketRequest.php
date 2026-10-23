<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('workspace')->projects()->findOrFail($this->route('project'));
        if ($this->isMethod('post')) {
            Gate::authorize('create', [Ticket::class, $project]);
        } else {
            Gate::authorize('update', $project->tickets()->findOrFail($this->route('ticket')));
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['title', 'description'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', Rule::in(array_keys(config('tickets.types')))], 'priority' => ['required', Rule::in(array_keys(config('tickets.priorities')))],
            'assignee_id' => ['nullable', 'integer'], 'due_date' => ['nullable', 'date_format:Y-m-d'],
            'version' => $this->isMethod('post') ? ['missing'] : ['required', 'integer', 'min:1'],
            ...array_fill_keys(['project_id', 'workspace_id', 'number', 'reporter_id', 'status_id', 'sort_order', 'deleted_at', 'created_at', 'updated_at'], ['missing'])];
    }
}
