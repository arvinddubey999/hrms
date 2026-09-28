<?php

namespace App\Http\Controllers;

use App\Models\AttendancePunch;
use App\Models\Category;
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
        $q = $request->get('q');

        $employees = User::query()
            ->with(['category', 'shift'])
            ->where('status', $request->get('status', 'active'))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('designation', 'like', "%{$q}%");
                });
            })
            ->orderBy('first_name')
            ->get();

        $stats = [
            'present' => 0, 'absent' => 0, 'not_marked' => 0, 'late' => 0, 'leave' => 0, 'early' => 0,
            'total' => $employees->count(),
            'admin' => $employees->where('role', 'admin')->count(),
            'manager' => $employees->where('role', 'manager')->count(),
            'employee' => $employees->where('role', 'employee')->count(),
            'archived' => User::query()->where('status', 'archived')->count(),
        ];

        $rows = $employees->map(function (User $user) use ($attendance, $date, &$stats) {
            $status = $attendance->dayStatus($user, $date);
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
            $punches = AttendancePunch::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $date->toDateString())
                ->orderBy('punched_at')
                ->get();

            return compact('user', 'status', 'punches');
        });

        return view('employees.index', [
            'rows' => $rows,
            'stats' => $stats,
            'date' => $date,
            'categories' => Category::orderBy('name')->get(),
            'setting' => Setting::current(),
            'q' => $q,
            'categoryId' => $categoryId,
        ]);
    }

    public function create()
    {
        return view('employees.form', [
            'staff' => new User(['status' => 'active', 'mobile_attendance' => true, 'ai_selfie' => true, 'punch_from' => 'geofence', 'role' => 'employee', 'pay_type' => 'monthly']),
            'categories' => Category::orderBy('name')->get(),
            'shifts' => Shift::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $staff = new User;
        $this->persist($request, $staff);
        return redirect()->route('employees.show', $staff)->with('ok', 'Staff member created.');
    }

    public function show(User $employee, Request $request, AttendanceService $attendance)
    {
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $summary = $attendance->monthSummary($employee, $year, $month);
        $employee->load(['category', 'shift', 'advances', 'incentives', 'faceImages', 'documents']);

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
            'shifts' => Shift::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $employee)
    {
        $this->persist($request, $employee);
        return redirect()->route('employees.show', $employee)->with('ok', 'Profile updated.');
    }

    public function destroy(User $employee)
    {
        $employee->update(['status' => 'archived']);
        return redirect()->route('attendances.index')->with('ok', 'Employee archived.');
    }

    public function mark(Request $request, User $employee, AttendanceService $attendance)
    {
        $data = $request->validate([
            'type' => 'required|in:in,out',
            'time' => 'nullable',
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
        );
        if (! empty($data['time'])) {
            $punch->update(['punched_at' => Carbon::parse($punch->work_date->toDateString().' '.$data['time'])]);
        }

        return back()->with('ok', 'Attendance marked.');
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

        foreach (['esi_applicable', 'mobile_attendance', 'multiple_attendance', 'shiftwise_attendance', 'self_odometer', 'live_tracking', 'ai_selfie', 'overtime_applicable', 'view_self_salary'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $data['name'] = trim($data['first_name'].' '.($data['last_name'] ?? ''));
        $data['role'] = $data['role'] ?? 'employee';
        if (empty($data['password'])) {
            unset($data['password']);
        }
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('profiles', 'public');
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
