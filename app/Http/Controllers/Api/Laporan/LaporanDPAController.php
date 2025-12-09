<?php

namespace App\Http\Controllers\Api\Laporan;

use App\Http\Controllers\Controller;
use App\Http\Resources\LaporanDPAResource;
use App\Models\LaporanDPAModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LaporanDPAController extends Controller
{
    public function index(Request $request)
    {
        $search = strtolower($request->search);
        $perPage = $request->per_page ?? 10;
    
        $query = LaporanDPAModel::with(['dpa', 'user', 'operator'])
            ->whereNull('deleted_at');
    
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(nama_operator) LIKE ?", ["%$search%"])
                  ->orWhereRaw("LOWER(file) LIKE ?", ["%$search%"])
                  ->orWhereRaw("LOWER(proses) LIKE ?", ["%$search%"])
                  ->orWhereRaw("LOWER(supervisor_proses) LIKE ?", ["%$search%"]);
            });
        }
    
        // 📌 Ambil pagination dulu
        $data = $query->orderBy('id', 'desc')->paginate($perPage);
    
        // 📌 Tambahkan SKPD dari accessor
        $data->getCollection()->transform(function ($item) {
            $item->skpd = $item->skpd; // memanggil accessor getSkpdAttribute
            return $item;
        });
    
        return LaporanDPAResource::collection($data);
    }
    

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_opd1' => 'required',
            'kd_opd2' => 'required',
            'kd_opd3' => 'required',
            'kd_opd4' => 'required',
            'kd_opd5' => 'required',
            'dpa_id' => 'required|integer',
            'user_id' => 'required|integer',
            'tahun' => 'required|integer',
            'proses' => 'nullable|string',
            'supervisor_proses' => 'nullable|string',
    
            // 🔥 validasi file
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480', // 20MB
        ]);
    
        // 🔥 sudah ada? upload file
        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
    
            // nama file unik
            $filename = time() . '_' . $uploadedFile->getClientOriginalName();
    
            // simpan file ke storage/app/laporan_dpa
            $path = $uploadedFile->storeAs('laporan_dpa', $filename);
    
            // simpan ke database
            $validated['file'] = $path;
        }
    
        $data = LaporanDPAModel::create($validated);
    
        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dibuat',
            'data' => $data
        ], 201);
    }
    

    public function show($id)
    {
        $data = LaporanDPAModel::with(['dpa', 'user', 'operator'])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {
        $lap = LaporanDPAModel::where('id', $id)
            ->whereNull('deleted_at')
            ->first();
    
        if (!$lap) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        $validated = $request->validate([
            'proses' => 'nullable|string',
            'supervisor_proses' => 'nullable|string',
    
            // 🔥 file upload
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
    
            'nama_operator' => 'nullable|string',
            'id_operator' => 'nullable|integer',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string',
        ]);
    
        // 🔥 Jika ada file baru yang diupload
        if ($request->hasFile('file')) {
    
            // 🔥 Hapus file lama (jika ada)
            if ($lap->file && Storage::exists($lap->file)) {
                Storage::delete($lap->file);
            }
    
            $uploadedFile = $request->file('file');
    
            // nama file unik
            $filename = time() . '_' . $uploadedFile->getClientOriginalName();
    
            // simpan file
            $path = $uploadedFile->storeAs('laporan_dpa', $filename);
    
            // simpan path ke DB
            $validated['file'] = $path;
        }
    
        // 🔥 update data selain file
        $lap->update($validated);
    
        return response()->json([
            'status' => true,
            'message' => 'Data berhasil diperbarui',
            'data' => $lap
        ]);
    }    

    public function destroy($id)
    {
        $lap = LaporanDPAModel::where('id', $id)->whereNull('deleted_at')->first();

        if (!$lap) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $lap->update(['deleted_at' => now()]);

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)'
        ]);
    }

    public function downloadBerkas(int $id)
    {
        // Ambil data permohonan SPD berdasarkan id
        $permohonan = LaporanDPAModel::findOrFail($id);

        $filePath = $permohonan->file;

        // Cek apakah file ada di disk public
        $disk = Storage::disk('public');
        if (!$disk->exists($filePath)) {
            abort(404, "File tidak ditemukan");
        }

        // Download file dengan nama asli
        return response()->download($disk->path($filePath), basename($filePath));
    }
}
