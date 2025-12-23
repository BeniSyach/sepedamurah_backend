<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DatRekeningModel;

class DatRekeningController extends Controller
{
    // List semua data
    public function index(Request $request)
    {
        $query = DatRekeningModel::query();
    
        // Filter berdasarkan status_rek jika ada
        if ($request->has('status_rek')) {
            $query->where('status_rek', $request->status_rek);
        }
    
        // Filter berdasarkan tahun_rek jika ada
        if ($request->has('tahun_rek')) {
            $query->where('tahun_rek', $request->tahun_rek);
        }
        $query->orderBy('status_rek', 'desc');
    
        // Pagination: ambil dari query params atau default
        $perPage = $request->input('per_page', 10); // default 10
        $data = $query->paginate($perPage);
    
        return response()->json($data);
    }
     

    // Detail berdasarkan composite key
    public function show($tahun, $kd1, $kd2, $kd3, $kd4, $kd5 = null, $kd6 = null)
    {
        $item = DatRekeningModel::findByKeys($tahun, $kd1, $kd2, $kd3, $kd4, $kd5, $kd6);
        if (!$item) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json($item);
    }

    // Simpan data baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tahun_rek' => 'required|string|size:4',
            'kd_rek1' => 'required|string|size:1',
            'kd_rek2' => 'required|string|size:1',
            'kd_rek3' => 'required|string|size:1',
            'kd_rek4' => 'required|string|size:2',
            'kd_rek5' => 'nullable|string|size:2',
            'kd_rek6' => 'nullable|string',
            'nm_rekening' => 'nullable|string|max:300',
            'status_rek' => 'required|string|size:1',
        ]);

        try {
            $item = DatRekeningModel::create($validated);
            return response()->json($item, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update data
    public function update(
        Request $request,
        $tahun,
        $kd1,
        $kd2,
        $kd3,
        $kd4,
        $kd5 = null,
        $kd6 = null
    ) {
        $validated = $request->validate([
            'nm_rekening' => 'nullable|string|max:300',
            'status_rek' => 'nullable|in:0,1',
        ]);
    
        $query = DatRekeningModel::whereRaw('TRIM(tahun_rek) = ?', [trim($tahun)])
            ->whereRaw('TRIM(kd_rek1) = ?', [trim($kd1)])
            ->whereRaw('TRIM(kd_rek2) = ?', [trim($kd2)])
            ->whereRaw('TRIM(kd_rek3) = ?', [trim($kd3)])
            ->whereRaw('TRIM(kd_rek4) = ?', [trim($kd4)]);
    
        // kd_rek5
        if ($kd5 !== null && trim($kd5) !== '') {
            $query->whereRaw('TRIM(kd_rek5) = ?', [trim($kd5)]);
        } else {
            $query->where(function ($q) {
                $q->whereNull('kd_rek5')
                  ->orWhereRaw("TRIM(kd_rek5) = ''");
            });
        }
    
        // kd_rek6
        if ($kd6 !== null && trim($kd6) !== '') {
            $query->whereRaw('TRIM(kd_rek6) = ?', [trim($kd6)]);
        } else {
            $query->where(function ($q) {
                $q->whereNull('kd_rek6')
                  ->orWhereRaw("TRIM(kd_rek6) = ''");
            });
        }
    
        $updated = $query->update($validated);
    
        if ($updated === 0) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
    
        return response()->json(['message' => 'Data berhasil diperbarui']);
    }
    
    

    // Hapus data
    public function destroy(
        $tahun,
        $kd1,
        $kd2,
        $kd3,
        $kd4,
        $kd5 = null,
        $kd6 = null
    ) {
        $query = DatRekeningModel::whereRaw('TRIM(tahun_rek) = ?', [trim($tahun)])
            ->whereRaw('TRIM(kd_rek1) = ?', [trim($kd1)])
            ->whereRaw('TRIM(kd_rek2) = ?', [trim($kd2)])
            ->whereRaw('TRIM(kd_rek3) = ?', [trim($kd3)])
            ->whereRaw('TRIM(kd_rek4) = ?', [trim($kd4)]);
    
        // kd_rek5
        if ($kd5 !== null && trim($kd5) !== '') {
            $query->whereRaw('TRIM(kd_rek5) = ?', [trim($kd5)]);
        } else {
            $query->where(function ($q) {
                $q->whereNull('kd_rek5')
                  ->orWhereRaw("TRIM(kd_rek5) = ''");
            });
        }
    
        // kd_rek6
        if ($kd6 !== null && trim($kd6) !== '') {
            $query->whereRaw('TRIM(kd_rek6) = ?', [trim($kd6)]);
        } else {
            $query->where(function ($q) {
                $q->whereNull('kd_rek6')
                  ->orWhereRaw("TRIM(kd_rek6) = ''");
            });
        }
    
        $deleted = $query->delete();
    
        if ($deleted === 0) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
    
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
    
}
