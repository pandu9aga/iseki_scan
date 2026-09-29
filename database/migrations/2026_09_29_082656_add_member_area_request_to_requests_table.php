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
        if (!Schema::hasColumn('requests', 'Member_Area_Request')) {
            Schema::table('requests', function (Blueprint $table) {
                $table->string('Member_Area_Request', 50)->nullable()->after('Area_Request');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('requests', 'Member_Area_Request')) {
            Schema::table('requests', function (Blueprint $table) {
                $table->dropColumn('Member_Area_Request');
            });
        }
    }
};
