<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BerkasLainModel;
use App\Http\Resources\BerkasLainResource;
use Illuminate\Support\Facades\Storage;

class BerkasLainController extends Controller
{
    /**
     * List Berkas Lain (pagination + search)
     */
    public function index(Request $request)
    {
        // Query BerkasLain dengan relasi user
        $query = BerkasLainModel::with('user')->whereNull('deleted_at');

        // Filter pencarian
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_dokumen', 'like', "%{$search}%")
                ->orWhere('nama_file_asli', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan user_id jika ada
        if ($userId = $request->get('user_id')) {
            $query->where('users_id', $userId);
        }

        // Pagination dan urut berdasarkan tanggal surat
        $data = $query->orderBy('tgl_surat', 'desc')
                    ->paginate($request->get('per_page', 10));
        
        // Transform data supaya user->skpd ikut tampil
        $data->getCollection()->transform(function ($item) {
            if ($item->relationLoaded('user') && $item->user) {
                $item->user->skpd = $item->user->skpd; // simpan ke properti baru
            }
            return $item;
        });

        // Return resource collection (pastikan BerkasLainResource menangani relasi user)
       // 🔙 Return JSON lengkap dengan pagination meta
       return response()->json([
        'success' => true,
        'message' => 'Daftar Berkas Lain berhasil diambil',
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
     * Store Berkas Lain baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tgl_surat' => 'required|date',
            'nama_file_asli' => 'required|file|mimes:pdf|max:5120', // file PDF max 5MB
            'nama_dokumen' => 'required|string|max:255',
            'status_tte' => 'nullable|string|max:50',
            'file_sdh_tte' => 'nullable|string|max:255',
            'users_id' => 'required|integer',
        ]);
    
        try {
            // Simpan file ke folder 'berkas_lain'
            $pathNamaFile = $request->file('nama_file_asli')->store('berkas_lain', 'public');
    
            // Ganti validated field dengan path file yang disimpan
            $validated['nama_file_asli'] = $pathNamaFile;
    
            // Simpan data ke database
            $berkas = BerkasLainModel::create($validated);
    
            return new BerkasLainResource($berkas);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    /**
     * Show detail Berkas Lain
     */
    public function show($id)
    {
        $berkas = BerkasLainModel::where('id', $id)
                                 ->whereNull('deleted_at')
                                 ->first();

        if (!$berkas) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new BerkasLainResource($berkas);
    }

    /**
     * Update Berkas Lain
     */
    public function update(Request $request, $id)
    {
        $berkas = BerkasLainModel::where('id', $id)
                                 ->whereNull('deleted_at')
                                 ->first();
    
        if (!$berkas) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        $validated = $request->validate([
            'tgl_surat' => 'required|date',
            'nama_file_asli' => 'nullable|file|mimes:pdf|max:5120', // file opsional untuk update
            'nama_dokumen' => 'required|string|max:255',
            'status_tte' => 'nullable|string|max:50',
            'file_sdh_tte' => 'nullable|string|max:255',
            'users_id' => 'required|integer',
        ]);
    
        try {
            // 🗑️ Hapus file lama & upload file baru jika ada
            if ($request->hasFile('nama_file_asli')) {
                if ($berkas->nama_file_asli && Storage::disk('public')->exists($berkas->nama_file_asli)) {
                    Storage::disk('public')->delete($berkas->nama_file_asli);
                }
    
                // Upload file baru
                $pathNamaFile = $request->file('nama_file_asli')->store('berkas_lain', 'public');
                $validated['nama_file_asli'] = $pathNamaFile;
            }
    
            // Update data
            $berkas->update($validated);
    
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new BerkasLainResource($berkas),
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
     * Soft delete Berkas Lain
     */
    public function destroy($id)
    {
        $berkas = BerkasLainModel::where('id', $id)
                                 ->whereNull('deleted_at')
                                 ->first();
    
        if (!$berkas) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        // 🗑️ Hapus file fisik jika ada
        if ($berkas->nama_file_asli && Storage::disk('public')->exists($berkas->nama_file_asli)) {
            Storage::disk('public')->delete($berkas->nama_file_asli);
        }
    
        if ($berkas->file_sdh_tte && Storage::disk('public')->exists($berkas->file_sdh_tte)) {
            Storage::disk('public')->delete($berkas->file_sdh_tte);
        }
    
        // Soft delete
        $berkas->deleted_at = now();
        $berkas->save();
    
        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete) dan file terkait juga dihapus',
        ]);
    }
    
    public function downloadBerkas(int $id)
    {
        // Ambil data permohonan SPD berdasarkan id
        $permohonan = BerkasLainModel::findOrFail($id);

        $filePath = $permohonan->nama_file_asli;
        return $filePath;
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
        $permohonan = BerkasLainModel::findOrFail($id);

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
