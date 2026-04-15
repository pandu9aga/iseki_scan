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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->integer('Id_Withdrawal', true, false)->length(11)->primary();
            $table->string('Name_Withdrawal')->nullable();
            $table->timestamp('Date_Withdrawal')->nullable();
            $table->string('Code_Item_Withdrawal')->nullable();
            $table->boolean('Oke_Withdrawal')->nullable();
            $table->integer('NIK_Withdrawal', false, false)->length(11)->nullable();
            $table->boolean('Oke_Receiving')->nullable();
            $table->timestamp('Date_Receiving')->nullable();
            $table->boolean('Finish_Receiving')->nullable();
            $table->timestamp('Date_Finish_Receiving')->nullable();
            $table->integer('NIK_Return', false, false)->length(11)->nullable();
            $table->string('Code_Rack_Return')->nullable();
            $table->timestamp('Date_Return')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
