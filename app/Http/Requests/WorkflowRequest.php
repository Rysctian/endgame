<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class WorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('setup', [Ticket::class, $this->route('workspace')->projects()->findOrFail($this->route('project'))]);

        return true;
    }

    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:0'], 'statuses' => ['required', 'array', 'min:1', 'max:20'],
            'statuses.*' => ['required', 'array:code,name,category,is_initial'],
            'statuses.*.code' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:40', 'distinct'],
            'statuses.*.name' => ['required', 'string', 'max:80'], 'statuses.*.category' => ['required', Rule::in(array_keys(config('tickets.categories')))],
            'statuses.*.is_initial' => ['required', 'boolean'], 'transitions' => ['present', 'array', 'max:380'],
            'transitions.*' => ['required', 'array:from,to'], 'transitions.*.from' => ['required', 'string'], 'transitions.*.to' => ['required', 'string']];
    }
}
