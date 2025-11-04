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

    public function get_program_sp2d(Request $request)
    {
        // Ambil user login dari JWT
        $user = auth()->user();
    
        if (!$user) {
            return response()->json(['error' => 'User tidak terautentikasi'], 401);
        }
    
        // Ambil kd_bu dari request
        $kd_bu1 = $request->input('kd_bu1');
        $kd_bu2 = $request->input('kd_bu2');
    
        if (!$kd_bu1 && !$kd_bu2) {
            return response()->json(['error' => 'Parameter kd_bu wajib diisi'], 400);
        }
    
        // Query Builder Laravel
        $query = DB::table('REF_PROGRAM')
            ->distinct()
            ->select('REF_PROGRAM.*')
            ->join('PAGU_BELANJA', function ($join) {
                $join->on('REF_PROGRAM.KD_PROG1', '=', 'PAGU_BELANJA.KD_PROG1')
                     ->on('REF_PROGRAM.KD_PROG2', '=', 'PAGU_BELANJA.KD_PROG2')
                     ->on('REF_PROGRAM.KD_PROG3', '=', 'PAGU_BELANJA.KD_PROG3');
            })
            ->join('REF_OPD', function ($join) {
                $join->on(DB::raw("
                    LOWER(REPLACE(COALESCE(REF_OPD.KODE_OPD, ''), ' ', ''))
                "), '=', DB::raw("
                    LOWER(REPLACE(
                        COALESCE(PAGU_BELANJA.KD_OPD1, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD2, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD3, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD4, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD5, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD6, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD7, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD8, '')
                    , ' ', ''))
                "));
            })
            ->where('REF_OPD.KD_OPD1', $user->kd_opd1)
            ->where('REF_OPD.KD_OPD2', $user->kd_opd2)
            ->where('REF_OPD.KD_OPD3', $user->kd_opd3)
            ->where('REF_OPD.KD_OPD4', $user->kd_opd4)
            ->where('REF_OPD.KD_OPD5', $user->kd_opd5)
            ->where('REF_OPD.HIDDEN', 0)
            ->where('PAGU_BELANJA.KD_PROG1', $kd_bu1)
            ->where('PAGU_BELANJA.KD_PROG2', $kd_bu2)
            ->where('PAGU_BELANJA.IS_DELETED', 0)
            ->get();
    
            return response()->json([
                'data' => $query
            ]);
    }
}
