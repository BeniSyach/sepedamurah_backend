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
        Schema::connection('oracle')->create('data_skpp', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat', 100);
            $table->string('lembar', 50)->nullable();
            $table->string('pejabat_menerangkan', 255)->nullable();

            // Data Pegawai
            $table->string('nip', 30);
            $table->string('nama', 255);
            $table->string('tgl_lahir', 20)->nullable();
            $table->string('golongan', 50)->nullable();
            $table->string('unit_kerja', 255)->nullable();
            $table->string('jabatan', 255)->nullable();

            // Dasar SK & Mutasi
            $table->string('sk_dari', 255)->nullable();
            $table->string('nomor_sk', 100)->nullable();
            $table->string('tgl_sk', 20)->nullable();
            $table->string('tmt_sk', 20)->nullable();
            $table->string('mutasi_ke', 255)->nullable();
            $table->string('gaji_terakhir_bulan', 50)->nullable();

            // A. Penerimaan (16 Pos)
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('tunj_istri_suami', 15, 2)->default(0);
            $table->decimal('tunj_anak', 15, 2)->default(0);
            $table->decimal('tunj_perb_penghasilan', 15, 2)->default(0);
            $table->decimal('tunj_struktural', 15, 2)->default(0);
            $table->decimal('tunj_fungsional', 15, 2)->default(0);
            $table->decimal('tunj_beras', 15, 2)->default(0);
            $table->decimal('tunj_umum', 15, 2)->default(0);
            $table->decimal('tunj_fungsi_khusus', 15, 2)->default(0);
            $table->decimal('tunj_kemahalan_daerah', 15, 2)->default(0);
            $table->decimal('tunj_daerah_terpencil', 15, 2)->default(0);
            $table->decimal('tunj_pajak', 15, 2)->default(0);
            $table->decimal('tunj_kesehatan', 15, 2)->default(0);
            $table->decimal('tunj_jkk', 15, 2)->default(0);
            $table->decimal('tunj_jkm', 15, 2)->default(0);
            $table->decimal('pembulatan', 15, 2)->default(0);
            $table->decimal('jumlah_kotor', 15, 2)->default(0);

            // B. Potongan (11 Pos)
            $table->decimal('pot_iwp_1', 15, 2)->default(0);
            $table->decimal('pot_iwp_8', 15, 2)->default(0);
            $table->decimal('pot_askes', 15, 2)->default(0);
            $table->decimal('pot_pajak', 15, 2)->default(0);
            $table->decimal('pot_bulog', 15, 2)->default(0);
            $table->decimal('pot_taperum', 15, 2)->default(0);
            $table->decimal('iuran_korpri', 15, 2)->default(0);
            $table->decimal('pot_sewa_rumah', 15, 2)->default(0);
            $table->decimal('pot_hutang', 15, 2)->default(0);
            $table->decimal('pot_jkk', 15, 2)->default(0);
            $table->decimal('pot_jkm', 15, 2)->default(0);
            $table->decimal('jumlah_potongan', 15, 2)->default(0);

            // Jumlah Bersih
            $table->decimal('jumlah_bersih', 15, 2)->default(0);

            // Tanggungan & Hutang
            $table->longText('tanggungan')->nullable(); // JSON data
            $table->decimal('jumlah_hutang', 15, 2)->default(0);
            $table->string('keterangan_hutang', 255)->nullable();

            // Penandatangan
            $table->string('tempat_penetapan', 100)->nullable();
            $table->string('tgl_penetapan', 20)->nullable();
            $table->string('jabatan_penandatangan', 255)->nullable();
            $table->string('nama_penandatangan', 255)->nullable();
            $table->string('nip_penandatangan', 50)->nullable();

            // Tembusan & Catatan
            $table->longText('tembusan')->nullable(); // JSON data
            $table->text('catatan')->nullable();

            // Status & Relasi
            $table->string('status', 50)->default('Draft');
            $table->unsignedBigInteger('users_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('oracle')->dropIfExists('data_skpp');
    }
};
