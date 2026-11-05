<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TaskCategory extends Model
{
    protected $guarded = [];

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = !empty($value) ? Str::title(mb_strtolower(trim($value))) : $value;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'category_id');
    }
}
