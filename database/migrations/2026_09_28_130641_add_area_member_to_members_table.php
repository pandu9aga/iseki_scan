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
        if (!Schema::hasColumn('members', 'Member_Area')) {
            Schema::table('members', function (Blueprint $table) {
                $table->enum('Member_Area', ['MOWER', 'LINE A', 'LINE B', 'TRANSMISI', 'ENGINE', 'MAINLINE', 'ASSY', 'INSPEKSI'])
                    ->nullable()
                    ->after('Status_Non_Active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('members', 'Member_Area')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('Member_Area');
            });
        }
    }
};
