<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_geofences', function (Blueprint $table) {
            if (!Schema::hasColumn('company_geofences', 'assigned_departments')) {
                $table->json('assigned_departments')->nullable()->after('assigned_categories');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_geofences', function (Blueprint $table) {
            if (Schema::hasColumn('company_geofences', 'assigned_departments')) {
                $table->dropColumn('assigned_departments');
            }
        });
    }
};
