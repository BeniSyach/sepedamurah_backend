<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\RekObjekModel;
use Illuminate\Http\Request;

class RekObjekController extends Controller
{
    // List semua rek_objek
    public function index(Request $request)
    {
        $query = RekObjekModel::query();
    
        // Filter berdasarkan kd_objek1
        if ($request->has('kd_objek1')) {
            $query->where('kd_objek1', $request->input('kd_objek1'));
        }
    
        // Filter berdasarkan kd_objek2
        if ($request->has('kd_objek2')) {
            $query->where('kd_objek2', $request->input('kd_objek2'));
        }
    
        // Filter berdasarkan kd_objek3
        if ($request->has('kd_objek3')) {
            $query->where('kd_objek3', $request->input('kd_objek3'));
        }
    
        // Filter berdasarkan kd_objek4
        if ($request->has('kd_objek4')) {
            $query->where('kd_objek4', $request->input('kd_objek4'));
        }
    
        // Filter berdasarkan nama (like search)
        if ($request->has('nm_rek_objek')) {
            $query->where('nm_rek_objek', 'like', '%' . $request->input('nm_rek_objek') . '%');
        }
    
        $data = $query->get();
        return response()->json($data);
    }
    

    // Tampilkan satu rek_objek
    public function show($id)
    {
        $rekObjek = RekObjekModel::find($id);
        if (!$rekObjek) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json($rekObjek);
    }

    // Simpan rek_objek baru
    public function store(Request $request)
    {
        $request->validate([
            'kd_objek1' => 'required|string|max:10',
            'kd_objek2' => 'required|string|max:10',
            'kd_objek3' => 'required|string|max:10',
            'kd_objek4' => 'required|string|max:10',
            'nm_rek_objek' => 'required|string|max:100',
        ]);

        $rekObjek = RekObjekModel::create($request->all());
        return response()->json($rekObjek, 201);
    }

    // Update rek_objek
    public function update(Request $request, $id)
    {
        $rekObjek = RekObjekModel::find($id);
        if (!$rekObjek) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $request->validate([
            'kd_objek1' => 'sometimes|required|string|max:10',
            'kd_objek2' => 'sometimes|required|string|max:10',
            'kd_objek3' => 'sometimes|required|string|max:10',
            'kd_objek4' => 'sometimes|required|string|max:10',
            'nm_rek_objek' => 'sometimes|required|string|max:100',
        ]);

        $rekObjek->update($request->all());
        return response()->json($rekObjek);
    }

    // Hapus rek_objek
    public function destroy($id)
    {
        $rekObjek = RekObjekModel::find($id);
        if (!$rekObjek) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $rekObjek->delete();
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
