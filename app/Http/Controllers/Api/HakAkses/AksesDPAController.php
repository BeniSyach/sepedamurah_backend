<?php

namespace App\Http\Controllers\Api\HakAkses;

use App\Http\Controllers\Controller;
use App\Models\AksesDPAModel;
use App\Models\LaporanDPAModel;
use App\Models\SKPDModel;
use Illuminate\Http\Request;

class AksesDPAController extends Controller
{
    /**
     * List semua akses DPA (bisa difilter tahun atau SKPD).
     */
    public function index(Request $request)
    {
        $data = AksesDPAModel::query()
        ->with(['dpa'])
        ->join('ref_opd', function ($join) {
            $join->on('akses_dpa.kd_opd1', '=', 'ref_opd.kd_opd1')
                 ->on('akses_dpa.kd_opd2', '=', 'ref_opd.kd_opd2')
                 ->on('akses_dpa.kd_opd3', '=', 'ref_opd.kd_opd3')
                 ->on('akses_dpa.kd_opd4', '=', 'ref_opd.kd_opd4')
                 ->on('akses_dpa.kd_opd5', '=', 'ref_opd.kd_opd5');
        })
        ->whereNull('akses_dpa.deleted_at')
    
        // ✅ FILTER TAHUN
        ->when($request->filled('tahun'), function ($q) use ($request) {
            $q->whereHas('dpa', function ($dpa) use ($request) {
                $dpa->where('tahun', $request->tahun);
            });
        })
    
        // 🔍 SEARCH
        ->when($request->filled('search'), function ($q) use ($request) {
            $search = strtolower($request->search);
    
            $q->where(function ($sub) use ($search) {
                $sub->whereRaw('LOWER(ref_opd.nm_opd) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('dpa', function ($dpa) use ($search) {
                        $dpa->whereRaw('LOWER(nm_dpa) LIKE ?', ["%{$search}%"]);
                    });
            });
        })
        ->select('akses_dpa.*', 'ref_opd.nm_opd')
        ->get();
    
    
    
        // Group berdasarkan kode OPD
        $grouped = $data->groupBy(function ($item) {
            return $item->kd_opd1 . '.' . 
                   $item->kd_opd2 . '.' . 
                   $item->kd_opd3 . '.' . 
                   $item->kd_opd4 . '.' . 
                   $item->kd_opd5;
        });
    
        $result = [];
    
        foreach ($grouped as $kode_opd => $items) {

            $first = $items->first();
            // Ambil SKPD
            $skpd = SKPDModel::where('kd_opd1', $items->first()->kd_opd1)
                ->where('kd_opd2', $items->first()->kd_opd2)
                ->where('kd_opd3', $items->first()->kd_opd3)
                ->where('kd_opd4', $items->first()->kd_opd4)
                ->where('kd_opd5', $items->first()->kd_opd5)
                ->first();
    
            $result[] = [
                'kode_opd' => $kode_opd,
                'kd_opd1' => $first->kd_opd1,
                'kd_opd2' => $first->kd_opd2,
                'kd_opd3' => $first->kd_opd3,
                'kd_opd4' => $first->kd_opd4,
                'kd_opd5' => $first->kd_opd5,
                'tahun' => $first->tahun,
                'nama_opd' => $skpd?->nm_opd ?? 'Tidak ditemukan',
                'dpa' => $items->map(fn($x) => [
                    'id' => $x->dpa->id,
                    'nm_dpa' => $x->dpa->nm_dpa
                ])->values()
            ];
        }
    
        // ==============================
        // 📄 Manual pagination
        // ==============================
        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);
    
        $paginated = collect($result)
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();
    
        return response()->json([
            'status' => true,
            'message' => 'Data akses DPA berhasil diambil',
            'data' => $paginated,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => count($result),
                'last_page' => ceil(count($result) / $perPage)
            ]
        ]);
    }
    

    /**
     * Simpan akses DPA baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_opd1' => 'required|string',
            'kd_opd2' => 'required|string',
            'kd_opd3' => 'required|string',
            'kd_opd4' => 'required|string',
            'kd_opd5' => 'required|string',
            'tahun'   => 'required|string',
    
            // TERIMA ARRAY
            'dpaIds'  => 'required|array|min:1',
            'dpaIds.*' => 'string|exists:ref_dpa,id',
        ]);
    
        $inserted = [];
    
        foreach ($validated['dpaIds'] as $dpaId) {
            $inserted[] = AksesDPAModel::create([
                'kd_opd1' => $validated['kd_opd1'],
                'kd_opd2' => $validated['kd_opd2'],
                'kd_opd3' => $validated['kd_opd3'],
                'kd_opd4' => $validated['kd_opd4'],
                'kd_opd5' => $validated['kd_opd5'],
                'tahun'   => $validated['tahun'],
                'dpa_id'  => $dpaId, // ambil dari looping
            ]);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Akses DPA berhasil ditambahkan',
            'data' => $inserted
        ]);
    }
    

    /**
     * Ambil satu data akses DPA berdasarkan ID.
     */
    public function show($id)
    {
        $data = AksesDPAModel::with(['dpa'])
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
            'status' => true,
            'message' => 'Detail akses DPA ditemukan',
            'data' => $data,
        ]);
    }

    /**
     * Update akses DPA.
     */
    public function update(Request $request, $kd1, $kd2, $kd3, $kd4, $kd5, $tahun)
    {
        // Ambil semua akses lama berdasarkan SKPD + Tahun
        $aksesLama = AksesDPAModel::where([
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
    
        // VALIDASI REQUEST
        $validated = $request->validate([
            'kd_opd1' => 'required|string',
            'kd_opd2' => 'required|string',
            'kd_opd3' => 'required|string',
            'kd_opd4' => 'required|string',
            'kd_opd5' => 'required|string',
            'tahun'   => 'required|string',
            'dpaIds'  => 'required|array|min:1',
            'dpaIds.*' => 'string|exists:ref_dpa,id',
        ]);
    
        // Soft delete semua akses lama
        AksesDPAModel::where([
            'kd_opd1' => $kd1,
            'kd_opd2' => $kd2,
            'kd_opd3' => $kd3,
            'kd_opd4' => $kd4,
            'kd_opd5' => $kd5,
            'tahun'   => $tahun,
        ])->update(['deleted_at' => now()]);
    
        // Insert ulang
        $inserted = [];
        foreach ($validated['dpaIds'] as $dpa) {
            $inserted[] = AksesDPAModel::create([
                'kd_opd1' => $validated['kd_opd1'],
                'kd_opd2' => $validated['kd_opd2'],
                'kd_opd3' => $validated['kd_opd3'],
                'kd_opd4' => $validated['kd_opd4'],
                'kd_opd5' => $validated['kd_opd5'],
                'tahun'   => $validated['tahun'],
                'dpa_id'  => $dpa,
            ]);
        }
    
        return response()->json([
            'status'  => true,
            'message' => 'Akses DPA berhasil diperbarui',
            'data'    => $inserted
        ]);
    }
    

    /**
     * Soft delete akses DPA.
     */
    public function destroy($kd1, $kd2, $kd3, $kd4, $kd5, $tahun)
    {
        // validasi tahun
        if (!is_numeric($tahun) || strlen($tahun) !== 4) {
            return response()->json([
                'status' => false,
                'message' => 'Tahun tidak valid',
            ], 400);
        }
    
        $akses = AksesDPAModel::where([
            'kd_opd1' => $kd1,
            'kd_opd2' => $kd2,
            'kd_opd3' => $kd3,
            'kd_opd4' => $kd4,
            'kd_opd5' => $kd5,
            'tahun'   => $tahun,
        ])->first();
    
        if (!$akses) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        $akses->delete();
    
        return response()->json([
            'status' => true,
            'message' => 'Akses DPA tahun ' . $tahun . ' berhasil dihapus',
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
    
        // OPD
        $kd_opd1 = $request->kd_opd1;
        $kd_opd2 = $request->kd_opd2;
        $kd_opd3 = $request->kd_opd3;
        $kd_opd4 = $request->kd_opd4;
        $kd_opd5 = $request->kd_opd5;
    
        // =============== 1. AMBIL AKSES DPA BERDASARKAN OPD ===============
        $aksesQuery = AksesDPAModel::with('dpa')
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
                'message' => 'Akses DPA tidak ditemukan untuk filter OPD tersebut'
            ], 200);
        }
    
        // =============== 2. AMBIL LAPORAN DPA BERDASARKAN OPD ===============
        $laporanQuery = LaporanDPAModel::where('tahun', $tahun)->whereNotNull('diterima');
    
        foreach (['kd_opd1','kd_opd2','kd_opd3','kd_opd4','kd_opd5'] as $opd) {
            if ($request->$opd !== null) {
                $laporanQuery->where($opd, $request->$opd);
            }
        }
    
        $laporan = $laporanQuery->get();
    
        // Index laporan berdasarkan dpa_id
        $laporanIndex = $laporan->keyBy('dpa_id');
    
        // =============== 3. CEK DPA SATU PER SATU ===============
        $hasil = [];
        $kurangUpload = [];
    
        foreach ($akses as $a) {
            $ada = $laporanIndex->get($a->dpa_id);
    
            $hasil[] = [
                'akses_id'          => $a->id,
                'opd'               => "{$a->kd_opd1}.{$a->kd_opd2}.{$a->kd_opd3}.{$a->kd_opd4}.{$a->kd_opd5}",
                'dpa_id'            => $a->dpa_id,
                'nama_dpa'          => $a->dpa->nm_dpa ?? null,
                'status_laporan'    => $ada ? true : false,
                'laporan_data'      => $ada
            ];
    
            if (!$ada) {
                $kurangUpload[] = [
                    'dpa_id'    => $a->dpa_id,
                    'nama_dpa'  => $a->dpa->nm_dpa ?? 'Nama tidak tersedia',
                    'opd'       => "{$a->kd_opd1}.{$a->kd_opd2}.{$a->kd_opd3}.{$a->kd_opd4}.{$a->kd_opd5}",
                    'pesan'     => "Laporan DPA belum diupload."
                ];
            }
        }
    
        // =============== 4. STATUS GLOBAL ===============
        // TRUE = semua laporan memenuhi akses DPA
        // FALSE = ada laporan yang kurang
        $statusGlobal = count($kurangUpload) === 0;
    
        return response()->json([
            'status' => true,
            'status_laporan_memenuhi' => $statusGlobal,    // <==== STATUS GLOBAL
            'data' => $hasil,
            'kurang_upload' => $kurangUpload,
        ]);
    }
    
    
}
