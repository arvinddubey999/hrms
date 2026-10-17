<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceImage extends Model
{
    protected $guarded = [];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        if (empty($this->path)) {
            return '';
        }
        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://')) {
            return $this->path;
        }
        return url('storage/' . $this->path);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
