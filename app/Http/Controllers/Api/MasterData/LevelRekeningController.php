<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\LevelRekeningModel;
use Illuminate\Http\Request;

class LevelRekeningController extends Controller
{
    // 🔹 Ambil semua data
    public function index()
    {
        $data = LevelRekeningModel::orderBy('id')->get();
        return response()->json($data);
    }

    // 🔹 Ambil 1 data by ID
    public function show($id)
    {
        $item = LevelRekeningModel::find($id);

        if (!$item) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($item);
    }

    // 🔹 Simpan data baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|max:20',
            'nm_level_rek' => 'required|max:100',
        ]);

        $item = LevelRekeningModel::create($validated);

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'data' => $item
        ], 201);
    }

    // 🔹 Update data
    public function update(Request $request, $id)
    {
        $item = LevelRekeningModel::find($id);

        if (!$item) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'kode' => 'required|max:20',
            'nm_level_rek' => 'required|max:100',
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Data berhasil diperbarui',
            'data' => $item
        ]);
    }

    // 🔹 Hapus data
    public function destroy($id)
    {
        $item = LevelRekeningModel::find($id);

        if (!$item) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
