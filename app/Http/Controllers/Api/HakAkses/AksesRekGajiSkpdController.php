<?php

namespace App\Http\Controllers\Api\HakAkses;

use App\Http\Controllers\Controller;
use App\Models\AksesRekGajiSkpdModel;
use App\Models\LaporanRekGajiSkpdModel;
use App\Models\SKPDModel;
use Illuminate\Http\Request;

class AksesRekGajiSkpdController extends Controller
{
    /**
     * List semua akses Rekonsiliasi Gaji SKPD
     */
    public function index(Request $request)
    {
        $data = AksesRekGajiSkpdModel::query()
            ->with(['rekonsiliasiGajiSkpd'])
            ->join('ref_opd', function ($join) {
                $join->on('akses_rek_gaji_skpd.kd_opd1', '=', 'ref_opd.kd_opd1')
                     ->on('akses_rek_gaji_skpd.kd_opd2', '=', 'ref_opd.kd_opd2')
                     ->on('akses_rek_gaji_skpd.kd_opd3', '=', 'ref_opd.kd_opd3')
                     ->on('akses_rek_gaji_skpd.kd_opd4', '=', 'ref_opd.kd_opd4')
                     ->on('akses_rek_gaji_skpd.kd_opd5', '=', 'ref_opd.kd_opd5');
            })
            ->whereNull('akses_rek_gaji_skpd.deleted_at')

            // ✅ FILTER TAHUN
            ->when($request->filled('tahun'), function ($q) use ($request) {
                $q->where('akses_rek_gaji_skpd.tahun', $request->tahun);
            })

            // 🔍 SEARCH
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = strtolower($request->search);

                $q->where(function ($sub) use ($search) {
                    $sub->whereRaw('LOWER(ref_opd.nm_opd) LIKE ?', ["%{$search}%"])
                        ->orWhereHas('rekonsiliasiGajiSkpd', function ($rek) use ($search) {
                            $rek->whereRaw('LOWER(nm_rekonsiliasi_gaji_skpd) LIKE ?', ["%{$search}%"]);
                        });
                });
            })
            ->select('akses_rek_gaji_skpd.*', 'ref_opd.nm_opd')
            ->get();

        // ==============================
        // GROUP BY OPD
        // ==============================
        $grouped = $data->groupBy(fn ($item) =>
            "{$item->kd_opd1}.{$item->kd_opd2}.{$item->kd_opd3}.{$item->kd_opd4}.{$item->kd_opd5}"
        );

        $result = [];

        foreach ($grouped as $kodeOpd => $items) {
            $first = $items->first();

            $skpd = SKPDModel::where([
                'kd_opd1' => $first->kd_opd1,
                'kd_opd2' => $first->kd_opd2,
                'kd_opd3' => $first->kd_opd3,
                'kd_opd4' => $first->kd_opd4,
                'kd_opd5' => $first->kd_opd5,
            ])->first();

            $result[] = [
                'kode_opd' => $kodeOpd,
                'kd_opd1' => $first->kd_opd1,
                'kd_opd2' => $first->kd_opd2,
                'kd_opd3' => $first->kd_opd3,
                'kd_opd4' => $first->kd_opd4,
                'kd_opd5' => $first->kd_opd5,
                'tahun' => $first->tahun,
                'nama_opd' => $skpd?->nm_opd ?? 'Tidak ditemukan',
                'rekonsiliasi' => $items->map(fn ($x) => [
                    'id' => $x->rekonsiliasiGajiSkpd->id,
                    'nm_rekonsiliasi_gaji_skpd' => $x->rekonsiliasiGajiSkpd->nm_rekonsiliasi_gaji_skpd,
                ])->values(),
            ];
        }

        // ==============================
        // 📄 Manual Pagination
        // ==============================
        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);

        $paginated = collect($result)
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Data akses Rekonsiliasi Gaji SKPD berhasil diambil',
            'data' => $paginated,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => count($result),
                'last_page' => ceil(count($result) / $perPage),
            ],
        ]);
    }

    /**
     * Simpan akses Rekonsiliasi Gaji SKPD
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_opd1' => 'required|string',
            'kd_opd2' => 'required|string',
            'kd_opd3' => 'required|string',
            'kd_opd4' => 'required|string',
            'kd_opd5' => 'required|string',
            'tahun'   => 'required|numeric',

            'rekGajiIds' => 'required|array|min:1',
            'rekGajiIds.*' => 'exists:ref_rekonsiliasi_gaji_skpd,id',
        ]);

        $inserted = [];

        foreach ($validated['rekGajiIds'] as $rekId) {
            $inserted[] = AksesRekGajiSkpdModel::create([
                'kd_opd1' => $validated['kd_opd1'],
                'kd_opd2' => $validated['kd_opd2'],
                'kd_opd3' => $validated['kd_opd3'],
                'kd_opd4' => $validated['kd_opd4'],
                'kd_opd5' => $validated['kd_opd5'],
                'tahun' => $validated['tahun'],
                'rek_gaji_id' => $rekId,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Akses Rekonsiliasi Gaji SKPD berhasil ditambahkan',
            'data' => $inserted,
        ]);
    }

    /**
     * Update akses Rekonsiliasi Gaji SKPD
     */
    public function update(Request $request, $kd1, $kd2, $kd3, $kd4, $kd5, $tahun)
    {
        $aksesLama = AksesRekGajiSkpdModel::where([
            'kd_opd1' => $kd1,
            'kd_opd2' => $kd2,
            'kd_opd3' => $kd3,
            'kd_opd4' => $kd4,
            'kd_opd5' => $kd5,
            'tahun'   => $tahun,
        ])->whereNull('deleted_at')->get();

        if ($aksesLama->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'rekGajiIds' => 'required|array|min:1',
            'rekGajiIds.*' => 'exists:ref_rekonsiliasi_gaji_skpd,id',
        ]);

        // soft delete lama
        AksesRekGajiSkpdModel::where([
            'kd_opd1' => $kd1,
            'kd_opd2' => $kd2,
            'kd_opd3' => $kd3,
            'kd_opd4' => $kd4,
            'kd_opd5' => $kd5,
            'tahun'   => $tahun,
        ])->update(['deleted_at' => now()]);

        $inserted = [];
        foreach ($validated['rekGajiIds'] as $rekId) {
            $inserted[] = AksesRekGajiSkpdModel::create([
                'kd_opd1' => $kd1,
                'kd_opd2' => $kd2,
                'kd_opd3' => $kd3,
                'kd_opd4' => $kd4,
                'kd_opd5' => $kd5,
                'tahun' => $tahun,
                'rek_gaji_id' => $rekId,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Akses Rekonsiliasi Gaji SKPD berhasil diperbarui',
            'data' => $inserted,
        ]);
    }

    /**
     * Soft delete akses Rekonsiliasi Gaji SKPD
     */
    public function destroy($kd1, $kd2, $kd3, $kd4, $kd5, $tahun)
    {
        AksesRekGajiSkpdModel::where([
            'kd_opd1' => $kd1,
            'kd_opd2' => $kd2,
            'kd_opd3' => $kd3,
            'kd_opd4' => $kd4,
            'kd_opd5' => $kd5,
            'tahun'   => $tahun,
        ])->update(['deleted_at' => now()]);

        return response()->json([
            'status' => true,
            'message' => "Akses Rekonsiliasi Gaji SKPD tahun {$tahun} berhasil dihapus",
        ]);
    }

    public function cek(Request $request)
    {
        // =============== VALIDASI ===============
        $tahun = $request->tahun;
        if (!$tahun) {
            return response()->json([
                'status' => false,
                'message' => 'Parameter tahun wajib diisi'
            ], 400);
        }

        // =============== 1. AMBIL AKSES GAJI SKPD ===============
        $aksesQuery = AksesRekGajiSkpdModel::with('rekonsiliasiGajiSkpd')
            ->where('tahun', $tahun)
            ->whereNull('deleted_at');

        foreach (['kd_opd1','kd_opd2','kd_opd3','kd_opd4','kd_opd5'] as $opd) {
            if ($request->$opd !== null) {
                $aksesQuery->where($opd, $request->$opd);
            }
        }

        $akses = $aksesQuery->get();

        if ($akses->isEmpty()) {
            return response()->json([
                'status' => true,
                'status_laporan_memenuhi' => true,
                'message' => 'Akses Rekonsiliasi Gaji SKPD tidak ditemukan',
                'data' => [],
                'kurang_upload' => [],
            ]);
        }

        // =============== 2. AMBIL LAPORAN GAJI SKPD ===============
        $laporanQuery = LaporanRekGajiSkpdModel::where('tahun', $tahun)
            ->whereNotNull('diterima');

        foreach (['kd_opd1','kd_opd2','kd_opd3','kd_opd4','kd_opd5'] as $opd) {
            if ($request->$opd !== null) {
                $laporanQuery->where($opd, $request->$opd);
            }
        }

        $laporan = $laporanQuery->get();

        // Index laporan berdasarkan rek_gaji_id
        $laporanIndex = $laporan->keyBy('rek_gaji_id');

        // =============== 3. CEK SATU PER SATU ===============
        $hasil = [];
        $kurangUpload = [];

        foreach ($akses as $a) {
            $ada = $laporanIndex->get($a->rek_gaji_id);

            $hasil[] = [
                'akses_id'       => $a->id,
                'opd'            => "{$a->kd_opd1}.{$a->kd_opd2}.{$a->kd_opd3}.{$a->kd_opd4}.{$a->kd_opd5}",
                'rek_gaji_id'    => $a->rek_gaji_id,
                'nama_rek_gaji'  => $a->rekonsiliasiGajiSkpd->nm_rekonsiliasi_gaji_skpd ?? null,
                'status_laporan' => $ada ? true : false,
                'laporan_data'   => $ada
            ];

            if (!$ada) {
                $kurangUpload[] = [
                    'rek_gaji_id' => $a->rek_gaji_id,
                    'nama'        => $a->rekonsiliasiGajiSkpd->nm_rekonsiliasi_gaji_skpd ?? 'Nama tidak tersedia',
                    'opd'         => "{$a->kd_opd1}.{$a->kd_opd2}.{$a->kd_opd3}.{$a->kd_opd4}.{$a->kd_opd5}",
                    'pesan'       => 'Laporan Rekonsiliasi Gaji SKPD belum diupload'
                ];
            }
        }

        // =============== 4. STATUS GLOBAL ===============
        $statusGlobal = count($kurangUpload) === 0;

        return response()->json([
            'status' => true,
            'status_laporan_memenuhi' => $statusGlobal,
            'data' => $hasil,
            'kurang_upload' => $kurangUpload,
        ]);
    }
}
