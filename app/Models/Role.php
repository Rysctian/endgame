<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'permissions', 'is_system'];

    protected function casts(): array
    {
        return ['permissions' => 'array', 'is_system' => 'boolean'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assignableBy(User $user, ?array $grants = null): bool
    {
        if ($user->is_system_admin) {
            return true;
        }
        if ($this->is_system && $this->code === 'manager' && ! $user->isWorkspaceManager($this->workspace)) {
            return false;
        }
        if ($grants !== null) {
            return array_diff($this->permissions, $grants) === [];
        }
        foreach ($this->permissions as $key) {
            if (! $user->hasWorkspacePermission($this->workspace, $key)) {
                return false;
            }
        }

        return true;
    }
}
