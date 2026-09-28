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
        $employees = User::query()->where('status', 'active')->orderBy('first_name')->get();
        $days = Carbon::create($year, $month, 1)->daysInMonth;

        return view('roster.index', compact('employees', 'month', 'year', 'days', 'attendance'));
    }
}
