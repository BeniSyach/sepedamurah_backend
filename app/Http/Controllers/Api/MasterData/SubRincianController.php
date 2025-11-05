<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\SubRincianModel;
use Illuminate\Http\Request;

class SubRincianController extends Controller
{
    // 🔹 GET /api/sub-rincian
    public function index(Request $request)
    {
        $query = SubRincianModel::query();

        // Filter dinamis berdasarkan query params
        foreach (['kd_subrincian1', 'kd_subrincian2', 'kd_subrincian3', 'kd_subrincian4', 'kd_subrincian5', 'kd_subrincian6'] as $key) {
            if ($request->has($key)) {
                $query->where($key, $request->input($key));
            }
        }

        if ($request->has('nm_sub_rincian')) {
            $query->where('nm_sub_rincian', 'like', '%' . $request->nm_sub_rincian . '%');
        }

        $data = $query->get();
        return response()->json($data);
    }

    // 🔹 GET /api/sub-rincian/{id}
    public function show($id)
    {
        $data = SubRincianModel::find($id);
        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json($data);
    }

    // 🔹 POST /api/sub-rincian
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_subrincian1' => 'required|string|max:10',
            'kd_subrincian2' => 'required|string|max:10',
            'kd_subrincian3' => 'required|string|max:10',
            'kd_subrincian4' => 'required|string|max:10',
            'kd_subrincian5' => 'required|string|max:10',
            'kd_subrincian6' => 'required|string|max:10',
            'nm_sub_rincian' => 'required|string|max:200',
        ]);

        $data = SubRincianModel::create($validated);
        return response()->json($data, 201);
    }

    // 🔹 PUT /api/sub-rincian/{id}
    public function update(Request $request, $id)
    {
        $data = SubRincianModel::find($id);
        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'kd_subrincian1' => 'sometimes|required|string|max:10',
            'kd_subrincian2' => 'sometimes|required|string|max:10',
            'kd_subrincian3' => 'sometimes|required|string|max:10',
            'kd_subrincian4' => 'sometimes|required|string|max:10',
            'kd_subrincian5' => 'sometimes|required|string|max:10',
            'kd_subrincian6' => 'sometimes|required|string|max:10',
            'nm_sub_rincian' => 'sometimes|required|string|max:200',
        ]);

        $data->update($validated);
        return response()->json($data);
    }

    // 🔹 DELETE /api/sub-rincian/{id}
    public function destroy($id)
    {
        $data = SubRincianModel::find($id);
        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $data->delete();
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
