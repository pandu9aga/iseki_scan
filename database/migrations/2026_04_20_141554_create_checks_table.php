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
        Schema::create('checks', function (Blueprint $table) {
            $table->integer('Id_Checks', true); // AUTO_INCREMENT primary key
            $table->timestamp('Time_Check')->nullable();
            $table->string('Code_Rack', 20);
            $table->string('Code_Item_Rack', 20);
            $table->integer('Id_User');
            $table->integer('Status_Check');
            $table->tinyInteger('Is_User')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checks');
    }
};
