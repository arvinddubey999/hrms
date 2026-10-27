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
        if (Schema::hasTable('task_replies') && !Schema::hasColumn('task_replies', 'attachments')) {
            Schema::table('task_replies', function (Blueprint $table) {
                $table->json('attachments')->nullable()->after('attachment');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('task_replies') && Schema::hasColumn('task_replies', 'attachments')) {
            Schema::table('task_replies', function (Blueprint $table) {
                $table->dropColumn('attachments');
            });
        }
    }
};
