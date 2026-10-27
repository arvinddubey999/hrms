<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskReply extends Model
{
    protected $guarded = [];

    protected $casts = [
        'attachments' => 'array',
    ];

    protected $appends = ['attachments_list'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Return all attachments as an array of relative storage paths.
     */
    public function getAttachmentsListAttribute(): array
    {
        $list = [];
        if (!empty($this->attachments) && is_array($this->attachments)) {
            $list = $this->attachments;
        } elseif (!empty($this->attachment)) {
            $decoded = json_decode($this->attachment, true);
            if (is_array($decoded)) {
                $list = $decoded;
            } else {
                $list = [$this->attachment];
            }
        }
        return array_values(array_filter(array_unique($list)));
    }
}

