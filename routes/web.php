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

// Helper route to run migrations & clear cache on live cPanel/hosting server
Route::get('/run-live-setup', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        return '<div style="font-family:sans-serif;padding:30px;background:#f0fdf4;color:#166534;border-radius:10px"><h2>✓ Live Server Setup Completed Successfully!</h2><p>Database migrations executed & system cache cleared.</p><a href="/attendances">Go to Attendances Dashboard</a></div>';
    } catch (\Exception $e) {
        return '<div style="font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border-radius:10px"><h2>X Error running setup:</h2><pre>' . $e->getMessage() . '</pre></div>';
    }
});

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
    Route::get('/employees/sample-csv', [EmployeeController::class, 'sampleCsv'])->name('employees.sample-csv');
    Route::post('/employees/import', [EmployeeController::class, 'importExcel'])->name('employees.import');
    Route::post('/departments/quick', [EmployeeController::class, 'quickStoreDepartment'])->name('departments.quick-store');
    Route::post('/categories/quick', [EmployeeController::class, 'quickStoreCategory'])->name('categories.quick-store');
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
    Route::post('/tasks/{task}/reply', [TaskController::class, 'reply'])->name('tasks.reply');
    Route::post('/tasks/{task}/reassign', [TaskController::class, 'reassign'])->name('tasks.reassign');
    Route::post('/tasks/{task}/remind', [TaskController::class, 'remind'])->name('tasks.remind');
    Route::post('/tasks/{task}/attachment', [TaskController::class, 'uploadAttachment'])->name('tasks.attachment');
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
    Route::put('/settings/categories/{category}', [SettingsController::class, 'updateCategory'])->name('settings.categories.update');
    Route::delete('/settings/categories/{category}', [SettingsController::class, 'destroyCategory'])->name('settings.categories.destroy');

    // Role & Permission Master
    Route::post('/settings/roles', [SettingsController::class, 'storeRole'])->name('settings.roles.store');
    Route::put('/settings/roles/{role}', [SettingsController::class, 'updateRole'])->name('settings.roles.update');
    Route::delete('/settings/roles/{role}', [SettingsController::class, 'destroyRole'])->name('settings.roles.destroy');

    // Designations & Permissions Master (Backward compatibility)
    Route::post('/settings/designations', [SettingsController::class, 'storeDesignation'])->name('settings.designations.store');
    Route::put('/settings/designations/{designation}', [SettingsController::class, 'updateDesignation'])->name('settings.designations.update');
    Route::delete('/settings/designations/{designation}', [SettingsController::class, 'destroyDesignation'])->name('settings.designations.destroy');

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

    // Geo-Fencing Locations Master
    Route::post('/settings/geofences', [SettingsController::class, 'storeGeofence'])->name('settings.geofences.store');
    Route::put('/settings/geofences/{geofence}', [SettingsController::class, 'updateGeofence'])->name('settings.geofences.update');
    Route::delete('/settings/geofences/{geofence}', [SettingsController::class, 'destroyGeofence'])->name('settings.geofences.destroy');
});
