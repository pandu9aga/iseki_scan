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
        Schema::create('mistakes', function (Blueprint $table) {
            $table->id('Id_Mistake');
            $table->unsignedBigInteger('Id_Request');
            $table->string('PIC');
            $table->string('Category_Mistake');
            $table->text('Manual_Category_Detail')->nullable();
            $table->date('Day_Mistake');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mistakes');
    }
};
