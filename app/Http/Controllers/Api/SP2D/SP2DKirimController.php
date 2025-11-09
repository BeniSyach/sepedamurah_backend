<?php

namespace App\Http\Controllers\Api\SP2D;

use App\Http\Controllers\Controller;
use App\Models\SP2DKirimModel;
use Illuminate\Http\Request;
use App\Http\Resources\SP2DKirimResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SP2DKirimController extends Controller
{
    /**
     * List SP2D Kirim (pagination + search)
     */
    public function index(Request $request)
    {
        $query = SP2DKirimModel::query()->whereNull('deleted_at');

        if ($search = $request->get('search')) {
            $query->where('nama_penerima', 'like', "%{$search}%")
                  ->orWhere('nama_operator', 'like', "%{$search}%")
                  ->orWhere('namafile', 'like', "%{$search}%");
        }

        $data = $query->orderBy('tanggal_upload', 'desc')
                      ->paginate($request->get('per_page', 10));

        return SP2DKirimResource::collection($data);
    }

    /**
     * Store SP2D Kirim baru
     */
    public function store(Request $request)
    {
        // 🧩 Validasi data
        $validated = $request->validate([
            'tahun' => 'required|string|max:4',
            'id_berkas' => 'required|integer',
            'id_penerima' => 'required|integer',
            'nama_penerima' => 'required|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'namafile' => 'required|string|max:255',
            'nama_file_asli' => 'required|file|mimes:pdf|max:5120', // max 5MB PDF
            'tanggal_upload' => 'nullable|date',
            'keterangan' => 'nullable|string|max:500',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'tte' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'tgl_tte' => 'nullable|date',
            'alasan_tolak' => 'nullable|string|max:500',
            'tgl_kirim_kebank' => 'nullable|date',
            'id_penandatangan' => 'nullable|integer',
            'nama_penandatangan' => 'nullable|string|max:255',
            'file_tte' => 'nullable|file|mimes:pdf|max:5120', // file PDF opsional
            'kd_opd1' => 'nullable|string|max:5',
            'kd_opd2' => 'nullable|string|max:5',
            'kd_opd3' => 'nullable|string|max:5',
            'kd_opd4' => 'nullable|string|max:5',
            'kd_opd5' => 'nullable|string|max:5',
            'publish' => 'nullable|string|max:50',
        ]);
    
        try {
            // 🚀 Simpan file ke folder berbeda
            $pathNamaFile = $request->file('nama_file_asli')
                ? $request->file('nama_file_asli')->store('sp2d_kirim', 'public')
                : null;
    
            $pathFileTte = $request->file('file_tte')
                ? $request->file('file_tte')->store('sp2d_tte', 'public')
                : null;
    
            // 🧱 Simpan data ke database
            $sp2d = SP2DKirimModel::create([
                ...$validated,
                'nama_file_asli' => $pathNamaFile,
                'file_tte' => $pathFileTte,
                'date_created' => now(),
                'created_at' => now(),
            ]);
    
            // 🟢 Response sukses
            return response()->json([
                'status' => true,
                'message' => 'Data SP2D berhasil disimpan',
                'data' => new SP2DKirimResource($sp2d),
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
     * Detail SP2D Kirim
     */
    public function show($id)
    {
        $sp2d = SP2DKirimModel::where('id', $id)
                               ->whereNull('deleted_at')
                               ->first();

        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new SP2DKirimResource($sp2d);
    }

    /**
     * Update SP2D Kirim
     */
    public function update(Request $request, $id)
    {
        $sp2d = SP2DKirimModel::where('id', $id)
            ->whereNull('deleted_at')
            ->first();
    
        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        // 🧩 Validasi data
        $validated = $request->validate([
            'tahun' => 'nullable|string|max:4',
            'id_berkas' => 'nullable|integer',
            'id_penerima' => 'nullable|integer',
            'nama_penerima' => 'nullable|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'namafile' => 'nullable|string|max:255',
            'nama_file_asli' => 'nullable|file|mimes:pdf|max:5120', // opsional, PDF max 5MB
            'tanggal_upload' => 'nullable|date',
            'keterangan' => 'nullable|string|max:500',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'tte' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'tgl_tte' => 'nullable|date',
            'alasan_tolak' => 'nullable|string|max:500',
            'tgl_kirim_kebank' => 'nullable|date',
            'id_penandatangan' => 'nullable|integer',
            'nama_penandatangan' => 'nullable|string|max:255',
            'file_tte' => 'nullable|file|mimes:pdf|max:5120', // opsional
            'kd_opd1' => 'nullable|string|max:5',
            'kd_opd2' => 'nullable|string|max:5',
            'kd_opd3' => 'nullable|string|max:5',
            'kd_opd4' => 'nullable|string|max:5',
            'kd_opd5' => 'nullable|string|max:5',
            'publish' => 'nullable|string|max:50',
        ]);
    
        try {
            // 🚀 Upload file baru jika ada
            if ($request->hasFile('nama_file_asli')) {
                $pathNamaFile = $request->file('nama_file_asli')->store('sp2d_kirim', 'public');
    
                // hapus file lama
                if ($sp2d->nama_file_asli && Storage::disk('public')->exists($sp2d->nama_file_asli)) {
                    Storage::disk('public')->delete($sp2d->nama_file_asli);
                }
    
                $validated['nama_file_asli'] = $pathNamaFile;
            }
    
            if ($request->hasFile('file_tte')) {
                $pathFileTte = $request->file('file_tte')->store('sp2d_tte', 'public');
    
                // hapus file TTE lama
                if ($sp2d->file_tte && Storage::disk('public')->exists($sp2d->file_tte)) {
                    Storage::disk('public')->delete($sp2d->file_tte);
                }
    
                $validated['file_tte'] = $pathFileTte;
            }
    
            // 🕒 Update timestamp
            $validated['updated_at'] = now();
    
            // Jika kolom date_created wajib isi (di Oracle kadang NOT NULL)
            if (empty($sp2d->date_created)) {
                $validated['date_created'] = now();
            }
    
            $sp2d->update($validated);
    
            return response()->json([
                'status' => true,
                'message' => 'Data SP2D berhasil diperbarui',
                'data' => new SP2DKirimResource($sp2d->fresh()),
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
     * Soft delete SP2D Kirim
     */
    public function destroy($id)
    {
        $sp2d = SP2DKirimModel::where('id', $id)
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
        $permohonan = SP2DKirimModel::findOrFail($id);

        $filePath = $permohonan->nama_file_asli;

        // Cek apakah file ada di disk public
        $disk = Storage::disk('public');
        if (!$disk->exists($filePath)) {
            abort(404, "File tidak ditemukan");
        }

        // Download file dengan nama asli
        return response()->download($disk->path($filePath), basename($filePath));
    }

    
    public function downloadBerkasTTE(int $id)
    {
        // Ambil data permohonan SPD berdasarkan id
        $permohonan = SP2DKirimModel::findOrFail($id);

        $filePath = $permohonan->file_tte;

        // Cek apakah file ada di disk public
        $disk = Storage::disk('public');
        if (!$disk->exists($filePath)) {
            abort(404, "File tidak ditemukan");
        }

        // Download file dengan nama asli
        return response()->download($disk->path($filePath), basename($filePath));
    }
}
