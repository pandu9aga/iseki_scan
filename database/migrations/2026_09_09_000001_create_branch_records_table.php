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
        Schema::create('branch_records', function (Blueprint $table) {
            $table->bigIncrements('Id_Branch_Record');
            $table->date('Day_Branch_Record');
            $table->time('Time_Branch_Record');
            $table->string('Code_Rack');
            $table->integer('Id_User');
            $table->dateTime('Updated_At_Branch_Record')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_records');
    }
};
