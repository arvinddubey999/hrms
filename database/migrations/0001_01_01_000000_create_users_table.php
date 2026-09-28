<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('employee'); // admin, manager, employee
            $table->string('status')->default('active'); // active, archived
            $table->string('profile_photo')->nullable();
            $table->string('country')->nullable();
            $table->text('address')->nullable();
            $table->date('birthday')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('pan')->nullable();
            $table->string('aadhaar')->nullable();
            $table->string('pf_number')->nullable();
            $table->string('uan')->nullable();
            $table->boolean('esi_applicable')->default(false);
            $table->string('esi_number')->nullable();
            $table->string('employee_type')->default('Employee');
            $table->foreignId('category_id')->nullable();
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->string('employee_code')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('ifsc')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('bank_holder')->nullable();
            $table->boolean('mobile_attendance')->default(true);
            $table->boolean('multiple_attendance')->default(false);
            $table->boolean('shiftwise_attendance')->default(false);
            $table->boolean('self_odometer')->default(false);
            $table->boolean('live_tracking')->default(false);
            $table->boolean('ai_selfie')->default(true);
            $table->string('punch_from')->default('geofence');
            $table->foreignId('shift_id')->nullable();
            $table->unsignedInteger('casual_leaves')->default(12);
            $table->unsignedInteger('sick_leaves')->default(6);
            $table->unsignedInteger('privilege_leaves')->default(6);
            $table->unsignedInteger('emergency_leaves')->default(2);
            $table->string('pay_type')->default('monthly');
            $table->decimal('salary', 12, 2)->default(0);
            $table->string('week_off_day')->default('Sunday');
            $table->boolean('overtime_applicable')->default(false);
            $table->boolean('view_self_salary')->default(true);
            $table->string('api_token', 80)->nullable()->unique();
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lng', 10, 7)->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
