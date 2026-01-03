<?php

namespace App\Http\Controllers\Api\HakAkses;

use App\Http\Controllers\Controller;
use App\Models\AksesAssetBendaharaModel;
use App\Models\LaporanAssetBendaharaModel;
use App\Models\SKPDModel;
use Illuminate\Http\Request;

class AksesAssetBendaharaController extends Controller
{
    /**
     * List semua akses Asset Bendahara
     */
    public function index(Request $request)
    {
        $data = AksesAssetBendaharaModel::query()
        ->with(['refAssetBendahara'])
        ->join('ref_opd', function ($join) {
            $join->on('akses_asset_bendahara.kd_opd1', '=', 'ref_opd.kd_opd1')
                 ->on('akses_asset_bendahara.kd_opd2', '=', 'ref_opd.kd_opd2')
                 ->on('akses_asset_bendahara.kd_opd3', '=', 'ref_opd.kd_opd3')
                 ->on('akses_asset_bendahara.kd_opd4', '=', 'ref_opd.kd_opd4')
                 ->on('akses_asset_bendahara.kd_opd5', '=', 'ref_opd.kd_opd5');
        })
        ->whereNull('akses_asset_bendahara.deleted_at')
    
        // ✅ FILTER TAHUN JIKA ADA
        ->when($request->filled('tahun'), function ($q) use ($request) {
            $q->where('akses_asset_bendahara.tahun', $request->tahun);
        })
    
        ->when($request->filled('search'), function ($q) use ($request) {
            $search = strtolower($request->search);
    
            $q->where(function ($sub) use ($search) {
                $sub->whereRaw('LOWER(ref_opd.nm_opd) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('refAssetBendahara', function ($asset) use ($search) {
                        $asset->whereRaw('LOWER(nm_asset_bendahara) LIKE ?', ["%{$search}%"]);
                    });
            });
        })
        ->select('akses_asset_bendahara.*', 'ref_opd.nm_opd')
        ->get();
    

        // GROUP BY OPD
        $grouped = $data->groupBy(function ($item) {
            return "{$item->kd_opd1}.{$item->kd_opd2}.{$item->kd_opd3}.{$item->kd_opd4}.{$item->kd_opd5}";
        });

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
                'kd_opd1'  => $first->kd_opd1,
                'kd_opd2'  => $first->kd_opd2,
                'kd_opd3'  => $first->kd_opd3,
                'kd_opd4'  => $first->kd_opd4,
                'kd_opd5'  => $first->kd_opd5,
                'tahun'    => $first->tahun,
                'nama_opd' => $skpd?->nm_opd ?? 'Tidak ditemukan',
                'asset'    => $items->map(fn ($x) => [
                    'id'       => $x->refAssetBendahara?->id,
                    'nm_asset_bendahara' => $x->refAssetBendahara?->nm_asset_bendahara,
                ])->values(),
            ];
        }

        // ==============================
        // Manual Pagination
        // ==============================
        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);

        $paginated = collect($result)
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return response()->json([
            'status'  => true,
            'message' => 'Data akses asset bendahara berhasil diambil',
            'data'    => $paginated,
            'meta'    => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => count($result),
                'last_page'    => ceil(count($result) / $perPage),
            ],
        ]);
    }

    /**
     * Simpan akses Asset Bendahara
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_opd1'   => 'required|string',
            'kd_opd2'   => 'required|string',
            'kd_opd3'   => 'required|string',
            'kd_opd4'   => 'required|string',
            'kd_opd5'   => 'required|string',
            'tahun'     => 'required|string',
            'assetIds'   => 'required|array|min:1',
            'assetIds.*' => 'exists:ref_asset_bendahara,id',
        ]);

        $inserted = [];

        foreach ($validated['assetIds'] as $assetId) {
            $inserted[] = AksesAssetBendaharaModel::create([
                'kd_opd1'      => $validated['kd_opd1'],
                'kd_opd2'      => $validated['kd_opd2'],
                'kd_opd3'      => $validated['kd_opd3'],
                'kd_opd4'      => $validated['kd_opd4'],
                'kd_opd5'      => $validated['kd_opd5'],
                'tahun'        => $validated['tahun'],
                'ref_asset_id' => $assetId,
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Akses asset bendahara berhasil ditambahkan',
            'data'    => $inserted,
        ]);
    }

    /**
     * Detail satu akses
     */
    public function show($id)
    {
        $data = AksesAssetBendaharaModel::with('refAssetBendahara')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail akses asset ditemukan',
            'data'    => $data,
        ]);
    }

    /**
     * Update akses Asset Bendahara (hapus lama, insert ulang)
     */
    public function update(Request $request, $kd1, $kd2, $kd3, $kd4, $kd5, $tahun)
    {
        $aksesLama = AksesAssetBendaharaModel::where([
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
            'assetIds'   => 'required|array|min:1',
            'assetIds.*' => 'exists:ref_asset_bendahara,id',
        ]);

        // soft delete lama
        AksesAssetBendaharaModel::where([
            'kd_opd1' => $kd1,
            'kd_opd2' => $kd2,
            'kd_opd3' => $kd3,
            'kd_opd4' => $kd4,
            'kd_opd5' => $kd5,
            'tahun'   => $tahun,
        ])->update(['deleted_at' => now()]);

        // insert ulang
        $inserted = [];
        foreach ($validated['assetIds'] as $assetId) {
            $inserted[] = AksesAssetBendaharaModel::create([
                'kd_opd1'      => $kd1,
                'kd_opd2'      => $kd2,
                'kd_opd3'      => $kd3,
                'kd_opd4'      => $kd4,
                'kd_opd5'      => $kd5,
                'tahun'        => $tahun,
                'ref_asset_id' => $assetId,
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Akses asset bendahara berhasil diperbarui',
            'data'    => $inserted,
        ]);
    }

    /**
     * Soft delete akses
     */
    public function destroy($id)
    {
        $data = AksesAssetBendaharaModel::where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $data->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Akses asset bendahara berhasil dihapus',
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

        // =============== 1. AMBIL AKSES ASSET BERDASARKAN OPD ===============
        $aksesQuery = AksesAssetBendaharaModel::with('refAssetBendahara')
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
                'message' => 'Akses Asset Bendahara tidak ditemukan untuk filter OPD tersebut',
                'data' => [],
                'kurang_upload' => [],
            ]);
        }

        // =============== 2. AMBIL LAPORAN ASSET (SUDAH DITERIMA) ===============
        $laporanQuery = LaporanAssetBendaharaModel::where('tahun', $tahun)
            ->whereNotNull('diterima');

        foreach (['kd_opd1','kd_opd2','kd_opd3','kd_opd4','kd_opd5'] as $opd) {
            if ($request->$opd !== null) {
                $laporanQuery->where($opd, $request->$opd);
            }
        }

        $laporan = $laporanQuery->get();

        // Index laporan berdasarkan ref_asset_id
        $laporanIndex = $laporan->keyBy('ref_asset_id');

        // =============== 3. CEK ASSET SATU PER SATU ===============
        $hasil = [];
        $kurangUpload = [];

        foreach ($akses as $a) {
            $ada = $laporanIndex->get($a->ref_asset_id);

            $hasil[] = [
                'akses_id'       => $a->id,
                'opd'            => "{$a->kd_opd1}.{$a->kd_opd2}.{$a->kd_opd3}.{$a->kd_opd4}.{$a->kd_opd5}",
                'ref_asset_id'   => $a->ref_asset_id,
                'nama_asset'     => $a->refAssetBendahara?->nm_asset_bendahara,
                'status_laporan' => $ada ? true : false,
                'laporan_data'   => $ada,
            ];

            if (!$ada) {
                $kurangUpload[] = [
                    'ref_asset_id' => $a->ref_asset_id,
                    'nama_asset'   => $a->refAssetBendahara?->nm_asset_bendahara ?? 'Nama tidak tersedia',
                    'opd'          => "{$a->kd_opd1}.{$a->kd_opd2}.{$a->kd_opd3}.{$a->kd_opd4}.{$a->kd_opd5}",
                    'pesan'        => 'Laporan Asset Bendahara belum diupload atau belum diverifikasi'
                ];
            }
        }

        // =============== 4. STATUS GLOBAL ===============
        // TRUE  = semua asset yang punya akses SUDAH ada laporannya
        // FALSE = masih ada asset yang belum dilaporkan
        $statusGlobal = count($kurangUpload) === 0;

        return response()->json([
            'status' => true,
            'status_laporan_memenuhi' => $statusGlobal,
            'data' => $hasil,
            'kurang_upload' => $kurangUpload,
        ]);
    }
}
