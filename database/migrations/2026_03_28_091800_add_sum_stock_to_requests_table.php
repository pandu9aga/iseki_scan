<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Sum_Stock column already exists in the requests table.
     */
    public function up(): void
    {
        // Column Sum_Stock already exists in requests table, no action needed
        if (!Schema::hasColumn('requests', 'Sum_Stock')) {
            Schema::table('requests', function (Blueprint $table) {
                $table->integer('Sum_Stock')->nullable()->after('Status_Validation');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'Sum_Stock')) {
                $table->dropColumn('Sum_Stock');
            }
        });
    }
};
