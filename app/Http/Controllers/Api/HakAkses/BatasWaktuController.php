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
        $query = BatasWaktuModel::whereNull('deleted_at');

        // Filter pencarian
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('hari', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        for ($i = 1; $i <= 5; $i++) {
            $param = $request->get("kd_opd{$i}");
            if ($param !== null) {
                $query->where("kd_opd{$i}", $param);
            }
        }

        $collection = $query->get();

        // Semua OPD unik
        $allOPD = $collection->map(function($item){
            return $item->kd_opd1.'-'.$item->kd_opd2.'-'.$item->kd_opd3.'-'.$item->kd_opd4.'-'.$item->kd_opd5;
        })->unique();

        // Mapping hari ke Indonesia dan urutan minggu
        $hariIndonesia = [
            'Monday'    => ['nama'=>'Senin', 'urutan'=>1],
            'Tuesday'   => ['nama'=>'Selasa', 'urutan'=>2],
            'Wednesday' => ['nama'=>'Rabu', 'urutan'=>3],
            'Thursday'  => ['nama'=>'Kamis', 'urutan'=>4],
            'Friday'    => ['nama'=>'Jumat', 'urutan'=>5],
            'Saturday'  => ['nama'=>'Sabtu', 'urutan'=>6],
            'Sunday'    => ['nama'=>'Minggu', 'urutan'=>7],
        ];

        // Grouping berdasarkan waktu + istirahat
        $groups = $collection->groupBy(function ($item) {
            return $item->waktu_awal
                .'|'.$item->waktu_akhir
                .'|'.$item->istirahat_awal
                .'|'.$item->istirahat_akhir;
        });

        $result = collect();

        foreach ($groups as $group) {
            // Ambil semua OPD unik dalam group
            $groupOPD = $group->map(function($g){
                return $g->kd_opd1.'-'.$g->kd_opd2.'-'.$g->kd_opd3.'-'.$g->kd_opd4.'-'.$g->kd_opd5;
            })->unique();

            if ($groupOPD->count() === $allOPD->count()) {
                // Semua OPD sama → gabungkan hari
                $hariGabung = $group->pluck('hari')
                    ->map(function($h) use ($hariIndonesia){
                        return $hariIndonesia[$h]['nama'] ?? $h;
                    })
                    ->sortBy(function($h) use ($hariIndonesia){
                        // urutkan berdasarkan minggu
                        $mapping = array_column($hariIndonesia,'urutan','nama');
                        return $mapping[$h] ?? 99;
                    })
                    ->unique()
                    ->implode(', ');

                $item = $group->first();
                $item->hari = $hariGabung;
                $item->all_opd = true;

                $result->push($item);

            } else {
                // Ada OPD berbeda → tampil per OPD
                foreach ($group as $item) {
                    $item->all_opd = false;
                    // panggil relasi skpd() agar nama OPD muncul
                    $item->setRelation('skpd', $item->skpd());

                    // ubah hari ke bahasa Indonesia
                    $item->hari = $hariIndonesia[$item->hari]['nama'] ?? $item->hari;

                    $result->push($item);
                }
            }
        }

        // Pagination manual
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        $paged = new LengthAwarePaginator(
            $result->forPage($page, $perPage)->values(),
            $result->count(),
            $perPage,
            $page
        );

        return BatasWaktuResource::collection($paged);
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
    
            return new BatasWaktuResource($batas);
    
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

}
