<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'key', 'description', 'created_by', 'status'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function scopeVisibleTo(Builder $query, User $user, Workspace $workspace): void
    {
        $query->where('workspace_id', $workspace->id);
        if (! $user->is_system_admin && ! $user->isWorkspaceManager($workspace)) {
            $query->whereHas('members', fn ($members) => $members->where('user_id', $user->id))
                ->whereHas('workspace.members', fn ($members) => $members->where('user_id', $user->id));
        }
    }
}
