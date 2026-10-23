<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStatusTransition extends Model
{
    public $timestamps = false;

    protected $fillable = ['from_status_id', 'to_status_id'];
}
