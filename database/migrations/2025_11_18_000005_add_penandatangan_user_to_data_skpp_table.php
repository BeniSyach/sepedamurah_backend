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
        Schema::connection('oracle')->table('data_skpp', function (Blueprint $table) {
            $table->string('penandatangan_id', 50)->nullable();
            $table->string('penandatangan_nik', 30)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('oracle')->table('data_skpp', function (Blueprint $table) {
            $table->dropColumn([
                'penandatangan_id',
                'penandatangan_nik',
            ]);
        });
    }
};

