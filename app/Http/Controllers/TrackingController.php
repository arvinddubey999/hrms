<?php

namespace App\Http\Controllers;

use App\Models\LocationPing;
use App\Models\User;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function timeline(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $userId = $request->get('employee');
        $authUser = auth()->user();
        $empQuery = User::query()->where('status', 'active');
        if ($authUser && $authUser->company_id && !$authUser->isAdmin()) {
            $empQuery->where('company_id', $authUser->company_id);
        }
        $employees = $empQuery->orderBy('first_name')->get();
        $employee = $userId ? User::find($userId) : $employees->first();
        $pings = collect();
        if ($employee) {
            $pings = LocationPing::query()
                ->where('user_id', $employee->id)
                ->whereDate('pinged_at', $date)
                ->orderBy('pinged_at')
                ->get();
        }

        return view('tracking.timeline', compact('employees', 'employee', 'date', 'pings'));
    }

    public function realtime()
    {
        $staff = User::query()
            ->where('status', 'active')
            ->whereNotNull('last_lat')
            ->orderByDesc('last_location_at')
            ->get();

        return view('tracking.realtime', compact('staff'));
    }
}
