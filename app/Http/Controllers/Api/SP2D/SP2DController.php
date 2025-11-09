<?php

namespace App\Http\Controllers\Api\SP2D;

use App\Http\Controllers\Controller;
use App\Models\SP2DModel;
use Illuminate\Http\Request;
use App\Http\Resources\SP2DResource;
use App\Models\AksesOperatorModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                    $rek->urusan          = $rek->urusan;
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
            'tahun' => 'required|string|max:4',
            'id_user' => 'required|integer',
            'nama_user' => 'required|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'kd_opd1' => 'nullable|string|max:5',
            'kd_opd2' => 'nullable|string|max:5',
            'kd_opd3' => 'nullable|string|max:5',
            'kd_opd4' => 'nullable|string|max:5',
            'kd_opd5' => 'nullable|string|max:5',
            'nama_file' => 'required|string|max:255',
            'nama_file_asli' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // max 10MB
            'file_tte' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'tanggal_upload' => 'nullable|date',
            'kode_file' => 'nullable|string|max:255',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string|max:500',
            'proses' => 'nullable|string|max:50',
            'supervisor_proses' => 'nullable|string|max:255',
            'urusan' => 'nullable|string|max:255',
            'kd_ref1' => 'nullable|string|max:5',
            'kd_ref2' => 'nullable|string|max:5',
            'kd_ref3' => 'nullable|string|max:5',
            'kd_ref4' => 'nullable|string|max:5',
            'kd_ref5' => 'nullable|string|max:5',
            'kd_ref6' => 'nullable|string|max:5',
            'no_spm' => 'nullable|string|max:50',
            'jenis_berkas' => 'nullable|string|max:50',
            'id_berkas' => 'nullable|integer',
            'agreement' => 'nullable|string|max:50',
            'kd_belanja1' => 'nullable|string|max:5',
            'kd_belanja2' => 'nullable|string|max:5',
            'kd_belanja3' => 'nullable|string|max:5',
            'jenis_belanja' => 'nullable|string|max:50',
            'nilai_belanja' => 'nullable|numeric',
            'status_laporan' => 'nullable|string|max:50',
        ]);
    
        try {
            $disk = Storage::disk('public');
            $folder = 'sp2d/' . date('Ymd');
    
            // Simpan file nama_file_asli jika ada
            if ($request->hasFile('nama_file_asli')) {
                $file = $request->file('nama_file_asli');
                $path = $file->store($folder, 'public');
                $validated['nama_file_asli'] = $path;
            }
    
            // Simpan file file_tte jika ada
            if ($request->hasFile('file_tte')) {
                $fileTte = $request->file('file_tte');
                $pathTte = $fileTte->store($folder, 'public');
                $validated['file_tte'] = $pathTte;
            }
    
            // Simpan data ke database
            $sp2d = SP2DModel::create(array_merge($validated, [
                'created_at' => now(),
            ]));
    
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil disimpan',
                'data' => new SP2DResource($sp2d),
            ]);
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
        $sp2d = SP2DModel::where('id_sp2d', $id)
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
        $sp2d = SP2DModel::where('id_sp2d', $id)
                         ->whereNull('deleted_at')
                         ->first();
    
        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        $validated = $request->validate([
            'tahun' => 'nullable|string|max:4',
            'nama_user' => 'nullable|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'nama_file' => 'nullable|string|max:255',
            'nama_file_asli' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'file_tte' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'tanggal_upload' => 'nullable|date',
            'alasan_tolak' => 'nullable|string|max:500',
            'proses' => 'nullable|string|max:50',
            'supervisor_proses' => 'nullable|string|max:255',
            'urusan' => 'nullable|string|max:255',
            'status_laporan' => 'nullable|string|max:50',
        ]);
    
        try {
            $disk = Storage::disk('public');
            $folder = 'sp2d/' . date('Ymd');
    
            // handle nama_file_asli
            if ($request->hasFile('nama_file_asli')) {
                if ($sp2d->nama_file_asli && $disk->exists($sp2d->nama_file_asli)) {
                    $disk->delete($sp2d->nama_file_asli);
                }
                $file = $request->file('nama_file_asli');
                $path = $file->store($folder, 'public');
                $validated['nama_file_asli'] = $path;
            } else {
                unset($validated['nama_file_asli']);
            }
    
            // handle file_tte
            if ($request->hasFile('file_tte')) {
                if ($sp2d->file_tte && $disk->exists($sp2d->file_tte)) {
                    $disk->delete($sp2d->file_tte);
                }
                $fileTte = $request->file('file_tte');
                $pathTte = $fileTte->store($folder, 'public');
                $validated['file_tte'] = $pathTte;
            } else {
                unset($validated['file_tte']);
            }
    
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
        $sp2d = SP2DModel::where('id_sp2d', $id)
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

    public function downloadBerkas(int $id)
    {
        // Ambil data permohonan SPD berdasarkan id
        $permohonan = SP2DModel::findOrFail($id);

        $filePath = $permohonan->nama_file_asli;

        // Cek apakah file ada di disk public
        $disk = Storage::disk('public');
        if (!$disk->exists($filePath)) {
            abort(404, "File tidak ditemukan");
        }

        // Download file dengan nama asli
        return response()->download($disk->path($filePath), basename($filePath));
    }
}
