<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('task_categories')) {
            Schema::create('task_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });

            // Seed initial task categories requested by client
            $defaultCategories = [
                'Electricity Bill',
                'Credit Card Bill',
                'Insurance Payment',
                'Sales OS Followup',
                'Tax / GST / TDS',
                'EPF / ESIC Closure',
                'License & Compliance',
                'General Followup',
            ];

            foreach ($defaultCategories as $catName) {
                DB::table('task_categories')->insertOrIgnore([
                    'name' => $catName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('department_id')->constrained('task_categories')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
        });

        Schema::dropIfExists('task_categories');
    }
};
