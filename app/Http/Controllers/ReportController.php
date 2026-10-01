<?php

namespace App\Http\Controllers;

use App\Models\AttendancePunch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Expense;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Task;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\SimpleZipWriter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', [
            'companies' => Company::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'employees' => User::where('status', 'active')->orderBy('first_name')->get(),
            'setting' => Setting::current(),
        ]);
    }

    public function generate(Request $request, AttendanceService $attendance)
    {
        $type = $request->get('type', 'daywise');
        $rangeType = $request->get('range_type', 'month');
        $format = $request->get('format', 'excel');
        $companyName = Setting::current()->company_name ?: 'TULSI FABRICS INDIA PRIVATE LIMITED';

        // Parse Date Filter
        if ($rangeType === 'single') {
            $dateStr = $request->get('date', now()->toDateString());
            $from = Carbon::parse($dateStr)->startOfDay();
            $to = Carbon::parse($dateStr)->endOfDay();
        } elseif ($rangeType === 'month') {
            $month = (int)$request->get('month', now()->month);
            $year = (int)$request->get('year', now()->year);
            $from = Carbon::create($year, $month, 1)->startOfDay();
            $to = $from->copy()->endOfMonth()->endOfDay();
        } else {
            $from = Carbon::parse($request->get('from', now()->startOfMonth()))->startOfDay();
            $to = Carbon::parse($request->get('to', now()))->endOfDay();
        }

        // Employee & Category Filtering
        $query = User::query()->where('status', 'active')->with(['company', 'category', 'shift']);
        
        $catIds = $request->get('category_ids');
        if (!empty($catIds) && !in_array('all', (array)$catIds)) {
            $query->whereIn('category_id', (array)$catIds);
        }

        $empIds = $request->get('employee_ids');
        if (!empty($empIds) && !in_array('all', (array)$empIds)) {
            $query->whereIn('id', (array)$empIds);
        }

        if ($request->get('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }
        if ($request->get('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }

        $employees = $query->orderBy('first_name')->get();

        // 1. Monthly Summary Sheet Zip Download
        if ($type === 'monthly_zip') {
            $files = [];
            foreach ($employees as $emp) {
                $safeName = preg_replace('/[^A-Za-z0-9_]/', '_', $emp->displayName());
                $fileName = "{$safeName}_Attendance_" . $from->format('F_Y') . ($format === 'pdf' ? '.html' : '.xls');
                
                $empSummary = $attendance->monthSummary($emp, $from->year, $from->month);
                
                if ($format === 'pdf') {
                    $html = view('reports.pdf_single_employee', [
                        'companyName' => $companyName,
                        'emp' => $emp,
                        'from' => $from,
                        'to' => $to,
                        'summary' => $empSummary,
                    ])->render();
                    $files[$fileName] = $html;
                } else {
                    $excelHtml = view('reports.excel_single_employee', [
                        'companyName' => $companyName,
                        'emp' => $emp,
                        'from' => $from,
                        'to' => $to,
                        'summary' => $empSummary,
                    ])->render();
                    $files[$fileName] = $excelHtml;
                }
            }

            $zipBinary = SimpleZipWriter::createZip($files);
            $zipName = "monthly-attendance-sheets-" . $from->format('F-Y') . ".zip";

            return response($zipBinary, 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => "attachment; filename=\"{$zipName}\"",
            ]);
        }

        // 2. PDF Format Generation
        if ($format === 'pdf') {
            $tasks = Task::with(['assignee', 'department'])->whereBetween('created_at', [$from, $to])->get();
            $expenses = Expense::with('user')->whereBetween('expense_date', [$from, $to])->get();
            $departments = Department::all();
            $companies = Company::all();

            return view('reports.pdf', compact(
                'companyName', 'type', 'from', 'to', 'employees', 'attendance', 'tasks', 'expenses', 'departments', 'companies'
            ));
        }

        // 3. Task Report
        if ($type === 'tasks') {
            $statusFilter = $request->get('status');
            $taskQuery = Task::with(['assignee', 'department'])->whereBetween('created_at', [$from, $to]);
            if ($statusFilter && $statusFilter !== 'all') {
                $taskQuery->where('status', $statusFilter);
            }
            $tasks = $taskQuery->latest()->get();

            $filename = "Task_Report_" . $from->format('F_Y') . ".xls";
            return response(view('reports.excel_tasks', compact('companyName', 'tasks', 'from', 'to'))->render(), 200, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        // 4. HR & Master Data (Employee Master, Department List, Company List)
        if (str_starts_with($type, 'master_')) {
            $filename = "{$companyName}_Master_Report.xls";
            $departments = Department::all();
            $companies = Company::all();
            $shifts = Shift::all();

            return response(view('reports.excel_master', compact('companyName', 'employees', 'departments', 'companies', 'shifts', 'type'))->render(), 200, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        // 5. Default Attendance & Hours Excel Generation
        $filename = "{$companyName}_{$type}_Report_" . $from->format('Y-m') . ".xls";

        return response(view('reports.excel_attendance', [
            'companyName' => $companyName,
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'employees' => $employees,
            'attendance' => $attendance,
        ])->render(), 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function tracker(Request $request, AttendanceService $attendance)
    {
        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $employees = User::query()->where('status', 'active')->with('category')->orderBy('department')->orderBy('first_name')->get();

        return view('reports.tracker', compact('employees', 'from', 'attendance'));
    }
}
