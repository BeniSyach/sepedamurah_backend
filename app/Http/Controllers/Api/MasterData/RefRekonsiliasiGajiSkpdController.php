<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefRekonsiliasiGajiSkpdResource;
use App\Models\RefRekonsiliasiGajiSkpdModel;
use Illuminate\Http\Request;

class RefRekonsiliasiGajiSkpdController extends Controller
{
    public function index(Request $request)
    {
        $query = RefRekonsiliasiGajiSkpdModel::query();
        // $currentYear = date('Y');

        // // hanya data nm_rekonsiliasi_gaji_skpd yang mengandung tahun sekarang
        // $query->whereRaw(
        //     'LOWER(nm_rekonsiliasi_gaji_skpd) LIKE ?',
        //     ["%{$currentYear}%"]
        // );

        if ($search = $request->get('search')) {
            $search = strtolower(trim($search));
            $query->whereRaw(
                'LOWER(nm_rekonsiliasi_gaji_skpd) LIKE ?',
                ["%{$search}%"]
            );
        }

        $perPage = $request->get('per_page', 10);
        $data = $query->orderBy('id')->paginate($perPage);

        return RefRekonsiliasiGajiSkpdResource::collection($data);
    }

    public function show($id)
    {
        $data = RefRekonsiliasiGajiSkpdModel::findOrFail($id);
        return new RefRekonsiliasiGajiSkpdResource($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nm_rekonsiliasi_gaji_skpd' => 'required|string|max:255',
        ]);

        $data = RefRekonsiliasiGajiSkpdModel::create($validated);
        return new RefRekonsiliasiGajiSkpdResource($data);
    }

    public function update(Request $request, $id)
    {
        $data = RefRekonsiliasiGajiSkpdModel::findOrFail($id);

        $validated = $request->validate([
            'nm_rekonsiliasi_gaji_skpd' => 'required|string|max:255',
        ]);

        $data->update($validated);
        return new RefRekonsiliasiGajiSkpdResource($data);
    }

    public function destroy($id)
    {
        $data = RefRekonsiliasiGajiSkpdModel::findOrFail($id);
        $data->delete();

        return response()->json([
            'message' => 'Ref Rekonsiliasi Gaji SKPD deleted successfully'
        ]);
    }
}
