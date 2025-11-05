<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\RekRincianModel;
use Illuminate\Http\Request;

class RekRincianController extends Controller
{
    // 🔹 GET /api/rek-rincian
    public function index(Request $request)
    {
        $query = RekRincianModel::query();

        // Filter opsional
        if ($request->has('kd_rincian1')) {
            $query->where('kd_rincian1', $request->kd_rincian1);
        }
        if ($request->has('kd_rincian2')) {
            $query->where('kd_rincian2', $request->kd_rincian2);
        }
        if ($request->has('kd_rincian3')) {
            $query->where('kd_rincian3', $request->kd_rincian3);
        }
        if ($request->has('kd_rincian4')) {
            $query->where('kd_rincian4', $request->kd_rincian4);
        }
        if ($request->has('kd_rincian5')) {
            $query->where('kd_rincian5', $request->kd_rincian5);
        }
        if ($request->has('nm_rek_rincian')) {
            $query->where('nm_rek_rincian', 'like', '%' . $request->nm_rek_rincian . '%');
        }

        $data = $query->get();

        return response()->json($data);
    }

    // 🔹 GET /api/rek-rincian/{id}
    public function show($id)
    {
        $data = RekRincianModel::find($id);
        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json($data);
    }

    // 🔹 POST /api/rek-rincian
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_rincian1' => 'required|string|max:10',
            'kd_rincian2' => 'required|string|max:10',
            'kd_rincian3' => 'required|string|max:10',
            'kd_rincian4' => 'required|string|max:10',
            'kd_rincian5' => 'required|string|max:10',
            'nm_rek_rincian' => 'required|string|max:150',
        ]);

        $data = RekRincianModel::create($validated);
        return response()->json($data, 201);
    }

    // 🔹 PUT /api/rek-rincian/{id}
    public function update(Request $request, $id)
    {
        $data = RekRincianModel::find($id);
        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'kd_rincian1' => 'sometimes|required|string|max:10',
            'kd_rincian2' => 'sometimes|required|string|max:10',
            'kd_rincian3' => 'sometimes|required|string|max:10',
            'kd_rincian4' => 'sometimes|required|string|max:10',
            'kd_rincian5' => 'sometimes|required|string|max:10',
            'nm_rek_rincian' => 'sometimes|required|string|max:150',
        ]);

        $data->update($validated);
        return response()->json($data);
    }

    // 🔹 DELETE /api/rek-rincian/{id}
    public function destroy($id)
    {
        $data = RekRincianModel::find($id);
        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $data->delete();
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
