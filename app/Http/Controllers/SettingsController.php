<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Role;
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
            'roles' => Role::orderBy('id')->get(),
            'designations' => \App\Models\Designation::orderBy('name')->get(),
            'geofences' => \App\Models\CompanyGeofence::with('company')->get(),
            'allEmployees' => \App\Models\User::where('status', 'active')->orderBy('first_name')->get(),
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
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'phone' => 'nullable|string',
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
        if ($admin) {
            $adminUpdate = [];
            if (!empty($data['first_name'])) {
                $adminUpdate['first_name'] = $data['first_name'];
                $adminUpdate['last_name'] = $data['last_name'] ?? '';
                $adminUpdate['name'] = trim($data['first_name'].' '.($data['last_name'] ?? ''));
            }
            if (!empty($data['phone'])) {
                $adminUpdate['phone'] = $data['phone'];
            }
            if ($request->has('mobile_attendance')) {
                $adminUpdate['mobile_attendance'] = $request->boolean('mobile_attendance');
            }
            if ($request->has('multiple_attendance')) {
                $adminUpdate['multiple_attendance'] = $request->boolean('multiple_attendance');
            }
            if ($request->has('live_tracking')) {
                $adminUpdate['live_tracking'] = $request->boolean('live_tracking');
            }
            if (!empty($adminUpdate)) {
                $admin->update($adminUpdate);
            }
        }

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
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'code_prefix' => 'nullable|string',
            'salary_calculation_days' => 'nullable|string',
            'pt_enabled' => 'nullable|boolean',
            'pt_threshold' => 'nullable|numeric',
            'pt_amount' => 'nullable|numeric',
        ]);

        $data['pt_enabled'] = $request->has('pt_enabled') ? $request->boolean('pt_enabled') : true;

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
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'code_prefix' => 'nullable|string',
            'salary_calculation_days' => 'nullable|string',
            'pt_enabled' => 'nullable|boolean',
            'pt_threshold' => 'nullable|numeric',
            'pt_amount' => 'nullable|numeric',
        ]);

        $data['pt_enabled'] = $request->has('pt_enabled') ? $request->boolean('pt_enabled') : false;

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies', 'public');
        }

        $company->update($data);

        return back()->with('ok', 'Company updated successfully.');
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

        return back()->with('ok', 'Department updated successfully.');
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
            'department_ids' => 'nullable|array',
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
            'department_ids' => 'nullable|array',
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

        return back()->with('ok', 'Shift updated successfully.');
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

    public function updateCategory(Request $request, Category $category)
    {
        $data = $request->validate(['name' => 'required|string']);
        $category->update($data);

        return back()->with('ok', 'Category updated.');
    }

    public function destroyCategory(Category $category)
    {
        $category->delete();
        return back()->with('ok', 'Category deleted.');
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);
        Role::create($data);

        return back()->with('ok', 'Role & Permissions created successfully.');
    }

    public function updateRole(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);
        $role->update($data);

        return back()->with('ok', 'Role & Permissions updated successfully.');
    }

    public function destroyRole(Role $role)
    {
        $role->delete();

        return back()->with('ok', 'Role deleted successfully.');
    }

    public function storeDesignation(Request $request)
    {
        return $this->storeRole($request);
    }

    public function updateDesignation(Request $request, \App\Models\Designation $designation)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'permissions' => 'nullable|array',
        ]);
        $designation->update($data);

        return back()->with('ok', 'Designation permissions updated.');
    }

    public function destroyDesignation(\App\Models\Designation $designation)
    {
        $designation->delete();

        return back()->with('ok', 'Designation deleted.');
    }

    public function storeGeofence(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer|min:10',
            'category' => 'nullable|string',
            'assigned_categories' => 'nullable|array',
            'assigned_employees' => 'nullable|array',
            'status' => 'nullable|string',
        ]);

        $data['status'] = $data['status'] ?? 'active';

        \App\Models\CompanyGeofence::create($data);

        return back()->with('ok', 'Geo-fence location added successfully.');
    }

    public function updateGeofence(Request $request, \App\Models\CompanyGeofence $geofence)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer|min:10',
            'category' => 'nullable|string',
            'assigned_categories' => 'nullable|array',
            'assigned_employees' => 'nullable|array',
            'status' => 'nullable|string',
        ]);

        $geofence->update($data);

        return back()->with('ok', 'Geo-fence location updated.');
    }

    public function destroyGeofence(\App\Models\CompanyGeofence $geofence)
    {
        $geofence->delete();

        return back()->with('ok', 'Geo-fence location deleted.');
    }
}
