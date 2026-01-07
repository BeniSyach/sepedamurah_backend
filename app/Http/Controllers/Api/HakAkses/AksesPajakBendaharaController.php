<?php

namespace App\Http\Controllers\Api\HakAkses;

use App\Http\Controllers\Controller;
use App\Models\AksesPajakBendaharaModel;
use App\Models\LaporanPajakBendaharaModel;
use App\Models\SKPDModel;
use Illuminate\Http\Request;

class AksesPajakBendaharaController extends Controller
{
    /**
     * List akses pajak bendahara (group per OPD)
     */
    public function index(Request $request)
    {
        $data = AksesPajakBendaharaModel::query()
            ->with('refPajakBendahara')
            ->join('ref_opd', function ($join) {
                $join->on('akses_pajak_bendahara.kd_opd1', '=', 'ref_opd.kd_opd1')
                    ->on('akses_pajak_bendahara.kd_opd2', '=', 'ref_opd.kd_opd2')
                    ->on('akses_pajak_bendahara.kd_opd3', '=', 'ref_opd.kd_opd3')
                    ->on('akses_pajak_bendahara.kd_opd4', '=', 'ref_opd.kd_opd4')
                    ->on('akses_pajak_bendahara.kd_opd5', '=', 'ref_opd.kd_opd5');
            })
            ->whereNull('akses_pajak_bendahara.deleted_at')

            // ✅ FILTER TAHUN JIKA ADA
            ->when($request->filled('tahun'), function ($q) use ($request) {
                $q->where('akses_pajak_bendahara.tahun', $request->tahun);
            })

            ->when($request->filled('search'), function ($q) use ($request) {
                $search = strtolower($request->search);

                $q->where(function ($sub) use ($search) {
                    $sub->whereRaw('LOWER(ref_opd.nm_opd) LIKE ?', ["%{$search}%"])
                        ->orWhereHas('refPajakBendahara', function ($pajak) use ($search) {
                            $pajak->whereRaw('LOWER(nm_pajak_bendahara) LIKE ?', ["%{$search}%"]);
                        });
                });
            })
            ->select('akses_pajak_bendahara.*', 'ref_opd.nm_opd')
            ->get();

        // GROUP BY OPD
        $grouped = $data->groupBy(function ($item) {
            return implode('.', [
                $item->kd_opd1,
                $item->kd_opd2,
                $item->kd_opd3,
                $item->kd_opd4,
                $item->kd_opd5,
            ]);
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
                'kd_opd1' => $first->kd_opd1,
                'kd_opd2' => $first->kd_opd2,
                'kd_opd3' => $first->kd_opd3,
                'kd_opd4' => $first->kd_opd4,
                'kd_opd5' => $first->kd_opd5,
                'tahun'    => $first->tahun,
                'nama_opd' => $skpd?->nm_opd ?? '-',
                'pajak'    => $items->map(fn ($x) => [
                    'id'   => $x->refPajakBendahara?->id,
                    'nm_pajak_bendahara' => $x->refPajakBendahara?->nm_pajak_bendahara,
                ])->values(),
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
                'message' => 'Data akses Pajak Bendahara berhasil diambil',
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
     * Simpan akses pajak bendahara (MULTI pajak)
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
            'pajakIds'   => 'required|array|min:1',
            'pajakIds.*' => 'exists:ref_pajak_bendahara,id',
        ]);

        $inserted = [];

        foreach ($validated['pajakIds'] as $pajakId) {
            $inserted[] = AksesPajakBendaharaModel::create([
                'kd_opd1'      => $validated['kd_opd1'],
                'kd_opd2'      => $validated['kd_opd2'],
                'kd_opd3'      => $validated['kd_opd3'],
                'kd_opd4'      => $validated['kd_opd4'],
                'kd_opd5'      => $validated['kd_opd5'],
                'tahun'        => $validated['tahun'],
                'ref_pajak_id' => $pajakId,
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Akses pajak bendahara berhasil ditambahkan',
            'data'    => $inserted
        ]);
    }

    /**
     * Update akses pajak bendahara (hapus lama, insert ulang)
     */
    public function update(
        Request $request,
        $kd1, $kd2, $kd3, $kd4, $kd5, $tahun
    ) {
        $validated = $request->validate([
            'pajakIds'   => 'required|array|min:1',
            'pajakIds.*' => 'exists:ref_pajak_bendahara,id',
        ]);

        // Soft delete lama
        AksesPajakBendaharaModel::where([
            'kd_opd1' => $kd1,
            'kd_opd2' => $kd2,
            'kd_opd3' => $kd3,
            'kd_opd4' => $kd4,
            'kd_opd5' => $kd5,
            'tahun'   => $tahun,
        ])->update(['deleted_at' => now()]);

        // Insert ulang
        $inserted = [];
        foreach ($validated['pajakIds'] as $pajakId) {
            $inserted[] = AksesPajakBendaharaModel::create([
                'kd_opd1'      => $kd1,
                'kd_opd2'      => $kd2,
                'kd_opd3'      => $kd3,
                'kd_opd4'      => $kd4,
                'kd_opd5'      => $kd5,
                'tahun'        => $tahun,
                'ref_pajak_id' => $pajakId,
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Akses pajak bendahara berhasil diperbarui',
            'data'    => $inserted
        ]);
    }

    /**
     * Soft delete satu akses
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
    
        $data = AksesPajakBendaharaModel::where([
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
            'message' => 'Akses pajak bendahara tahun ' . $tahun . ' berhasil dihapus',
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

        // =============== 1. AMBIL AKSES PAJAK BERDASARKAN OPD ===============
        $aksesQuery = AksesPajakBendaharaModel::with('refPajakBendahara')
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
                'message' => 'Akses Pajak Bendahara tidak ditemukan untuk filter OPD tersebut',
                'status_laporan_memenuhi' => true,
                'data' => [],
                'kurang_upload' => []
            ]);
        }

        // =============== 2. AMBIL LAPORAN PAJAK (SUDAH DITERIMA) ===============
        $laporanQuery = LaporanPajakBendaharaModel::where('tahun', $tahun)
            ->whereNotNull('diterima');

        foreach (['kd_opd1','kd_opd2','kd_opd3','kd_opd4','kd_opd5'] as $opd) {
            if ($request->$opd !== null) {
                $laporanQuery->where($opd, $request->$opd);
            }
        }

        $laporan = $laporanQuery->get();

        // Index laporan berdasarkan ref_pajak_id
        $laporanIndex = $laporan->keyBy('ref_pajak_id');

        // =============== 3. CEK PAJAK SATU PER SATU ===============
        $hasil = [];
        $kurangUpload = [];

        foreach ($akses as $a) {
            $ada = $laporanIndex->get($a->ref_pajak_id);

            $hasil[] = [
                'akses_id'       => $a->id,
                'opd'            => "{$a->kd_opd1}.{$a->kd_opd2}.{$a->kd_opd3}.{$a->kd_opd4}.{$a->kd_opd5}",
                'ref_pajak_id'   => $a->ref_pajak_id,
                'nama_pajak'     => $a->refPajakBendahara?->nm_pajak_bendahara,
                'status_laporan' => $ada ? true : false,
                'laporan_data'   => $ada,
            ];

            if (!$ada) {
                $kurangUpload[] = [
                    'ref_pajak_id' => $a->ref_pajak_id,
                    'nama_pajak'   => $a->refPajakBendahara?->nm_pajak_bendahara ?? 'Nama tidak tersedia',
                    'opd'          => "{$a->kd_opd1}.{$a->kd_opd2}.{$a->kd_opd3}.{$a->kd_opd4}.{$a->kd_opd5}",
                    'pesan'        => 'Laporan Pajak Bendahara belum diupload atau belum diverifikasi'
                ];
            }
        }

        // =============== 4. STATUS GLOBAL ===============
        // TRUE  = semua pajak yang punya akses SUDAH ada laporannya
        // FALSE = masih ada pajak yang belum dilaporkan
        $statusGlobal = count($kurangUpload) === 0;

        return response()->json([
            'status' => true,
            'status_laporan_memenuhi' => $statusGlobal,
            'data' => $hasil,
            'kurang_upload' => $kurangUpload,
        ]);
    }
}
