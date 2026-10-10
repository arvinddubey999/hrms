<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'wop_applicable')) {
                $table->boolean('wop_applicable')->default(true);
            }
            if (!Schema::hasColumn('users', 'hop_applicable')) {
                $table->boolean('hop_applicable')->default(true);
            }
            if (!Schema::hasColumn('users', 'payroll_remarks')) {
                $table->text('payroll_remarks')->nullable();
            }
        });

        if (!Schema::hasTable('designations')) {
            Schema::create('designations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->json('permissions')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wop_applicable', 'hop_applicable', 'payroll_remarks']);
        });
        Schema::dropIfExists('designations');
    }
};
