<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityPhoto extends Model
{
    protected $table = 'activity_photos';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(LearningProject::class, 'project_id');
    }
}
