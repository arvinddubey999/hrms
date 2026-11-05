<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskHistory extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_created_at',
    ];

    public function getFormattedCreatedAtAttribute(): string
    {
        if (!$this->created_at) {
            return '';
        }
        return Carbon::parse($this->created_at)->timezone('Asia/Kolkata')->format('d M Y h:i A');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
