<?php

namespace App\Http\Controllers;

use App\Models\LocationPing;
use App\Models\User;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function timeline(Request $request)
    {
        $authUser = auth()->user();
        if ($authUser && !$authUser->hasPermission('tracking.view') && !$authUser->hasPermission('geofence.tracking') && !$authUser->isAdmin()) {
            return back()->with('error', 'You do not have permission to access Live Tracking.');
        }

        $date = $request->get('date', now()->toDateString());
        $userId = $request->get('employee');

        $empQuery = User::query()->where('status', 'active');
        if ($authUser && !$authUser->isAdmin()) {
            $accessible = $authUser->accessibleCompanyIds();
            if (!empty($accessible)) {
                $empQuery->whereIn('company_id', $accessible);
            }
        }
        $employees = $empQuery->orderBy('first_name')->get();
        $employee = $userId ? User::find($userId) : $employees->first();

        $pings = collect();
        if ($employee) {
            $pings = LocationPing::query()
                ->where('user_id', $employee->id)
                ->whereDate('pinged_at', $date)
                ->orderBy('pinged_at', 'asc')
                ->get();
        }

        $totalLocations = $pings->count();
        $totalPoints = $pings->sum(function ($p) {
            return $p->points ?: 1;
        });

        return view('tracking.timeline', compact('employees', 'employee', 'date', 'pings', 'totalLocations', 'totalPoints'));
    }

    /**
     * Download Timeline Report in formatted Excel (.xls) matching Screenshot 2
     */
    public function exportTimeline(Request $request)
    {
        $authUser = auth()->user();
        if ($authUser && !$authUser->hasPermission('tracking.view') && !$authUser->hasPermission('geofence.tracking') && !$authUser->isAdmin()) {
            abort(403, 'Unauthorized access to live tracking report.');
        }

        $date = $request->get('date', now()->toDateString());
        $userId = $request->get('employee');

        $employee = $userId ? User::with('company')->find($userId) : null;
        if (!$employee) {
            return back()->with('error', 'Please select an employee to export timeline.');
        }

        $pings = LocationPing::query()
            ->where('user_id', $employee->id)
            ->whereDate('pinged_at', $date)
            ->orderBy('pinged_at', 'asc')
            ->get();

        $companyName = strtoupper($employee->company?->name ?? 'TULSI FABRICS INDIA PRIVATE LIMITED');
        $empName = $employee->displayName();
        $formattedDate = \Carbon\Carbon::parse($date)->format('d M Y');

        $cleanEmpName = preg_replace('/[\\/:*?"<>|]/', '_', $empName);
        $filename = "Location_Timeline_Report_{$cleanEmpName}_{$formattedDate}.xls";

        return response()->streamDownload(function () use ($companyName, $empName, $formattedDate, $pings) {
            echo view('tracking.export_excel', compact('companyName', 'empName', 'formattedDate', 'pings'))->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }


    public function realtime()
    {
        $authUser = auth()->user();
        if ($authUser && !$authUser->hasPermission('tracking.view') && !$authUser->hasPermission('geofence.tracking')) {
            return back()->with('error', 'You do not have permission to access Live Tracking.');
        }

        $staffQuery = User::query()
            ->where('status', 'active')
            ->whereNotNull('last_lat');

        if ($authUser && !$authUser->isAdmin()) {
            $accessible = $authUser->accessibleCompanyIds();
            if (!empty($accessible)) {
                $staffQuery->whereIn('company_id', $accessible);
            }
        }

        $staff = $staffQuery->orderByDesc('last_location_at')->get();

        return view('tracking.realtime', compact('staff'));
    }
}
