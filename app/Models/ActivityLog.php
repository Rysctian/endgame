<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    public static function record(?User $actor, string $action, Model $subject, ?int $workspaceId = null, array $changes = []): self
    {
        return self::create(['actor_id' => $actor?->id, 'workspace_id' => $workspaceId, 'action' => $action, 'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(), 'changes' => $changes]);
    }
}
