<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\TaskController;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('/login', [AuthController::class, 'login']);

// Self Password Reset
Route::post('/reset-password', function (Request $request) {
    $data = $request->validate([
        'login' => 'required|string',
        'password' => 'required|string|min:6',
    ]);
    $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
    $user = \App\Models\User::where($field, $data['login'])->first();
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }
    $user->update(['password' => Hash::make($data['password'])]);
    return response()->json(['ok' => true, 'message' => 'Password reset successfully!']);
});

Route::middleware('api.token')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/team', [AuthController::class, 'team']);

    Route::post('/attendance/punch', [AttendanceController::class, 'punch']);
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);
    Route::get('/attendance/statistics', [AttendanceController::class, 'statistics']);
    Route::get('/attendance/monthly-report', [AttendanceController::class, 'monthlyReport']);
    Route::post('/register-fcm-token', [AttendanceController::class, 'registerFcmToken']);

    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::post('/leaves', [LeaveController::class, 'store']);
    Route::post('/leaves/{leaveRequest}/status', [LeaveController::class, 'updateStatus']);

    // Requests Module Routes
    Route::get('/requests/dashboard', [\App\Http\Controllers\Api\RequestController::class, 'dashboard']);
    Route::get('/requests', [\App\Http\Controllers\Api\RequestController::class, 'index']);
    Route::post('/requests/leave', [\App\Http\Controllers\Api\RequestController::class, 'storeLeave']);
    Route::post('/requests/loan', [\App\Http\Controllers\Api\RequestController::class, 'storeLoan']);
    Route::post('/requests/{id}/status', [\App\Http\Controllers\Api\RequestController::class, 'updateStatus']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::post('/tasks/{task}/status', [TaskController::class, 'updateStatus']);
    Route::post('/tasks/{task}/reply', [TaskController::class, 'reply']);
    Route::post('/tasks/{task}/reassign', [TaskController::class, 'reassign']);
    Route::post('/tasks/{task}/remind', [TaskController::class, 'remind']);
    Route::get('/department-employees', [TaskController::class, 'departmentEmployees']);

    Route::get('/payroll', [PayrollController::class, 'me']);
    Route::post('/location/ping', [LocationController::class, 'ping']);

    Route::get('/masters', function () {
        return response()->json([
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    });
});
