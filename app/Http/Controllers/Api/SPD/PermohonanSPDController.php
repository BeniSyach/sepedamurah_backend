<?php

namespace App\Http\Controllers\Api\SPD;

use App\Http\Controllers\Controller;
use App\Models\PermohonanSPDModel;
use Illuminate\Http\Request;
use App\Http\Resources\PermohonanSPDResource;
use App\Http\Resources\SPDTerkirimResource;
use App\Models\AksesOperatorModel;
use App\Models\SPDTerkirimModel;
use App\Models\User;
use App\Models\UsersPermissionModel;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Storage;

class PermohonanSPDController extends Controller
{
    /**
     * List permohonan SPD (pagination + search)
     */
    public function index(Request $request)
    {
        $dateFrom = $request->get('date_from'); // ex: '2025-11-14'
        $dateTo   = $request->get('date_to');   // ex: '2025-11-14'
        $orderColumn = $request->get('sort_by', 'tanggal_upload');
        $orderDir    = $request->get('sort_dir', 'desc');
        $FilterTanggal = 'tanggal_upload';

        $query = PermohonanSPDModel::query()
        ->with(['pengirim', 'operator']) // eager load relasi
        ->whereNull('permohonan_spd.deleted_at')
        ->join('ref_opd', function ($join) {
            $join->on('permohonan_spd.kd_opd1', '=', 'ref_opd.kd_opd1')
                 ->on('permohonan_spd.kd_opd2', '=', 'ref_opd.kd_opd2')
                 ->on('permohonan_spd.kd_opd3', '=', 'ref_opd.kd_opd3')
                 ->on('permohonan_spd.kd_opd4', '=', 'ref_opd.kd_opd4')
                 ->on('permohonan_spd.kd_opd5', '=', 'ref_opd.kd_opd5');
        });


        if ($menu = $request->get('menu')) {

            if($menu == 'permohonan_spd'){
                
                if ($userId = $request->get('user_id')) {
                    $query->where('id_pengirim', $userId);
                }
                    // ambil data yg belum diperiksa operator
                    $query->where('id_operator', '0');
                    $query->whereNull('diterima')->whereNull('ditolak');
            }

             // Operator
            if($menu == 'permohonan_spd_operator'){
                // Ambil data SKPD dari operator yang login
                $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
                if ($operatorSkpd) {
                 $query->where(function ($q) use ($operatorSkpd) {
                     foreach ($operatorSkpd as $op) {
                         $q->orWhere(function ($q2) use ($op) {
                             $q2->where('permohonan_spd.kd_opd1', $op->kd_opd1)
                                ->where('permohonan_spd.kd_opd2', $op->kd_opd2)
                                ->where('permohonan_spd.kd_opd3', $op->kd_opd3)
                                ->where('permohonan_spd.kd_opd4', $op->kd_opd4)
                                ->where('permohonan_spd.kd_opd5', $op->kd_opd5);
                         });
                     }
                 });
                 
                }
                 // ambil data yg belum diperiksa operator
                 $query->where('id_operator', '0');
                 $query->where('proses', '1');
                 $query->whereNotNull('supervisor_proses');
                 $query->whereNull('diterima')->whereNull('ditolak');
            }

            if($menu == 'permohonan_spd_terima_operator'){
                // Ambil data SKPD dari operator yang login
                $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
 
     
                if ($operatorSkpd) {
                 $query->where(function ($q) use ($operatorSkpd) {
                     foreach ($operatorSkpd as $op) {
                         $q->orWhere(function ($q2) use ($op) {
                             $q2->where('permohonan_spd.kd_opd1', $op->kd_opd1)
                                ->where('permohonan_spd.kd_opd2', $op->kd_opd2)
                                ->where('permohonan_spd.kd_opd3', $op->kd_opd3)
                                ->where('permohonan_spd.kd_opd4', $op->kd_opd4)
                                ->where('permohonan_spd.kd_opd5', $op->kd_opd5);
                         });
                     }
                 });
                 
                }
                 $query->whereNotNull('supervisor_proses');
                 $query->whereNotNull('diterima');
                 $FilterTanggal = 'diterima';
            }

            if($menu == 'permohonan_spd_tolak_operator'){
                // Ambil data SKPD dari operator yang login
                $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
 
     
                if ($operatorSkpd) {
                 $query->where(function ($q) use ($operatorSkpd) {
                     foreach ($operatorSkpd as $op) {
                         $q->orWhere(function ($q2) use ($op) {
                             $q2->where('permohonan_spd.kd_opd1', $op->kd_opd1)
                                ->where('permohonan_spd.kd_opd2', $op->kd_opd2)
                                ->where('permohonan_spd.kd_opd3', $op->kd_opd3)
                                ->where('permohonan_spd.kd_opd4', $op->kd_opd4)
                                ->where('permohonan_spd.kd_opd5', $op->kd_opd5);
                         });
                     }
                 });
                 
                }
                 // ambil data yg belum diperiksa operator
                //  $query->where('id_operator', '0');
                //  $query->where('proses', '1');
                 $query->whereNotNull('supervisor_proses');
                 $query->whereNotNull('ditolak');
                 $FilterTanggal = 'ditolak';
            }
            
            if($menu == 'permohonan_spd_tandatangan_bud_operator'){
                // Ambil data SKPD dari operator yang login
                $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
 
     
                if ($operatorSkpd) {
                 $query->where(function ($q) use ($operatorSkpd) {
                     foreach ($operatorSkpd as $op) {
                         $q->orWhere(function ($q2) use ($op) {
                             $q2->where('permohonan_spd.kd_opd1', $op->kd_opd1)
                                ->where('permohonan_spd.kd_opd2', $op->kd_opd2)
                                ->where('permohonan_spd.kd_opd3', $op->kd_opd3)
                                ->where('permohonan_spd.kd_opd4', $op->kd_opd4)
                                ->where('permohonan_spd.kd_opd5', $op->kd_opd5);
                         });
                     }
                 });
                 
                }
                 // ambil data yg belum diperiksa operator
                 $query->where('id_operator', '0');
                 $query->where('proses', '1');
                 $query->whereNotNull('supervisor_proses');
                 $query->whereNotNull('diterima');
            }

            
             if($menu == 'berkas_masuk_spd'){
                $query->whereNull('proses');
                // hanya tampilkan yang belum diverifikasi
                $query->whereNull('diterima')->whereNull('ditolak');
            }

            // ✅ SPD Diterima
            if ($menu === 'spd_diterima') {
                
                if ($userId = $request->get('user_id')) {
                    $query->where('id_pengirim', $userId);
                }
                $query->where('proses', '2');
                $query->whereNotNull('diterima'); // hanya yang sudah diterima
                $FilterTanggal = 'diterima';
            }

            // ✅ SPD Diterima BUD
            if ($menu === 'spd_diterima_bud') {

                $query = SPDTerkirimModel::with('permohonan')
                    ->whereNull('deleted_at');

                if ($userId = $request->get('user_id')) {
                    $query->where('id_penerima', $userId);
                }

                if ($request->filled('tahun')) {
                    $query->whereYear('tanggal_upload', $request->tahun);
                }

                $query->where('TTE', 'Yes');

                return SPDTerkirimResource::collection(
                    $query->orderBy('created_at', 'desc')
                        ->paginate($request->get('per_page', 10))
                );
            }


            // (opsional) kalau kamu juga punya 'spd_ditolak'
            if ($menu === 'spd_ditolak') {
                
                if ($userId = $request->get('user_id')) {
                    $query->where('id_pengirim', $userId);
                }
                $query->whereNotNull('ditolak'); // hanya yang ditolak
                $FilterTanggal = 'ditolak';
            }
        }

        // 🔍 Filter pencarian
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->orWhereRaw("LOWER(nama_pengirim) LIKE ?", ["%$search%"])
                ->orWhereRaw("LOWER(nama_file) LIKE ?", ["%$search%"])
                ->orWhereRaw("LOWER(jenis_berkas) LIKE ?", ["%$search%"])
                ->orWhereRaw("LOWER(nama_operator) LIKE ?", ["%$search%"])
                ->orWhereRaw("LOWER(nm_opd) LIKE ?", ["%$search%"]);
            });
        }

        if ($dateFrom) {
            $query->whereDate($FilterTanggal, '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->whereDate($FilterTanggal, '<=', $dateTo);
        }

        // 🔢 Pagination & urutan terbaru
        $data = $query->orderBy($orderColumn, $orderDir)
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
    public function store(Request $request, TelegramService $telegram)
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

            $supervisors = UsersPermissionModel::with('user')
            ->where('users_rule_id', 4)
            ->get();

            foreach ($supervisors as $supervisor) {
                $chatId = $supervisor->user->chat_id ?? null;

                if ($chatId) {
                    $telegram->sendSpdFromSupervisor($chatId);
                }
            }

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
    public function update(Request $request, $id, TelegramService $telegram)
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

        $old_diterima = $permohonan->diterima;
        $old_ditolak  = $permohonan->ditolak;
    
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
            /*
            |--------------------------------------------------------------------------
            |  CEK PERUBAHAN STATUS "DITERIMA" / "DITOLAK"
            |--------------------------------------------------------------------------
            */


           // TRIGGER TERIMA
            if (is_null($old_diterima) && !is_null($permohonan->diterima)) {
                $id_pengirim = $permohonan->id_pengirim;
                $user = User::where('id', $id_pengirim)->first();
                $chatId = $user->chat_id ?? null;
                if ($chatId) {
                    $telegram->sendSpdDiverifikasi($chatId);
                }
            }

            // TRIGGER TOLAK
            if (is_null($old_ditolak) && !is_null($permohonan->ditolak)) {
                $id_pengirim = $permohonan->id_pengirim;
                $user = User::where('id', $id_pengirim)->first();
                $chatId = $user->chat_id ?? null;
                $ket = $request->alasan_tolak ?? '-';
                if ($chatId) {
                    $telegram->sendSpdDitolak($chatId, $ket);
                }
            }
    
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
