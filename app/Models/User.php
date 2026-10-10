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
            'resignation_date' => 'date',
            'anywhere_from_date' => 'date',
            'anywhere_to_date' => 'date',
            'can_manage_tasks' => 'boolean',
            'esi_applicable' => 'boolean',
            'wop_applicable' => 'boolean',
            'hop_applicable' => 'boolean',
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
            'permissions' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }

    public function hasPermission(string $permissionKey): bool
    {
        if (in_array(strtolower($this->role ?? ''), ['admin', 'super admin'], true)) {
            return true;
        }

        $directPerms = is_array($this->permissions) ? $this->permissions : [];
        if (in_array($permissionKey, $directPerms, true)) {
            return true;
        }

        $roleObj = Role::where('name', $this->role)->first();
        if ($roleObj && is_array($roleObj->permissions) && in_array($permissionKey, $roleObj->permissions, true)) {
            return true;
        }

        return false;
    }

    public function allPermissions(): array
    {
        $directPerms = is_array($this->permissions) ? $this->permissions : [];
        $roleObj = Role::where('name', $this->role)->first();
        $rolePerms = ($roleObj && is_array($roleObj->permissions)) ? $roleObj->permissions : [];

        return array_values(array_unique(array_merge($rolePerms, $directPerms)));
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

    public function isAnywherePunchValid(?Carbon $date = null): bool
    {
        if ($this->punch_from !== 'anywhere') {
            return false;
        }

        if ($this->anywhere_from_date || $this->anywhere_to_date) {
            $target = $date ? $date->format('Y-m-d') : now('Asia/Kolkata')->format('Y-m-d');
            if ($this->anywhere_from_date && $target < $this->anywhere_from_date->format('Y-m-d')) {
                return false;
            }
            if ($this->anywhere_to_date && $target > $this->anywhere_to_date->format('Y-m-d')) {
                return false;
            }
        }

        return true;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'manager'], true);
    }

    public function scopedEmployeesQuery()
    {
        $query = static::query()->where('status', 'active');
        if ($this->isAdmin()) {
            return $query;
        }
        if ($this->role === 'manager') {
            return $query->where(function ($q) {
                if ($this->department_id) {
                    $q->orWhere('department_id', $this->department_id);
                }
                if ($this->company_id) {
                    $q->orWhere('company_id', $this->company_id);
                }
            });
        }
        return $query->where('id', $this->id);
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

    public static function generateNextEmployeeCode($companyOrPrefix = null): string
    {
        $prefix = 'RI';
        if ($companyOrPrefix instanceof Company) {
            $prefix = $companyOrPrefix->code_prefix ?: strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $companyOrPrefix->name ?? ''), 0, 3));
        } elseif (is_numeric($companyOrPrefix)) {
            $company = Company::find($companyOrPrefix);
            if ($company) {
                $prefix = $company->code_prefix ?: strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $company->name ?? ''), 0, 3));
            }
        } elseif (is_string($companyOrPrefix) && !empty($companyOrPrefix)) {
            $prefix = $companyOrPrefix;
        }

        if (empty($prefix)) {
            $prefix = 'RI';
        }

        $nextNum = (static::count() ?? 0) + 1;
        $code = sprintf('%s%04d', strtoupper($prefix), $nextNum);
        while (static::where('employee_code', $code)->exists()) {
            $nextNum++;
            $code = sprintf('%s%04d', strtoupper($prefix), $nextNum);
        }
        return $code;
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

    public function dailyRate(?int $daysInMonth = null): float
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

        $setting = Setting::current();
        $basis = $setting->salary_day_basis ?? 'month_days'; // 30, 31, 26, or month_days

        $divider = 30;
        if ($basis === '26') {
            $divider = 26;
        } elseif ($basis === '31') {
            $divider = 31;
        } elseif ($basis === '30') {
            $divider = 30;
        } else {
            $divider = $daysInMonth ?: now('Asia/Kolkata')->daysInMonth;
        }

        return round(((float) $this->salary) / $divider, 4);
    }
}
