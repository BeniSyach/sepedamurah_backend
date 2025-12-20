<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefAssetBendaharaResource;
use App\Models\RefAssetBendaharaModel;
use Illuminate\Http\Request;

class RefAssetBendaharaController extends Controller
{
    public function index(Request $request)
    {
        $query = RefAssetBendaharaModel::query();

        // search by nama asset
        if ($search = $request->get('search')) {
            $search = strtolower(trim($search));
            $query->whereRaw(
                'LOWER(nm_asset_bendahara) LIKE ?',
                ["%{$search}%"]
            );
        }

        $perPage = $request->get('per_page', 10);

        $data = $query
            ->orderBy('id')
            ->paginate($perPage);

        return RefAssetBendaharaResource::collection($data);
    }

    public function show($id)
    {
        $data = RefAssetBendaharaModel::findOrFail($id);
        return new RefAssetBendaharaResource($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nm_asset_bendahara' => 'required|string|max:255',
        ]);

        $data = RefAssetBendaharaModel::create($validated);

        return new RefAssetBendaharaResource($data);
    }

    public function update(Request $request, $id)
    {
        $data = RefAssetBendaharaModel::findOrFail($id);

        $validated = $request->validate([
            'nm_asset_bendahara' => 'required|string|max:255',
        ]);

        $data->update($validated);

        return new RefAssetBendaharaResource($data);
    }

    public function destroy($id)
    {
        $data = RefAssetBendaharaModel::findOrFail($id);
        $data->delete();

        return response()->json([
            'message' => 'Ref Asset Bendahara deleted successfully'
        ]);
    }
}
