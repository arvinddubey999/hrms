<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('attendances.index'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    // Attendance & Employees
    Route::match(['get', 'post'], '/attendances', [EmployeeController::class, 'index'])->name('attendances.index');
    Route::get('/attendances/stat-modal', [EmployeeController::class, 'statModal'])->name('attendances.stat-modal');
    Route::post('/employees/bulk-shift', [EmployeeController::class, 'bulkShift'])->name('employees.bulk-shift');
    Route::post('/employees/bulk-mark', [EmployeeController::class, 'bulkMark'])->name('employees.bulk-mark');

    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
    Route::post('/employees/{employee}/mark', [EmployeeController::class, 'mark'])->name('employees.mark');
    Route::get('/employees/{employee}/day', [EmployeeController::class, 'dayPunches'])->name('employees.day');
    Route::get('/employees/{employee}/monthly', [EmployeeController::class, 'monthlyPrint'])->name('employees.monthly');

    // Leave Requests
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    Route::post('/requests/{leaveRequest}/status', [RequestController::class, 'updateStatus'])->name('requests.status');
    Route::get('/requests/{leaveRequest}/pdf', [RequestController::class, 'pdf'])->name('requests.pdf');

    // Payroll
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/export', [PayrollController::class, 'export'])->name('payroll.export');
    Route::get('/payroll/{employee}/payslip', [PayrollController::class, 'payslip'])->name('payroll.payslip');
    Route::post('/payroll/{employee}/advance', [PayrollController::class, 'addAdvance'])->name('payroll.advance');
    Route::post('/payroll/{employee}/incentive', [PayrollController::class, 'addIncentive'])->name('payroll.incentive');

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/export/excel', [TaskController::class, 'exportExcel'])->name('tasks.export.excel');
    Route::get('/tasks/export/pdf', [TaskController::class, 'exportPdf'])->name('tasks.export.pdf');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    Route::get('/reports/tracker', [ReportController::class, 'tracker'])->name('reports.tracker');

    // Live Tracking
    Route::get('/tracking/timeline', [TrackingController::class, 'timeline'])->name('tracking.timeline');
    Route::get('/tracking/realtime', [TrackingController::class, 'realtime'])->name('tracking.realtime');

    // Monthly Roster
    Route::get('/roster', [RosterController::class, 'index'])->name('roster.index');
    Route::get('/roster/export/excel', [RosterController::class, 'exportExcel'])->name('roster.export.excel');
    Route::get('/roster/export/pdf', [RosterController::class, 'exportPdf'])->name('roster.export.pdf');

    // Settings & Masters
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/shifts', [SettingsController::class, 'storeShift'])->name('settings.shifts');
    Route::put('/settings/shifts/{shift}', [SettingsController::class, 'updateShift'])->name('settings.shifts.update');
    Route::delete('/settings/shifts/{shift}', [SettingsController::class, 'destroyShift'])->name('settings.shifts.destroy');
    Route::post('/settings/categories', [SettingsController::class, 'storeCategory'])->name('settings.categories');

    // Company Master
    Route::post('/settings/companies', [SettingsController::class, 'storeCompany'])->name('settings.companies.store');
    Route::put('/settings/companies/{company}', [SettingsController::class, 'updateCompany'])->name('settings.companies.update');
    Route::delete('/settings/companies/{company}', [SettingsController::class, 'destroyCompany'])->name('settings.companies.destroy');

    // Department Master
    Route::post('/settings/departments', [SettingsController::class, 'storeDepartment'])->name('settings.departments.store');
    Route::put('/settings/departments/{department}', [SettingsController::class, 'updateDepartment'])->name('settings.departments.update');
    Route::delete('/settings/departments/{department}', [SettingsController::class, 'destroyDepartment'])->name('settings.departments.destroy');

    // Holiday Master
    Route::post('/settings/holidays', [SettingsController::class, 'storeHoliday'])->name('settings.holidays.store');
    Route::put('/settings/holidays/{holiday}', [SettingsController::class, 'updateHoliday'])->name('settings.holidays.update');
    Route::delete('/settings/holidays/{holiday}', [SettingsController::class, 'destroyHoliday'])->name('settings.holidays.destroy');
});
