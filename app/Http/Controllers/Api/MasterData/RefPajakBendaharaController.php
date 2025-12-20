<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefPajakBendaharaResource;
use App\Models\RefPajakBendaharaModel;
use Illuminate\Http\Request;

class RefPajakBendaharaController extends Controller
{
    public function index(Request $request)
    {
        $query = RefPajakBendaharaModel::query();

        if ($search = $request->get('search')) {
            $search = strtolower(trim($search));
            $query->whereRaw(
                'LOWER(nm_pajak_bendahara) LIKE ?',
                ["%{$search}%"]
            );
        }

        $perPage = $request->get('per_page', 10);
        $data = $query->orderBy('id')->paginate($perPage);

        return RefPajakBendaharaResource::collection($data);
    }

    public function show($id)
    {
        $data = RefPajakBendaharaModel::findOrFail($id);
        return new RefPajakBendaharaResource($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nm_pajak_bendahara' => 'required|string|max:255',
        ]);

        $data = RefPajakBendaharaModel::create($validated);
        return new RefPajakBendaharaResource($data);
    }

    public function update(Request $request, $id)
    {
        $data = RefPajakBendaharaModel::findOrFail($id);

        $validated = $request->validate([
            'nm_pajak_bendahara' => 'required|string|max:255',
        ]);

        $data->update($validated);
        return new RefPajakBendaharaResource($data);
    }

    public function destroy($id)
    {
        $data = RefPajakBendaharaModel::findOrFail($id);
        $data->delete();

        return response()->json([
            'message' => 'Ref Pajak Bendahara deleted successfully'
        ]);
    }
}
