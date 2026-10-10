<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Driver',
            'Accounts',
            'Supervisor',
            'CHECKER',
            'FOLDER',
            'Halper',
            'OPERATOR',
            'Dispatch',
        ];

        foreach ($departments as $name) {
            $formattedName = Str::title(mb_strtolower(trim($name)));
            Department::firstOrCreate(['name' => $formattedName]);
        }
    }
}
