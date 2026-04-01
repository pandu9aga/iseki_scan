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
        Schema::create('wa_queues', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->string('group_id')->default('true_120363417614072057@g.us_3EB060ECE12DE31EBADF26_187381403668615@lid');
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_queues');
    }
};
