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
        Schema::create('sum_mismatches', function (Blueprint $table) {
            $table->id('Id_Sum_Mismatch');
            $table->unsignedBigInteger('Id_Request')->nullable();
            $table->unsignedBigInteger('Id_Record')->nullable();
            $table->string('Code_Item_Rack', 50)->nullable();
            $table->string('Code_Rack', 50);
            $table->integer('Sum_Request');
            $table->integer('Received_Qty');
            $table->integer('Outstanding_Qty');
            $table->string('Status', 20)->default('open'); // open | ready | closed | cancelled
            $table->dateTime('Time_Mismatch')->nullable();
            $table->dateTime('Ready_Date')->nullable();
            $table->unsignedBigInteger('Reported_By')->nullable();
            $table->dateTime('Resolved_At')->nullable();
            $table->unsignedBigInteger('Updated_By')->nullable();
            $table->dateTime('Updated_At_Sum')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sum_mismatches');
    }
};