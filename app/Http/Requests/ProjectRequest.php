<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');
        if ($this->isMethod('post')) {
            Gate::authorize('create', [Project::class, $workspace]);
        } else {
            $project = $workspace->projects()->findOrFail($this->route('project'));
            Gate::authorize('update', $project);
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['name', 'description'] as $field) {
            if (is_string($this->input($field))) {
                $values[$field] = trim($this->input($field));
            }
        }
        if ($this->isMethod('post') && is_string($this->input('key'))) {
            $values['key'] = strtoupper(trim($this->input('key')));
        }
        $this->merge($values);
    }

    public function rules(): array
    {
        $rules = config('projects.validationRules');

        return [
            'name' => ['bail', 'required', 'string', 'max:'.$rules['name']['maxLength']],
            'key' => $this->isMethod('post')
                ? ['bail', 'required', 'string', 'min:'.$rules['key']['minLength'], 'max:'.$rules['key']['maxLength'], 'regex:/^'.$rules['key']['pattern'].'$/', Rule::unique('projects')->where('workspace_id', $this->route('workspace')->id)]
                : ['missing'],
            'description' => ['nullable', 'string', 'max:'.$rules['description']['maxLength']],
            'workspace_id' => ['missing'],
            'created_by' => ['missing'],
            'project_id' => ['missing'],
            'status' => ['missing'],
            'permissions' => ['missing'],
            'is_system_admin' => ['missing'],
        ];
    }
}
