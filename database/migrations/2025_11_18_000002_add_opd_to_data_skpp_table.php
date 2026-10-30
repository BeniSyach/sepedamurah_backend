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
            $table->string('kd_opd1', 2)->nullable();
            $table->string('kd_opd2', 2)->nullable();
            $table->string('kd_opd3', 2)->nullable();
            $table->string('kd_opd4', 2)->nullable();
            $table->string('kd_opd5', 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('oracle')->table('data_skpp', function (Blueprint $table) {
            $table->dropColumn([
                'kd_opd1',
                'kd_opd2',
                'kd_opd3',
                'kd_opd4',
                'kd_opd5',
            ]);
        });
    }
};
