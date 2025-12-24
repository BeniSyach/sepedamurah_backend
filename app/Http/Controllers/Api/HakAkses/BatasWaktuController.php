<?php

namespace App\Http\Controllers\Api\HakAkses;

use App\Http\Controllers\Controller;
use App\Models\BatasWaktuModel;
use Illuminate\Http\Request;
use App\Http\Resources\BatasWaktuResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BatasWaktuController extends Controller
{
    /**
     * List Batas Waktu (pagination + search)
     */
    public function index(Request $request)
    {
        $query = BatasWaktuModel::query()
            ->whereNull('batas_waktu.deleted_at')
            ->leftJoin('ref_opd AS opd', function ($join) {
                $join->on('opd.kd_opd1', '=', 'batas_waktu.kd_opd1')
                    ->on('opd.kd_opd2', '=', 'batas_waktu.kd_opd2')
                    ->on('opd.kd_opd3', '=', 'batas_waktu.kd_opd3')
                    ->on('opd.kd_opd4', '=', 'batas_waktu.kd_opd4')
                    ->on('opd.kd_opd5', '=', 'batas_waktu.kd_opd5');
            })
            ->select('batas_waktu.*', 'opd.nm_opd');
    
        // --- SEARCH ---
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('batas_waktu.hari', 'like', "%{$search}%")
                  ->orWhere('batas_waktu.keterangan', 'like', "%{$search}%")
                  ->orWhere('opd.nm_opd', 'like', "%{$search}%");
            });
        }
    
        // --- FILTER OPD ---
        for ($i = 1; $i <= 5; $i++) {
            if ($v = $request->get("kd_opd{$i}")) {
                $query->where("batas_waktu.kd_opd{$i}", $v);
            }
        }
    
        $collection = $query->get();
    
        // Mapping hari
        $hariIndonesia = [
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
        ];
    
        // Urutan hari
        $urutanHari = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    
        // Dapatkan semua OPD unik
        $allOPD = $collection->map(function ($item) {
            return $item->kd_opd1.'-'.$item->kd_opd2.'-'.$item->kd_opd3.'-'.$item->kd_opd4.'-'.$item->kd_opd5;
        })->unique();
    
        $totalOPD = $allOPD->count();
    
        // GROUP BY: hari|waktu_awal|waktu_akhir|istirahat_awal|istirahat_akhir
        $groups = $collection->groupBy(function ($item) {
            return $item->hari.'|'.
                   $item->waktu_awal.'|'.
                   $item->waktu_akhir.'|'.
                   $item->istirahat_awal.'|'.
                   $item->istirahat_akhir;
        });
    
        // STEP 1: Cari waktu mayoritas per hari (yang digunakan paling banyak OPD)
        $majortasPerHari = [];
        
        foreach ($urutanHari as $hari) {
            $groupsForDay = $groups->filter(function ($group, $key) use ($hari) {
                return strpos($key, $hari.'|') === 0;
            });
    
            if ($groupsForDay->isEmpty()) {
                continue;
            }
    
            // Cari grup dengan OPD terbanyak di hari ini
            $maxCount = 0;
            $majorKey = null;
    
            foreach ($groupsForDay as $key => $group) {
                $opdCount = $group->map(function ($item) {
                    return $item->kd_opd1.'-'.$item->kd_opd2.'-'.$item->kd_opd3.'-'.$item->kd_opd4.'-'.$item->kd_opd5;
                })->unique()->count();
    
                if ($opdCount > $maxCount) {
                    $maxCount = $opdCount;
                    $majorKey = $key;
                }
            }
    
            $majortasPerHari[$hari] = $majorKey;
        }
    
        // STEP 2: Pisahkan data mayoritas dan yang berubah
        $waktu_all = collect();
        $waktu_changed = collect();
    
        foreach ($groups as $key => $group) {
            $parts = explode('|', $key);
            $hari = $parts[0];
    
            // Cek apakah grup ini adalah mayoritas di harinya
            $isMajoritas = isset($majortasPerHari[$hari]) && $majortasPerHari[$hari] === $key;
    
            if ($isMajoritas) {
                // Ini grup mayoritas (Seluruh SKPD)
                $first = $group->first();
                $first->hari_key = $hari;
                $waktu_all->push($first);
            } else {
                // Ini grup minoritas (OPD yang berubah)
                foreach ($group as $item) {
                    $item->hari_key = $hari;
                    $waktu_changed->push($item);
                }
            }
        }
    
        // STEP 3: Sort waktu_all berdasarkan urutan hari
        $waktu_all = $waktu_all->sortBy(function ($item) use ($urutanHari) {
            return array_search($item->hari_key, $urutanHari);
        })->values();
    
        // STEP 4: GROUP waktu_all berdasarkan waktu yang sama (untuk gabungkan hari)
        $groupedByTime = [];
        
        foreach ($waktu_all as $item) {
            $timeKey = $item->waktu_awal.'|'.$item->waktu_akhir.'|'.$item->istirahat_awal.'|'.$item->istirahat_akhir;
            
            if (!isset($groupedByTime[$timeKey])) {
                $groupedByTime[$timeKey] = [
                    'days' => [],
                    'item' => $item
                ];
            }
            
            $groupedByTime[$timeKey]['days'][] = $item->hari_key;
        }
    
        // STEP 5: Buat hasil akhir
        $result = collect();
    
        // Tambahkan data yang telah dikelompokkan (Seluruh SKPD)
        foreach ($groupedByTime as $data) {
            $days = $data['days'];
            $item = $data['item'];
    
            // Sort hari sesuai urutan
            usort($days, function($a, $b) use ($urutanHari) {
                return array_search($a, $urutanHari) - array_search($b, $urutanHari);
            });
    
            // Convert ke Indonesia
            $hariIndo = array_map(fn($h) => $hariIndonesia[$h] ?? $h, $days);
    
            $row = $item->replicate();
            $row->hari = implode(', ', $hariIndo);
            $row->all_opd = true;
            $row->nm_opd = 'Seluruh SKPD';
            
            $result->push($row);
        }
    
        // Tambahkan data yang berubah (OPD tertentu)
        $waktu_changed = $waktu_changed->sortBy(function ($item) use ($urutanHari) {
            return array_search($item->hari_key, $urutanHari);
        })->values();
    
        foreach ($waktu_changed as $item) {
            $row = $item->replicate();
            $row->hari = $hariIndonesia[$item->hari_key] ?? $item->hari_key;
            $row->all_opd = false;
            
            $result->push($row);
        }
    
        return BatasWaktuResource::collection($result);
    }
    /**
     * Simpan data baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hari' => 'required|string|max:50',
            'kd_opd1' => 'required|string|max:2',
            'kd_opd2' => 'required|string|max:2',
            'kd_opd3' => 'required|string|max:2',
            'kd_opd4' => 'required|string|max:2',
            'kd_opd5' => 'required|string|max:2',
            'waktu_awal' => 'required|date_format:H:i',
            'waktu_akhir' => 'required|date_format:H:i|after:waktu_awal',
            'keterangan' => 'nullable|string|max:255',
            'istirahat_awal' => 'required|date_format:H:i',
            'istirahat_akhir' => 'required|date_format:H:i|after:istirahat_awal',
        ]);
    
        try {
            // Cari record yang sudah ada
            $batas = BatasWaktuModel::where('hari', $validated['hari'])
                ->where('kd_opd1', $validated['kd_opd1'])
                ->where('kd_opd2', $validated['kd_opd2'])
                ->where('kd_opd3', $validated['kd_opd3'] ?? null)
                ->where('kd_opd4', $validated['kd_opd4'] ?? null)
                ->where('kd_opd5', $validated['kd_opd5'] ?? null)
                ->whereNull('deleted_at')
                ->first();
    
            if ($batas) {
                // Update jika ada
                $batas->update([
                    'waktu_awal' => $validated['waktu_awal'],
                    'waktu_akhir' => $validated['waktu_akhir'],
                    'istirahat_awal' => $validated['istirahat_awal'] ?? null,
                    'istirahat_akhir' => $validated['istirahat_akhir'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);
            } else {
                // Insert baru
                $batas = BatasWaktuModel::create($validated);
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Berhasil Menambah Batas Waktu SKPD',
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    /**
     * Detail data
     */
    public function show($id)
    {
        $batas = DB::connection('oracle')->table('BATAS_WAKTU')
                    ->where('id', $id)
                    ->whereNull('deleted_at')
                    ->first();

        if (!$batas) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $batas,
        ]);
    }

    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        $batas = DB::connection('oracle')->table('BATAS_WAKTU')
                    ->where('id', $id)
                    ->whereNull('deleted_at')
                    ->first();

        if (!$batas) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'hari' => 'required|string|max:50',
            'kd_opd1' => 'required|string|max:2',
            'kd_opd2' => 'required|string|max:2',
            'kd_opd3' => 'nullable|string|max:2',
            'kd_opd4' => 'nullable|string|max:2',
            'kd_opd5' => 'nullable|string|max:2',
            'waktu_awal' => 'required|date_format:H:i',
            'waktu_akhir' => 'required|date_format:H:i|after:waktu_awal',
            'keterangan' => 'nullable|string|max:255',
            'istirahat_awal' => 'nullable|date_format:H:i',
            'istirahat_akhir' => 'nullable|date_format:H:i|after:istirahat_awal',
        ]);

        try {
            DB::connection('oracle')->table('BATAS_WAKTU')
                ->where('id', $id)
                ->update(array_merge($validated, [
                    'updated_at' => now(),
                ]));

            $updatedBatas = DB::connection('oracle')->table('BATAS_WAKTU')->where('id', $id)->first();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => $updatedBatas,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft delete data
     */
    public function destroy($id)
    {
        $batas = BatasWaktuModel::where('id', $id)
            ->whereNull('deleted_at')
            ->first();
    
        if (!$batas) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        // Default jam kerja per hari
        $defaultJam = [
            'Monday'    => ['waktu_awal' => '08:00', 'waktu_akhir' => '14:30'],
            'Tuesday'   => ['waktu_awal' => '08:00', 'waktu_akhir' => '14:30'],
            'Wednesday' => ['waktu_awal' => '08:00', 'waktu_akhir' => '14:30'],
            'Thursday'  => ['waktu_awal' => '08:00', 'waktu_akhir' => '14:30'],
            'Friday'    => ['waktu_awal' => '08:00', 'waktu_akhir' => '13:30'],
        ];
    
        $hari = $batas->hari;
        if (!isset($defaultJam[$hari])) {
            return response()->json([
                'status' => false,
                'message' => "Tidak ada default jam untuk hari {$hari}",
            ], 400);
        }
    
        // Reset jadwal + istirahat
        $batas->update([
            'waktu_awal' => $defaultJam[$hari]['waktu_awal'],
            'waktu_akhir' => $defaultJam[$hari]['waktu_akhir'],
            'istirahat_awal' => '12:00',
            'istirahat_akhir' => '13:00',
            'keterangan' => 'Pelayanan Dibuka',
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dikembalikan ke jam default dengan istirahat 12:00–13:00',
            'data' => $batas,
        ]);
    }
    
    public function resetAll()
    {
        try {
            $allBatas = BatasWaktuModel::whereNull('deleted_at')->get();

            if ($allBatas->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data jadwal untuk di-reset',
                ], 404);
            }

            // Default jam kerja per hari
            $defaultJam = [
                'Monday'    => ['waktu_awal' => '08:00', 'waktu_akhir' => '14:30'],
                'Tuesday'   => ['waktu_awal' => '08:00', 'waktu_akhir' => '14:30'],
                'Wednesday' => ['waktu_awal' => '08:00', 'waktu_akhir' => '14:30'],
                'Thursday'  => ['waktu_awal' => '08:00', 'waktu_akhir' => '14:30'],
                'Friday'    => ['waktu_awal' => '08:00', 'waktu_akhir' => '13:30'],
            ];

            foreach ($allBatas as $batas) {
                $hari = $batas->hari;
                if (!isset($defaultJam[$hari])) {
                    continue; // skip jika hari tidak ada default
                }

                $batas->update([
                    'waktu_awal' => $defaultJam[$hari]['waktu_awal'],
                    'waktu_akhir' => $defaultJam[$hari]['waktu_akhir'],
                    'istirahat_awal' => '12:00',
                    'istirahat_akhir' => '13:00',
                    'keterangan' => 'Pelayanan Dibuka',
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Semua jadwal berhasil di-reset ke default',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat reset jadwal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function resetAllTutup()
    {
        try {
            $allBatas = BatasWaktuModel::whereNull('deleted_at')->get();

            if ($allBatas->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data jadwal untuk di-reset',
                ], 404);
            }

            // Default jam kerja per hari
            $defaultJam = [
                'Monday'    => ['waktu_awal' => '08:00', 'waktu_akhir' => '08:00'],
                'Tuesday'   => ['waktu_awal' => '08:00', 'waktu_akhir' => '08:00'],
                'Wednesday' => ['waktu_awal' => '08:00', 'waktu_akhir' => '08:00'],
                'Thursday'  => ['waktu_awal' => '08:00', 'waktu_akhir' => '08:00'],
                'Friday'    => ['waktu_awal' => '08:00', 'waktu_akhir' => '08:00'],
            ];

            foreach ($allBatas as $batas) {
                $hari = $batas->hari;
                if (!isset($defaultJam[$hari])) {
                    continue; // skip jika hari tidak ada default
                }

                $batas->update([
                    'waktu_awal' => $defaultJam[$hari]['waktu_awal'],
                    'waktu_akhir' => $defaultJam[$hari]['waktu_akhir'],
                    'istirahat_awal' => '12:00',
                    'istirahat_akhir' => '13:00',
                    'keterangan' => 'Pelayanan Ditutup',
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Semua jadwal berhasil di tutup',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat reset jadwal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
