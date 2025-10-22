<?php

namespace App\Http\Controllers\Api\HakAkses;

use App\Http\Controllers\Controller;
use App\Models\UsersRoleModel;
use Illuminate\Http\Request;
use App\Http\Resources\UsersRoleResource;
use Illuminate\Support\Facades\DB;

class UsersRoleController extends Controller
{
    /**
     * Tampilkan daftar role (dengan pagination + search)
     */
    public function index(Request $request)
    {
        $query = DB::connection('oracle')->table('USERS_ROLE')
                    ->whereNull('deleted_at');

        if ($search = $request->get('search')) {
            $query->where('rule', 'like', "%{$search}%");
        }

        $data = $query->orderBy('id', 'asc')->paginate($request->get('per_page', 10));

        return UsersRoleResource::collection($data);
    }

    /**
     * Simpan role baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rule' => 'required|string|max:255|unique:USERS_ROLE,RULE',
        ]);

        try {
            $id = DB::connection('oracle')->selectOne('SELECT USR_RULE.NEXTVAL AS id FROM dual')->id;

            DB::connection('oracle')->table('USERS_ROLE')->insert([
                'id' => $id,
                'rule' => $validated['rule'],
                'created_at' => now(),
            ]);

            $role = DB::connection('oracle')->table('USERS_ROLE')->where('id', $id)->first();

            return new UsersRoleResource($role);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tampilkan detail role
     */
    public function show($id)
    {
        $role = DB::connection('oracle')->table('USERS_ROLE')
                    ->where('id', $id)
                    ->whereNull('deleted_at')
                    ->first();

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Role tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $role,
        ]);
    }

    /**
     * Update role
     */
    public function update(Request $request, $id)
    {
        $role = DB::connection('oracle')->table('USERS_ROLE')
                    ->where('id', $id)
                    ->whereNull('deleted_at')
                    ->first();

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Role tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'rule' => 'required|string|max:255',
        ]);

        try {
            DB::connection('oracle')->table('USERS_ROLE')
                ->where('id', $id)
                ->update([
                    'rule' => $validated['rule'],
                    'updated_at' => now(),
                ]);

            $updatedRole = DB::connection('oracle')->table('USERS_ROLE')->where('id', $id)->first();

            return response()->json([
                'status' => true,
                'message' => 'Data role berhasil diperbarui',
                'data' => $updatedRole,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft delete role
     */
    public function destroy($id)
    {
        $affected = DB::connection('oracle')->table('USERS_ROLE')
                        ->where('id', $id)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => now()]);

        if ($affected === 0) {
            return response()->json([
                'status' => false,
                'message' => 'Role tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Role berhasil dihapus (soft delete)',
        ]);
    }
}
