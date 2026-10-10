<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Department extends Model
{
    protected $fillable = ['name'];

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = !empty($value) ? Str::title(mb_strtolower(trim($value))) : $value;
    }

    public function employees()
    {
        return $this->hasMany(User::class);
    }
}
