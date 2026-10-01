<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Companies Table
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('logo')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->timestamps();
            });
        }

        // Departments Table
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Holidays Table
        if (!Schema::hasTable('holidays')) {
            Schema::create('holidays', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->date('date');
                $table->text('description')->nullable();
                $table->json('company_ids')->nullable(); // JSON list of applicable company IDs
                $table->timestamps();
            });
        }

        // Expenses Table
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->decimal('amount', 12, 2);
                $table->date('expense_date');
                $table->string('status')->default('pending'); // pending, approved, rejected
                $table->text('notes')->nullable();
                $table->string('receipt_photo')->nullable();
                $table->timestamps();
            });
        }

        // Update Users Table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'pan_document')) {
                $table->string('pan_document')->nullable();
            }
            if (!Schema::hasColumn('users', 'aadhaar_document')) {
                $table->string('aadhaar_document')->nullable();
            }
        });

        // Update Settings Table
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'company_logo')) {
                $table->string('company_logo')->nullable();
            }
        });

        // Update Attendance Punches Table
        Schema::table('attendance_punches', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_punches', 'remarks')) {
                $table->text('remarks')->nullable();
            }
        });

        // Update Tasks Table
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('companies');
    }
};
