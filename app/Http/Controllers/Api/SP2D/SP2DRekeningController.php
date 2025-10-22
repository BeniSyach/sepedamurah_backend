<?php

namespace App\Http\Controllers\Api\SP2D;

use App\Http\Controllers\Controller;
use App\Models\Sp2dRekening;
use Illuminate\Http\Request;
use App\Http\Resources\Sp2dRekeningResource;
use Illuminate\Support\Facades\DB;

class SP2DRekeningController extends Controller
{
    /**
     * List SP2D Rekening (pagination + search)
     */
    public function index(Request $request)
    {
        $query = Sp2dRekening::query()->whereNull('DELETED_AT');

        if ($search = $request->get('search')) {
            $query->where('SP2D_ID', 'like', "%{$search}%")
                  ->orWhere('KD_REKENING1', 'like', "%{$search}%")
                  ->orWhere('KD_REKENING2', 'like', "%{$search}%");
        }

        $data = $query->orderBy('SP2D_ID', 'asc')
                      ->paginate($request->get('per_page', 10));

        return Sp2dRekeningResource::collection($data);
    }

    /**
     * Store SP2D Rekening baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'SP2D_ID' => 'required|integer',
            'KD_REKENING1' => 'nullable|string|max:5',
            'KD_REKENING2' => 'nullable|string|max:5',
            'KD_REKENING3' => 'nullable|string|max:5',
            'KD_REKENING4' => 'nullable|string|max:5',
            'KD_REKENING5' => 'nullable|string|max:5',
            'KD_REKENING6' => 'nullable|string|max:5',
            'NILAI' => 'nullable|numeric',
            'KD_KEG1' => 'nullable|string|max:5',
            'KD_KEG2' => 'nullable|string|max:5',
            'KD_KEG3' => 'nullable|string|max:5',
            'KD_KEG4' => 'nullable|string|max:5',
            'KD_KEG5' => 'nullable|string|max:5',
            'KD_SUBKEG1' => 'nullable|string|max:5',
            'KD_SUBKEG2' => 'nullable|string|max:5',
            'KD_SUBKEG3' => 'nullable|string|max:5',
            'KD_SUBKEG4' => 'nullable|string|max:5',
            'KD_SUBKEG5' => 'nullable|string|max:5',
            'KD_SUBKEG6' => 'nullable|string|max:5',
            'KD_PROG1' => 'nullable|string|max:5',
            'KD_PROG2' => 'nullable|string|max:5',
            'KD_PROG3' => 'nullable|string|max:5',
            'KD_URUSAN' => 'nullable|string|max:5',
            'KD_BU1' => 'nullable|string|max:5',
            'KD_BU2' => 'nullable|string|max:5',
        ]);

        try {
            $rekening = Sp2dRekening::create(array_merge($validated, [
                'CREATED_AT' => now(),
            ]));

            return new Sp2dRekeningResource($rekening);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail SP2D Rekening
     */
    public function show($id)
    {
        $rekening = Sp2dRekening::where('ID', $id)
                                 ->whereNull('DELETED_AT')
                                 ->first();

        if (!$rekening) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new Sp2dRekeningResource($rekening);
    }

    /**
     * Update SP2D Rekening
     */
    public function update(Request $request, $id)
    {
        $rekening = Sp2dRekening::where('ID', $id)
                                 ->whereNull('DELETED_AT')
                                 ->first();

        if (!$rekening) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'NILAI' => 'nullable|numeric',
            'KD_REKENING1' => 'nullable|string|max:5',
            'KD_REKENING2' => 'nullable|string|max:5',
            'KD_REKENING3' => 'nullable|string|max:5',
            'KD_REKENING4' => 'nullable|string|max:5',
            'KD_REKENING5' => 'nullable|string|max:5',
            'KD_REKENING6' => 'nullable|string|max:5',
        ]);

        try {
            $rekening->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new Sp2dRekeningResource($rekening),
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
     * Soft delete SP2D Rekening
     */
    public function destroy($id)
    {
        $rekening = Sp2dRekening::where('ID', $id)
                                 ->whereNull('DELETED_AT')
                                 ->first();

        if (!$rekening) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $rekening->DELETED_AT = now();
        $rekening->save();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }
}
