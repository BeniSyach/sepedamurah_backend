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
        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where('NAME', 'like', "%{$search}%")
                  ->orWhere('EMAIL', 'like', "%{$search}%")
                  ->orWhere('NIK', 'like', "%{$search}%")
                  ->orWhere('NIP', 'like', "%{$search}%");
        }

        $data = $query->orderBy('NAME', 'asc')
                      ->paginate($request->get('per_page', 10));

        return UserResource::collection($data);
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
