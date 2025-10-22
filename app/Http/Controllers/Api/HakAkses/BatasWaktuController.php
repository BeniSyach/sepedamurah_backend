<?php

namespace App\Http\Controllers\Api\HakAkses;

use App\Http\Controllers\Controller;
use App\Models\BatasWaktuModel;
use Illuminate\Http\Request;
use App\Http\Resources\BatasWaktuResource;
use Illuminate\Support\Facades\DB;

class BatasWaktuController extends Controller
{
    /**
     * List Batas Waktu (pagination + search)
     */
    public function index(Request $request)
    {
        $query = BatasWaktuModel::whereNull('deleted_at');

        // 🔍 Filter pencarian jika ada parameter "search"
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('hari', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        // 📄 Ambil data dengan pagination
        $data = $query->orderBy('id', 'asc')
            ->paginate($request->get('per_page', 10));

        // 🔗 Tambahkan relasi skpd secara manual
        $data->getCollection()->transform(function ($item) {
            $skpd = $item->skpd(); // panggil accessor skpd() dari model
            $item->setRelation('skpd', $skpd); // daftarkan relasi ke Eloquent
            return $item;
        });

        return BatasWaktuResource::collection($data);
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
            // Ambil ID dari sequence trigger Oracle
            $id = DB::connection('oracle')->selectOne('SELECT NO_BATAS_WAKTU.NEXTVAL AS id FROM dual')->id;

            DB::connection('oracle')->table('BATAS_WAKTU')->insert(array_merge($validated, [
                'id' => $id,
                'created_at' => now(),
            ]));

            $batas = DB::connection('oracle')->table('BATAS_WAKTU')->where('id', $id)->first();

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
        $affected = DB::connection('oracle')->table('BATAS_WAKTU')
                        ->where('id', $id)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => now()]);

        if ($affected === 0) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }
}
