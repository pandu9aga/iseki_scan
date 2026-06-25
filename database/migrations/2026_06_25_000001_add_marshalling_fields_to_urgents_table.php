<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('urgents', function (Blueprint $table) {
            $table->boolean('Is_Marshalling')->nullable()->after('Id_Mistake');
            $table->string('Sequence_No_Record', 255)->nullable()->after('Is_Marshalling');
        });
    }

    public function down(): void
    {
        Schema::table('urgents', function (Blueprint $table) {
            $table->dropColumn('Is_Marshalling');
            $table->dropColumn('Sequence_No_Record');
        });
    }
};
