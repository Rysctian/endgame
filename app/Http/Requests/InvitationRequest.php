<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvitationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    public function authorize(): bool
    {
        return $this->user()->hasWorkspacePermission($this->route('workspace'), 'members.create');
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:255'], 'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('workspace_id', $this->route('workspace')->id)]];
    }
}
