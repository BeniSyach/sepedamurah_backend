<?php

namespace App\Http\Controllers\Api\History;

use App\Http\Controllers\Controller;
use App\Models\LogUsersModel;
use Illuminate\Http\Request;
use App\Http\Resources\LogUsersResource;
use Illuminate\Support\Facades\DB;

class LogHapusUsersController extends Controller
{
    /**
     * List log users (pagination + search)
     */
    public function index(Request $request)
    {
        $query = LogUsersModel::with('user') // ✅ include relasi User
        ->whereNull('deleted_at');

        if ($search = $request->get('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('users_id', 'like', "%{$search}%")
            ->orWhere('deleted_by', 'like', "%{$search}%")
            ->orWhere('alasan', 'like', "%{$search}%")
            ->orWhereHas('user', function ($sub) use ($search) {
                // jika model User punya kolom name / username
                $sub->where('name', 'like', "%{$search}%");
            });
        });
        }

        $data = $query->orderBy('deleted_time', 'desc')
                ->paginate($request->get('per_page', 10));

        return LogUsersResource::collection($data);
    }

    /**
     * Store log baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'users_id' => 'required|integer',
            'deleted_time' => 'required|date',
            'deleted_by' => 'required|string|max:255',
            'alasan' => 'nullable|string|max:500',
        ]);

        try {
            // Ambil ID dari sequence Oracle
            $id = DB::connection('oracle')->selectOne('SELECT NO_USER_DELETE_LOG.NEXTVAL AS log_id FROM dual')->log_id;

            DB::connection('oracle')->table('USER_DELETE_LOG')->insert(array_merge($validated, [
                'log_id' => $id,
                'created_at' => now(),
            ]));

            $log = DB::connection('oracle')->table('USER_DELETE_LOG')->where('log_id', $id)->first();

            return new LogUsersResource($log);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail log user
     */
    public function show($id)
    {
        $log = DB::connection('oracle')->table('USER_DELETE_LOG')
                    ->where('log_id', $id)
                    ->whereNull('deleted_at')
                    ->first();

        if (!$log) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $log,
        ]);
    }

    /**
     * Update log user
     */
    public function update(Request $request, $id)
    {
        $log = DB::connection('oracle')->table('USER_DELETE_LOG')
                    ->where('log_id', $id)
                    ->whereNull('deleted_at')
                    ->first();

        if (!$log) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'users_id' => 'required|integer',
            'deleted_time' => 'required|date',
            'deleted_by' => 'required|string|max:255',
            'alasan' => 'nullable|string|max:500',
        ]);

        try {
            DB::connection('oracle')->table('USER_DELETE_LOG')
                ->where('log_id', $id)
                ->update(array_merge($validated, [
                    'updated_at' => now(),
                ]));

            $updatedLog = DB::connection('oracle')->table('USER_DELETE_LOG')->where('log_id', $id)->first();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => $updatedLog,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft delete log
     */
    public function destroy($id)
    {
        $affected = DB::connection('oracle')->table('USER_DELETE_LOG')
                        ->where('log_id', $id)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => now()]);

        if ($affected === 0) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }
}
