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
        $query = UsersRoleModel::query()
            ->with('menus')
            ->when($request->get('search'), function ($q, $search) {
                $q->where('rule', 'like', "%{$search}%");
            })
            ->orderBy('id', 'asc');

        $data = $query->paginate($request->get('per_page', 10));

        // Bentuk response menyerupai Laravel Resource
        return response()->json([
            'data' => $data->items(),
            'links' => [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $data->currentPage(),
                'from' => $data->firstItem(),
                'last_page' => $data->lastPage(),
                'path' => $request->url(),
                'per_page' => $data->perPage(),
                'to' => $data->lastItem(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * Simpan role baru
     */
    public function store(Request $request)
    {
        // Validasi input dari frontend
        $validated = $request->validate([
            'rule' => 'required|string|max:255',
            'menuIds' => 'required|array|min:1',    // menuIds wajib ada
            'menuIds.*' => 'string',                // tiap item string
        ]);
    
        try {
            // Mulai transaksi agar insert role dan menuIds konsisten
            DB::connection('oracle')->beginTransaction();
    
            // Insert ke USERS_ROLE (trigger akan isi ID otomatis)
            DB::connection('oracle')->table('USERS_ROLE')->insert([
                'rule' => $validated['rule'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            // Ambil ID terakhir yang baru dimasukkan
            $role = DB::connection('oracle')->table('USERS_ROLE')
                ->where('rule', $validated['rule'])
                ->select('id', 'rule', 'created_at', 'updated_at')
                ->first();
    
            $roleId = $role->id;
    
            // Insert menuIds ke USERS_ROLE_MENU
            foreach ($validated['menuIds'] as $menu) {
                DB::connection('oracle')->table('USERS_ROLE_MENU')->insert([
                    'role_id' => $roleId,
                    'menu' => $menu,
                ]);
            }
    
            DB::connection('oracle')->commit();
    
            return response()->json([
                'status' => true,
                'message' => 'role Berhasil ditambahkan',
            ]);
    
        } catch (\Exception $e) {
            DB::connection('oracle')->rollBack();
    
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
        // Cek apakah role ada
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
    
        // Validasi input
        $validated = $request->validate([
            'rule' => 'required|string|max:255',
            'menuIds' => 'required|array|min:1',    // menuIds wajib ada
            'menuIds.*' => 'string',                // tiap item string
        ]);
    
        try {
            DB::connection('oracle')->beginTransaction();
    
            // Update kolom role
            DB::connection('oracle')->table('USERS_ROLE')
                ->where('id', $id)
                ->update([
                    'rule' => $validated['rule'],
                    'updated_at' => now(),
                ]);
    
            // Hapus menu lama untuk role ini
            DB::connection('oracle')->table('USERS_ROLE_MENU')
                ->where('role_id', $id)
                ->delete();
    
            // Insert menuIds baru
            foreach ($validated['menuIds'] as $menu) {
                DB::connection('oracle')->table('USERS_ROLE_MENU')->insert([
                    'role_id' => $id,
                    'menu' => $menu,
                ]);
            }
    
            DB::connection('oracle')->commit();
    
            // Ambil data role terbaru beserta menu
            $updatedRole = DB::connection('oracle')->table('USERS_ROLE')
                ->where('id', $id)
                ->first();
    
            $menus = DB::connection('oracle')->table('USERS_ROLE_MENU')
                ->where('role_id', $id)
                ->pluck('menu');
    
            return response()->json([
                'status' => true,
                'message' => 'Data role berhasil diperbarui',
                'data' => [
                    'role' => $updatedRole,
                    'menus' => $menus,
                ],
            ]);
    
        } catch (\Exception $e) {
            DB::connection('oracle')->rollBack();
    
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
