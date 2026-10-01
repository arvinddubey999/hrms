<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Setting;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'setting' => Setting::current(),
            'shifts' => Shift::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'holidays' => Holiday::orderByDesc('date')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string',
            'company_address' => 'nullable|string',
            'office_lat' => 'required|numeric',
            'office_lng' => 'required|numeric',
            'geofence_radius_m' => 'required|integer|min:20',
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'phone' => 'required|string',
            'pay_type' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'date_of_joining' => 'nullable|date',
            'mobile_attendance' => 'nullable|boolean',
            'multiple_attendance' => 'nullable|boolean',
            'live_tracking' => 'nullable|boolean',
        ]);

        $setting = Setting::current();
        $settingData = [
            'company_name' => $data['company_name'],
            'company_address' => $data['company_address'] ?? $setting->company_address,
            'office_lat' => $data['office_lat'],
            'office_lng' => $data['office_lng'],
            'geofence_radius_m' => $data['geofence_radius_m'],
        ];

        if ($request->hasFile('company_logo')) {
            $settingData['company_logo'] = $request->file('company_logo')->store('company', 'public');
        }

        $setting->update($settingData);

        $admin = $request->user();
        $admin->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? '',
            'name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
            'phone' => $data['phone'],
            'pay_type' => $data['pay_type'] ?? $admin->pay_type,
            'salary' => $data['salary'] ?? $admin->salary,
            'date_of_joining' => $data['date_of_joining'] ?? $admin->date_of_joining,
            'mobile_attendance' => $request->boolean('mobile_attendance'),
            'multiple_attendance' => $request->boolean('multiple_attendance'),
            'live_tracking' => $request->boolean('live_tracking'),
        ]);

        if ($request->hasFile('profile_photo')) {
            $admin->update(['profile_photo' => $request->file('profile_photo')->store('profiles', 'public')]);
        }

        return back()->with('ok', 'Settings & Company info updated.');
    }

    // Company Master CRUD
    public function storeCompany(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies', 'public');
        }

        Company::create($data);

        return back()->with('ok', 'Company added to master.');
    }

    public function updateCompany(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies', 'public');
        }

        $company->update($data);

        return back()->with('ok', 'Company updated.');
    }

    public function destroyCompany(Company $company)
    {
        $company->delete();
        return back()->with('ok', 'Company deleted.');
    }

    // Department Master CRUD
    public function storeDepartment(Request $request)
    {
        $data = $request->validate(['name' => 'required|string']);
        Department::create($data);

        return back()->with('ok', 'Department added to master.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $data = $request->validate(['name' => 'required|string']);
        $department->update($data);

        return back()->with('ok', 'Department updated.');
    }

    public function destroyDepartment(Department $department)
    {
        $department->delete();
        return back()->with('ok', 'Department deleted.');
    }

    // Holiday Master CRUD
    public function storeHoliday(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'company_ids' => 'nullable|array',
        ]);

        Holiday::create($data);

        return back()->with('ok', 'Holiday added.');
    }

    public function updateHoliday(Request $request, Holiday $holiday)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'company_ids' => 'nullable|array',
        ]);

        $holiday->update($data);

        return back()->with('ok', 'Holiday updated.');
    }

    public function destroyHoliday(Holiday $holiday)
    {
        $holiday->delete();
        return back()->with('ok', 'Holiday deleted.');
    }

    // Shift Master
    public function storeShift(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);
        Shift::create($data);

        return back()->with('ok', 'Shift added.');
    }

    public function updateShift(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);
        $shift->update($data);

        return back()->with('ok', 'Shift updated.');
    }

    public function destroyShift(Shift $shift)
    {
        $shift->delete();
        return back()->with('ok', 'Shift deleted.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string']);
        Category::create($data);

        return back()->with('ok', 'Category added.');
    }
}
