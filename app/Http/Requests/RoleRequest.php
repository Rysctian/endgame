<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['name', 'code'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $this->merge([$field => $field === 'code' ? Str::lower(trim($value)) : trim($value)]);
            }
        }
    }

    public function authorize(): bool
    {
        $workspace = $this->route('workspace');
        if ($this->isMethod('post')) {
            return $this->user()->hasWorkspacePermission($workspace, 'roles.create');
        }

        return $this->user()->hasWorkspacePermission($workspace, 'roles.edit') || $this->user()->hasWorkspacePermission($workspace, 'roles.setup');
    }

    public function rules(): array
    {
        $workspace = $this->route('workspace');
        $role = $this->route('role');

        return [
            'name' => ['bail', 'required', 'string', 'max:100', Rule::unique('roles')->where('workspace_id', $workspace->id)->ignore($role), function (string $attribute, mixed $value, Closure $fail) {
                if (preg_replace('/[\s_-]+/u', '', Str::lower($value)) === 'systemadmin') {
                    $fail('System Admin is a reserved identity.');
                }
            }],
            'code' => ['bail', 'required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/', Rule::notIn(['system-admin']), Rule::unique('roles')->where('workspace_id', $workspace->id)->ignore($role)],
            'permissions' => ['present', 'array', 'list'],
            'permissions.*' => ['bail', 'string', 'distinct', Rule::in(array_keys(config('access.permissions')))],
            'workspace_id' => ['missing'], 'is_system' => ['missing'], 'is_system_admin' => ['missing'],
        ];
    }
}
