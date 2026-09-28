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
            'company_address' => '400, 212F, Shipra Path, SFS Mansarovar, Jaipur, Rajasthan 302020',
            'office_lat' => 26.8581,
            'office_lng' => 75.7642,
            'geofence_radius_m' => 250,
        ]);

        $cats = collect(['Gurukul', 'Support Team', 'Batch 1', 'Firm'])->map(fn ($n) => Category::query()->create(['name' => $n]));
        $general = Shift::query()->create(['name' => 'General Shift', 'start_time' => '10:00:00', 'end_time' => '19:00:00']);
        Shift::query()->create(['name' => 'Morning', 'start_time' => '09:00:00', 'end_time' => '18:00:00']);
        Shift::query()->create(['name' => 'Factory Shift', 'start_time' => '09:00:00', 'end_time' => '17:30:00']);

        $admin = User::query()->create([
            'first_name' => 'sonu',
            'last_name' => 'ray',
            'name' => 'sonu ray',
            'email' => 'sonu@rrvsoftech.com',
            'phone' => '7375010611',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'active',
            'designation' => 'Admin',
            'employee_type' => 'Admin',
            'date_of_joining' => '2025-09-24',
            'salary' => 20000,
            'pay_type' => 'monthly',
            'mobile_attendance' => true,
            'multiple_attendance' => true,
            'week_off_day' => 'Sunday',
        ]);

        $manager = User::query()->create([
            'first_name' => 'Ashok',
            'last_name' => 'Kirodiwal',
            'name' => 'Ashok Kirodiwal',
            'email' => 'ashok@rrvsoftech.com',
            'phone' => '7375010132',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
            'designation' => 'Employee',
            'department' => 'Sales',
            'category_id' => $cats[1]->id,
            'shift_id' => $general->id,
            'salary' => 22000,
            'live_tracking' => true,
            'ai_selfie' => true,
            'mobile_attendance' => true,
            'week_off_day' => 'Sunday',
        ]);

        $staff = [
            ['Arvind', 'Sharma', '9000000001', 'Employee', 'Support Team', 25000],
            ['Ajay Singh', 'Rajpoot', '8302059447', 'Hr', 'Batch 1', 28000],
            ['Akash', 'Kumar', '7357168094', 'Employee', 'Support Team', 18000],
            ['chetan', 'Hada', '8209118500', 'Employee', 'Gurukul', 17000],
            ['KAMAL SINGH', 'MEENA', '8005517899', 'Employee', 'Firm', 25000],
            ['Harsh', 'Modi', '9887777352', 'Senior Service Executive', 'Support Team', 21000],
            ['chelsi', 'nayak', '9256131822', 'Employee', 'Batch 1', 16000],
            ['Ramesh', 'Keypad', '9000000099', 'Helper', 'Firm', 12000],
        ];

        $users = collect();
        foreach ($staff as $i => $row) {
            [$fn, $ln, $phone, $desig, $catName, $sal] = $row;
            $cat = $cats->firstWhere('name', $catName);
            $users->push(User::query()->create([
                'first_name' => $fn,
                'last_name' => $ln,
                'name' => trim($fn.' '.$ln),
                'email' => strtolower(str_replace(' ', '.', $fn)).$i.'@rrvsoftech.com',
                'phone' => $phone,
                'password' => 'password',
                'role' => 'employee',
                'status' => 'active',
                'designation' => $desig,
                'category_id' => $cat?->id,
                'shift_id' => $general->id,
                'salary' => $sal,
                'pay_type' => 'monthly',
                'ai_selfie' => $fn !== 'Ramesh',
                'mobile_attendance' => $fn !== 'Ramesh',
                'multiple_attendance' => $fn === 'KAMAL SINGH',
                'live_tracking' => $fn === 'KAMAL SINGH' || $fn === 'Arvind',
                'punch_from' => 'geofence',
                'week_off_day' => 'Sunday',
                'date_of_joining' => '2025-11-05',
                'view_self_salary' => true,
            ]));
        }

        $kamal = $users->firstWhere('first_name', 'KAMAL SINGH');
        $arvind = $users->firstWhere('first_name', 'Arvind');

        $today = now('Asia/Kolkata');
        foreach ([$kamal, $arvind, $manager, $users[3]] as $u) {
            if (! $u) {
                continue;
            }
            AttendancePunch::query()->create([
                'user_id' => $u->id,
                'work_date' => $today->toDateString(),
                'type' => 'in',
                'source' => 'mobile',
                'punched_at' => $today->copy()->setTime(9, 58),
                'lat' => 26.8581,
                'lng' => 75.7642,
                'location_text' => '4/213 Mansarovar Industrial Area Road, Jaipur',
                'face_detected' => true,
                'greeting' => 'GOOD MORNING '.strtoupper($u->first_name),
            ]);
        }

        for ($d = 1; $d <= 22; $d++) {
            $date = Carbon::create($today->year, $today->month, min($d, $today->day));
            if ($date->isSunday() || $date->gt($today)) {
                continue;
            }
            AttendancePunch::query()->create([
                'user_id' => $kamal->id,
                'work_date' => $date->toDateString(),
                'type' => 'in',
                'source' => 'mobile',
                'punched_at' => $date->copy()->setTime(10, 0),
                'location_text' => 'RRV office',
                'face_detected' => true,
            ]);
            AttendancePunch::query()->create([
                'user_id' => $kamal->id,
                'work_date' => $date->toDateString(),
                'type' => 'out',
                'source' => 'mobile',
                'punched_at' => $date->copy()->setTime(19, 0),
                'location_text' => 'RRV office',
                'face_detected' => true,
            ]);
        }

        LeaveRequest::query()->create([
            'user_id' => $arvind->id,
            'leave_type' => 'Sick',
            'from_date' => $today->copy()->addDay()->toDateString(),
            'to_date' => $today->copy()->addDays(2)->toDateString(),
            'reason' => 'Fever',
            'status' => 'pending',
        ]);
        LeaveRequest::query()->create([
            'user_id' => $kamal->id,
            'leave_type' => 'casual',
            'from_date' => $today->copy()->subDays(3)->toDateString(),
            'to_date' => $today->copy()->subDays(3)->toDateString(),
            'reason' => 'xyz',
            'status' => 'approved',
            'updated_by' => $admin->id,
        ]);
        LeaveRequest::query()->create([
            'user_id' => $users[1]->id,
            'leave_type' => 'Privilege',
            'from_date' => $today->copy()->subDays(5)->toDateString(),
            'to_date' => $today->copy()->subDays(5)->toDateString(),
            'reason' => 'testing',
            'status' => 'rejected',
            'updated_by' => $admin->id,
        ]);

        Advance::query()->create(['user_id' => $kamal->id, 'title' => 'cash', 'amount' => 1000, 'paid_on' => $today->copy()->subDays(3), 'status' => 'paid']);
        Advance::query()->create(['user_id' => $kamal->id, 'title' => 'Cash Advance payment', 'amount' => 2000, 'paid_on' => $today->copy()->subDays(4), 'status' => 'paid']);
        Incentive::query()->create(['user_id' => $kamal->id, 'title' => 'Target Achieve', 'amount' => 1000, 'paid_on' => $today->copy()->subDays(4), 'status' => 'paid']);

        $task = Task::query()->create([
            'title' => 'sale of goods',
            'description' => 'you need to sale at least 10 products',
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => $today->toDateString(),
            'assigned_to' => $arvind->id,
            'created_by' => $admin->id,
        ]);
        $task->others()->attach([$kamal->id, $manager->id]);
        Task::query()->create([
            'title' => 'Ac repair',
            'description' => '-',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => $today->toDateString(),
            'assigned_to' => $manager->id,
            'created_by' => $admin->id,
        ]);
    }
}
