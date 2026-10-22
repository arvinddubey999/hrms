<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_geofences', function (Blueprint $table) {
            if (!Schema::hasColumn('company_geofences', 'assigned_companies')) {
                $table->json('assigned_companies')->nullable()->after('company_id');
            }
            $table->foreignId('company_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('company_geofences', function (Blueprint $table) {
            if (Schema::hasColumn('company_geofences', 'assigned_companies')) {
                $table->dropColumn('assigned_companies');
            }
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }
};
