<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('api.token')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/team', [AuthController::class, 'team']);

    Route::post('/attendance/punch', [AttendanceController::class, 'punch']);
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);

    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::post('/leaves', [LeaveController::class, 'store']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks/{task}/status', [TaskController::class, 'updateStatus']);

    Route::get('/payroll', [PayrollController::class, 'me']);
    Route::post('/location/ping', [LocationController::class, 'ping']);
});
