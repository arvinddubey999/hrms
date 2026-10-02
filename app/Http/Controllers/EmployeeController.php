<?php

namespace App\Http\Controllers;

use App\Models\AttendancePunch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index(Request $request, AttendanceService $attendance)
    {
        $date = Carbon::parse($request->get('date', now('Asia/Kolkata')->toDateString()));
        $categoryId = $request->get('category');
        $departmentId = $request->get('department_id');
        $companyId = $request->get('company_id');
        $status = $request->get('status', 'active');
        $q = $request->get('q');
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = strtolower($request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = User::query()
            ->with(['category', 'shift', 'company', 'department'])
            ->where('status', $status);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($q) {
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('employee_code', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('designation', 'like', "%{$q}%")
                    ->orWhere('department', 'like', "%{$q}%");
            });
        }

        // Sorting
        if ($sortBy === 'designation') {
            $query->orderBy('designation', $sortDir);
        } elseif ($sortBy === 'department') {
            $query->orderBy('department', $sortDir);
        } elseif ($sortBy === 'category') {
            $query->orderBy('category_id', $sortDir);
        } else {
            $query->orderBy('first_name', $sortDir)->orderBy('last_name', $sortDir);
        }

        $employees = $query->get();

        $stats = [
            'present' => 0, 'absent' => 0, 'not_marked' => 0, 'late' => 0, 'leave' => 0, 'early' => 0,
            'total' => $employees->count(),
            'admin' => $employees->where('role', 'admin')->count(),
            'manager' => $employees->where('role', 'manager')->count(),
            'employee' => $employees->where('role', 'employee')->count(),
            'archived' => User::query()->where('status', 'archived')->count(),
            'pending_tasks' => \App\Models\Task::query()->where('status', 'pending')->count(),
        ];

        $rows = $employees->map(function (User $user) use ($attendance, $date, &$stats) {
            $dayStatus = $attendance->dayStatus($user, $date);
            if (isset($stats[$dayStatus])) {
                $stats[$dayStatus]++;
            }
            $punches = AttendancePunch::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $date->toDateString())
                ->orderBy('punched_at')
                ->get();

            return [
                'user' => $user,
                'status' => $dayStatus,
                'punches' => $punches,
            ];
        });

        return view('employees.index', [
            'rows' => $rows,
            'stats' => $stats,
            'date' => $date,
            'categories' => Category::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'shifts' => Shift::orderBy('name')->get(),
            'setting' => Setting::current(),
            'q' => $q,
            'categoryId' => $categoryId,
            'departmentId' => $departmentId,
            'companyId' => $companyId,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'status' => $status,
        ]);
    }

    public function create()
    {
        return view('employees.form', [
            'staff' => new User([
                'status' => 'active',
                'mobile_attendance' => true,
                'ai_selfie' => true,
                'punch_from' => 'geofence',
                'role' => 'employee',
                'pay_type' => 'monthly',
                'employee_code' => User::generateNextEmployeeCode(),
            ]),
            'categories' => Category::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'shifts' => Shift::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $staff = new User;
        $this->persist($request, $staff);
        return redirect()->route('employees.show', $staff)->with('ok', 'Staff member created successfully.');
    }

    public function show(User $employee, Request $request, AttendanceService $attendance)
    {
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $summary = $attendance->monthSummary($employee, $year, $month);
        $employee->load(['category', 'company', 'department', 'shift', 'advances', 'incentives', 'expenses', 'faceImages', 'documents']);

        return view('employees.show', [
            'staff' => $employee,
            'summary' => $summary,
            'year' => $year,
            'month' => $month,
            'tab' => $request->get('tab', 'attendance'),
        ]);
    }

    public function edit(User $employee)
    {
        return view('employees.form', [
            'staff' => $employee,
            'categories' => Category::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'shifts' => Shift::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $employee)
    {
        $this->persist($request, $employee);
        return redirect()->route('employees.show', $employee)->with('ok', 'Profile updated successfully.');
    }

    public function destroy(User $employee)
    {
        $employee->update(['status' => 'archived']);
        return redirect()->route('attendances.index')->with('ok', 'Employee archived successfully.');
    }

    public function restore(User $employee)
    {
        $employee->update(['status' => 'active']);
        return redirect()->route('attendances.index')->with('ok', 'Employee restored to active state.');
    }

    public function mark(Request $request, User $employee, AttendanceService $attendance)
    {
        $data = $request->validate([
            'type' => 'required|in:in,out',
            'time' => 'nullable',
            'remarks' => 'nullable|string',
        ]);
        $punch = $attendance->punch(
            $employee,
            $data['type'],
            (float) Setting::current()->office_lat,
            (float) Setting::current()->office_lng,
            Setting::current()->company_name.' office',
            null,
            true,
            'admin',
            $request->user(),
            true,
            $data['remarks'] ?? null
        );
        if (! empty($data['time'])) {
            $punch->update(['punched_at' => Carbon::parse($punch->work_date->toDateString().' '.$data['time'])]);
        }

        return back()->with('ok', 'Attendance marked.');
    }

    public function bulkMark(Request $request, AttendanceService $attendance)
    {
        $data = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:users,id',
            'type' => 'required|in:in,out',
            'time' => 'nullable',
            'remarks' => 'nullable|string',
            'date' => 'nullable|date',
        ]);

        $workDate = $data['date'] ?? now('Asia/Kolkata')->toDateString();

        foreach ($data['employee_ids'] as $id) {
            $user = User::find($id);
            if ($user) {
                $punch = $attendance->punch(
                    $user,
                    $data['type'],
                    (float) Setting::current()->office_lat,
                    (float) Setting::current()->office_lng,
                    'Admin Manual Bulk Mark',
                    null,
                    true,
                    'admin',
                    $request->user(),
                    true,
                    $data['remarks'] ?? null
                );
                if (!empty($data['time'])) {
                    $punch->update(['punched_at' => Carbon::parse($workDate.' '.$data['time'])]);
                }
            }
        }

        return back()->with('ok', 'Bulk attendance marked for selected employees.');
    }

    public function bulkShift(Request $request)
    {
        $data = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:users,id',
            'shift_id' => 'required|exists:shifts,id',
        ]);

        User::whereIn('id', $data['employee_ids'])->update(['shift_id' => $data['shift_id']]);

        return back()->with('ok', 'Shift assigned to selected employees.');
    }

    public function statModal(Request $request, AttendanceService $attendance)
    {
        $date = Carbon::parse($request->get('date', now('Asia/Kolkata')->toDateString()));
        $type = strtolower($request->get('type', 'present'));

        $query = User::query();
        if ($type === 'archived') {
            $query->where('status', 'archived');
        } else {
            $query->where('status', 'active');
        }

        if (in_array($type, ['admin', 'manager', 'employee'])) {
            $query->where('role', $type);
        }

        $employees = $query->orderBy('first_name')->get();
        $list = [];

        foreach ($employees as $emp) {
            $st = $attendance->dayStatus($emp, $date);
            $ins = AttendancePunch::query()->where('user_id', $emp->id)->whereDate('work_date', $date)->where('type', 'in')->orderBy('punched_at')->first();
            $outs = AttendancePunch::query()->where('user_id', $emp->id)->whereDate('work_date', $date)->where('type', 'out')->orderByDesc('punched_at')->first();

            $match = false;
            if (in_array($type, ['total', 'admin', 'manager', 'employee', 'archived'])) {
                $match = true;
            } elseif ($type === 'present' && in_array($st, ['present', 'late', 'wop', 'half_day'])) {
                $match = true;
            } elseif ($type === 'late' && $st === 'late') {
                $match = true;
            } elseif ($type === 'not_marked' && $st === 'not_marked') {
                $match = true;
            } elseif ($type === 'absent' && $st === 'absent') {
                $match = true;
            } elseif ($type === 'leave' && $st === 'leave') {
                $match = true;
            } elseif ($type === 'early' && $st === 'early') {
                $match = true;
            }

            if ($match) {
                $list[] = [
                    'id' => $emp->id,
                    'user_name' => $emp->displayName(),
                    'status' => $st,
                    'in_time' => $ins ? $ins->punched_at->format('g:i A') : null,
                    'out_time' => $outs ? $outs->punched_at->format('g:i A') : null,
                ];
            }
        }

        $typeTitle = match($type) {
            'total', 'employee' => 'Employee Statistics',
            'admin' => 'Admin Statistics',
            'manager' => 'Manager Statistics',
            'archived' => 'Archived Employee Statistics',
            'present' => 'Present Statistics',
            'absent' => 'Absent Statistics',
            'not_marked' => 'Not Marked Statistics',
            'late' => 'Late Statistics',
            'leave' => 'Leave Statistics',
            'early' => 'Early Statistics',
            default => ucfirst($type) . ' Statistics',
        };

        $dateFormatted = $date->isToday() ? 'Today' : $date->format('j M Y');

        return response()->json([
            'title' => "{$typeTitle} for {$dateFormatted}",
            'employees' => $list,
        ]);
    }

    public function dayPunches(User $employee, Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $punches = AttendancePunch::query()
            ->where('user_id', $employee->id)
            ->whereDate('work_date', $date)
            ->orderBy('punched_at')
            ->get();

        return view('employees.day-modal', ['staff' => $employee, 'date' => $date, 'punches' => $punches]);
    }

    public function monthlyPrint(User $employee, Request $request, AttendanceService $attendance)
    {
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        return view('employees.monthly-print', [
            'staff' => $employee,
            'summary' => $attendance->monthSummary($employee, $year, $month),
            'year' => $year,
            'month' => $month,
        ]);
    }

    private function persist(Request $request, User $staff): void
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:80',
            'last_name' => 'nullable|string|max:80',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'password' => $staff->exists ? 'nullable|string|min:6' : 'required|string|min:6',
            'status' => 'required|in:active,archived',
            'role' => 'nullable|in:admin,manager,employee',
            'country' => 'nullable|string',
            'address' => 'nullable|string',
            'birthday' => 'nullable|date',
            'blood_group' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'marital_status' => 'nullable|string',
            'pan' => 'nullable|string',
            'aadhaar' => 'nullable|string',
            'pf_number' => 'nullable|string',
            'uan' => 'nullable|string',
            'esi_applicable' => 'nullable|boolean',
            'employee_type' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'company_id' => 'nullable|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'designation' => 'nullable|string',
            'department' => 'nullable|string',
            'employee_code' => 'nullable|string',
            'gender' => 'nullable|string',
            'date_of_joining' => 'nullable|date',
            'bank_account' => 'nullable|string',
            'ifsc' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'branch_name' => 'nullable|string',
            'bank_holder' => 'nullable|string',
            'mobile_attendance' => 'nullable|boolean',
            'multiple_attendance' => 'nullable|boolean',
            'shiftwise_attendance' => 'nullable|boolean',
            'self_odometer' => 'nullable|boolean',
            'live_tracking' => 'nullable|boolean',
            'ai_selfie' => 'nullable|boolean',
            'punch_from' => 'nullable|string',
            'shift_id' => 'nullable|exists:shifts,id',
            'casual_leaves' => 'nullable|integer',
            'sick_leaves' => 'nullable|integer',
            'privilege_leaves' => 'nullable|integer',
            'emergency_leaves' => 'nullable|integer',
            'pay_type' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'week_off_day' => 'nullable|string',
            'overtime_applicable' => 'nullable|boolean',
            'view_self_salary' => 'nullable|boolean',
        ]);

        if (empty($data['employee_code'])) {
            $data['employee_code'] = User::generateNextEmployeeCode();
        }

        // If department_id selected, set department text name
        if (!empty($data['department_id'])) {
            $deptObj = Department::find($data['department_id']);
            if ($deptObj) {
                $data['department'] = $deptObj->name;
            }
        }

        foreach (['esi_applicable', 'mobile_attendance', 'multiple_attendance', 'shiftwise_attendance', 'self_odometer', 'live_tracking', 'ai_selfie', 'overtime_applicable', 'view_self_salary'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $data['name'] = trim($data['first_name'].' '.($data['last_name'] ?? ''));
        $data['role'] = $data['role'] ?? 'employee';

        if (empty($data['password']) || $data['password'] === '********' || trim($data['password']) === '') {
            unset($data['password']);
        } else {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('profiles', 'public');
        }

        if ($request->hasFile('pan_document')) {
            $data['pan_document'] = $request->file('pan_document')->store('documents', 'public');
        }
        if ($request->hasFile('aadhaar_document')) {
            $data['aadhaar_document'] = $request->file('aadhaar_document')->store('documents', 'public');
        }

        $staff->fill($data)->save();

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $staff->documents()->create([
                    'path' => $file->store('documents', 'public'),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }
        if ($request->hasFile('face_images')) {
            foreach ($request->file('face_images') as $file) {
                $staff->faceImages()->create([
                    'path' => $file->store('faces', 'public'),
                ]);
            }
        }
    }
}
