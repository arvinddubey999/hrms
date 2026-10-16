<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_pings', function (Blueprint $table) {
            if (!Schema::hasColumn('location_pings', 'journey_status')) {
                $table->string('journey_status', 30)->nullable()->default('Transit')->after('inside_geofence');
            }
            if (!Schema::hasColumn('location_pings', 'points')) {
                $table->unsignedInteger('points')->default(1)->after('journey_status');
            }
            if (!Schema::hasColumn('location_pings', 'battery')) {
                $table->unsignedTinyInteger('battery')->nullable()->after('points');
            }
            if (!Schema::hasColumn('location_pings', 'time_range')) {
                $table->string('time_range', 100)->nullable()->after('battery');
            }
            if (!Schema::hasColumn('location_pings', 'stayed_minutes')) {
                $table->unsignedInteger('stayed_minutes')->nullable()->after('time_range');
            }
            if (!Schema::hasColumn('location_pings', 'speed')) {
                $table->decimal('speed', 8, 2)->nullable()->after('stayed_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('location_pings', function (Blueprint $table) {
            $table->dropColumn([
                'journey_status',
                'points',
                'battery',
                'time_range',
                'stayed_minutes',
                'speed',
            ]);
        });
    }
};
