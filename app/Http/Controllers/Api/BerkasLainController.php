<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BerkasLainModel;
use App\Http\Resources\BerkasLainResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\TTE_BSRE;
use App\Models\LogTTEModel;

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

        $filePath = $permohonan->file_sdh_tte;

        // Cek apakah file ada di disk public
        $disk = Storage::disk('public');
        if (!$disk->exists($filePath)) {
            abort(404, "File tidak ditemukan");
        }

        // Download file dengan nama asli
        return response()->download($disk->path($filePath), basename($filePath));
    }

    public function sign(Request $request)
    {
        $request->validate([
            'file'       => 'required|mimes:pdf|max:2048',
            'passphrase' => 'required',
            'tampilan'   => 'required',
            'nama_file'  => 'required',
            'id'    => 'required|integer'
        ]);

        $user = Auth::user();

        // Upload PDF sebelum sign
        $uploaded = $request->file('file');
        $saveName = $request->nama_file . ".pdf";
        $originalFilePath = $uploaded->storeAs("berkas_lain_original", $saveName, "public");
        $fullPath = storage_path("app/public/" . $originalFilePath);

        // Kirim ke BSRE
        $service = new TTE_BSRE();
        $result = $service->signPdf(
            $fullPath,
            $user->nik,
            $request->passphrase,
            $request->tampilan,
            $request->nama_file,
            $request->id
        );

        $errorCode = null;
        if (isset($result['detail']) && is_array($result['detail']) && isset($result['detail']['status_code'])) {
            $errorCode = $result['detail']['status_code'];
        }

        // ================================
        // LOG JIKA TTE GAGAL
        // ================================
        if ($result['status'] != 'success') {
            $detailRaw = $result['detail'] ?? null;

            if (is_string($detailRaw)) {
                // Jika berupa JSON string → decode
                $detail = json_decode($detailRaw, true);
            } elseif (is_array($detailRaw)) {
                // Jika sudah array → langsung pakai
                $detail = $detailRaw;
            } else {
                $detail = [];
            }
            
            $errorMsg = $detail['error'] ?? ($result['message'] ?? 'Unknown error');
            LogTTEModel::create([
                'id_berkas'         => $request->id,
                'kategori'          => 'berkas lain',
                'tte'               => 'Error',
                'status'            => 0,
                'tgl_tte'           => now(),
                'keterangan'        => "Gagal tandatangan dokumen berkas lain - $errorMsg",
                'message'           => $errorMsg,
                'id_penandatangan'  => $user->id,
                'nama_penandatangan'=> $user->name,
                'date_created'      => now(),
            ]);

            return response()->json([
                'status'     => 'error',
                'message'    => $result['message'] ?? 'Gagal terhubung ke server BSRE',
                'error_code' => $errorCode,
                'detail'     => $result['detail'] ?? null
            ], 400);
        }

        // ================================
        // UPDATE DATA berkas_lain JIKA SUKSES
        // ================================
        $berkas_lain = BerkasLainModel::find($request->id);
        $berkas_lain->update([
            'status_tte'            => 1,
            'file_sdh_tte'          => $result['file_path'],
        ]);

        // ================================
        // LOG JIKA TTE SUKSES
        // ================================
        LogTTEModel::create([
            'id_berkas'         => $request->id,
            'kategori'          => 'berkas lain',
            'tte'               => 'Yes',
            'status'            => 1,
            'tgl_tte'           => now(),
            'keterangan'        => 'Berhasil tandatangan dokumen berkas lain',
            'message'           => 'Tanda Tangan Berhasil Dan PDF berhasil disimpan.',
            'id_penandatangan'  => $user->id,
            'nama_penandatangan'=> $user->name,
            'date_created'      => now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil TTE',
            'file'    => $result['file_path']
        ]);
    }

    public function verify_tte($id)
    {
        // Ambil data Berkas Lain + Relasi User
        $data = BerkasLainModel::with(['user'])
            ->where('id', $id)
            ->first();
    
        if (!$data) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        return response()->json([
            'status'  => 'success',
            'message' => 'Data verifikasi ditemukan',
            'data' => [
                // Nama penandatangan dari relasi user
                'penandatangan'  => $data->user->name ?? '-',
    
                // Nama file asli / nama dokumen
                'nama_dokumen'   => $data->nama_dokumen ?? '-',
                'file_asli'      => $data->nama_file_asli ?? '-',
    
                // Status TTE
                'status_tte'     => $data->status_tte == 1 ? 'TTE Selesai' : 'Belum TTE',
    
                // File hasil TTE (jika ada)
                'file_sdh_tte'   => $data->file_sdh_tte ?? '-',
    
                // Tanggal TTE / tanggal surat
                'tanggal_tte'    => $data->tgl_surat ?? '-',
    
                // Raw data
                'raw'            => $data,
            ]
        ]);
    }
    
}
