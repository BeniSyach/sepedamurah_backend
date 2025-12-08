<?php

use App\Http\Controllers\Api\AlokasiDana\BesaranUPSKPDController;
use App\Http\Controllers\Api\AlokasiDana\PaguSumberDanaController;
use App\Http\Controllers\Api\AlokasiDana\RealisasiTransferSumberDanaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BerkasLainController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\DatRekeningController;
use App\Http\Controllers\Api\HakAkses\AksesDPAController;
use App\Http\Controllers\Api\HakAkses\AksesKuasaBudController;
use App\Http\Controllers\Api\HakAkses\AksesOperatorController;
use App\Http\Controllers\Api\HakAkses\BatasWaktuController;
use App\Http\Controllers\Api\HakAkses\UsersRoleController;
use App\Http\Controllers\Api\History\LogHapusUsersController;
use App\Http\Controllers\Api\History\LogTTEController;
use App\Http\Controllers\Api\Laporan\LaporanDaftarBelanjaPerSKPDController;
use App\Http\Controllers\Api\Laporan\LaporanRealisasiBelanjaController;
use App\Http\Controllers\Api\Laporan\LaporanRealisasiSumberDanaController;
use App\Http\Controllers\Api\LaporanFungsional\LaporanFungsionalController;
use App\Http\Controllers\Api\MasterData\BidangUrusanController;
use App\Http\Controllers\Api\MasterData\JenisBelanjaController;
use App\Http\Controllers\Api\MasterData\JenisSPMController;
use App\Http\Controllers\Api\MasterData\KategoriSPMController;
use App\Http\Controllers\Api\MasterData\KegiatanController;
use App\Http\Controllers\Api\MasterData\LevelRekeningController;
use App\Http\Controllers\Api\MasterData\PaguBelanjaController;
use App\Http\Controllers\Api\MasterData\PersetujuanController;
use App\Http\Controllers\Api\MasterData\ProgramController;
use App\Http\Controllers\Api\MasterData\RefDpaController;
use App\Http\Controllers\Api\MasterData\RekAkunController;
use App\Http\Controllers\Api\MasterData\RekeningController;
use App\Http\Controllers\Api\MasterData\RekJenisController;
use App\Http\Controllers\Api\MasterData\RekKelompokController;
use App\Http\Controllers\Api\MasterData\RekObjekController;
use App\Http\Controllers\Api\MasterData\RekRincianController;
use App\Http\Controllers\Api\MasterData\SKPDController;
use App\Http\Controllers\Api\MasterData\SubKegiatanController;
use App\Http\Controllers\Api\MasterData\SubRincianController;
use App\Http\Controllers\Api\MasterData\SumberDanaController;
use App\Http\Controllers\Api\MasterData\UrusanController;
use App\Http\Controllers\Api\PengembalianController;
use App\Http\Controllers\Api\SP2D\SP2DController;
use App\Http\Controllers\Api\SP2D\SP2DKirimController;
use App\Http\Controllers\Api\SP2D\SP2DRekeningController;
use App\Http\Controllers\Api\SP2D\SP2DSumberDanaController;
use App\Http\Controllers\Api\SPD\PermohonanSPDController;
use App\Http\Controllers\Api\SPD\SPDTerkirimController;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/login', [AuthController::class, 'login']);
Route::get('/dat-rekening', [DatRekeningController::class, 'index']);
Route::get('/master-data/master-skpd', [SKPDController::class, 'index']);
Route::get('/pengembalian', [PengembalianController::class, 'index']);
Route::get('/pengembalian/download', [PengembalianController::class, 'tabelPrint'])->name('pengembalian.print');
Route::post('/pengembalian', [PengembalianController::class, 'store']);
Route::get('/telegram/set-webhook', [TelegramBotController::class, 'setWebhook']);
Route::post('/telegram/webhook', [TelegramBotController::class, 'webhook'])->name('telegram.webhook');
Route::get('/verify-tte-sp2d/{id}', [SP2DKirimController::class, 'verify_tte']);
Route::get('/verify-tte-berkaslain/{id}', [BerkasLainController::class, 'verify_tte']);
Route::get('/verify-tte-fungsional/{id}', [LaporanFungsionalController::class, 'verify_tte']);

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/count-data',[ DashboardController::class, 'index']);
        Route::get('/data-berkas-masuk-sp2d',[ DashboardController::class, 'berkas_masuk_sp2d']);
        Route::get('/chart-sp2d-per-bulan',[ DashboardController::class, 'chartSp2dPerBulan']);
        Route::get('/check-fungsional',[ DashboardController::class, 'tableCheckFungsional']);
        Route::get('/count-fungsional',[ DashboardController::class, 'summary']);
    });

    // Master Data
    Route::prefix('master-data')->group(function () {
        
        Route::apiResource('/urusan', UrusanController::class);
        Route::get('/urusan-by-pagu-belanja',[ UrusanController::class, 'get_urusan_sp2d']);

        // bidang Urusan
        Route::get('/bidang-urusan/{kd_bu1}/{kd_bu2}', [BidangUrusanController::class, 'show']);
        Route::put('/bidang-urusan/{kd_bu1}/{kd_bu2}', [BidangUrusanController::class, 'update']);
        Route::delete('/bidang-urusan/{kd_bu1}/{kd_bu2}', [BidangUrusanController::class, 'destroy']);
        Route::apiResource('/bidang-urusan', BidangUrusanController::class)->only(['index', 'store']);
        Route::get('/bidang-urusan-by-pagu-belanja',[ BidangUrusanController::class, 'get_bidang_urusan_sp2d']);
        
        // Program
        Route::get('/program/{kd_prog1}/{kd_prog2}/{kd_prog3}', [ProgramController::class, 'show']);
        Route::put('/program/{kd_prog1}/{kd_prog2}/{kd_prog3}', [ProgramController::class, 'update']);
        Route::delete('/program/{kd_prog1}/{kd_prog2}/{kd_prog3}', [ProgramController::class, 'destroy']);
        Route::apiResource('/program', ProgramController::class)->only(['index', 'store']);
        Route::get('/program-by-pagu-belanja',[ ProgramController::class, 'get_program_sp2d']);

        // Kegiatan
        Route::get('/kegiatan/{kd_keg1}/{kd_keg2}/{kd_keg3}/{kd_keg4}/{kd_keg5}', [KegiatanController::class, 'show']);
        Route::put('/kegiatan/{kd_keg1}/{kd_keg2}/{kd_keg3}/{kd_keg4}/{kd_keg5}', [KegiatanController::class, 'update']);
        Route::delete('/kegiatan/{kd_keg1}/{kd_keg2}/{kd_keg3}/{kd_keg4}/{kd_keg5}', [KegiatanController::class, 'destroy']);
        Route::apiResource('/kegiatan', KegiatanController::class)->only(['index', 'store']);
        Route::get('/kegiatan-by-pagu-belanja', [KegiatanController::class, 'get_kegiatan_sp2d']);

        // Sub Kegiatan
        Route::get('/sub-kegiatan/{kd_subkeg1}/{kd_subkeg2}/{kd_subkeg3}/{kd_subkeg4}/{kd_subkeg5}/{kd_subkeg6}', [SubKegiatanController::class, 'show']);
        Route::put('/sub-kegiatan/{kd_subkeg1}/{kd_subkeg2}/{kd_subkeg3}/{kd_subkeg4}/{kd_subkeg5}/{kd_subkeg6}', [SubKegiatanController::class, 'update']);
        Route::delete('/sub-kegiatan/{kd_subkeg1}/{kd_subkeg2}/{kd_subkeg3}/{kd_subkeg4}/{kd_subkeg5}/{kd_subkeg6}', [SubKegiatanController::class, 'destroy']);
        Route::apiResource('/sub-kegiatan', SubKegiatanController::class)->only(['index', 'store']);
        Route::get('/sub-kegiatan-by-pagu-belanja', [SubKegiatanController::class, 'get_sub_kegiatan_sp2d']);

        // Rekening
        Route::get('/rekening/{kd_rekening1}/{kd_rekening2}/{kd_rekening3}/{kd_rekening4}/{kd_rekening5}/{kd_rekening6}', [RekeningController::class, 'show']);
        Route::put('/rekening/{kd_rekening1}/{kd_rekening2}/{kd_rekening3}/{kd_rekening4}/{kd_rekening5}/{kd_rekening6}', [RekeningController::class, 'update']);
        Route::delete('/rekening/{kd_rekening1}/{kd_rekening2}/{kd_rekening3}/{kd_rekening4}/{kd_rekening5}/{kd_rekening6}', [RekeningController::class, 'destroy']);
        Route::apiResource('/rekening', RekeningController::class)->only(['index', 'store']);
        Route::get('/rekening-by-pagu-belanja', [RekeningController::class, 'get_rekening_sp2d']);


        // Pagu Belanja
        Route::apiResource('/pagu-belanja', PaguBelanjaController::class);

        // Jenis SPM
        Route::apiResource('/jenis-spm', JenisSPMController::class);

        // Ceklis Kelengkapan Dokumen
        Route::apiResource('/ceklis-kelengkapan-dokumen', KategoriSPMController::class);

        // Persetujuan
        Route::apiResource('/persetujuan', PersetujuanController::class);

        // Jenis Belanja
        Route::get('/jenis-belanja/{kd_ref1}/{kd_ref2}/{kd_ref3}', [JenisBelanjaController::class, 'show']);
        Route::put('/jenis-belanja/{kd_ref1}/{kd_ref2}/{kd_ref3}', [JenisBelanjaController::class, 'update']);
        Route::delete('/jenis-belanja/{kd_ref1}/{kd_ref2}/{kd_ref3}', [JenisBelanjaController::class, 'destroy']);
        Route::apiResource('/jenis-belanja', JenisBelanjaController::class)->only(['index', 'store']);

        // Sumber Dana
        Route::get('/sumber-dana/{kd_ref1}/{kd_ref2}/{kd_ref3}/{kd_ref4}/{kd_ref5}/{kd_ref6}', [SumberDanaController::class, 'show']);
        Route::put('/sumber-dana/{kd_ref1}/{kd_ref2}/{kd_ref3}/{kd_ref4}/{kd_ref5}/{kd_ref6}', [SumberDanaController::class, 'update']);
        Route::delete('/sumber-dana/{kd_ref1}/{kd_ref2}/{kd_ref3}/{kd_ref4}/{kd_ref5}/{kd_ref6}', [SumberDanaController::class, 'destroy']);
        Route::apiResource('/sumber-dana', SumberDanaController::class)->only(['index', 'store']);

        // Master SKPD
        Route::get('/master-skpd/{kd_opd1}/{kd_opd2}/{kd_opd3}/{kd_opd4}/{kd_opd5}', [SKPDController::class, 'show']);
        Route::put('/master-skpd/{kd_opd1}/{kd_opd2}/{kd_opd3}/{kd_opd4}/{kd_opd5}', [SKPDController::class, 'update']);
        Route::delete('/master-skpd/{kd_opd1}/{kd_opd2}/{kd_opd3}/{kd_opd4}/{kd_opd5}', [SKPDController::class, 'destroy']);
        Route::post('/master-skpd', [SKPDController::class, 'store']);

        // Level Rekening
        Route::apiResource('/level-rekening', LevelRekeningController::class);

        // Rek Akun
        Route::apiResource('/rek-akun', RekAkunController::class);

        // Rek Kelompok
        Route::apiResource('/rek-kelompok', RekKelompokController::class);

        // Rek Jenis
        Route::apiResource('/rek-jenis', RekJenisController::class);

        // Rek Objek
        Route::apiResource('/rek-objek', RekObjekController::class);

        // Rek Rincian
        Route::apiResource('/rek-rincian', RekRincianController::class);

        // Sub Rincian
        Route::apiResource('/sub-rincian', SubRincianController::class);

        // Ref DPA
        Route::apiResource('/ref-dpa', RefDpaController::class);
    });

    // alokasi Dana
    Route::prefix('alokasi-dana')->group(function () {

        // Pagu Sumber Dana
        Route::get('/pagu-sumber-dana/{kd_ref1}/{kd_ref2}/{kd_ref3}/{kd_ref4}/{kd_ref5}/{kd_ref6}/{tahun}', [PaguSumberDanaController::class, 'show']);
        Route::put('/pagu-sumber-dana/{kd_ref1}/{kd_ref2}/{kd_ref3}/{kd_ref4}/{kd_ref5}/{kd_ref6}/{tahun}', [PaguSumberDanaController::class, 'update']);
        Route::delete('/pagu-sumber-dana/{kd_ref1}/{kd_ref2}/{kd_ref3}/{kd_ref4}/{kd_ref5}/{kd_ref6}/{tahun}', [PaguSumberDanaController::class, 'destroy']);
        Route::apiResource('/pagu-sumber-dana', PaguSumberDanaController::class)->only(['index', 'store']);

        // Besaran UP SKPD
        Route::get('/up-skpd/{kd_opd1}/{kd_opd2}/{kd_opd3}/{kd_opd4}/{kd_opd5}/{tahun}', [BesaranUPSKPDController::class, 'show']);
        Route::put('/up-skpd/{kd_opd1}/{kd_opd2}/{kd_opd3}/{kd_opd4}/{kd_opd5}/{tahun}', [BesaranUPSKPDController::class, 'update']);
        Route::delete('/up-skpd/{kd_opd1}/{kd_opd2}/{kd_opd3}/{kd_opd4}/{kd_opd5}/{tahun}', [BesaranUPSKPDController::class, 'destroy']);
        Route::apiResource('/up-skpd', BesaranUPSKPDController::class)->only(['index', 'store']);

        // Realisasi Transfer Sumber Dana
        Route::post('/sinkron-sumber-dana-pajak', [RealisasiTransferSumberDanaController::class, 'sumberDanaPajak']);

        Route::get('/detail-realisasi-transfer-sumber-dana', [RealisasiTransferSumberDanaController::class, 'detailTFSD']);

        Route::apiResource('/realisasi-transfer-sumber-dana', RealisasiTransferSumberDanaController::class);

    });

    // Users
    Route::apiResource('/users', UsersController::class);

    // Hak Akses
    Route::prefix('hak-akses')->group(function () {

        // User Role
        Route::apiResource('/users-role', UsersRoleController::class);

        // Akses Operator
        Route::apiResource('/akses-operator', AksesOperatorController::class);

        // Akses Kuasa BUD
        Route::apiResource('/akses-kuasa-bud', AksesKuasaBudController::class);

        // Batas Waktu
        Route::post('/batas-waktu/reset-all', [BatasWaktuController::class, 'resetAll']);
        Route::apiResource('/batas-waktu', BatasWaktuController::class);

         // Akses DPA
         Route::put('/akses-dpa-skpd/{kd1}/{kd2}/{kd3}/{kd4}/{kd5}/{tahun}', [AksesDPAController::class, 'update']);
         // hilangkan update bawaan
        Route::apiResource('/akses-dpa-skpd', AksesDPAController::class)
        ->except(['update']);
    });

    // History
    Route::prefix('history')->group(function () {

        // History TTE
        Route::apiResource('/log-tte', LogTTEController::class);

        // History Users Hapus
        Route::apiResource('/log-users-hapus', LogHapusUsersController::class);
    });

    Route::prefix('spd')->group(function () {

        Route::get('/permohonan-spd/download/{id}', [PermohonanSpdController::class, 'downloadBerkas'])->name('permohonan-spd.download');
        // Permohonan SPD
        Route::apiResource('/permohonan-spd', PermohonanSPDController::class);

        // SPD Terkirim
        Route::get('/spd-terkirim/download/{id}', [SPDTerkirimController::class, 'downloadBerkas'])->name('spd-terkirim.download');
        Route::get('/spd-terkirim/downloadSPDTTE/{id}', [SPDTerkirimController::class, 'downloadBerkasTTE'])->name('spd-terkirim.downloadspdTTE');
        Route::apiResource('/spd-terkirim', SPDTerkirimController::class);
        Route::post('/sign', [SPDTerkirimController::class, 'sign'])->name('spd-kirim.sign');

    });

    Route::prefix('sp2d')->group(function () {

        // Permohonan SP2D
        Route::get('/permohonan-sp2d/download/{id}', [SP2DController::class, 'downloadBerkas'])->name('permohonan-sp2d.download');
        Route::post('/terima-multi', [SP2DController::class, 'terimaMulti']);
        Route::post('/tolak-multi', [SP2DController::class, 'tolakMulti']);
        Route::post('/hapus-multi-sp2d', [SP2DController::class, 'HapusMulti']);
        Route::apiResource('/permohonan-sp2d', SP2DController::class);
  


        // SP2D Terkirim
        Route::apiResource('/sp2d-kirim', SP2DKirimController::class);
        Route::get('/sp2d-kirim/download/{id}', [SP2DKirimController::class, 'downloadBerkas'])->name('sp2d-kirim.download');
        Route::get('/sp2d-kirim/downloadTTE/{id}', [SP2DKirimController::class, 'downloadBerkasTTE'])->name('sp2d-kirim.downloadtte');
        Route::post('/sign', [SP2DKirimController::class, 'sign'])->name('sp2d-kirim.sign');

        // SP2D Sumber Dana
        Route::get('/sp2d-sumber-dana/check_sd', [SP2DSumberDanaController::class, 'check_sd'])->name('sp2d-sumber-dana.check_sd');
        Route::apiResource('/sp2d-sumber-dana', SP2DSumberDanaController::class); //skip

        // SP2D Rekening
        Route::apiResource('/sp2d-rekening', SP2DRekeningController::class); // skip
    });

    Route::prefix('laporan')->group(function () {

        // Laporan Fungsional
        Route::post('/sign-fungsional', [LaporanFungsionalController::class, 'sign'])->name('fungsional.sign');
        Route::post('/terima-multi', [LaporanFungsionalController::class, 'terimaMulti']);
        Route::post('/tolak-multi', [LaporanFungsionalController::class, 'tolakMulti']);
        Route::get('/cek-upload-fungsional', [LaporanFungsionalController::class, 'apiCekSudahUploadFungsional']);
        Route::apiResource('/fungsional', LaporanFungsionalController::class);
        Route::get('/fungsional/download/{id}', [LaporanFungsionalController::class, 'downloadBerkas'])->name('fungsional.download');
        Route::get('/fungsional/downloadTTE/{id}', [LaporanFungsionalController::class, 'downloadBerkasTTE'])->name('fungsional.downloadtte');

        // Laporan Realisasi Sumber Dana 
        Route::get('/realisasi-sumber-dana', [LaporanRealisasiSumberDanaController::class, 'index']);
        Route::get('/realisasi-sumber-dana/download/pdf/{tahun}', [LaporanRealisasiSumberDanaController::class, 'export_pdf'])->name('realisasi-sumber-dana.download_pdf');
        Route::get('/realisasi-sumber-dana/download/excel/{tahun}', [LaporanRealisasiSumberDanaController::class, 'export_excel'])->name('realisasi-sumber-dana.download_excel');

        // Laporan Realisasi Belanja
        Route::get('/realisasi-belanja', [LaporanRealisasiBelanjaController::class, 'index']);
        Route::get('/realisasi-belanja/download/pdf/{tahun}/{bulan}', [LaporanRealisasiBelanjaController::class, 'export_pdf'])->name('realisasi-belanja.download_pdf');
        Route::get('/realisasi-belanja/download/excel/{tahun}/{bulan}', [LaporanRealisasiBelanjaController::class, 'export_excel'])->name('realisasi-belanja.download_excel');

        // Laporan Daftar Belanja Per SKPD
        Route::get('/daftar-belanja-skpd', [LaporanDaftarBelanjaPerSKPDController::class, 'index']);
        Route::get('/detail/daftar-belanja-skpd', 
        [LaporanDaftarBelanjaPerSKPDController::class, 'detail_daftar_belanja_SKPD']);
        Route::get('/daftar-belanja-skpd/download/pdf', [LaporanDaftarBelanjaPerSKPDController::class, 'export_pdf'])->name('daftar-belanja-skpd.download_pdf');
        Route::get('/daftar-belanja-skpd/download/excel', [LaporanDaftarBelanjaPerSKPDController::class, 'export_excel'])->name('daftar-belanja-skpd.download_excel');
    });

    // Berkas Berkas Lain
    Route::post('/sign-berkas-lain', [BerkasLainController::class, 'sign'])->name('berkas-lain.sign');
    Route::apiResource('/berkas-lain', BerkasLainController::class);
    Route::get('/berkas-lain/download/{id}', [BerkasLainController::class, 'downloadBerkas'])->name('berkas-lain.download');
    Route::get('/berkas-lain/downloadTTE/{id}', [BerkasLainController::class, 'downloadBerkasTTE'])->name('berkas-lain.downloadtte');

    Route::apiResource('/pengembalian', PengembalianController::class)->except(['index', 'store']);
    Route::get('/pengembalian/rekap/pdf',[PengembalianController::class, 'rekapPengembalianPDF']);
    Route::get('/pengembalian/rekap/excel', [PengembalianController::class, 'rekapPengembalianExcel']);

    Route::apiResource('/dat-rekening', DatRekeningController::class)
        ->except(['index']);
});
