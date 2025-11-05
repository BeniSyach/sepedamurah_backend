<?php
namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\RekJenisModel;
use Illuminate\Http\Request;

class RekJenisController extends Controller
{
    // GET /api/rek-jenis
    public function index(Request $request)
    {
        $query = RekJenisModel::query();

        // Filter jika ada query parameter
        if ($request->has('kd_jenis1')) {
            $query->where('kd_jenis1', $request->kd_jenis1);
        }
        if ($request->has('kd_jenis2')) {
            $query->where('kd_jenis2', $request->kd_jenis2);
        }
        if ($request->has('kd_jenis3')) {
            $query->where('kd_jenis3', $request->kd_jenis3);
        }
        if ($request->has('nm_rek_jenis')) {
            $query->where('nm_rek_jenis', 'like', '%' . $request->nm_rek_jenis . '%');
        }

        return response()->json($query->get());
    }

    // GET /api/rek-jenis/{id}
    public function show($id)
    {
        $rekJenis = RekJenisModel::find($id);
        if (!$rekJenis) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json($rekJenis);
    }

    // POST /api/rek-jenis
    public function store(Request $request)
    {
        $request->validate([
            'kd_jenis1' => 'required|string|max:10',
            'kd_jenis2' => 'required|string|max:10',
            'kd_jenis3' => 'required|string|max:10',
            'nm_rek_jenis' => 'required|string|max:100',
        ]);

        $rekJenis = RekJenisModel::create($request->all());
        return response()->json($rekJenis, 201);
    }

    // PUT /api/rek-jenis/{id}
    public function update(Request $request, $id)
    {
        $rekJenis = RekJenisModel::find($id);
        if (!$rekJenis) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $request->validate([
            'kd_jenis1' => 'sometimes|required|string|max:10',
            'kd_jenis2' => 'sometimes|required|string|max:10',
            'kd_jenis3' => 'sometimes|required|string|max:10',
            'nm_rek_jenis' => 'sometimes|required|string|max:100',
        ]);

        $rekJenis->update($request->all());
        return response()->json($rekJenis);
    }

    // DELETE /api/rek-jenis/{id}
    public function destroy($id)
    {
        $rekJenis = RekJenisModel::find($id);
        if (!$rekJenis) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $rekJenis->delete();
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
