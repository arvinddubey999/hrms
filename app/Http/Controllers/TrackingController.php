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


    public function realtime(Request $request)
    {
        $authUser = auth()->user();
        if ($authUser && !$authUser->hasPermission('tracking.view') && !$authUser->hasPermission('geofence.tracking') && !$authUser->isSuperAdmin()) {
            return back()->with('error', 'You do not have permission to access Live Tracking.');
        }

        $allActiveStaffQuery = User::query()->where('status', 'active');
        if ($authUser && !$authUser->isSuperAdmin()) {
            $accessible = $authUser->accessibleCompanyIds();
            if (!empty($accessible)) {
                $allActiveStaffQuery->whereIn('company_id', $accessible);
            }
        }

        $allActiveStaff = $allActiveStaffQuery->with(['company', 'department', 'faceImages'])->orderBy('first_name')->get();
        $totalActiveCount = $allActiveStaff->count();

        // Check who is punched in today
        $todayDate = now('Asia/Kolkata')->toDateString();
        $punchedInUserIds = \App\Models\AttendancePunch::whereDate('work_date', $todayDate)
            ->where('type', 'in')
            ->pluck('user_id')
            ->unique()
            ->all();

        // Staff who have coordinates
        $staffWithLocation = $allActiveStaff->filter(function($u) {
            return !empty($u->last_lat) && !empty($u->last_lng);
        })->values();

        $punchInCount = count(array_intersect($allActiveStaff->pluck('id')->all(), $punchedInUserIds));
        $visibleCount = $staffWithLocation->count();
        if ($visibleCount === 0 && $punchInCount > 0) {
            $visibleCount = $punchInCount;
        }

        // Live ON vs Live OFF cutoff (within last 30 minutes)
        $cutoff = now()->subMinutes(30);
        $liveOnCount = 0;
        $liveOffCount = 0;

        $staffData = $allActiveStaff->map(function($u) use ($punchedInUserIds, $cutoff, &$liveOnCount, &$liveOffCount) {
            $isLive = !empty($u->last_location_at) && $u->last_location_at >= $cutoff;
            if ($isLive) {
                $liveOnCount++;
            } else {
                $liveOffCount++;
            }

            $isPunchedIn = in_array($u->id, $punchedInUserIds, true);
            $lastPing = $u->locationPings()->latest('pinged_at')->first();
            $address = $lastPing?->address ?: ($u->address ?: 'Surat, Gujarat, India');
            $lastSeen = $u->last_location_at ? $u->last_location_at->diffForHumans() : 'Never';

            return [
                'id' => $u->id,
                'name' => $u->displayName(),
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'code' => $u->employee_code,
                'phone' => $u->phone,
                'role' => $u->role,
                'department_id' => $u->department_id,
                'department_name' => $u->department?->name ?? 'General',
                'company_id' => $u->company_id,
                'company_name' => $u->company?->name ?? 'Tulsi Fabrics',
                'avatar_url' => $u->avatarUrl(),
                'initials' => $u->initials(),
                'lat' => $u->last_lat ? (float)$u->last_lat : null,
                'lng' => $u->last_lng ? (float)$u->last_lng : null,
                'last_seen' => $lastSeen,
                'address' => $address,
                'is_live' => $isLive,
                'is_punched_in' => $isPunchedIn,
                'profile_url' => route('employees.show', $u->id),
            ];
        });

        $departments = \App\Models\Department::orderBy('name')->get();
        $companies = \App\Models\Company::orderBy('name')->get();
        $categories = \App\Models\Category::orderBy('name')->get();

        return view('tracking.realtime', compact(
            'allActiveStaff',
            'staffData',
            'totalActiveCount',
            'visibleCount',
            'liveOnCount',
            'liveOffCount',
            'departments',
            'companies',
            'categories'
        ));
    }

    /**
     * Manage Geo-fences list and map screen matching Screenshot 3
     */
    public function geofences(Request $request)
    {
        $authUser = auth()->user();
        if ($authUser && !$authUser->hasPermission('geofence.view') && !$authUser->hasPermission('geofence.tracking') && !$authUser->isSuperAdmin()) {
            return back()->with('error', 'You do not have permission to view Geofences.');
        }

        $compQuery = \App\Models\CompanyGeofence::with('company');
        if ($authUser && !$authUser->isSuperAdmin()) {
            $accessible = $authUser->accessibleCompanyIds();
            if (!empty($accessible)) {
                $compQuery->whereIn('company_id', $accessible);
            }
        }
        $geofences = $compQuery->orderByDesc('id')->get();

        $companies = \App\Models\Company::orderBy('name')->get();
        $departments = \App\Models\Department::orderBy('name')->get();
        $categories = \App\Models\Category::orderBy('name')->get();
        $employees = User::where('status', 'active')->with(['category', 'department'])->orderBy('first_name')->get();

        return view('tracking.geofences', compact('geofences', 'companies', 'departments', 'categories', 'employees'));
    }

    public function storeGeofence(Request $request)
    {
        $authUser = auth()->user();
        if ($authUser && !$authUser->hasPermission('settings.manage') && !$authUser->isSuperAdmin() && !$authUser->isAdmin()) {
            return back()->with('error', 'You do not have permission to add Geofences.');
        }

        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer|min:5',
            'category' => 'nullable|string',
            'assigned_categories' => 'nullable|array',
            'assigned_departments' => 'nullable|array',
            'assigned_employees' => 'nullable|array',
            'status' => 'nullable|string',
        ]);

        if (empty($data['name'])) {
            $data['name'] = !empty($data['address']) ? \Illuminate\Support\Str::limit($data['address'], 30) : 'Geofence ' . date('Y-m-d H:i');
        }

        $data['status'] = $data['status'] ?? 'active';

        \App\Models\CompanyGeofence::create($data);

        return redirect()->route('tracking.geofences')->with('ok', 'New Geo-fence location configured successfully.');
    }

    public function updateGeofence(Request $request, \App\Models\CompanyGeofence $geofence)
    {
        $authUser = auth()->user();
        if ($authUser && !$authUser->hasPermission('settings.manage') && !$authUser->isSuperAdmin() && !$authUser->isAdmin()) {
            return back()->with('error', 'You do not have permission to edit Geofences.');
        }

        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer|min:5',
            'category' => 'nullable|string',
            'assigned_categories' => 'nullable|array',
            'assigned_departments' => 'nullable|array',
            'assigned_employees' => 'nullable|array',
            'status' => 'nullable|string',
        ]);

        if (empty($data['name'])) {
            $data['name'] = !empty($data['address']) ? \Illuminate\Support\Str::limit($data['address'], 30) : $geofence->name;
        }

        $geofence->update($data);

        return redirect()->route('tracking.geofences')->with('ok', 'Geo-fence updated successfully.');
    }


    public function destroyGeofence(\App\Models\CompanyGeofence $geofence)
    {
        $authUser = auth()->user();
        if ($authUser && !$authUser->hasPermission('settings.manage') && !$authUser->isSuperAdmin() && !$authUser->isAdmin()) {
            return back()->with('error', 'You do not have permission to delete Geofences.');
        }

        $geofence->delete();

        return redirect()->route('tracking.geofences')->with('ok', 'Geo-fence deleted successfully.');
    }
}

