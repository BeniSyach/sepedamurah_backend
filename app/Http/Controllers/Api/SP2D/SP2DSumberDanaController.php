<?php

namespace App\Http\Controllers\Api\SP2D;

use App\Http\Controllers\Controller;
use App\Models\SP2DSumberDanaModel;
use Illuminate\Http\Request;
use App\Http\Resources\SP2DSumberDanaResource;

class SP2DSumberDanaController extends Controller
{
    /**
     * List SP2D Sumber Dana (pagination + search)
     */
    public function index(Request $request)
    {
        $query = SP2DSumberDanaModel::query()->whereNull('DELETED_AT');

        if ($search = $request->get('search')) {
            $query->where('SP2D_ID', 'like', "%{$search}%")
                  ->orWhere('KD_REF1', 'like', "%{$search}%")
                  ->orWhere('KD_REF2', 'like', "%{$search}%");
        }

        $data = $query->orderBy('SP2D_ID', 'asc')
                      ->paginate($request->get('per_page', 10));

        return SP2DSumberDanaResource::collection($data);
    }

    /**
     * Store SP2D Sumber Dana baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'SP2D_ID' => 'required|integer',
            'KD_REF1' => 'required|string|max:1',
            'KD_REF2' => 'required|string|max:1',
            'KD_REF3' => 'nullable|string|max:2',
            'KD_REF4' => 'nullable|string|max:2',
            'KD_REF5' => 'nullable|string|max:2',
            'KD_REF6' => 'nullable|string|max:4',
            'NILAI' => 'nullable|numeric',
        ]);

        try {
            $sumber = SP2DSumberDanaModel::create(array_merge($validated, [
                'CREATED_AT' => now(),
            ]));

            return new SP2DSumberDanaResource($sumber);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail SP2D Sumber Dana
     */
    public function show($id)
    {
        $sumber = SP2DSumberDanaModel::where('ID', $id)
                                     ->whereNull('DELETED_AT')
                                     ->first();

        if (!$sumber) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new SP2DSumberDanaResource($sumber);
    }

    /**
     * Update SP2D Sumber Dana
     */
    public function update(Request $request, $id)
    {
        $sumber = SP2DSumberDanaModel::where('ID', $id)
                                     ->whereNull('DELETED_AT')
                                     ->first();

        if (!$sumber) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'KD_REF1' => 'required|string|max:1',
            'KD_REF2' => 'required|string|max:1',
            'KD_REF3' => 'nullable|string|max:2',
            'KD_REF4' => 'nullable|string|max:2',
            'KD_REF5' => 'nullable|string|max:2',
            'KD_REF6' => 'nullable|string|max:4',
            'NILAI' => 'nullable|numeric',
        ]);

        try {
            $sumber->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new SP2DSumberDanaResource($sumber),
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
     * Soft delete SP2D Sumber Dana
     */
    public function destroy($id)
    {
        $sumber = SP2DSumberDanaModel::where('ID', $id)
                                     ->whereNull('DELETED_AT')
                                     ->first();

        if (!$sumber) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $sumber->DELETED_AT = now();
        $sumber->save();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }
}
