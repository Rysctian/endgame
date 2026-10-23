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

    public function assignableBy(User $user): bool
    {
        if ($user->is_system_admin) {
            return true;
        }
        foreach ($this->permissions as $key) {
            if (! $user->hasWorkspacePermission($this->workspace, $key)) {
                return false;
            }
        }

        return true;
    }
}
