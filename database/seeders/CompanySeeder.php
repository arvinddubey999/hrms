<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            'Navkar Fab',
        ];

        foreach ($companies as $name) {
            $formattedName = Str::title(mb_strtolower(trim($name)));
            Company::firstOrCreate(['name' => $formattedName]);
        }
    }
}
