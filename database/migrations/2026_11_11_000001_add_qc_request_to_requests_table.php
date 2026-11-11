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
        if (! Schema::hasColumn('requests', 'Qc_Request')) {
            Schema::table('requests', function (Blueprint $table) {
                $table->timestamp('Qc_Request')->nullable()->after('Design_Changes_Request');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('requests', 'Qc_Request')) {
            Schema::table('requests', function (Blueprint $table) {
                $table->dropColumn('Qc_Request');
            });
        }
    }
};
