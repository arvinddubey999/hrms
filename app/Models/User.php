<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthday' => 'date',
            'date_of_joining' => 'date',
            'esi_applicable' => 'boolean',
            'mobile_attendance' => 'boolean',
            'multiple_attendance' => 'boolean',
            'shiftwise_attendance' => 'boolean',
            'self_odometer' => 'boolean',
            'live_tracking' => 'boolean',
            'ai_selfie' => 'boolean',
            'overtime_applicable' => 'boolean',
            'view_self_salary' => 'boolean',
            'salary' => 'decimal:2',
            'last_location_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function punches(): HasMany
    {
        return $this->hasMany(AttendancePunch::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function faceImages(): HasMany
    {
        return $this->hasMany(FaceImage::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StaffDocument::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(Advance::class);
    }

    public function incentives(): HasMany
    {
        return $this->hasMany(Incentive::class);
    }

    public function locationPings(): HasMany
    {
        return $this->hasMany(LocationPing::class);
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'manager'], true);
    }

    public function issueApiToken(): string
    {
        $token = Str::random(60);
        $this->forceFill(['api_token' => $token])->save();

        return $token;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public static function generateNextEmployeeCode(): string
    {
        $maxId = static::max('id') ?? 0;
        return sprintf('%05d', $maxId + 1);
    }

    public function displayName(): string
    {
        return trim($this->first_name.' '.$this->last_name) ?: $this->name;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', $this->displayName()) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= strtoupper(substr($part, 0, 1));
        }

        return $letters ?: 'ST';
    }

    public function dailyRate(): float
    {
        if ((float) $this->salary <= 0) {
            return 0;
        }
        if ($this->pay_type === 'daily') {
            return (float) $this->salary;
        }
        if ($this->pay_type === 'hourly') {
            return (float) $this->salary * 8;
        }

        return round(((float) $this->salary) / 30, 3);
    }
}
