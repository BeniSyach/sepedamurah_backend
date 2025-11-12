<?php

namespace App\Http\Controllers\Api\SPD;

use App\Http\Controllers\Controller;
use App\Models\PermohonanSPDModel;
use Illuminate\Http\Request;
use App\Http\Resources\PermohonanSPDResource;
use App\Models\AksesOperatorModel;
use Illuminate\Support\Facades\Storage;

class PermohonanSPDController extends Controller
{
    /**
     * List permohonan SPD (pagination + search)
     */
    public function index(Request $request)
    {
        $query = PermohonanSPDModel::query()
        ->with(['pengirim', 'operator']) // eager load relasi
        ->whereNull('deleted_at'); // pastikan soft delete diabaikan


        if ($menu = $request->get('menu')) {

            if($menu == 'permohonan_spd'){
                
                if ($userId = $request->get('user_id')) {
                    $query->where('id_pengirim', $userId);
                }
                    // ambil data yg belum diperiksa operator
                    $query->where('id_operator', '0');
                    $query->whereNull('diterima')->whereNull('ditolak');
            }

            if($menu == 'berkas_masuk_spd'){
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
            if ($menu === 'spd_diterima') {
                
                if ($userId = $request->get('user_id')) {
                    $query->where('id_pengirim', $userId);
                }
                $query->whereNotNull('diterima'); // hanya yang sudah diterima
            }

            // (opsional) kalau kamu juga punya 'spd_ditolak'
            if ($menu === 'spd_ditolak') {
                
                if ($userId = $request->get('user_id')) {
                    $query->where('id_pengirim', $userId);
                }
                $query->whereNotNull('ditolak'); // hanya yang ditolak
            }
        }

        // 🔍 Filter pencarian
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pengirim', 'like', "%{$search}%")
                ->orWhere('nama_file', 'like', "%{$search}%")
                ->orWhere('jenis_berkas', 'like', "%{$search}%")
                ->orWhere('nama_operator', 'like', "%{$search}%");
            });
        }

        // 🔢 Pagination & urutan terbaru
        $data = $query->orderBy('date_created', 'desc')
                    ->paginate($request->get('per_page', 10));

                    
        // Attach skpd secara manual (karena tidak bisa eager load)
        $data->getCollection()->transform(function ($item) {
            $skpd = $item->skpd(); // panggil accessor manual
            $item->setRelation('skpd', $skpd); // daftarkan ke relasi Eloquent
            return $item;
        });

        // 🧾 Kembalikan hasil sebagai resource
        return PermohonanSpdResource::collection($data);
    }

    /**
     * Store permohonan baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_pengirim' => 'required|integer',
            'nama_pengirim' => 'required|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'jenis_berkas' => 'nullable|string|max:100',
            'nama_file' => 'required|string|max:255',
            'nama_file_asli' => 'required|file|mimes:pdf|max:5120', // <= HARUS PDF, max 5MB
            'kode_file' => 'nullable|string|max:100',
            'kd_opd1' => 'nullable|string|max:5',
            'kd_opd2' => 'nullable|string|max:5',
            'kd_opd3' => 'nullable|string|max:5',
            'kd_opd4' => 'nullable|string|max:5',
            'kd_opd5' => 'nullable|string|max:5',
        ]);

        try {
            // === 2️⃣ Simpan file PDF ke storage ===
            $file = $request->file('nama_file_asli');
            $tanggalFolder = now()->format('Ymd'); // contoh: 20251107
            $folder = "permohonan_spd/{$tanggalFolder}";

            // Pastikan folder ada di storage/app/public
            $path = $file->storeAs($folder, $file->getClientOriginalName(), 'public');

            // === 3️⃣ Simpan data ke database ===
            $permohonan = PermohonanSPDModel::create([
                'id_pengirim' => $validated['id_pengirim'],
                'nama_pengirim' => $validated['nama_pengirim'],
                'id_operator' => $validated['id_operator'] ?? 0,
                'nama_operator' => $validated['nama_operator'] ?? null,
                'jenis_berkas' => $validated['jenis_berkas'] ?? null,
                'nama_file' => $validated['nama_file'],
                'nama_file_asli' => $path, // simpan path hasil upload
                'kode_file' => $validated['kode_file'] ?? null,
                'kd_opd1' => $validated['kd_opd1'] ?? null,
                'kd_opd2' => $validated['kd_opd2'] ?? null,
                'kd_opd3' => $validated['kd_opd3'] ?? null,
                'kd_opd4' => $validated['kd_opd4'] ?? null,
                'kd_opd5' => $validated['kd_opd5'] ?? null,
                'tanggal_upload' => now(),
                'date_created' => now(),
            ]);

            return new PermohonanSPDResource($permohonan);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data ke database.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    /**
     * Detail permohonan SPD
     */
    public function show($id)
    {
        $permohonan = PermohonanSPDModel::where('ID', $id)
                                        ->whereNull('DELETED_AT')
                                        ->first();

        if (!$permohonan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new PermohonanSPDResource($permohonan);
    }

    /**
     * Update permohonan
     */
    public function update(Request $request, $id)
    {
        $permohonan = PermohonanSPDModel::where('id', $id)
                                        ->whereNull('deleted_at')
                                        ->first();
    
        if (!$permohonan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        // Validasi request
        $validated = $request->validate([
            'id_pengirim' => 'nullable|integer',
            'nama_pengirim' => 'nullable|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'jenis_berkas' => 'nullable|string|max:100',
            'nama_file' => 'nullable|string|max:255',
            'nama_file_asli' => 'nullable|file|mimes:pdf|max:10240', // max 10MB
            'tanggal_upload' => 'nullable|date',
            'kode_file' => 'nullable|string|max:100',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string|max:500',
            'proses' => 'nullable|string|max:50',
            'supervisor_proses' => 'nullable|string|max:50',
            'kd_opd1' => 'nullable|string|max:5',
            'kd_opd2' => 'nullable|string|max:5',
            'kd_opd3' => 'nullable|string|max:5',
            'kd_opd4' => 'nullable|string|max:5',
            'kd_opd5' => 'nullable|string|max:5',
        ]);
    
        $disk = Storage::disk('public');
    
        // Handle file update
        if ($request->hasFile('nama_file_asli')) {
            // Hapus file lama jika ada
            if ($permohonan->nama_file_asli && $disk->exists($permohonan->nama_file_asli)) {
                $disk->delete($permohonan->nama_file_asli);
            }
    
            // Simpan file baru
            $file = $request->file('nama_file_asli');
            $path = $file->store('permohonan_spd/' . date('Ymd'), 'public');
    
            $validated['nama_file_asli'] = $path;
        } else {
            // Jika tidak ada file baru, biarkan tetap
            unset($validated['nama_file_asli']);
        }
    
        try {
            $permohonan->update($validated);
    
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new PermohonanSPDResource($permohonan),
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
     * Soft delete permohonan
     */
    public function destroy($id)
    {
        $permohonan = PermohonanSPDModel::where('id', $id)
                                        ->whereNull('deleted_at')
                                        ->first();
    
        if (!$permohonan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        // Hapus file fisik jika ada
        $disk = Storage::disk('public');
        if ($permohonan->NAMA_FILE_ASLI && $disk->exists($permohonan->NAMA_FILE_ASLI)) {
            $disk->delete($permohonan->NAMA_FILE_ASLI);
        }
    
        // Soft delete di database
        $permohonan->DELETED_AT = now();
        $permohonan->save();
    
        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus dan file dihapus (soft delete)',
        ]);
    }
    

    public function downloadBerkas(int $id)
    {
        // Ambil data permohonan SPD berdasarkan id
        $permohonan = PermohonanSPDModel::findOrFail($id);

        $filePath = $permohonan->nama_file_asli; // misal: permohonan_spd/20251107/testing.pdf

        // Cek apakah file ada di disk public
        $disk = Storage::disk('public');
        if (!$disk->exists($filePath)) {
            abort(404, "File tidak ditemukan");
        }

        // Download file dengan nama asli
        return response()->download($disk->path($filePath), basename($filePath));
    }
}
