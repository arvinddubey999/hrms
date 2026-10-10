<?php

namespace Database\Seeders;

use App\Models\Advance;
use App\Models\AttendancePunch;
use App\Models\Category;
use App\Models\Incentive;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $setting = Setting::current();
        $setting->update([
            'company_name' => 'Tulsi Fabrics Pvt. Ltd.',
            'company_address' => 'A-2018-2026, KOHINOOR TEXTILE MKT, RING ROAD, SURAT, 395002, Gujarat, India',
            'office_lat' => 21.1936,
            'office_lng' => 72.8508,
            'geofence_radius_m' => 250,
        ]);

        $this->call([
            DepartmentSeeder::class,
            DesignationSeeder::class,
            CategorySeeder::class,
            CompanySeeder::class,
        ]);

        $general = Shift::query()->firstOrCreate(['name' => 'General Shift'], ['start_time' => '10:00:00', 'end_time' => '19:00:00']);
        Shift::query()->firstOrCreate(['name' => 'Morning'], ['start_time' => '09:00:00', 'end_time' => '18:00:00']);
        Shift::query()->firstOrCreate(['name' => 'Factory Shift'], ['start_time' => '09:00:00', 'end_time' => '17:30:00']);

        $admin = User::query()->firstOrCreate(
            ['phone' => '9377741054'],
            [
                'first_name' => 'RAJIV',
                'last_name' => 'RAKHECHA',
                'name' => 'RAJIV R.',
                'email' => 'rajiv_rakhecha@yahoo.co.in',
                'password' => 'password',
                'role' => 'admin',
                'status' => 'active',
                'designation' => 'Admin',
                'employee_type' => 'Admin',
                'employee_code' => 'RI0001',
                'date_of_joining' => '2026-09-01',
                'salary' => 20000,
                'pay_type' => 'monthly',
                'mobile_attendance' => true,
                'multiple_attendance' => true,
                'week_off_day' => 'Sunday',
            ]
        );

        $manager = User::query()->firstOrCreate(
            ['phone' => '8169426418'],
            [
                'first_name' => 'Arvind',
                'last_name' => 'Dubey',
                'name' => 'Arvind Dubey',
                'email' => 'arvinddubey999@gmail.com',
                'password' => 'password',
                'role' => 'manager',
                'status' => 'active',
                'designation' => 'Manager',
                'department' => 'Sales',
                'employee_code' => 'RI0002',
                'category_id' => Category::where('name', 'Staff')->first()?->id ?? Category::first()?->id,
                'shift_id' => $general->id,
                'salary' => 22000,
                'live_tracking' => true,
                'ai_selfie' => true,
                'mobile_attendance' => true,
                'week_off_day' => 'Sunday',
            ]
        );
    }
}
