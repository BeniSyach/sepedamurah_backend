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
            $table->string('alasan_tolak', 1000)->nullable();
            $table->string('tgl_diterima', 50)->nullable();
            $table->string('tgl_ditolak', 50)->nullable();
            $table->string('operator_id', 50)->nullable();
            $table->string('operator_nama', 255)->nullable();
            $table->string('status_tte', 50)->default('Belum TTE');
            $table->string('berkas_tte', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('oracle')->table('data_skpp', function (Blueprint $table) {
            $table->dropColumn([
                'alasan_tolak',
                'tgl_diterima',
                'tgl_ditolak',
                'operator_id',
                'operator_nama',
                'status_tte',
                'berkas_tte',
            ]);
        });
    }
};
