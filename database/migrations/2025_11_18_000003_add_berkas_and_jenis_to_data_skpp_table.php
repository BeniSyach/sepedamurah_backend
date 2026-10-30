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
            $table->string('jenis_skpp', 50)->default('mutasi')->nullable();
            $table->integer('nomor_urut')->nullable();
            $table->string('berkas_pengantar_opd', 500)->nullable();
            $table->string('berkas_sk', 500)->nullable();
            $table->string('berkas_daftar_gaji', 500)->nullable();
            $table->string('berkas_kk', 500)->nullable();
            $table->string('berkas_pendukung', 500)->nullable();
            $table->longText('verifikasi_berkas')->nullable();
            $table->string('catatan_perbaikan', 1000)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('oracle')->table('data_skpp', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_skpp',
                'nomor_urut',
                'berkas_pengantar_opd',
                'berkas_sk',
                'berkas_daftar_gaji',
                'berkas_kk',
                'berkas_pendukung',
                'verifikasi_berkas',
                'catatan_perbaikan',
            ]);
        });
    }
};
