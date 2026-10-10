<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'description' => 'Full access to all features and settings',
                'permissions' => [
                    'dashboard.view', 'dashboard.reports', 'dashboard.download',
                    'employee.view', 'employee.add', 'employee.edit', 'employee.delete',
                    'attendance.view', 'attendance.mark', 'attendance.bulk', 'attendance.edit',
                    'leave.view', 'leave.apply', 'leave.approve',
                    'payroll.view', 'payroll.process', 'payroll.advances', 'payroll.expenses',
                    'tasks.view', 'tasks.create', 'tasks.assign', 'tasks.manage',
                    'geofence.view', 'geofence.tracking',
                    'settings.view', 'settings.roles', 'settings.manage'
                ],
            ],
            [
                'name' => 'Manager',
                'description' => 'Can manage team and reports',
                'permissions' => [
                    'dashboard.view', 'dashboard.reports',
                    'employee.view', 'employee.add', 'employee.edit',
                    'attendance.view', 'attendance.mark', 'attendance.bulk',
                    'leave.view', 'leave.approve',
                    'tasks.view', 'tasks.create', 'tasks.assign', 'tasks.manage',
                    'geofence.view', 'geofence.tracking'
                ],
            ],
            [
                'name' => 'HR',
                'description' => 'HR related access and employee management',
                'permissions' => [
                    'dashboard.view', 'dashboard.reports',
                    'employee.view', 'employee.add', 'employee.edit', 'employee.delete',
                    'attendance.view', 'attendance.mark',
                    'leave.view', 'leave.approve'
                ],
            ],
            [
                'name' => 'Accountant',
                'description' => 'Finance and payroll access',
                'permissions' => [
                    'dashboard.view', 'dashboard.reports',
                    'employee.view',
                    'payroll.view', 'payroll.process', 'payroll.advances', 'payroll.expenses'
                ],
            ],
            [
                'name' => 'Supervisor',
                'description' => 'Supervisor access: team attendance & task assignment',
                'permissions' => [
                    'dashboard.view',
                    'employee.view',
                    'attendance.view', 'attendance.mark', 'attendance.bulk',
                    'tasks.view', 'tasks.create', 'tasks.assign'
                ],
            ],
            [
                'name' => 'Developer',
                'description' => 'Can manage projects and code',
                'permissions' => [
                    'dashboard.view',
                    'tasks.view', 'tasks.create', 'tasks.manage'
                ],
            ],
            [
                'name' => 'Employee',
                'description' => 'Standard self-service employee access',
                'permissions' => [
                    'dashboard.view',
                    'attendance.view', 'attendance.mark',
                    'leave.view', 'leave.apply',
                    'tasks.view'
                ],
            ],
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate(
                ['name' => $r['name']],
                [
                    'description' => $r['description'],
                    'permissions' => $r['permissions'],
                ]
            );
        }
    }
}
