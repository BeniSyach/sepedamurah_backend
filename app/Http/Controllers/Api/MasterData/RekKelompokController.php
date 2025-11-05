<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\RekKelompokModel;
use Illuminate\Http\Request;

class RekKelompokController extends Controller
{
    // GET /api/rek-kelompok
    public function index(Request $request)
    {
        $query = RekKelompokModel::query();
    
        // Filter berdasarkan kd_kel1
        if ($request->has('kd_kel1')) {
            $query->where('kd_kel1', $request->kd_kel1);
        }
    
        // Filter berdasarkan kd_kel2
        if ($request->has('kd_kel2')) {
            $query->where('kd_kel2', $request->kd_kel2);
        }
    
        // Filter berdasarkan nm_rek_kelompok (like search)
        if ($request->has('nm_rek_kelompok')) {
            $query->where('nm_rek_kelompok', 'like', '%' . $request->nm_rek_kelompok . '%');
        }
    
        // Ambil data hasil filter
        $rekKelompok = $query->get();
    
        return response()->json($rekKelompok);
    }
    
    // GET /api/rek-kelompok/{id}
    public function show($id)
    {
        $rekKelompok = RekKelompokModel::find($id);
        if (!$rekKelompok) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json($rekKelompok);
    }

    // POST /api/rek-kelompok
    public function store(Request $request)
    {
        $request->validate([
            'kd_kel1' => 'required|string|max:10',
            'kd_kel2' => 'required|string|max:10',
            'nm_rek_kelompok' => 'required|string|max:100',
        ]);

        $rekKelompok = RekKelompokModel::create($request->all());
        return response()->json($rekKelompok, 201);
    }

    // PUT /api/rek-kelompok/{id}
    public function update(Request $request, $id)
    {
        $rekKelompok = RekKelompokModel::find($id);
        if (!$rekKelompok) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $request->validate([
            'kd_kel1' => 'sometimes|required|string|max:10',
            'kd_kel2' => 'sometimes|required|string|max:10',
            'nm_rek_kelompok' => 'sometimes|required|string|max:100',
        ]);

        $rekKelompok->update($request->all());
        return response()->json($rekKelompok);
    }

    // DELETE /api/rek-kelompok/{id}
    public function destroy($id)
    {
        $rekKelompok = RekKelompokModel::find($id);
        if (!$rekKelompok) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $rekKelompok->delete();
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
