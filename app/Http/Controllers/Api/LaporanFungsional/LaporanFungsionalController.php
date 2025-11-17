<?php

namespace App\Http\Controllers\Api\LaporanFungsional;

use App\Http\Controllers\Controller;
use App\Models\LaporanFungsionalModel;
use Illuminate\Http\Request;
use App\Http\Resources\LaporanFungsionalResource;
use App\Models\AksesOperatorModel;
use App\Models\UsersPermissionModel;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LaporanFungsionalController extends Controller
{
    /**
     * List Laporan Fungsional (pagination + search)
     */
    public function index(Request $request)
    {
        $query = LaporanFungsionalModel::with(['pengirim', 'operator'])
        ->whereNull('deleted_at');

        if ($jenis = $request->get('jenis')) {
            if($jenis == 'Pengeluaran'){
                $query->where('jenis_berkas', 'Pengeluaran');

                if ($menu = $request->get('menu')) {
                    if($menu == 'pengeluaran'){
                        if ($userId = $request->get('user_id')) {
                            $query->where('id_pengirim', $userId);
                        }
                        $query->whereNull('diterima')->whereNull('ditolak');
                    }

                    if($menu == 'berkas_masuk_pengeluaran'){
                        $query->whereNull('proses');
                        // hanya tampilkan yang belum diverifikasi
                        $query->whereNull('diterima')->whereNull('ditolak');
                    }

                    if($menu == 'operator_pengeluaran')
                    {
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
                        $query->where('id_operator', '0');
                        $query->where('proses', '1');
                        // $query->whereNotNull('supervisor_proses');
                        $query->whereNull('diterima')->whereNull('ditolak');
                    }
                    
                    if($menu == 'operator_pengeluaran_diterima'){
                        // Ambil data SKPD dari operator yang login
                        $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
        
            
                        if ($operatorSkpd) {
                        $query->where(function ($q) use ($operatorSkpd) {
                            foreach ($operatorSkpd as $op) {
                                $q->orWhere(function ($q2) use ($op) {
                                    $q2->where('kd_opd1', $op->kd_opd1)
                                        ->where('kd_opd2', $op->kd_opd2)
                                        ->where('kd_opd3', $op->kd_opd3)
                                        ->where('kd_opd4', $op->kd_opd4)
                                        ->where('kd_opd5', $op->kd_opd5);
                                });
                            }
                        });
                        
                        }
                        // ambil data yg belum diperiksa operator
                        //  $query->where('id_operator', '0');
                        $query->where('proses', '2');
                        //  $query->whereNotNull('supervisor_proses');
                        $query->whereNotNull('diterima');
                    }

                    if($menu == 'operator_pengeluaran_ditolak'){
                        // Ambil data SKPD dari operator yang login
                        $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
        
            
                        if ($operatorSkpd) {
                        $query->where(function ($q) use ($operatorSkpd) {
                            foreach ($operatorSkpd as $op) {
                                $q->orWhere(function ($q2) use ($op) {
                                    $q2->where('kd_opd1', $op->kd_opd1)
                                        ->where('kd_opd2', $op->kd_opd2)
                                        ->where('kd_opd3', $op->kd_opd3)
                                        ->where('kd_opd4', $op->kd_opd4)
                                        ->where('kd_opd5', $op->kd_opd5);
                                });
                            }
                        });
                        
                        }
                        // ambil data yg belum diperiksa operator
                        //  $query->where('id_operator', '0');
                        // $query->where('proses', '2');
                        //  $query->whereNotNull('supervisor_proses');
                        $query->whereNotNull('ditolak');
                    }

                    if($menu == 'fungsional_pengeluaran_diterima'){
                        if ($userId = $request->get('user_id')) {
                            $query->where('id_pengirim', $userId);
                        }
                        $query->whereNotNull('diterima'); // hanya yang sudah diterima
                    }

                    if($menu == 'fungsional_pengeluaran_ditolak'){
                        if ($userId = $request->get('user_id')) {
                            $query->where('id_pengirim', $userId);
                        }
                        $query->whereNotNull('ditolak'); // hanya yang sudah diterima
                    }
                }
            }

            if($jenis == 'Penerimaan'){
                $query->where('jenis_berkas', 'Penerimaan');

                if ($menu = $request->get('menu')) {
                    if($menu == 'penerimaan'){
                        if ($userId = $request->get('user_id')) {
                            $query->where('id_pengirim', $userId);
                        }
                        $query->whereNull('diterima')->whereNull('ditolak');
                    }

                    if($menu == 'berkas_masuk_penerimaan'){
                        $query->whereNull('proses');
                        // hanya tampilkan yang belum diverifikasi
                        $query->whereNull('diterima')->whereNull('ditolak');
                    }

                    if($menu == 'operator_penerimaan')
                    {
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
                        $query->where('id_operator', '0');
                        $query->where('proses', '1');
                        // $query->whereNotNull('supervisor_proses');
                        $query->whereNull('diterima')->whereNull('ditolak');
                    }
                    
                    if($menu == 'operator_penerimaan_diterima'){
                        // Ambil data SKPD dari operator yang login
                        $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
        
            
                        if ($operatorSkpd) {
                        $query->where(function ($q) use ($operatorSkpd) {
                            foreach ($operatorSkpd as $op) {
                                $q->orWhere(function ($q2) use ($op) {
                                    $q2->where('kd_opd1', $op->kd_opd1)
                                        ->where('kd_opd2', $op->kd_opd2)
                                        ->where('kd_opd3', $op->kd_opd3)
                                        ->where('kd_opd4', $op->kd_opd4)
                                        ->where('kd_opd5', $op->kd_opd5);
                                });
                            }
                        });
                        
                        }
                        // ambil data yg belum diperiksa operator
                        //  $query->where('id_operator', '0');
                        $query->where('proses', '2');
                        //  $query->whereNotNull('supervisor_proses');
                        $query->whereNotNull('diterima');
                    }

                    if($menu == 'operator_penerimaan_ditolak'){
                        // Ambil data SKPD dari operator yang login
                        $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
        
            
                        if ($operatorSkpd) {
                        $query->where(function ($q) use ($operatorSkpd) {
                            foreach ($operatorSkpd as $op) {
                                $q->orWhere(function ($q2) use ($op) {
                                    $q2->where('kd_opd1', $op->kd_opd1)
                                        ->where('kd_opd2', $op->kd_opd2)
                                        ->where('kd_opd3', $op->kd_opd3)
                                        ->where('kd_opd4', $op->kd_opd4)
                                        ->where('kd_opd5', $op->kd_opd5);
                                });
                            }
                        });
                        
                        }
                        // ambil data yg belum diperiksa operator
                        //  $query->where('id_operator', '0');
                        // $query->where('proses', '2');
                        //  $query->whereNotNull('supervisor_proses');
                        $query->whereNotNull('ditolak');
                    }

                    if($menu == 'fungsional_penerimaan_diterima'){
                        if ($userId = $request->get('user_id')) {
                            $query->where('id_pengirim', $userId);
                        }
                        $query->whereNotNull('diterima'); // hanya yang sudah diterima
                    }

                    if($menu == 'fungsional_penerimaan_ditolak'){
                        if ($userId = $request->get('user_id')) {
                            $query->where('id_pengirim', $userId);
                        }
                        $query->whereNotNull('ditolak'); // hanya yang sudah diterima
                  }
                }
                
            }
        }

        // Filter pencarian
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pengirim', 'like', "%{$search}%")
                ->orWhere('nama_file', 'like', "%{$search}%")
                ->orWhere('tahun', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 10);
        $data = $query->orderBy('tanggal_upload', 'desc')
                    ->paginate($perPage);

        // Tambahkan skpd dari accessor pada setiap item
        $data->getCollection()->transform(function ($item) {
            $item->skpd = $item->skpd; // memanggil accessor getSkpdAttribute
            return $item;
        });

        return LaporanFungsionalResource::collection($data);
    }

    /**
     * Store Laporan Fungsional baru
     */
    public function store(Request $request, TelegramService $telegram)
    {
        $validated = $request->validate([
            'id_pengirim' => 'required|integer',
            'kd_opd1' => 'required|string|max:10',
            'kd_opd2' => 'required|string|max:10',
            'kd_opd3' => 'required|string|max:10',
            'kd_opd4' => 'required|string|max:10',
            'kd_opd5' => 'required|string|max:10',
            'nama_pengirim' => 'required|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'jenis_berkas' => 'required|string|max:50',
            'nama_file' => 'required|string|max:255',
            'nama_file_asli' => 'nullable|file|mimes:pdf|max:5120', // ubah ke file agar bisa upload
            'tanggal_upload' => 'nullable|date',
            'kode_file' => 'nullable|string|max:50',
            'tahun' => 'required|string|max:4',
            'bulan' => 'required|string',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string',
            'proses' => 'nullable|string|max:50',
            'supervisor_proses' => 'nullable|string|max:255',
            'berkas_tte' => 'nullable|file|mimes:pdf|max:5120', // file opsional
        ]);
    
        try {
            // 🧩 Ambil folder dari 'jenis_berkas' (otomatis dari payload)
            $folder = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '_', $request->jenis_berkas));
            // contoh: "Laporan Bulanan 2025" → "laporan_bulanan_2025"
    
            // 🚀 Simpan file nama_file_asli ke folder sesuai jenis_berkas
            $pathNamaFile = $request->file('nama_file_asli')
                ? $request->file('nama_file_asli')->store($folder, 'public')
                : null;
    
            // 🚀 Simpan file berkas_tte ke folder yang sama (opsional)
            $pathBerkasTte = $request->file('berkas_tte')
                ? $request->file('berkas_tte')->store($folder, 'public')
                : null;

                        // Ambil tanggal dan waktu saat ini
        $now = Carbon::now();

        // Gabungkan tahun + bulan dari request, dengan tanggal & waktu dari server
        $tanggal_upload = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            "{$validated['tahun']}-{$validated['bulan']}-01 " . $now->format('H:i:s')
        );

    
            // 🧱 Simpan data ke database
            $laporan = LaporanFungsionalModel::create([
                ...$validated,
               'tanggal_upload' => $tanggal_upload,
               'kode_file' => Str::random(10),
                'nama_file_asli' => $pathNamaFile,
                'berkas_tte' => $pathBerkasTte,
                'date_created' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($laporan) {
                $supervisors = UsersPermissionModel::with('user')
                    ->where('users_rule_id', 4)
                    ->get();
            
                $jenis_berkas = $request->jenis_berkas;
            
                foreach ($supervisors as $supervisor) {
                    $chatId = $supervisor->user->chat_id ?? null;
            
                    if (!$chatId) {
                        continue; // skip kalau user tidak punya chat_id
                    }
            
                    if ($jenis_berkas === 'Pengeluaran') {
                        $telegram->sendFungsionalPengeluaranToSupervisor($chatId);
                    }
            
                    if ($jenis_berkas === 'Penerimaan') {
                        $telegram->sendFungsionalToSupervisor($chatId);
                    }
                }
            }
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil disimpan',
                'data' => new LaporanFungsionalResource($laporan),
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
     * Detail Laporan Fungsional
     */
    public function show($id)
    {
        $laporan = LaporanFungsionalModel::where('id', $id)
                                         ->whereNull('deleted_at')
                                         ->first();

        if (!$laporan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new LaporanFungsionalResource($laporan);
    }

    /**
     * Update Laporan Fungsional
     */
    public function update(Request $request, $id)
    {
        // 🔍 Cari data lama
        $laporan = LaporanFungsionalModel::where('id', $id)
                                         ->whereNull('deleted_at')
                                         ->first();
    
        if (!$laporan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        // 🧩 Validasi input
        $validated = $request->validate([
            'kd_opd1' => 'nullable|string|max:10',
            'kd_opd2' => 'nullable|string|max:10',
            'kd_opd3' => 'nullable|string|max:10',
            'kd_opd4' => 'nullable|string|max:10',
            'kd_opd5' => 'nullable|string|max:10',
            'nama_pengirim' => 'nullable|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'jenis_berkas' => 'nullable|string|max:50',
            'nama_file' => 'nullable|string|max:255',
            'nama_file_asli' => 'nullable|file|mimes:pdf|max:5120',
            'tanggal_upload' => 'nullable|date',
            'kode_file' => 'nullable|string|max:50',
            'tahun' => 'nullable|string|max:4',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string',
            'proses' => 'nullable|string|max:50',
            'supervisor_proses' => 'nullable|string|max:255',
            'berkas_tte' => 'nullable|file|mimes:pdf|max:5120',
        ]);
    
        try {
            // 🧭 Tentukan folder berdasarkan jenis_berkas (dibersihkan agar aman untuk nama folder)
            $folder = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '_', $request->jenis_berkas));
    
            // 🗑️ Hapus file lama & upload file baru jika ada
            if ($request->hasFile('nama_file_asli')) {
                // Hapus file lama kalau masih ada
                if ($laporan->nama_file_asli && Storage::disk('public')->exists($laporan->nama_file_asli)) {
                    Storage::disk('public')->delete($laporan->nama_file_asli);
                }
    
                // Upload file baru
                $pathNamaFile = $request->file('nama_file_asli')->store($folder, 'public');
                $validated['nama_file_asli'] = $pathNamaFile;
            }
    
            if ($request->hasFile('berkas_tte')) {
                // Hapus file lama kalau masih ada
                if ($laporan->berkas_tte && Storage::disk('public')->exists($laporan->berkas_tte)) {
                    Storage::disk('public')->delete($laporan->berkas_tte);
                }
    
                // Upload file baru
                $pathBerkasTte = $request->file('berkas_tte')->store($folder, 'public');
                $validated['berkas_tte'] = $pathBerkasTte;
            }
    
            // 💾 Update data di database
            $laporan->update(array_merge($validated, [
                'updated_at' => now(),
            ]));
    
            // ✅ Berhasil
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new LaporanFungsionalResource($laporan),
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
     * Soft delete Laporan Fungsional
     */
    public function destroy($id)
    {
        // Ambil data laporan yang belum dihapus
        $laporan = LaporanFungsionalModel::where('id', $id)
                                         ->whereNull('deleted_at')
                                         ->first();
    
        if (!$laporan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        // 🗑️ Hapus file lama jika ada
        $filesToDelete = [
            $laporan->nama_file_asli ?? null,
            $laporan->berkas_tte ?? null,
        ];
    
        foreach ($filesToDelete as $file) {
            if ($file && Storage::disk('public')->exists($file)) {
                Storage::disk('public')->delete($file);
            }
        }
    
        // 💾 Soft delete
        $laporan->deleted_at = now();
        $laporan->save();
    
        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete) dan file terkait dihapus',
        ]);
    }
    

    public function downloadBerkas(int $id)
    {
        // Ambil data permohonan SPD berdasarkan id
        $permohonan = LaporanFungsionalModel::findOrFail($id);

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
        $permohonan = LaporanFungsionalModel::findOrFail($id);

        $filePath = $permohonan->berkas_tte;

        // Cek apakah file ada di disk public
        $disk = Storage::disk('public');
        if (!$disk->exists($filePath)) {
            abort(404, "File tidak ditemukan");
        }

        // Download file dengan nama asli
        return response()->download($disk->path($filePath), basename($filePath));
    }
}
