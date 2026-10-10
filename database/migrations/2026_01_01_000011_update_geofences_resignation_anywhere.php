<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'resignation_date')) {
                $table->date('resignation_date')->nullable();
            }
            if (!Schema::hasColumn('users', 'resignation_remarks')) {
                $table->text('resignation_remarks')->nullable();
            }
            if (!Schema::hasColumn('users', 'anywhere_from_date')) {
                $table->date('anywhere_from_date')->nullable();
            }
            if (!Schema::hasColumn('users', 'anywhere_to_date')) {
                $table->date('anywhere_to_date')->nullable();
            }
            if (!Schema::hasColumn('users', 'can_manage_tasks')) {
                $table->boolean('can_manage_tasks')->default(false);
            }
        });

        if (!Schema::hasTable('company_geofences')) {
            Schema::create('company_geofences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('address')->nullable();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->integer('radius')->default(100);
                $table->string('category')->nullable();
                $table->json('assigned_categories')->nullable();
                $table->json('assigned_employees')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'resignation_date',
                'resignation_remarks',
                'anywhere_from_date',
                'anywhere_to_date',
                'can_manage_tasks'
            ]);
        });

        Schema::dropIfExists('company_geofences');
    }
};
