<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\RekAkunModel;
use Illuminate\Http\Request;

class RekAkunController extends Controller
{
    // GET /api/rek-akun
    public function index()
    {
        return response()->json(RekAkunModel::all());
    }

    // GET /api/rek-akun/{id}
    public function show($id)
    {
        $rekAkun = RekAkunModel::find($id);
        if (!$rekAkun) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json($rekAkun);
    }

    // POST /api/rek-akun
    public function store(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:20|unique:rek_akun,kode',
            'nm_rek_akun' => 'required|string|max:100',
        ]);

        $rekAkun = RekAkunModel::create($request->all());
        return response()->json($rekAkun, 201);
    }

    // PUT /api/rek-akun/{id}
    public function update(Request $request, $id)
    {
        $rekAkun = RekAkunModel::find($id);
        if (!$rekAkun) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $request->validate([
            'kode' => 'sometimes|required|string|max:20|unique:rek_akun,kode,' . $id . ',id',
            'nm_rek_akun' => 'sometimes|required|string|max:100',
        ]);

        $rekAkun->update($request->all());
        return response()->json($rekAkun);
    }

    // DELETE /api/rek-akun/{id}
    public function destroy($id)
    {
        $rekAkun = RekAkunModel::find($id);
        if (!$rekAkun) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $rekAkun->delete();
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
