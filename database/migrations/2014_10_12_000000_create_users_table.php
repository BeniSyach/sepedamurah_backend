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
        // Ini hanya dokumentasi struktur tabel Oracle.
        // Jangan dijalankan di Oracle (bisa dibiarkan tidak di-migrate).
        Schema::create('USERS', function (Blueprint $table) {
            $table->id('id');
            $table->string('nik', 20)->nullable();
            $table->string('nip', 25)->nullable();
            $table->string('name', 128)->nullable();
            $table->string('email', 128);
            $table->string('no_hp', 20)->nullable();
            $table->char('kd_opd1', 2)->nullable();
            $table->char('kd_opd2', 2)->nullable();
            $table->char('kd_opd3', 2)->nullable();
            $table->char('kd_opd4', 2)->nullable();
            $table->char('kd_opd5', 2)->nullable();
            $table->string('image', 128);
            $table->string('password', 256);
            $table->integer('is_active')->default(0);
            $table->date('date_created')->nullable();
            $table->string('visualisasi_tte', 128)->nullable();
            $table->integer('deleted')->default(0);
            $table->string('chat_id', 225)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('USERS');
    }
};
