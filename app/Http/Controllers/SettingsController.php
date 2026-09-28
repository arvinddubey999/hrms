<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Setting;
use App\Models\Shift;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'setting' => Setting::current(),
            'shifts' => Shift::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
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
        $setting->update([
            'company_name' => $data['company_name'],
            'company_address' => $data['company_address'] ?? $setting->company_address,
            'office_lat' => $data['office_lat'],
            'office_lng' => $data['office_lng'],
            'geofence_radius_m' => $data['geofence_radius_m'],
        ]);

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

        return back()->with('ok', 'Settings saved.');
    }

    public function storeShift(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);
        Shift::query()->create($data);

        return back()->with('ok', 'Shift added.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string']);
        Category::query()->create($data);

        return back()->with('ok', 'Category added.');
    }
}
