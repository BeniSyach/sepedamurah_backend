<?php

namespace App\Http\Controllers\Api\SPD;

use App\Http\Controllers\Controller;
use App\Models\SPDTerkirimModel;
use Illuminate\Http\Request;
use App\Http\Resources\SPDTerkirimResource;
use Illuminate\Support\Facades\DB;

class SPDTerkirimController extends Controller
{
    /**
     * List SPD Terkirim (pagination + search)
     */
    public function index(Request $request)
    {
        $query = SPDTerkirimModel::query()->whereNull('deleted_at');

        if ($userId = $request->get('user_id')) {
            $query->where('id_penerima', $userId);
        }

        if ($menu = $request->get('menu')) {
            if ($menu === 'spd_tte') {
                // 🔍 Ambil data yang belum di-TTE (selain 'Yes')
                $query->where(function ($q) {
                    $q->where('tte', '!=', 'Yes')
                      ->orWhereNull('tte')
                      ->orWhere('tte', '=', '0')
                      ->orWhere('tte', '=', '');
                });
            }
        }

        if ($search = $request->get('search')) {
            $query->where('NAMA_PENERIMA', 'like', "%{$search}%")
                  ->orWhere('NAMA_OPERATOR', 'like', "%{$search}%")
                  ->orWhere('NAMAFILE', 'like', "%{$search}%");
        }

        $data = $query->orderBy('created_at', 'desc')
                      ->paginate($request->get('per_page', 10));

        // Attach skpd secara manual (karena tidak bisa eager load)
        $data->getCollection()->transform(function ($item) {
            $skpd = $item->skpd(); // panggil accessor manual
            $item->setRelation('skpd', $skpd); // daftarkan ke relasi Eloquent
            return $item;
        });

        return SPDTerkirimResource::collection($data);
    }

    /**
     * Store SPD Terkirim baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ID_BERKAS' => 'required|integer',
            'ID_PENERIMA' => 'required|integer',
            'NAMA_PENERIMA' => 'required|string|max:255',
            'ID_OPERATOR' => 'nullable|integer',
            'NAMA_OPERATOR' => 'nullable|string|max:255',
            'NAMAFILE' => 'required|string|max:255',
            'NAMA_FILE_ASLI' => 'nullable|string|max:255',
            'NAMA_FILE_LAMPIRAN' => 'nullable|string|max:255',
            'TANGGAL_UPLOAD' => 'nullable|date',
            'KETERANGAN' => 'nullable|string|max:500',
            'PARAF_KBUD' => 'nullable|string|max:50',
            'TGL_PARAF' => 'nullable|date',
            'TTE' => 'nullable|string|max:255',
            'PASSPHARASE' => 'nullable|string|max:255',
            'STATUS' => 'nullable|string|max:50',
            'TGL_TTE' => 'nullable|date',
            'ID_PENANDATANGAN' => 'nullable|integer',
            'NAMA_PENANDATANGAN' => 'nullable|string|max:255',
            'KD_OPD1' => 'nullable|string|max:5',
            'KD_OPD2' => 'nullable|string|max:5',
            'KD_OPD3' => 'nullable|string|max:5',
            'KD_OPD4' => 'nullable|string|max:5',
            'KD_OPD5' => 'nullable|string|max:5',
            'FILE_TTE' => 'nullable|string|max:255',
            'PUBLISH' => 'nullable|boolean',
        ]);

        try {
            // Ambil ID dari sequence Oracle
            $id = DB::connection('oracle')->selectOne('SELECT NO_SPD_TERKIRIM.NEXTVAL AS ID FROM dual')->ID;

            $spd = SPDTerkirimModel::create(array_merge($validated, [
                'ID' => $id,
                'CREATED_AT' => now(),
            ]));

            return new SPDTerkirimResource($spd);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail SPD Terkirim
     */
    public function show($id)
    {
        $spd = SPDTerkirimModel::where('ID', $id)
                               ->whereNull('DELETED_AT')
                               ->first();

        if (!$spd) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new SPDTerkirimResource($spd);
    }

    /**
     * Update SPD Terkirim
     */
    public function update(Request $request, $id)
    {
        $spd = SPDTerkirimModel::where('ID', $id)
                               ->whereNull('DELETED_AT')
                               ->first();

        if (!$spd) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'ID_BERKAS' => 'required|integer',
            'ID_PENERIMA' => 'required|integer',
            'NAMA_PENERIMA' => 'required|string|max:255',
            'ID_OPERATOR' => 'nullable|integer',
            'NAMA_OPERATOR' => 'nullable|string|max:255',
            'NAMAFILE' => 'required|string|max:255',
            'NAMA_FILE_ASLI' => 'nullable|string|max:255',
            'NAMA_FILE_LAMPIRAN' => 'nullable|string|max:255',
            'TANGGAL_UPLOAD' => 'nullable|date',
            'KETERANGAN' => 'nullable|string|max:500',
            'PARAF_KBUD' => 'nullable|string|max:50',
            'TGL_PARAF' => 'nullable|date',
            'TTE' => 'nullable|string|max:255',
            'PASSPHARASE' => 'nullable|string|max:255',
            'STATUS' => 'nullable|string|max:50',
            'TGL_TTE' => 'nullable|date',
            'ID_PENANDATANGAN' => 'nullable|integer',
            'NAMA_PENANDATANGAN' => 'nullable|string|max:255',
            'KD_OPD1' => 'nullable|string|max:5',
            'KD_OPD2' => 'nullable|string|max:5',
            'KD_OPD3' => 'nullable|string|max:5',
            'KD_OPD4' => 'nullable|string|max:5',
            'KD_OPD5' => 'nullable|string|max:5',
            'FILE_TTE' => 'nullable|string|max:255',
            'PUBLISH' => 'nullable|boolean',
        ]);

        try {
            $spd->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new SPDTerkirimResource($spd),
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
     * Soft delete SPD Terkirim
     */
    public function destroy($id)
    {
        $spd = SPDTerkirimModel::where('ID', $id)
                               ->whereNull('DELETED_AT')
                               ->first();

        if (!$spd) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $spd->DELETED_AT = now();
        $spd->save();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }
}
