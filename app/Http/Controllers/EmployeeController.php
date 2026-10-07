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
            'date' => 'nullable|date',
            'time' => 'nullable',
            'remarks' => 'nullable|string',
            'photo' => 'nullable|image|max:5120',
        ]);

        $targetDate = $data['date'] ?? now('Asia/Kolkata')->toDateString();
        $targetTime = !empty($data['time']) ? $data['time'] : now('Asia/Kolkata')->format('H:i:s');
        $customPunchedAt = Carbon::parse($targetDate.' '.$targetTime)->toDateTimeString();

        $punch = $attendance->punch(
            $employee,
            $data['type'],
            (float) Setting::current()->office_lat,
            (float) Setting::current()->office_lng,
            Setting::current()->company_name.' office',
            $request->file('photo'),
            true,
            'admin',
            $request->user(),
            true,
            $data['remarks'] ?? null,
            $targetDate,
            $customPunchedAt
        );

        return back()->with('ok', 'Attendance punch recorded successfully for ' . $targetDate);
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

    public function sampleCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employee_sample_import.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'First Name', 'Last Name', 'Phone', 'Email', 'Employee Code',
                'Company Name', 'Department', 'Designation', 'Category', 'Role',
                'Base Salary', 'Status', 'Gender', 'Date of Joining', 'Birthdate',
                'Blood Group', 'Address', 'Emergency Contact Name', 'Emergency Contact Phone',
                'PAN Card Number', 'Aadhaar Card Number', 'Bank Account Number', 'IFSC Code',
                'Bank Name', 'Branch Name', 'Bank A/C Holder Name', 'Pay Type',
                'Week Off Day', 'PF Number', 'UAN', 'ESI Applicable',
                'Overtime Applicable', 'View Self Salary', 'Mobile Attendance',
                'Multiple Attendance', 'Shiftwise Attendance', 'Live Tracking',
                'AI Selfie', 'Punch From'
            ]);
            fputcsv($file, [
                'Rajiv', 'Rakhecha', '9825100001', 'rajiv@rakhecha.com', '',
                'Tulsi Fabrics', 'Management', 'Managing Director', 'Management', 'employee',
                '50000.00', 'active', 'Male', '2025-01-01', '1990-05-15',
                'O+', 'Surat, Gujarat', 'Emergency Contact', '9825199999',
                'ABCDE1234F', '123456789012', '918010001234', 'HDFC0001234',
                'HDFC Bank', 'Main Branch', 'Rajiv Rakhecha', 'monthly',
                'Sunday', 'PF123456', 'UAN123456', '1',
                '1', '1', '1',
                '0', '0', '0',
                '1', 'geofence'
            ]);
            fputcsv($file, [
                'Aarav', 'Sharma', '9825100002', 'aarav@tulsi.com', '',
                'Tulsi Fabrics', 'Accounts', 'Senior Accountant', 'Staff', 'employee',
                '35000.00', 'active', 'Male', '2025-02-01', '1995-08-20',
                'B+', 'Ahmedabad, Gujarat', 'Sunita Sharma', '9825188888',
                'XYZPQ5678K', '987654321098', '918010005678', 'SBIN0005678',
                'SBI', 'CG Road Branch', 'Aarav Sharma', 'monthly',
                'Sunday', '', '', '0',
                '1', '1', '1',
                '0', '0', '0',
                '1', 'geofence'
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $file = $request->file('excel_file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'Unable to open file.');
        }

        $header = fgetcsv($handle);
        $importedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue;

            $firstName             = trim($row[0]);
            $lastName              = isset($row[1]) ? trim($row[1]) : '';
            $phone                 = isset($row[2]) ? trim($row[2]) : '';
            $email                 = isset($row[3]) ? trim($row[3]) : null;
            $empCodeInput          = isset($row[4]) ? trim($row[4]) : null;
            $companyName           = isset($row[5]) ? trim($row[5]) : null;
            $departmentName        = isset($row[6]) ? trim($row[6]) : null;
            $designation           = isset($row[7]) ? trim($row[7]) : null;
            $categoryName          = isset($row[8]) ? trim($row[8]) : null;
            $role                  = isset($row[9]) && in_array(strtolower(trim($row[9])), ['admin', 'manager', 'employee']) ? strtolower(trim($row[9])) : 'employee';
            $salary                = isset($row[10]) ? (float) $row[10] : 0.00;
            $status                = isset($row[11]) ? (strtolower(trim($row[11])) === 'archived' ? 'archived' : 'active') : 'active';
            $gender                = isset($row[12]) ? trim($row[12]) : null;
            $dateOfJoining         = isset($row[13]) && !empty(trim($row[13])) ? trim($row[13]) : null;
            $birthday              = isset($row[14]) && !empty(trim($row[14])) ? trim($row[14]) : null;
            $bloodGroup            = isset($row[15]) ? trim($row[15]) : null;
            $address               = isset($row[16]) ? trim($row[16]) : null;
            $emergencyContactName  = isset($row[17]) ? trim($row[17]) : null;
            $emergencyContactPhone = isset($row[18]) ? trim($row[18]) : null;
            $pan                   = isset($row[19]) ? trim($row[19]) : null;
            $aadhaar               = isset($row[20]) ? trim($row[20]) : null;
            $bankAccount           = isset($row[21]) ? trim($row[21]) : null;
            $ifsc                  = isset($row[22]) ? trim($row[22]) : null;
            $bankName              = isset($row[23]) ? trim($row[23]) : null;
            $branchName            = isset($row[24]) ? trim($row[24]) : null;
            $bankHolder            = isset($row[25]) ? trim($row[25]) : null;
            $payType               = isset($row[26]) ? trim($row[26]) : 'monthly';
            $weekOffDay            = isset($row[27]) ? trim($row[27]) : 'Sunday';
            $pfNumber              = isset($row[28]) ? trim($row[28]) : null;
            $uan                   = isset($row[29]) ? trim($row[29]) : null;
            $esiApplicable         = isset($row[30]) ? in_array(strtolower(trim($row[30])), ['1', 'true', 'yes']) : false;
            $overtimeApplicable    = isset($row[31]) ? in_array(strtolower(trim($row[31])), ['1', 'true', 'yes']) : false;
            $viewSelfSalary        = isset($row[32]) ? in_array(strtolower(trim($row[32])), ['1', 'true', 'yes']) : false;
            $mobileAttendance      = isset($row[33]) ? in_array(strtolower(trim($row[33])), ['1', 'true', 'yes']) : true;
            $multipleAttendance    = isset($row[34]) ? in_array(strtolower(trim($row[34])), ['1', 'true', 'yes']) : false;
            $shiftwiseAttendance   = isset($row[35]) ? in_array(strtolower(trim($row[35])), ['1', 'true', 'yes']) : false;
            $liveTracking          = isset($row[36]) ? in_array(strtolower(trim($row[36])), ['1', 'true', 'yes']) : false;
            $aiSelfie              = isset($row[37]) ? in_array(strtolower(trim($row[37])), ['1', 'true', 'yes']) : true;
            $punchFrom             = isset($row[38]) ? trim($row[38]) : 'geofence';

            if (empty($phone)) {
                $phone = '98000' . rand(10000, 99999);
            }

            $companyId = null;
            $companyObj = null;
            if ($companyName) {
                $companyObj = Company::firstOrCreate(['name' => $companyName]);
                $companyId = $companyObj->id;
            }

            $deptId = null;
            if ($departmentName) {
                $dept = Department::firstOrCreate(['name' => $departmentName]);
                $deptId = $dept->id;
            }

            $categoryId = null;
            if ($categoryName) {
                $cat = Category::firstOrCreate(['name' => $categoryName]);
                $categoryId = $cat->id;
            }

            if (empty($empCodeInput) || User::where('employee_code', $empCodeInput)->exists()) {
                $empCode = User::generateNextEmployeeCode($companyObj ?: $companyId);
            } else {
                $empCode = $empCodeInput;
            }

            User::create([
                'first_name'             => $firstName,
                'last_name'              => $lastName,
                'name'                   => trim($firstName . ' ' . $lastName),
                'phone'                  => $phone,
                'email'                  => $email,
                'employee_code'          => $empCode,
                'designation'            => $designation,
                'company_id'             => $companyId,
                'department_id'          => $deptId,
                'department'             => $departmentName,
                'category_id'            => $categoryId,
                'salary'                 => $salary,
                'status'                 => $status,
                'role'                   => $role,
                'gender'                 => $gender,
                'date_of_joining'        => $dateOfJoining,
                'birthday'               => $birthday,
                'blood_group'            => $bloodGroup,
                'address'                => $address,
                'emergency_contact_name' => $emergencyContactName,
                'emergency_contact_phone'=> $emergencyContactPhone,
                'pan'                    => $pan,
                'aadhaar'                => $aadhaar,
                'bank_account'           => $bankAccount,
                'ifsc'                   => $ifsc,
                'bank_name'              => $bankName,
                'branch_name'            => $branchName,
                'bank_holder'            => $bankHolder,
                'pay_type'               => $payType,
                'week_off_day'           => $weekOffDay,
                'pf_number'              => $pfNumber,
                'uan'                    => $uan,
                'esi_applicable'         => $esiApplicable,
                'overtime_applicable'    => $overtimeApplicable,
                'view_self_salary'       => $viewSelfSalary,
                'mobile_attendance'      => $mobileAttendance,
                'multiple_attendance'    => $multipleAttendance,
                'shiftwise_attendance'   => $shiftwiseAttendance,
                'live_tracking'          => $liveTracking,
                'ai_selfie'              => $aiSelfie,
                'punch_from'             => $punchFrom,
                'password'               => \Illuminate\Support\Facades\Hash::make('123456'),
            ]);

            $importedCount++;
        }

        fclose($handle);

        return back()->with('ok', "Successfully imported {$importedCount} employees.");
    }

    public function quickStoreDepartment(Request $request)
    {
        $data = $request->validate(['name' => 'required|string']);
        $dept = Department::firstOrCreate(['name' => $data['name']]);

        if ($request->wantsJson() || $request->ajax() || $request->isJson() || $request->acceptsJson()) {
            return response()->json(['success' => true, 'department' => $dept]);
        }

        return response()->json(['success' => true, 'department' => $dept]);
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
            'permissions' => 'nullable|array',
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

        $data['salary'] = $data['salary'] ?? 0.00;

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
