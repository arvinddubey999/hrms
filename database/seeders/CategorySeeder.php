<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Driver',
            'Staff',
            'CHECKER',
            'FOLDER',
            'Halper',
            'OPERATOR',
        ];

        foreach ($categories as $name) {
            $formattedName = Str::title(mb_strtolower(trim($name)));
            Category::firstOrCreate(['name' => $formattedName]);
        }
    }
}
