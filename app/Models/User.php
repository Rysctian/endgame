<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $attributes = ['status' => 'active', 'is_system_admin' => false];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_system_admin' => 'boolean'];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function isWorkspaceManager(Workspace $workspace): bool
    {
        return $this->memberships()->where('workspace_id', $workspace->id)
            ->whereHas('role', fn ($roles) => $roles->where('is_system', true)->where('code', 'manager'))->exists();
    }

    public function hasWorkspacePermission(Workspace $workspace, string $permission): bool
    {
        if (! array_key_exists($permission, config('access.permissions')) || $this->status !== 'active' || ! $this->hasVerifiedEmail()) {
            return false;
        }
        if ($this->is_system_admin) {
            return true;
        }
        $member = $this->memberships()->where('workspace_id', $workspace->id)->with('role')->first();

        return $member && in_array($permission, $member->role->permissions, true);
    }
}
