<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefDpaResource;
use App\Models\DPAModel;
use Illuminate\Http\Request;

class RefDpaController extends Controller
{
    public function index(Request $request)
    {
        $query = DPAModel::query();

        if ($search = $request->get('search')) {
            $search = strtolower(trim($search));
            $query->whereRaw('LOWER(nm_dpa) LIKE ?', ["%{$search}%"]);
        }

        $perPage = $request->get('per_page', 10);
        $data = $query->orderBy('id')->paginate($perPage);

        return RefDpaResource::collection($data);
    }

    public function show($id)
    {
        $dpa = DPAModel::findOrFail($id);
        return new RefDpaResource($dpa);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nm_dpa' => 'required|string|max:255',
        ]);

        $dpa = DPAModel::create($validated);
        return new RefDpaResource($dpa);
    }

    public function update(Request $request, $id)
    {
        $dpa = DPAModel::findOrFail($id);

        $validated = $request->validate([
            'nm_dpa' => 'required|string|max:255',
        ]);

        $dpa->update($validated);
        return new RefDpaResource($dpa);
    }

    public function destroy($id)
    {
        $dpa = DPAModel::findOrFail($id);
        $dpa->delete();

        return response()->json([
            'message' => 'RefDPA deleted successfully'
        ]);
    }
}
