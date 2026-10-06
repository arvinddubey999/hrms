<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function others(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TaskReply::class)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->orderBy('created_at');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TaskHistory::class)->orderBy('created_at');
    }

    public function logHistory(string $action, ?string $details = null, User|int|null $user = null): TaskHistory
    {
        $userId = $user instanceof User ? $user->id : ($user ?? auth()->id());
        return $this->histories()->create([
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
        ]);
    }
}
