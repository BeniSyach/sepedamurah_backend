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
            if (!Schema::connection('oracle')->hasColumn('data_skpp', 'pensiun_pokok')) {
                $table->double('pensiun_pokok', 15, 2)->nullable()->default(0);
            }
            if (!Schema::connection('oracle')->hasColumn('data_skpp', 'alasan_pensiun')) {
                $table->string('alasan_pensiun', 255)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('oracle')->table('data_skpp', function (Blueprint $table) {
            if (Schema::connection('oracle')->hasColumn('data_skpp', 'pensiun_pokok')) {
                $table->dropColumn('pensiun_pokok');
            }
            if (Schema::connection('oracle')->hasColumn('data_skpp', 'alasan_pensiun')) {
                $table->dropColumn('alasan_pensiun');
            }
        });
    }
};
