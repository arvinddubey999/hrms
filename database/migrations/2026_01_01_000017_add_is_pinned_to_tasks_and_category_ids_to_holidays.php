<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_pinned to tasks
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('priority');
            }
        });

        // Add category_ids to holidays
        Schema::table('holidays', function (Blueprint $table) {
            if (!Schema::hasColumn('holidays', 'category_ids')) {
                $table->json('category_ids')->nullable()->after('department_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'is_pinned')) {
                $table->dropColumn('is_pinned');
            }
        });

        Schema::table('holidays', function (Blueprint $table) {
            if (Schema::hasColumn('holidays', 'category_ids')) {
                $table->dropColumn('category_ids');
            }
        });
    }
};
