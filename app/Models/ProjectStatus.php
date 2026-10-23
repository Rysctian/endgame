<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStatus extends Model
{
    protected $fillable = ['code', 'name', 'category', 'position', 'is_initial'];

    protected function casts(): array
    {
        return ['is_initial' => 'boolean', 'position' => 'integer'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
