<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ProgramModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ProgramResource;

class ProgramController extends Controller
{
    /**
     * Tampilkan daftar Program (dengan pagination & pencarian).
     */
    public function index(Request $request)
    {
        $query = ProgramModel::query();

        if ($search = $request->get('search')) {
            $query->where('nm_program', 'like', "%{$search}%")
                ->orWhere('kd_prog1', 'like', "%{$search}%")
                ->orWhere('kd_prog2', 'like', "%{$search}%")
                ->orWhere('kd_prog3', 'like', "%{$search}%");
        }

        $data = $query->paginate(10);

        return ProgramResource::collection($data);
    }

    /**
     * Simpan data Program baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_prog1'   => 'required|string|max:10',
            'kd_prog2'   => 'required|string|max:10',
            'kd_prog3'   => 'required|string|max:10',
            'nm_program' => 'required|string|max:255',
        ]);

        try {
            $program = ProgramModel::create($validated);

            return new ProgramResource($program);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kode program sudah terdaftar.',
                ], 409);
            }

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tampilkan detail Program.
     */
    public function show($kd_prog1, $kd_prog2, $kd_prog3)
    {
        try {
            $program = DB::connection('oracle')
                ->table(DB::raw('REF_PROGRAM'))
                ->whereRaw('TRIM(KD_PROG1) = ?', [trim($kd_prog1)])
                ->whereRaw('TRIM(KD_PROG2) = ?', [trim($kd_prog2)])
                ->whereRaw('TRIM(KD_PROG3) = ?', [trim($kd_prog3)])
                ->whereNull('DELETED_AT')
                ->first();

            if (!$program) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data program tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $program,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update data Program.
     */
    public function update(Request $request, $kd_prog1, $kd_prog2, $kd_prog3)
    {
        try {
            $program = ProgramModel::whereRaw('TRIM(KD_PROG1) = ?', [trim($kd_prog1)])
                ->whereRaw('TRIM(KD_PROG2) = ?', [trim($kd_prog2)])
                ->whereRaw('TRIM(KD_PROG3) = ?', [trim($kd_prog3)])
                ->first();

            if (!$program) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data program tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nm_program' => 'required|string|max:255',
            ]);

            $program->nm_program = $validated['nm_program'];
            $program->save();

            return response()->json([
                'status' => true,
                'message' => 'Data program berhasil diperbarui',
                'data' => new ProgramResource($program),
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
     * Soft delete data Program.
     */
    public function destroy($kd_prog1, $kd_prog2, $kd_prog3)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('REF_PROGRAM')
                ->whereRaw('TRIM(KD_PROG1) = ?', [trim($kd_prog1)])
                ->whereRaw('TRIM(KD_PROG2) = ?', [trim($kd_prog2)])
                ->whereRaw('TRIM(KD_PROG3) = ?', [trim($kd_prog3)])
                ->update([
                    'DELETED_AT' => now(),
                ]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data program tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data program berhasil dihapus (soft delete)',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
