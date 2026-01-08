<?php

namespace App\Http\Controllers\Api\HakAkses;

use App\Http\Controllers\Controller;
use App\Models\AksesKuasaBUDModel;
use Illuminate\Http\Request;
use App\Http\Resources\AksesKuasaBUDResource;
use Illuminate\Support\Facades\DB;

class AksesKuasaBudController extends Controller
{
    /**
     * List Akses Kuasa BUD (pagination + search)
     */
    public function index(Request $request)
    {

        $data = AksesKuasaBUDModel::with('user')
        ->whereNull('deleted_at')
        ->orderByDesc('date_created')
        ->get();

        // Tambahkan skpd secara manual
        $data->transform(function ($item) {
            $item->setRelation('skpd', $item->skpd()); // panggil accessor skpd()
            return $item;
        });

        return AksesKuasaBUDResource::collection($data);
    }

    /**
     * Simpan data baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kbud' => 'required|string|max:50',
            'kd_opd1' => 'required|string|max:2',
            'kd_opd2' => 'required|string|max:2',
            'kd_opd3' => 'nullable|string|max:2',
            'kd_opd4' => 'nullable|string|max:2',
            'kd_opd5' => 'nullable|string|max:2',
        ]);

        try {
            $id = DB::connection('oracle')->selectOne('SELECT NO_OPERATOR.NEXTVAL AS id FROM dual')->id;

            DB::connection('oracle')->table('KUASA_BUD')->insert([
                'id' => $id,
                'id_kbud' => $validated['id_kbud'],
                'kd_opd1' => $validated['kd_opd1'],
                'kd_opd2' => $validated['kd_opd2'],
                'kd_opd3' => $validated['kd_opd3'] ?? null,
                'kd_opd4' => $validated['kd_opd4'] ?? null,
                'kd_opd5' => $validated['kd_opd5'] ?? null,
                'date_created' => now(),
                'created_at' => now(),
            ]);

            $kbud = DB::connection('oracle')->table('KUASA_BUD')->where('id', $id)->first();

            return new AksesKuasaBUDResource($kbud);

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
        $kbud = DB::connection('oracle')->table('KUASA_BUD')
                    ->where('id', $id)
                    ->whereNull('deleted_at')
                    ->first();

        if (!$kbud) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $kbud,
        ]);
    }

    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        $kbud = DB::connection('oracle')->table('KUASA_BUD')
                    ->where('id', $id)
                    ->whereNull('deleted_at')
                    ->first();

        if (!$kbud) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'id_kbud' => 'required|string|max:50',
            'kd_opd1' => 'required|string|max:2',
            'kd_opd2' => 'required|string|max:2',
            'kd_opd3' => 'nullable|string|max:2',
            'kd_opd4' => 'nullable|string|max:2',
            'kd_opd5' => 'nullable|string|max:2',
        ]);

        try {
            DB::connection('oracle')->table('KUASA_BUD')
                ->where('id', $id)
                ->update([
                    'id_kbud' => $validated['id_kbud'],
                    'kd_opd1' => $validated['kd_opd1'],
                    'kd_opd2' => $validated['kd_opd2'],
                    'kd_opd3' => $validated['kd_opd3'] ?? null,
                    'kd_opd4' => $validated['kd_opd4'] ?? null,
                    'kd_opd5' => $validated['kd_opd5'] ?? null,
                    'updated_at' => now(),
                ]);

            $updatedKbud = DB::connection('oracle')->table('KUASA_BUD')->where('id', $id)->first();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => $updatedKbud,
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
        $affected = DB::connection('oracle')->table('KUASA_BUD')
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
