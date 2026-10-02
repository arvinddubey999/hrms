<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'document_photo')) {
                $table->string('document_photo')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('leave_requests', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable()->after('status');
            }
        });

        Schema::table('advances', function (Blueprint $table) {
            if (!Schema::hasColumn('advances', 'reason')) {
                $table->text('reason')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('advances', 'repayment_months')) {
                $table->integer('repayment_months')->default(1)->after('reason');
            }
            if (!Schema::hasColumn('advances', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'document_photo')) {
                $table->dropColumn('document_photo');
            }
            if (Schema::hasColumn('leave_requests', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });

        Schema::table('advances', function (Blueprint $table) {
            if (Schema::hasColumn('advances', 'reason')) {
                $table->dropColumn('reason');
            }
            if (Schema::hasColumn('advances', 'repayment_months')) {
                $table->dropColumn('repayment_months');
            }
            if (Schema::hasColumn('advances', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });
    }
};
