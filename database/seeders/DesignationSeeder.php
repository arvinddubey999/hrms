<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            'Driver',
            'Junior Accountant',
            'Supervisor',
            'CHECKER',
            'FOLDER',
            'Halper',
            'OPERATOR',
            'Dispatcher',
        ];

        foreach ($designations as $name) {
            $formattedName = Str::title(mb_strtolower(trim($name)));
            Designation::firstOrCreate(['name' => $formattedName]);
        }
    }
}
