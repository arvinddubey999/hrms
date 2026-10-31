<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Carbon\Carbon;
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
            'accessible_company_ids' => 'array',
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

    public function isSuperAdmin(): bool
    {
        $role = strtolower(trim($this->role ?? ''));
        if (in_array($role, ['super admin', 'superadmin', 'super_admin'], true) || in_array((int)$this->id, [1, 2], true)) {
            return true;
        }
        return false;
    }

    public function hasPermission(string $permissionKey): bool
    {
        // 1. Super Admin can see EVERYTHING unconditionally
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 2. If employee has explicit custom permissions set on their profile, it is the STRICT authority!
        if (is_array($this->permissions)) {
            return in_array($permissionKey, $this->permissions, true);
        }

        // 3. Admin / Other Roles: check the Role's assigned permissions (Admin has limited rights as per Role!)
        if (!empty($this->role)) {
            $roleObj = Role::where('name', $this->role)
                ->orWhereRaw('LOWER(name) = ?', [strtolower($this->role)])
                ->first();
            if ($roleObj && is_array($roleObj->permissions)) {
                return in_array($permissionKey, $roleObj->permissions, true);
            }
        }

        return false;
    }

    public function allPermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return [
                'dashboard.view', 'dashboard.reports', 'dashboard.download',
                'employee.view', 'employee.add', 'employee.edit', 'employee.delete',
                'attendance.view', 'attendance.mark', 'attendance.bulk', 'attendance.edit',
                'leave.view', 'leave.apply', 'leave.approve',
                'payroll.view', 'payroll.process', 'payroll.advances', 'payroll.expenses',
                'tasks.view', 'tasks.create', 'tasks.manage',
                'geofence.view', 'geofence.tracking',
                'settings.view', 'settings.roles', 'settings.manage'
            ];
        }

        if (is_array($this->permissions)) {
            return array_values(array_unique($this->permissions));
        }

        $roleObj = !empty($this->role) ? Role::where('name', $this->role)->orWhereRaw('LOWER(name) = ?', [strtolower($this->role)])->first() : null;
        return ($roleObj && is_array($roleObj->permissions)) ? array_values(array_unique($roleObj->permissions)) : [];
    }


    public function defaultLandingRoute(): string
    {
        if ($this->hasPermission('attendance.view') || $this->hasPermission('employee.view')) {
            return 'attendances.index';
        }
        if ($this->hasPermission('leave.view') || $this->hasPermission('leave.apply') || $this->hasPermission('leave.approve')) {
            return 'requests.index';
        }
        if ($this->hasPermission('payroll.view') || $this->hasPermission('payroll.process')) {
            return 'payroll.index';
        }
        if ($this->hasPermission('tasks.view') || $this->hasPermission('tasks.create')) {
            return 'tasks.index';
        }
        if ($this->hasPermission('dashboard.reports') || $this->hasPermission('dashboard.view')) {
            return 'reports.index';
        }
        if ($this->hasPermission('settings.view') || $this->hasPermission('settings.roles')) {
            return 'settings.index';
        }

        return 'attendances.index';
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

    public function isAnywherePunchValid(?\Carbon\Carbon $date = null): bool
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
        $role = strtolower(trim($this->role ?? ''));
        return in_array($role, ['admin', 'super admin', 'superadmin', 'super_admin'], true) || in_array((int)$this->id, [1, 2], true);
    }

    public function isManager(): bool
    {
        return in_array(strtolower($this->role ?? ''), ['admin', 'super admin', 'manager'], true);
    }

    public function accessibleCompanyIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Company::pluck('id')->all();
        }

        if (!empty($this->accessible_company_ids) && is_array($this->accessible_company_ids)) {
            $ids = array_map('intval', $this->accessible_company_ids);
            if ($this->company_id && !in_array((int)$this->company_id, $ids, true)) {
                $ids[] = (int)$this->company_id;
            }
            return array_values(array_filter($ids));
        }

        return $this->company_id ? [(int)$this->company_id] : [];
    }

    public function scopedEmployeesQuery()
    {
        $query = static::query()->where('status', 'active');
        if ($this->isSuperAdmin()) {
            return $query;
        }

        $compIds = $this->accessibleCompanyIds();

        // 1. If Admin or "View Employees" (employee.view) is granted:
        // They can see employees of their assigned company / companies
        if ($this->isAdmin() || $this->hasPermission('employee.view')) {
            if (!empty($compIds)) {
                $query->whereIn('company_id', $compIds);
            }
            return $query;
        }


        // 2. Manager, Supervisor, HR without global employee.view: scoped to their assigned department and company
        if ($this->isManager() || in_array(strtolower($this->role ?? ''), ['manager', 'supervisor', 'hr'], true)) {
            if ($this->department_id) {
                $query->where('department_id', $this->department_id);
            }
            if (!empty($compIds)) {
                $query->whereIn('company_id', $compIds);
            }
            return $query;
        }

        // 3. Staff granted permission to take attendance for their team/department:
        if ($this->hasPermission('attendance.bulk') || $this->hasPermission('attendance.mark')) {
            if ($this->department_id) {
                if (!empty($compIds)) {
                    $query->whereIn('company_id', $compIds);
                }
                return $query->where('department_id', $this->department_id);
            }
            if (!empty($compIds)) {
                $query->whereIn('company_id', $compIds);
                return $query;
            }
        }

        // 4. Standard employee (View Employees unchecked): strictly their own self!
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

    public function avatarUrl(): string
    {
        if (!empty($this->profile_photo)) {
            return asset('storage/' . $this->profile_photo);
        }
        $face = $this->faceImages()->latest()->first();
        if ($face) {
            $path = $face->path ?? $face->image_path;
            if (!empty($path)) {
                return asset('storage/' . $path);
            }
        }
        return '';
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

        // Rule priority: Company Master salary_calculation_days -> Setting salary_day_basis -> default 30
        $basis = $this->company?->salary_calculation_days ?? Setting::current()->salary_day_basis ?? '30';

        $divider = 30;
        if ($basis === '26') {
            $divider = 26;
        } elseif ($basis === '31') {
            $divider = 31;
        } elseif ($basis === '30') {
            $divider = 30;
        } elseif ($basis === 'actual') {
            $divider = $daysInMonth ?: now('Asia/Kolkata')->daysInMonth;
        } else {
            $divider = $daysInMonth ?: now('Asia/Kolkata')->daysInMonth;
        }

        return round(((float) $this->salary) / $divider, 4);
    }
}
