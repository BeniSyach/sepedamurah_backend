<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Models\UsersPermissionModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UsersController extends Controller
{
    /**
     * List users (pagination + search)
     */
    public function index(Request $request)
    {
        $query = User::with(['rules.menus'])
        ->join('ref_opd', function ($join) {
            $join->on('users.kd_opd1', '=', 'ref_opd.kd_opd1')
                 ->on('users.kd_opd2', '=', 'ref_opd.kd_opd2')
                 ->on('users.kd_opd3', '=', 'ref_opd.kd_opd3')
                 ->on('users.kd_opd4', '=', 'ref_opd.kd_opd4')
                 ->on('users.kd_opd5', '=', 'ref_opd.kd_opd5');
        })
        ->where('users.deleted', 0)
        ->where('users.id', '!=', 54)
        ->where('users.id', '!=', 61);
    
    
        if ($search = strtolower($request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(users.name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(users.email) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(users.nik) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(users.nip) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(ref_opd.nm_opd) LIKE ?', ["%{$search}%"]); // ← search OPD
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
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'nullable|string|max:20',
            'nip' => 'nullable|string|max:25',
            'name' => 'required|string|max:128',
            'email' => 'required|email|max:128|unique:USERS,email',
            'no_hp' => 'nullable|string|max:20',
            'kd_opd1' => 'nullable|string|size:2',
            'kd_opd2' => 'nullable|string|size:2',
            'kd_opd3' => 'nullable|string|size:2',
            'kd_opd4' => 'nullable|string|size:2',
            'kd_opd5' => 'nullable|string|size:2',
            'image' => 'nullable|file|image|max:2048', // file opsional
            'visualisasi_tte' => 'nullable|file|max:5120', // file opsional, max 5MB
            'password' => 'required|string|min:8',
            'confirmPassword' => 'required|string|min:8',
            'chat_id' => 'nullable|string|max:225',
            'role' => 'required|array|min:1', // pastikan array role dikirim
            'role.*' => 'required|string',
        ]);

        if ($request->password !== $request->confirmPassword) {
            return response()->json([
                'status' => false,
                'message' => 'Password dan konfirmasi password tidak cocok',
            ], 422);
        }
    
        try {
            $pathImage = $request->hasFile('image') ? $request->file('image')->store('profile', 'public') : ' ';
            $pathTte = $request->hasFile('visualisasi_tte') ? $request->file('visualisasi_tte')->store('visualisasi_tte', 'public') : ' ';
    
            $user = User::create([
                'nik' => $validated['nik'] ?? null,
                'nip' => $validated['nip'] ?? null,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'no_hp' => $validated['no_hp'] ?? null,
                'kd_opd1' => $validated['kd_opd1'] ?? null,
                'kd_opd2' => $validated['kd_opd2'] ?? null,
                'kd_opd3' => $validated['kd_opd3'] ?? null,
                'kd_opd4' => $validated['kd_opd4'] ?? null,
                'kd_opd5' => $validated['kd_opd5'] ?? null,
                'image' => $pathImage,
                'visualisasi_tte' => $pathTte,
                'password' => bcrypt($validated['password']),
                'is_active' => 0,
                'chat_id' => $validated['chat_id'] ?? null,
                'deleted' => 0,
            ]);

            // ✅ Insert ke users_permissions
            foreach ($validated['role'] as $roleId) {
                UsersPermissionModel::create([
                    'users_id' => $user->id,
                    'users_rule_id' => $roleId,
                ]);
            }
    
            return response()->json([
                'status' => true,
                'message' => 'User berhasil dibuat',
                'data' => $user,
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
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
        $user = User::where('id', $id)
                    ->where('deleted', 0)
                    ->first();
    
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }
    
        $validated = $request->validate([
            'nik' => 'nullable|string|max:20',
            'nip' => 'nullable|string|max:25',
            'name' => 'nullable|string|max:128',
            'email' => 'nullable|email|max:128|unique:USERS,email,' . $id,
            'no_hp' => 'nullable|string|max:20',
            'kd_opd1' => 'nullable|string|size:2',
            'kd_opd2' => 'nullable|string|size:2',
            'kd_opd3' => 'nullable|string|size:2',
            'kd_opd4' => 'nullable|string|size:2',
            'kd_opd5' => 'nullable|string|size:2',
            'image' => 'nullable|file|image|max:2048',
            'visualisasi_tte' => 'nullable|file|max:5120',
            'password' => 'nullable|string|min:8',
            'confirmPassword' => 'nullable|string|min:8',
            'chat_id' => 'nullable|string|max:225',
            'role' => 'nullable|array|min:1', // pastikan array role dikirim
            'role.*' => 'nullable|string',
        ]);
    
        // Password validation jika diisi
        if ($request->password || $request->confirmPassword) {
            if ($request->password !== $request->confirmPassword) {
                return response()->json([
                    'status' => false,
                    'message' => 'Password dan konfirmasi password tidak cocok',
                ], 422);
            }
            $user->password = bcrypt($validated['password']);
        }
    
        // Upload foto profil
        if ($request->hasFile('image')) {
            if ($user->image && Storage::disk('public')->exists('profile/' . $user->image)) {
                Storage::disk('public')->delete('profile/' . $user->image);
            }

            $path = $request->file('image')->store('profile', 'public');
            $user->image = basename($path); // hanya nama file
        }

        // Upload visualisasi TTE
        if ($request->hasFile('visualisasi_tte')) {
            if ($user->visualisasi_tte && Storage::disk('public')->exists('visualisasi_tte/' . $user->visualisasi_tte)) {
                Storage::disk('public')->delete('visualisasi_tte/' . $user->visualisasi_tte);
            }

            $path = $request->file('visualisasi_tte')->store('visualisasi_tte', 'public');
            $user->visualisasi_tte = basename($path); // hanya nama file
        }

    
        // Update field lain
        $user->nik = $validated['nik'] ?? $user->nik;
        $user->nip = $validated['nip'] ?? $user->nip;
        $user->name = $validated['name']?? $user->name;
        $user->email = $validated['email'] ?? $user->email;
        $user->no_hp = $validated['no_hp'] ?? $user->no_hp;
        $user->kd_opd1 = $validated['kd_opd1'] ?? $user->kd_opd1;
        $user->kd_opd2 = $validated['kd_opd2'] ?? $user->kd_opd2;
        $user->kd_opd3 = $validated['kd_opd3'] ?? $user->kd_opd3;
        $user->kd_opd4 = $validated['kd_opd4'] ?? $user->kd_opd4;
        $user->kd_opd5 = $validated['kd_opd5'] ?? $user->kd_opd5;
        $user->chat_id = $validated['chat_id'] ?? $user->chat_id;
    
        $user->save();
    
        // Sync roles
        if (!empty($validated['role'])) {
            // Hapus role lama
            UsersPermissionModel::where('users_id', $user->id)->forceDelete();

            // Insert role baru
            foreach ($validated['role'] as $roleId) {
                UsersPermissionModel::create([
                    'users_id' => $user->id,
                    'users_rule_id' => $roleId,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'User berhasil diperbarui',
            'data' => $user,
        ]);
    }
    

    /**
     * Soft delete user
     */
    public function destroy($id)
    {
        $user = User::where('id', $id)
                    ->where('deleted', 0)
                    ->first();
    
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }
    
        try {
            // Hapus file image jika ada
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }
    
            // Hapus file visualisasi_tte jika ada
            if ($user->visualisasi_tte && Storage::disk('public')->exists($user->visualisasi_tte)) {
                Storage::disk('public')->delete($user->visualisasi_tte);
            }
    
            // Soft delete
            $user->deleted = 1;
            $user->save();
    
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
