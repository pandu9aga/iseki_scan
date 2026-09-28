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
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->boolean('Is_Part_Ng')->default(false)->after('Date_Return');
            $table->dateTime('Date_Part_Ng')->nullable()->after('Is_Part_Ng');
            $table->integer('NIK_Return_Ng')->nullable()->after('Date_Part_Ng');
            $table->string('Code_Rack_Return_Ng')->nullable()->after('NIK_Return_Ng');
            $table->dateTime('Date_Return_Ng')->nullable()->after('Code_Rack_Return_Ng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn([
                'Is_Part_Ng',
                'Date_Part_Ng',
                'NIK_Return_Ng',
                'Code_Rack_Return_Ng',
                'Date_Return_Ng'
            ]);
        });
    }
};
