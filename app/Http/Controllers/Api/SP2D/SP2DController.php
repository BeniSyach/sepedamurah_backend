<?php

namespace App\Http\Controllers\Api\SP2D;

use App\Http\Controllers\Controller;
use App\Models\SP2DModel;
use Illuminate\Http\Request;
use App\Http\Resources\SP2DResource;
use App\Models\AksesOperatorModel;
use Illuminate\Support\Facades\DB;

class SP2DController extends Controller
{
    /**
     * List SP2D (pagination + search)
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search  = $request->get('search');
    
        // 🔍 Query dasar SP2D + relasi yang bisa di-eager-load
        $query = Sp2dModel::query()
            ->with(['rekening', 'sumberDana', 'sp2dkirim']) // relasi Eloquent valid
            ->whereNull('deleted_at');

        if ($menu = $request->get('menu')) {

            if($menu == 'permohonan_sp2d'){
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
            // ambil data yg belum diperiksa operator
            $query->where('id_operator', '0');
            $query->whereNull('diterima')->whereNull('ditolak');
            }

            if($menu == 'berkas_masuk_sp2d'){
                // Ambil data SKPD dari operator yang login
                $operator = AksesOperatorModel::where('id_operator', $request->get('user_id'))->first();
    
                if ($operator) {
                    // tampilkan berkas dari SKPD yang diampunya
                    $query->where(function ($q) use ($operator) {
                        $q->where('kd_opd1', $operator->kd_opd1)
                        ->where('kd_opd2', $operator->kd_opd2)
                        ->where('kd_opd3', $operator->kd_opd3)
                        ->where('kd_opd4', $operator->kd_opd4)
                        ->where('kd_opd5', $operator->kd_opd5);
                    });
                }
                // hanya tampilkan yang belum diverifikasi
                $query->whereNull('diterima')->whereNull('ditolak');
            }

            // ✅ SPD Diterima
            if ($menu === 'sp2d_diterima') {
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
                $query->whereNotNull('diterima'); // hanya yang sudah diterima
            }

            // (opsional) kalau kamu juga punya 'sp2d_ditolak'
            if ($menu === 'sp2d_ditolak') {
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
                $query->whereNotNull('ditolak'); // hanya yang ditolak
            }

            // (opsional) kalau kamu juga punya 'sp2d_publish_kuasa_bud'
            if ($menu === 'sp2d_publish_kuasa_bud') {
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
                $query->whereHas('sp2dkirim', function ($q) {
                    $q->whereNotNull('publish')
                      ->where('publish', '1');
                });
            }

            // (opsional) kalau kamu juga punya 'sp2d_publish_kuasa_bud'
            if ($menu === 'sp2d_kirim_bank') {
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
                $query->whereNotNull('diterima');
                $query->whereHas('sp2dkirim', function ($q) {
                    $q->whereNotNull('tgl_kirim_kebank');
                });
            }

            // (opsional) kalau kamu juga punya 'sp2d_publish_kuasa_bud'
            if ($menu === 'sp2d_tte') {
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
                $query->whereNotNull('diterima');
                $query->whereHas('sp2dkirim', function ($q) {
                    $q->whereNull('tgl_tte');
                });
            }
        }
    
        // 🔎 Pencarian fleksibel
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_user', 'like', "%{$search}%")
                  ->orWhere('nama_operator', 'like', "%{$search}%")
                  ->orWhere('nama_file', 'like', "%{$search}%");
            });
        }
    
        // 🔽 Urutan dan pagination
        $data = $query->orderBy('tanggal_upload', 'desc')
                      ->paginate($perPage);
    
        // ==========================================================
        // 🔗 Transformasi agar accessor & relasi manual ikut tampil
        // ==========================================================
        $data->getCollection()->transform(function ($item) {
            // relasi accessor (akan menjalankan getXxxAttribute)
            $item->program     = $item->program;
            $item->kegiatan    = $item->kegiatan;
            $item->subkegiatan = $item->subkegiatan;
            $item->rekening    = $item->rekening;
            $item->bu          = $item->bu;
            $item->skpd        = $item->skpd;
    
            // kalau SP2D punya relasi rekening (hasMany)
            if ($item->relationLoaded('rekening')) {
                $item->rekening->transform(function ($rek) {
                    $rek->program     = $rek->program;
                    $rek->kegiatan    = $rek->kegiatan;
                    $rek->subkegiatan = $rek->subkegiatan;
                    $rek->rekening    = $rek->rekening;
                    $rek->bu          = $rek->bu;
                    return $rek;
                });
            }
    
            // kalau SP2D punya relasi sumberDana
            if ($item->relationLoaded('sumberDana')) {
                $item->sumberDana->transform(function ($sd) {
                    $sd->referensi = $sd->sumberDana;
                    return $sd;
                });
            }
    
            return $item;
        });
    
        // 🔙 Return JSON lengkap dengan pagination meta
        return response()->json([
            'success' => true,
            'message' => 'Daftar SP2D berhasil diambil',
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
                'from'         => $data->firstItem(),
                'to'           => $data->lastItem(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last'  => $data->url($data->lastPage()),
                'prev'  => $data->previousPageUrl(),
                'next'  => $data->nextPageUrl(),
            ],
        ]);
    }
    

    /**
     * Store SP2D baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'TAHUN' => 'required|string|max:4',
            'ID_USER' => 'required|integer',
            'NAMA_USER' => 'required|string|max:255',
            'ID_OPERATOR' => 'nullable|integer',
            'NAMA_OPERATOR' => 'nullable|string|max:255',
            'KD_OPD1' => 'nullable|string|max:5',
            'KD_OPD2' => 'nullable|string|max:5',
            'KD_OPD3' => 'nullable|string|max:5',
            'KD_OPD4' => 'nullable|string|max:5',
            'KD_OPD5' => 'nullable|string|max:5',
            'NAMA_FILE' => 'required|string|max:255',
            'NAMA_FILE_ASLI' => 'nullable|string|max:255',
            'FILE_TTE' => 'nullable|string|max:255',
            'TANGGAL_UPLOAD' => 'nullable|date',
            'KODE_FILE' => 'nullable|string|max:255',
            'DITERIMA' => 'nullable|date',
            'DITOLAK' => 'nullable|date',
            'ALASAN_TOLAK' => 'nullable|string|max:500',
            'PROSES' => 'nullable|string|max:50',
            'SUPERVISOR_PROSES' => 'nullable|string|max:255',
            'URUSAN' => 'nullable|string|max:255',
            'KD_REF1' => 'nullable|string|max:5',
            'KD_REF2' => 'nullable|string|max:5',
            'KD_REF3' => 'nullable|string|max:5',
            'KD_REF4' => 'nullable|string|max:5',
            'KD_REF5' => 'nullable|string|max:5',
            'KD_REF6' => 'nullable|string|max:5',
            'NO_SPM' => 'nullable|string|max:50',
            'JENIS_BERKAS' => 'nullable|string|max:50',
            'ID_BERKAS' => 'nullable|integer',
            'AGREEMENT' => 'nullable|string|max:50',
            'KD_BELANJA1' => 'nullable|string|max:5',
            'KD_BELANJA2' => 'nullable|string|max:5',
            'KD_BELANJA3' => 'nullable|string|max:5',
            'JENIS_BELANJA' => 'nullable|string|max:50',
            'NILAI_BELANJA' => 'nullable|numeric',
            'STATUS_LAPORAN' => 'nullable|string|max:50',
        ]);

        try {
            // Ambil ID dari sequence Oracle (jika ada)
            $id = DB::connection('oracle')->selectOne('SELECT NO_SP2D.NEXTVAL AS ID FROM dual')->ID;

            $sp2d = SP2DModel::create(array_merge($validated, [
                'ID_SP2D' => $id,
                'created_at' => now(),
            ]));

            return new SP2DResource($sp2d);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail SP2D
     */
    public function show($id)
    {
        $sp2d = SP2DModel::where('ID_SP2D', $id)
                         ->whereNull('deleted_at')
                         ->first();

        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new SP2DResource($sp2d);
    }

    /**
     * Update SP2D
     */
    public function update(Request $request, $id)
    {
        $sp2d = SP2DModel::where('ID_SP2D', $id)
                         ->whereNull('deleted_at')
                         ->first();

        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'TAHUN' => 'required|string|max:4',
            'NAMA_USER' => 'required|string|max:255',
            'ID_OPERATOR' => 'nullable|integer',
            'NAMA_OPERATOR' => 'nullable|string|max:255',
            'NAMA_FILE' => 'required|string|max:255',
            'NAMA_FILE_ASLI' => 'nullable|string|max:255',
            'FILE_TTE' => 'nullable|string|max:255',
            'TANGGAL_UPLOAD' => 'nullable|date',
            'ALASAN_TOLAK' => 'nullable|string|max:500',
            'PROSES' => 'nullable|string|max:50',
            'SUPERVISOR_PROSES' => 'nullable|string|max:255',
            'URUSAN' => 'nullable|string|max:255',
            'STATUS_LAPORAN' => 'nullable|string|max:50',
        ]);

        try {
            $sp2d->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new SP2DResource($sp2d),
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
     * Soft delete SP2D
     */
    public function destroy($id)
    {
        $sp2d = SP2DModel::where('ID_SP2D', $id)
                         ->whereNull('deleted_at')
                         ->first();

        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $sp2d->deleted_at = now();
        $sp2d->save();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }
}
