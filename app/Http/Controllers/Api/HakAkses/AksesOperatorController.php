<?php

namespace App\Http\Controllers\Api\HakAkses;

use App\Http\Controllers\Controller;
use App\Models\AksesOperatorModel;
use Illuminate\Http\Request;
use App\Http\Resources\AksesOperatorResource;
use Illuminate\Support\Facades\DB;

class AksesOperatorController extends Controller
{
    /**
     * List Akses Operator (pagination + search)
     */
    public function index(Request $request)
    {
        $data = AksesOperatorModel::with('user')
        ->whereNull('deleted_at')
        ->orderByDesc('date_created')
        ->paginate($request->get('per_page', 10));

        // Attach skpd secara manual (karena tidak bisa eager load)
        $data->getCollection()->transform(function ($item) {
            $skpd = $item->skpd(); // panggil accessor manual
            $item->setRelation('skpd', $skpd); // daftarkan ke relasi Eloquent
            return $item;
        });

        return AksesOperatorResource::collection($data);
    }

    /**
     * Simpan akses operator baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_operator' => 'required|string|max:50|unique:AKSES_OPERATOR,id_operator',
            'kd_opd1' => 'required|string|max:2',
            'kd_opd2' => 'required|string|max:2',
            'kd_opd3' => 'nullable|string|max:2',
            'kd_opd4' => 'nullable|string|max:2',
            'kd_opd5' => 'nullable|string|max:2',
        ]);

        try {
            // Ambil ID dari sequence Oracle
            $id = DB::connection('oracle')->selectOne('SELECT NO_OPERATOR.NEXTVAL AS id FROM dual')->id;

            DB::connection('oracle')->table('AKSES_OPERATOR')->insert([
                'id' => $id,
                'id_operator' => $validated['id_operator'],
                'kd_opd1' => $validated['kd_opd1'],
                'kd_opd2' => $validated['kd_opd2'],
                'kd_opd3' => $validated['kd_opd3'] ?? null,
                'kd_opd4' => $validated['kd_opd4'] ?? null,
                'kd_opd5' => $validated['kd_opd5'] ?? null,
                'date_created' => now(),
                'created_at' => now(),
            ]);

            $operator = DB::connection('oracle')->table('AKSES_OPERATOR')->where('id', $id)->first();

            return new AksesOperatorResource($operator);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail akses operator
     */
    public function show($id)
    {
        $operator = DB::connection('oracle')->table('AKSES_OPERATOR')
                        ->where('id', $id)
                        ->whereNull('deleted_at')
                        ->first();

        if (!$operator) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $operator,
        ]);
    }

    /**
     * Update akses operator
     */
    public function update(Request $request, $id)
    {
        $operator = DB::connection('oracle')->table('AKSES_OPERATOR')
                        ->where('id', $id)
                        ->whereNull('deleted_at')
                        ->first();

        if (!$operator) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'id_operator' => 'required|string|max:50',
            'kd_opd1' => 'required|string|max:2',
            'kd_opd2' => 'required|string|max:2',
            'kd_opd3' => 'nullable|string|max:2',
            'kd_opd4' => 'nullable|string|max:2',
            'kd_opd5' => 'nullable|string|max:2',
        ]);

        try {
            DB::connection('oracle')->table('AKSES_OPERATOR')
                ->where('id', $id)
                ->update([
                    'id_operator' => $validated['id_operator'],
                    'kd_opd1' => $validated['kd_opd1'],
                    'kd_opd2' => $validated['kd_opd2'],
                    'kd_opd3' => $validated['kd_opd3'] ?? null,
                    'kd_opd4' => $validated['kd_opd4'] ?? null,
                    'kd_opd5' => $validated['kd_opd5'] ?? null,
                    'updated_at' => now(),
                ]);

            $updatedOperator = DB::connection('oracle')->table('AKSES_OPERATOR')->where('id', $id)->first();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => $updatedOperator,
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
     * Soft delete akses operator
     */
    public function destroy($id)
    {
        $affected = DB::connection('oracle')->table('AKSES_OPERATOR')
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
