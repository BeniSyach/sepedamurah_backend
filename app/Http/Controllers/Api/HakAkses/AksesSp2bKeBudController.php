<?php

namespace App\Http\Controllers\Api\HakAkses;

use App\Http\Controllers\Controller;
use App\Models\AksesSp2bKeBudModel;
use App\Models\LaporanSp2bKeBudModel;
use App\Models\SKPDModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AksesSp2bKeBudController extends Controller
{
    /**
     * List semua akses SP2B ke BUD
     */
    public function index(Request $request)
    {
        $data = AksesSp2bKeBudModel::query()
        ->with(['refSp2bKeBud'])
        ->join('ref_opd', function ($join) {
            $join->on('akses_sp2b_ke_bud.kd_opd1', '=', 'ref_opd.kd_opd1')
                 ->on('akses_sp2b_ke_bud.kd_opd2', '=', 'ref_opd.kd_opd2')
                 ->on('akses_sp2b_ke_bud.kd_opd3', '=', 'ref_opd.kd_opd3')
                 ->on('akses_sp2b_ke_bud.kd_opd4', '=', 'ref_opd.kd_opd4')
                 ->on('akses_sp2b_ke_bud.kd_opd5', '=', 'ref_opd.kd_opd5');
        })
        ->whereNull('akses_sp2b_ke_bud.deleted_at')
    
        // ✅ FILTER TAHUN JIKA ADA
        ->when($request->filled('tahun'), function ($q) use ($request) {
            $q->where('akses_sp2b_ke_bud.tahun', $request->tahun);
        })
    
        ->when($request->filled('search'), function ($q) use ($request) {
            $search = strtolower($request->search);
    
            $q->where(function ($sub) use ($search) {
                $sub->whereRaw('LOWER(ref_opd.nm_opd) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('refSp2bKeBud', function ($sp2b) use ($search) {
                        $sp2b->whereRaw('LOWER(nm_sp2b_ke_bud) LIKE ?', ["%{$search}%"]);
                    });
            });
        })
        ->select('akses_sp2b_ke_bud.*', 'ref_opd.nm_opd')
        ->get();    

        // ==============================
        // GROUP BY OPD
        // ==============================
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
                'sp2b'     => $items->map(fn ($x) => [
                    'id'       => $x->refSp2bKeBud?->id,
                    'nm_sp2b_ke_bud'  => $x->refSp2bKeBud?->nm_sp2b_ke_bud,
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
            'message' => 'Data akses SP2B ke BUD berhasil diambil',
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
     * Simpan akses SP2B ke BUD
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tahun' => 'required|string',
    
            'sp2bIds'   => 'required|array|min:1',
            'sp2bIds.*' => 'exists:ref_sp2b_ke_bud,id',
    
            'opd' => 'required|array|min:1',
            'opd.*.kd_opd1' => 'required|string',
            'opd.*.kd_opd2' => 'required|string',
            'opd.*.kd_opd3' => 'required|string',
            'opd.*.kd_opd4' => 'required|string',
            'opd.*.kd_opd5' => 'required|string',
        ]);
    
        $inserted = [];
    
        DB::transaction(function () use ($validated, &$inserted) {
    
            foreach ($validated['opd'] as $opd) {
                foreach ($validated['sp2bIds'] as $sp2bId) {
    
                    $query = [
                        'kd_opd1' => $opd['kd_opd1'],
                        'kd_opd2' => $opd['kd_opd2'],
                        'kd_opd3' => $opd['kd_opd3'],
                        'kd_opd4' => $opd['kd_opd4'],
                        'kd_opd5' => $opd['kd_opd5'],
                        'tahun'   => $validated['tahun'],
                        'ref_sp2b_ke_bud_id' => $sp2bId,
                    ];
    
                    // ⛔ sudah ada & aktif → skip
                    $exists = AksesSp2bKeBudModel::where($query)
                        ->whereNull('deleted_at')
                        ->exists();
    
                    if ($exists) {
                        continue;
                    }
    
                    // ♻️ pernah soft delete → restore
                    $softDeleted = AksesSp2bKeBudModel::where($query)
                        ->whereNotNull('deleted_at')
                        ->first();
    
                    if ($softDeleted) {
                        $softDeleted->update(['deleted_at' => null]);
                        $inserted[] = $softDeleted;
                        continue;
                    }
    
                    // ➕ benar-benar baru
                    $inserted[] = AksesSp2bKeBudModel::create($query);
                }
            }
        });
    
        return response()->json([
            'status'  => true,
            'message' => 'Akses SP2B ke BUD berhasil ditambahkan',
            'data'    => $inserted,
        ]);
    }
    

    /**
     * Detail satu akses
     */
    public function show($id)
    {
        $data = AksesSp2bKeBudModel::with('refSp2bKeBud')
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
            'message' => 'Detail akses SP2B ditemukan',
            'data'    => $data,
        ]);
    }

    /**
     * Update akses SP2B ke BUD (hapus lama, insert ulang)
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'tahun' => 'required|string',
    
            'sp2bIds'   => 'required|array|min:1',
            'sp2bIds.*' => 'exists:ref_sp2b_ke_bud,id',
    
            'opd' => 'required|array|min:1',
            'opd.*.kd_opd1' => 'required|string',
            'opd.*.kd_opd2' => 'required|string',
            'opd.*.kd_opd3' => 'required|string',
            'opd.*.kd_opd4' => 'required|string',
            'opd.*.kd_opd5' => 'required|string',
        ]);
    
        $inserted = [];
    
        DB::transaction(function () use ($validated, &$inserted) {
    
            foreach ($validated['opd'] as $opd) {
    
                // Ambil data lama per OPD
                $aksesLama = AksesSp2bKeBudModel::where([
                    'kd_opd1' => $opd['kd_opd1'],
                    'kd_opd2' => $opd['kd_opd2'],
                    'kd_opd3' => $opd['kd_opd3'],
                    'kd_opd4' => $opd['kd_opd4'],
                    'kd_opd5' => $opd['kd_opd5'],
                    'tahun'   => $validated['tahun'],
                ])->whereNull('deleted_at')->exists();
    
                if (!$aksesLama) {
                    // kalau mau strict → throw exception
                    continue;
                }
    
                // 🔥 soft delete lama
                AksesSp2bKeBudModel::where([
                    'kd_opd1' => $opd['kd_opd1'],
                    'kd_opd2' => $opd['kd_opd2'],
                    'kd_opd3' => $opd['kd_opd3'],
                    'kd_opd4' => $opd['kd_opd4'],
                    'kd_opd5' => $opd['kd_opd5'],
                    'tahun'   => $validated['tahun'],
                ])->update(['deleted_at' => now()]);
    
                // ➕ insert ulang
                foreach ($validated['sp2bIds'] as $sp2bId) {
                    $inserted[] = AksesSp2bKeBudModel::create([
                        'kd_opd1'            => $opd['kd_opd1'],
                        'kd_opd2'            => $opd['kd_opd2'],
                        'kd_opd3'            => $opd['kd_opd3'],
                        'kd_opd4'            => $opd['kd_opd4'],
                        'kd_opd5'            => $opd['kd_opd5'],
                        'tahun'              => $validated['tahun'],
                        'ref_sp2b_ke_bud_id' => $sp2bId,
                    ]);
                }
            }
        });
    
        return response()->json([
            'status'  => true,
            'message' => 'Akses SP2B ke BUD berhasil diperbarui',
            'data'    => $inserted,
        ]);
    }
    

    /**
     * Soft delete akses
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
    
        $data = AksesSp2bKeBudModel::where([
            'kd_opd1' => $kd1,
            'kd_opd2' => $kd2,
            'kd_opd3' => $kd3,
            'kd_opd4' => $kd4,
            'kd_opd5' => $kd5,
            'tahun'   => $tahun,
        ])->first();
    
        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        $data->delete();
    
        return response()->json([
            'status'  => true,
            'message' => 'Akses SP2B ke BUD tahun ' . $tahun . ' berhasil dihapus',
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

        // =============== 1. AMBIL AKSES SP2B BERDASARKAN OPD ===============
        $aksesQuery = AksesSp2bKeBudModel::with('refSp2bKeBud')
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
                'message' => 'Akses SP2B ke BUD tidak ditemukan untuk filter OPD tersebut',
                'data' => [],
                'kurang_upload' => [],
            ]);
        }

        // =============== 2. AMBIL LAPORAN SP2B (SUDAH DITERIMA) ===============
        $laporanQuery = LaporanSp2bKeBudModel::where('tahun', $tahun)
            ->whereNotNull('diterima');

        foreach (['kd_opd1','kd_opd2','kd_opd3','kd_opd4','kd_opd5'] as $opd) {
            if ($request->$opd !== null) {
                $laporanQuery->where($opd, $request->$opd);
            }
        }

        $laporan = $laporanQuery->get();

        // Index laporan berdasarkan ref_sp2b_ke_bud_id
        $laporanIndex = $laporan->keyBy('ref_sp2b_ke_bud_id');

        // =============== 3. CEK SP2B SATU PER SATU ===============
        $hasil = [];
        $kurangUpload = [];

        foreach ($akses as $a) {
            $ada = $laporanIndex->get($a->ref_sp2b_ke_bud_id);

            $hasil[] = [
                'akses_id'        => $a->id,
                'opd'             => "{$a->kd_opd1}.{$a->kd_opd2}.{$a->kd_opd3}.{$a->kd_opd4}.{$a->kd_opd5}",
                'ref_sp2b_id'     => $a->ref_sp2b_ke_bud_id,
                'nama_sp2b'       => $a->refSp2bKeBud?->nm_sp2b_ke_bud,
                'status_laporan'  => $ada ? true : false,
                'laporan_data'    => $ada,
            ];

            if (!$ada) {
                $kurangUpload[] = [
                    'ref_sp2b_id' => $a->ref_sp2b_ke_bud_id,
                    'nama_sp2b'   => $a->refSp2bKeBud?->nm_sp2b_ke_bud ?? 'Nama tidak tersedia',
                    'opd'         => "{$a->kd_opd1}.{$a->kd_opd2}.{$a->kd_opd3}.{$a->kd_opd4}.{$a->kd_opd5}",
                    'pesan'       => 'Laporan SP2B ke BUD belum diupload atau belum diverifikasi'
                ];
            }
        }

        // =============== 4. STATUS GLOBAL ===============
        // TRUE  = semua SP2B yang punya akses SUDAH ada laporannya
        // FALSE = masih ada SP2B yang belum dilaporkan
        $statusGlobal = count($kurangUpload) === 0;

        return response()->json([
            'status' => true,
            'status_laporan_memenuhi' => $statusGlobal,
            'data' => $hasil,
            'kurang_upload' => $kurangUpload,
        ]);
    }
}
