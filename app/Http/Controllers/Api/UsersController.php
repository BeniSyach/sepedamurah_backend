<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    /**
     * List users (pagination + search)
     */
    public function index(Request $request)
    {
        $query = User::with(['rules.menus'])
            ->where('deleted', 0); // skip user yang sudah dihapus soft delete custom
    
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }
    
        $data = $query->orderBy('name', 'asc')
                      ->paginate($request->get('per_page', 10));
    
        return response()->json([
            'data' => $data->getCollection()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'nik' => $user->nik,
                    'nip' => $user->nip,
                    'name' => $user->name,
                    'email' => $user->email,
                    'no_hp' => $user->no_hp,
                    'kd_opd1' => $user->kd_opd1,
                    'kd_opd2' => $user->kd_opd2,
                    'kd_opd3' => $user->kd_opd3,
                    'kd_opd4' => $user->kd_opd4,
                    'kd_opd5' => $user->kd_opd5,
                    'is_active' => $user->is_active,
                    'date_created' => $user->date_created,
                    'updated_at' => $user->updated_at,
                    'deleted' => $user->deleted,
                    'access_level' => $user->rules->pluck('rule')->implode(', '),
                    // 🧩 Tambahan SKPD dari accessor model
                    'skpd' => $user->skpd ? [
                        'kd_opd1' => $user->skpd->kd_opd1,
                        'kd_opd2' => $user->skpd->kd_opd2,
                        'kd_opd3' => $user->skpd->kd_opd3,
                        'kd_opd4' => $user->skpd->kd_opd4,
                        'kd_opd5' => $user->skpd->kd_opd5,
                        'nm_opd' => $user->skpd->nm_opd ?? null,
                    ] : null,
    
                    // 🧠 Rules & Menus
                    'rules' => $user->rules->map(function ($rule) {
                        return [
                            'id' => $rule->id,
                            'rule' => $rule->rule,
                            'menus' => $rule->menus->map(function ($menu) {
                                return [
                                    'id' => $menu->id,
                                    'role_id' => $menu->role_id,
                                    'menu' => $menu->menu,
                                ];
                            }),
                        ];
                    }),
                ];
            }),
    
            // 📦 Pagination info sama seperti bawaan resource
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
                'path' => $data->path(),
                'per_page' => $data->perPage(),
                'to' => $data->lastItem(),
                'total' => $data->total(),
            ],
        ]);
    }
    
    

    /**
     * Show detail user
     */
    public function show($id)
    {
        try {
            $user = DB::connection('oracle')
                ->table(DB::raw('USERS'))
                ->where('ID', $id)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'NAME' => 'required|string|max:255',
                'EMAIL' => 'required|email|max:255',
                'NO_HP' => 'nullable|string|max:20',
                'IS_ACTIVE' => 'nullable|integer',
                'VISUALISASI_TTE' => 'nullable|string',
                'CHAT_ID' => 'nullable|string',
            ]);

            $user->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'User berhasil diperbarui',
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft delete user
     */
    public function destroy($id)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('USERS')
                ->where('ID', $id)
                ->update(['DELETED' => 1]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'User berhasil dihapus (soft delete)',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
