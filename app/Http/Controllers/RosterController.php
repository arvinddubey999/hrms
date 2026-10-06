<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RosterController extends Controller
{
    public function index(Request $request, AttendanceService $attendance)
    {
        $month = (int) $request->get('m', now()->month);
        $year = (int) $request->get('y', now()->year);
        $employeeId = $request->get('employee_id');
        $departmentId = $request->get('department_id');
        $companyId = $request->get('company_id');

        $query = User::query()->where('status', 'active');

        if ($employeeId) {
            $query->where('id', $employeeId);
        }
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $employees = $query->orderBy('first_name')->get();
        $days = Carbon::create($year, $month, 1)->daysInMonth;

        return view('roster.index', [
            'employees' => $employees,
            'month' => $month,
            'year' => $year,
            'days' => $days,
            'attendance' => $attendance,
            'employeeId' => $employeeId,
            'departmentId' => $departmentId,
            'companyId' => $companyId,
            'allEmployees' => User::where('status', 'active')->orderBy('first_name')->get(),
            'departments' => \App\Models\Department::orderBy('name')->get(),
            'companies' => \App\Models\Company::orderBy('name')->get(),
        ]);
    }

    public function exportExcel(Request $request, AttendanceService $attendance)
    {
        $month = (int) $request->get('m', now()->month);
        $year = (int) $request->get('y', now()->year);
        $employees = User::query()->where('status', 'active')->orderBy('first_name')->get();
        $days = Carbon::create($year, $month, 1)->daysInMonth;

        $filename = "monthly-roster-{$year}-{$month}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($employees, $year, $month, $days, $attendance) {
            $out = fopen('php://output', 'w');
            $headerRow = ['Employee Code', 'Employee Name', 'Department'];
            for ($d = 1; $d <= $days; $d++) {
                $headerRow[] = sprintf('%02d', $d);
            }
            fputcsv($out, $headerRow);

            foreach ($employees as $emp) {
                $row = [$emp->employee_code, $emp->displayName(), $emp->department ?: '-'];
                for ($d = 1; $d <= $days; $d++) {
                    $date = Carbon::create($year, $month, $d);
                    $st = $attendance->dayStatus($emp, $date);
                    $ins = \App\Models\AttendancePunch::where('user_id', $emp->id)->whereDate('work_date', $date)->where('type', 'in')->orderBy('punched_at')->first();
                    $outs = \App\Models\AttendancePunch::where('user_id', $emp->id)->whereDate('work_date', $date)->where('type', 'out')->orderByDesc('punched_at')->first();

                    $stCode = match($st) {
                        'present' => 'P',
                        'late' => 'P(L)',
                        'wop' => 'WOP',
                        'absent' => 'A',
                        'leave' => 'L',
                        'week_off' => 'WO',
                        'holiday' => 'H',
                        default => '-'
                    };

                    $times = '';
                    if ($ins) {
                        $times .= ' IN:' . $ins->punched_at->format('H:i');
                    }
                    if ($outs) {
                        $times .= ' OUT:' . $outs->punched_at->format('H:i');
                    }

                    $row[] = $stCode . ($times ? " ({$times})" : '');
                }
                fputcsv($out, $row);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request, AttendanceService $attendance)
    {
        $month = (int) $request->get('m', now()->month);
        $year = (int) $request->get('y', now()->year);
        $employees = User::query()->where('status', 'active')->orderBy('first_name')->get();
        $days = Carbon::create($year, $month, 1)->daysInMonth;

        return view('roster.pdf', compact('employees', 'month', 'year', 'days', 'attendance'));
    }
}
