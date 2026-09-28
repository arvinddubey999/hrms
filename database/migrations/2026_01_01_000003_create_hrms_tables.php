<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('RRV SOFTECH PRIVATE LIMITED');
            $table->string('company_address')->nullable();
            $table->string('brand')->default('AdCodeNexus.');
            $table->decimal('office_lat', 10, 7)->default(26.8581000);
            $table->decimal('office_lng', 10, 7)->default(75.7642000);
            $table->unsignedInteger('geofence_radius_m')->default(200);
            $table->string('timezone')->default('Asia/Kolkata');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        Schema::create('staff_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });

        Schema::create('face_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });

        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('work_date');
            $table->string('type'); // in, out
            $table->string('source')->default('mobile'); // mobile, admin, manager, terminal, geofence
            $table->timestamp('punched_at');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('location_text')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('face_detected')->default(false);
            $table->string('greeting')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('leave_type');
            $table->date('from_date');
            $table->date('to_date');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, unapproved
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('client')->nullable();
            $table->string('status')->default('pending');
            $table->string('priority')->default('medium');
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('task_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        });

        Schema::create('advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('status')->default('paid');
            $table->timestamps();
        });

        Schema::create('incentives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('status')->default('paid');
            $table->timestamps();
        });

        Schema::create('location_pings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedInteger('accuracy')->nullable();
            $table->string('address')->nullable();
            $table->boolean('inside_geofence')->default(true);
            $table->timestamp('pinged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_pings');
        Schema::dropIfExists('incentives');
        Schema::dropIfExists('advances');
        Schema::dropIfExists('task_user');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendance_punches');
        Schema::dropIfExists('face_images');
        Schema::dropIfExists('staff_documents');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('settings');
    }
};
