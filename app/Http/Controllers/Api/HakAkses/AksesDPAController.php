<?php

namespace App\Http\Controllers\Api\HakAkses;

use App\Http\Controllers\Controller;
use App\Models\AksesDPAModel;
use App\Models\SKPDModel;
use Illuminate\Http\Request;

class AksesDPAController extends Controller
{
    /**
     * List semua akses DPA (bisa difilter tahun atau SKPD).
     */
    public function index(Request $request)
    {
        // Ambil akses_dpa + relasi DPA
        $data = AksesDPAModel::with(['dpa'])
            ->whereNull('deleted_at')
            ->when($request->filled('tahun'), fn($q) => $q->where('tahun', $request->tahun))
            ->orderBy('kd_opd1')
            ->orderBy('kd_opd2')
            ->orderBy('kd_opd3')
            ->orderBy('kd_opd4')
            ->orderBy('kd_opd5')
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
                'nama_opd' => $skpd?->nm_opd ?? 'Tidak ditemukan',
                'dpa' => $items->map(fn($x) => [
                    'id' => $x->dpa->id,
                    'nm_dpa' => $x->dpa->nm_dpa
                ])->values()
            ];
        }
    
        // ==============================
        // 🔎 Search berdasarkan nama OPD atau nama DPA
        // ==============================
        if ($request->filled('search')) {
            $search = strtolower($request->search);
    
            $result = collect($result)->filter(function ($row) use ($search) {
                $namaOpd = strtolower($row['nm_opd']);
    
                $matchDpa = collect($row['dpa'])->contains(function ($dpa) use ($search) {
                    return str_contains(strtolower($dpa['nm_dpa']), $search);
                });
    
                return str_contains($namaOpd, $search) || $matchDpa;
            })->values();
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
    public function destroy($id)
    {
        $akses = AksesDPAModel::where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$akses) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $akses->delete();

        return response()->json([
            'status' => true,
            'message' => 'Akses DPA berhasil dihapus',
        ]);
    }
}
