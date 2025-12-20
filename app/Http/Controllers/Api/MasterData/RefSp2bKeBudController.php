<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefSp2bKeBudResource;
use App\Models\RefSp2bKeBudModel;
use Illuminate\Http\Request;

class RefSp2bKeBudController extends Controller
{
    public function index(Request $request)
    {
        $query = RefSp2bKeBudModel::query();

        if ($search = $request->get('search')) {
            $search = strtolower(trim($search));
            $query->whereRaw(
                'LOWER(nm_sp2b_ke_bud) LIKE ?',
                ["%{$search}%"]
            );
        }

        $perPage = $request->get('per_page', 10);
        $data = $query->orderBy('id')->paginate($perPage);

        return RefSp2bKeBudResource::collection($data);
    }

    public function show($id)
    {
        $data = RefSp2bKeBudModel::findOrFail($id);
        return new RefSp2bKeBudResource($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nm_sp2b_ke_bud' => 'required|string|max:255',
        ]);

        $data = RefSp2bKeBudModel::create($validated);
        return new RefSp2bKeBudResource($data);
    }

    public function update(Request $request, $id)
    {
        $data = RefSp2bKeBudModel::findOrFail($id);

        $validated = $request->validate([
            'nm_sp2b_ke_bud' => 'required|string|max:255',
        ]);

        $data->update($validated);
        return new RefSp2bKeBudResource($data);
    }

    public function destroy($id)
    {
        $data = RefSp2bKeBudModel::findOrFail($id);
        $data->delete();

        return response()->json([
            'message' => 'Ref SP2B ke BUD deleted successfully'
        ]);
    }
}
