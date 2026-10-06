<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update Companies Table
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'salary_calculation_days')) {
                $table->string('salary_calculation_days')->default('30'); // 30, 31, actual
            }
            if (!Schema::hasColumn('companies', 'pt_enabled')) {
                $table->boolean('pt_enabled')->default(true);
            }
            if (!Schema::hasColumn('companies', 'pt_threshold')) {
                $table->decimal('pt_threshold', 10, 2)->default(12000.00);
            }
            if (!Schema::hasColumn('companies', 'pt_amount')) {
                $table->decimal('pt_amount', 10, 2)->default(200.00);
            }
            if (!Schema::hasColumn('companies', 'code_prefix')) {
                $table->string('code_prefix')->nullable();
            }
        });

        // Update Holidays Table
        Schema::table('holidays', function (Blueprint $table) {
            if (!Schema::hasColumn('holidays', 'department_ids')) {
                $table->json('department_ids')->nullable();
            }
        });

        // Update Users Table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'birthday')) {
                $table->date('birthday')->nullable();
            }
            if (!Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable();
            }
        });

        // Update Tasks Table
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'repeat_type')) {
                $table->string('repeat_type')->default('none');
            }
            if (!Schema::hasColumn('tasks', 'attachment')) {
                $table->string('attachment')->nullable();
            }
        });

        // Create Task Attachments Table
        if (!Schema::hasTable('task_attachments')) {
            Schema::create('task_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('file_path');
                $table->string('file_name')->nullable();
                $table->timestamps();
            });
        }

        // Create Task Replies Table
        if (!Schema::hasTable('task_replies')) {
            Schema::create('task_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('message');
                $table->string('attachment')->nullable();
                $table->timestamps();
            });
        }

        // Create Task Histories Table (Immutable Audit Log)
        if (!Schema::hasTable('task_histories')) {
            Schema::create('task_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action'); // created, status_updated, reassigned, replied
                $table->text('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_histories');
        Schema::dropIfExists('task_replies');
        Schema::dropIfExists('task_attachments');
    }
};
