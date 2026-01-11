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
use Illuminate\Support\Facades\Auth;
use App\Services\TTE_BSRE;
use App\Models\LogTTEModel;
use Illuminate\Support\Facades\DB;

class LaporanFungsionalController extends Controller
{
    /**
     * List Laporan Fungsional (pagination + search)
     */
    public function index(Request $request)
    {
        $query = LaporanFungsionalModel::with(['pengirim', 'operator'])
        ->whereNull('fungsional.deleted_at')
        ->join('ref_opd as opd', function ($join) {
            $join->on('opd.kd_opd1', '=', 'fungsional.kd_opd1')
                 ->on('opd.kd_opd2', '=', 'fungsional.kd_opd2')
                 ->on('opd.kd_opd3', '=', 'fungsional.kd_opd3')
                 ->on('opd.kd_opd4', '=', 'fungsional.kd_opd4')
                 ->on('opd.kd_opd5', '=', 'fungsional.kd_opd5');
        })
        ->select(
            'fungsional.*',
            'opd.nm_opd' // Hanya ambil nm_opd
        );
    
        $search = $request->get('search');
        $userId = $request->get('user_id');
        $jenis = $request->get('jenis');
        $menu = $request->get('menu');
    
        // Filter jenis
        if ($jenis) {
            $query->whereRaw("LOWER(jenis_berkas) = ?", [strtolower($jenis)]);
    
            if ($menu) {
    
                // PENGELUARAN
                if ($jenis === 'Pengeluaran') {
                    if ($menu === 'pengeluaran') {
                        if ($userId = $request->get('user_id')) {
                            // $q->where('id_pengirim', $userId);
                            $query->where('fungsional.kd_opd1', $request->get('kd_opd1'));
                            $query->where('fungsional.kd_opd2', $request->get('kd_opd2'));
                            $query->where('fungsional.kd_opd3', $request->get('kd_opd3'));
                            $query->where('fungsional.kd_opd4', $request->get('kd_opd4'));
                            $query->where('fungsional.kd_opd5', $request->get('kd_opd5'));
                        }
                        // $query->when($userId, function ($q) use ($userId) {
                        //     $q->where('id_pengirim', $userId);
                        // })
                        $query->whereNull('diterima');
                        $query->whereNull('ditolak');
                    }
    
                    if ($menu === 'berkas_masuk_pengeluaran') {
                        $query->whereNull('proses')
                              ->whereNull('diterima')
                              ->whereNull('ditolak');
                    }
    
                    if ($menu === 'operator_pengeluaran') {
                        $operator = AksesOperatorModel::where('id_operator', $userId)->first();
                        if ($operator) {
                            $query->where(function ($q) use ($operator) {
                                $q->where('kd_opd1', $operator->kd_opd1)
                                  ->where('kd_opd2', $operator->kd_opd2)
                                  ->where('kd_opd3', $operator->kd_opd3)
                                  ->where('kd_opd4', $operator->kd_opd4)
                                  ->where('kd_opd5', $operator->kd_opd5);
                            });
                        }
                        $query->where('id_operator', '0')
                              ->where('proses', '1')
                              ->whereNull('diterima')
                              ->whereNull('ditolak');
                    }
    
                    if ($menu === 'operator_pengeluaran_diterima') {
                        $operatorSkpd = AksesOperatorModel::where('id_operator', $userId)->get();
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
                        $query->where('proses', '2')
                              ->whereNotNull('diterima');
                    }
    
                    if ($menu === 'operator_pengeluaran_ditolak') {
                        $operatorSkpd = AksesOperatorModel::where('id_operator', $userId)->get();
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
                        $query->whereNotNull('ditolak');
                    }
    
                    if ($menu === 'fungsional_pengeluaran_diterima') {
                        if ($userId = $request->get('user_id')) {
                            // $q->where('id_pengirim', $userId);
                            $query->where('fungsional.kd_opd1', $request->get('kd_opd1'));
                            $query->where('fungsional.kd_opd2', $request->get('kd_opd2'));
                            $query->where('fungsional.kd_opd3', $request->get('kd_opd3'));
                            $query->where('fungsional.kd_opd4', $request->get('kd_opd4'));
                            $query->where('fungsional.kd_opd5', $request->get('kd_opd5'));
                        }
                        // $query->when($userId, function ($q) use ($userId) {
                        //     $q->where('id_pengirim', $userId);
                        // })
                        $query->whereNotNull('diterima');
                    }
                    
    
                    if ($menu === 'fungsional_pengeluaran_ditolak') {
                        if ($userId = $request->get('user_id')) {
                            // $q->where('id_pengirim', $userId);
                            $query->where('fungsional.kd_opd1', $request->get('kd_opd1'));
                            $query->where('fungsional.kd_opd2', $request->get('kd_opd2'));
                            $query->where('fungsional.kd_opd3', $request->get('kd_opd3'));
                            $query->where('fungsional.kd_opd4', $request->get('kd_opd4'));
                            $query->where('fungsional.kd_opd5', $request->get('kd_opd5'));
                        }
                        // $query->when($userId, function ($q) use ($userId) {
                        //     $q->where('id_pengirim', $userId);
                        // })
                        $query->whereNotNull('ditolak');
                    }
                    
                }
    
                // PENERIMAAN
                if ($jenis === 'Penerimaan') {
                    if ($menu === 'penerimaan') {
                        if ($userId = $request->get('user_id')) {
                            // $q->where('id_pengirim', $userId);
                            $query->where('fungsional.kd_opd1', $request->get('kd_opd1'));
                            $query->where('fungsional.kd_opd2', $request->get('kd_opd2'));
                            $query->where('fungsional.kd_opd3', $request->get('kd_opd3'));
                            $query->where('fungsional.kd_opd4', $request->get('kd_opd4'));
                            $query->where('fungsional.kd_opd5', $request->get('kd_opd5'));
                        }
                         $query->whereNull('diterima');
                         $query->whereNull('ditolak');
                    }
    
                    if ($menu === 'berkas_masuk_penerimaan') {
                        $query->whereNull('proses')
                              ->whereNull('diterima')
                              ->whereNull('ditolak');
                    }
    
                    if ($menu === 'operator_penerimaan') {
                        $operator = AksesOperatorModel::where('id_operator', $userId)->first();
                        if ($operator) {
                            $query->where(function ($q) use ($operator) {
                                $q->where('kd_opd1', $operator->kd_opd1)
                                  ->where('kd_opd2', $operator->kd_opd2)
                                  ->where('kd_opd3', $operator->kd_opd3)
                                  ->where('kd_opd4', $operator->kd_opd4)
                                  ->where('kd_opd5', $operator->kd_opd5);
                            });
                        }
                        $query->where('id_operator', '0')
                              ->where('proses', '1')
                              ->whereNull('diterima')
                              ->whereNull('ditolak');
                    }
    
                    if ($menu === 'operator_penerimaan_diterima') {
                        $operatorSkpd = AksesOperatorModel::where('id_operator', $userId)->get();
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
                        $query->where('proses', '2')
                              ->whereNotNull('diterima');
                    }
    
                    if ($menu === 'operator_penerimaan_ditolak') {
                        $operatorSkpd = AksesOperatorModel::where('id_operator', $userId)->get();
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
                        $query->whereNotNull('ditolak');
                    }
    
                    if ($menu === 'fungsional_penerimaan_diterima') {
                        if ($userId = $request->get('user_id')) {
                            // $q->where('id_pengirim', $userId);
                            $query->where('fungsional.kd_opd1', $request->get('kd_opd1'));
                            $query->where('fungsional.kd_opd2', $request->get('kd_opd2'));
                            $query->where('fungsional.kd_opd3', $request->get('kd_opd3'));
                            $query->where('fungsional.kd_opd4', $request->get('kd_opd4'));
                            $query->where('fungsional.kd_opd5', $request->get('kd_opd5'));
                        }
                        // $query->when($userId, function ($q) use ($userId) {
                        //     $q->where('id_pengirim', $userId);
                        // })
                        $query->whereNotNull('diterima');
                    }
    
                    if ($menu === 'fungsional_penerimaan_ditolak') {
                        if ($userId = $request->get('user_id')) {
                            // $q->where('id_pengirim', $userId);
                            $query->where('fungsional.kd_opd1', $request->get('kd_opd1'));
                            $query->where('fungsional.kd_opd2', $request->get('kd_opd2'));
                            $query->where('fungsional.kd_opd3', $request->get('kd_opd3'));
                            $query->where('fungsional.kd_opd4', $request->get('kd_opd4'));
                            $query->where('fungsional.kd_opd5', $request->get('kd_opd5'));
                        }
                        // $query->when($userId, function ($q) use ($userId) {
                        //     $q->where('id_pengirim', $userId);
                        // })
                        $query->whereNotNull('ditolak');
                    }
                }
            }
        }
    
        // Filter pencarian teks
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(fungsional.nama_pengirim) LIKE ?", ["%".strtolower($search)."%"])
                    ->orWhereRaw("LOWER(fungsional.nama_file) LIKE ?", ["%".strtolower($search)."%"])
                    ->orWhereRaw("LOWER(opd.nm_opd) LIKE ?", ["%".strtolower($search)."%"]);

            });
        }       
        
        if ($request->filled('tahun')) {
            $query->where('tahun', $request->tahun);
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

     /**
     * Menolak banyak Laporan Fungsional sekaligus
     */
    public function terimaMulti(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'supervisor_proses' => 'required|string'
        ]);
    
        $ids = $validated['ids'];
        $supervisor = $validated['supervisor_proses'];
    
        // Update semua berkas yang dipilih
        $updated = LaporanFungsionalModel::whereIn('id', $ids)->update([
            'proses' => 1,                     // status diterima
            'ditolak' => null,                 // pastikan ditolak kosong
            'alasan_tolak' => null,            // hapus alasan tolak
            'supervisor_proses' => $supervisor,
        ]);
    
        return response()->json([
            'success' => true,
            'message' => "Berhasil menerima $updated berkas Laporan Fungsional.",
            'updated' => $updated
        ]);
    }
    

     /**
     * Menolak banyak Laporan Fungsional sekaligus
     */
    public function tolakMulti(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'alasan' => 'required|string|max:500',
            'supervisor_proses' => 'required|string'
        ]);

        $ids = $validated['ids'];
        $alasan = $validated['alasan'];
        $supervisor = $validated['supervisor_proses'];

        // Update semua berkas yang dipilih
        $updated = LaporanFungsionalModel::whereIn('id', $ids)->update([
            'ditolak' => now(),
            'alasan_tolak' => $alasan,
            'proses' => 1,              // status proses kalau ditolak
            'supervisor_proses' => $supervisor,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil menolak $updated berkas Laporan Fungsional.",
            'updated' => $updated
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

    public function cekDataPerBulan($kd_opd1, $kd_opd2, $kd_opd3, $kd_opd4, $kd_opd5)
    {
        $result = [
            'status' => true,
            'missing_pengeluaran' => [],
            'missing_penerimaan' => []
        ];
    
        // Ambil tahun dan bulan sekarang
        // $tahun = date('Y');
        $tahun = '2025';
        // $bulanSekarang = date('n'); // bulan tanpa leading zero (1-12)
        $bulanSekarang = '12'; // bulan tanpa leading zero (1-12)
    
        // Ambil informasi penerimaan dari tabel ref_opd
        $opd = DB::table('ref_opd')
            ->select('status_penerimaan')
            ->where('kd_opd1', $kd_opd1)
            ->where('kd_opd2', $kd_opd2)
            ->where('kd_opd3', $kd_opd3)
            ->where('kd_opd4', $kd_opd4)
            ->where('kd_opd5', $kd_opd5)
            ->where('hidden', 0)
            ->first();
    
        if (!$opd) {
            return [
                'status' => false,
                'message' => "OPD tidak ditemukan.",
                'missing_pengeluaran' => [],
                'missing_penerimaan' => []
            ];
        }
    
        // Loop dari bulan 1 hingga bulan sebelum bulan sekarang
        for ($bulan = 1; $bulan < $bulanSekarang; $bulan++) {
            // Cek data pengeluaran
            $pengeluaranExists = DB::table('fungsional')
                ->where('kd_opd1', $kd_opd1)
                ->where('kd_opd2', $kd_opd2)
                ->where('kd_opd3', $kd_opd3)
                ->where('kd_opd4', $kd_opd4)
                ->where('kd_opd5', $kd_opd5)
                ->where('tahun', $tahun)
                ->whereRaw("EXTRACT(MONTH FROM tanggal_upload) = ?", [$bulan])
                ->where('jenis_berkas', 'Pengeluaran')
                ->whereNotNull('diterima')
                ->whereNull('deleted_at')
                ->exists();
    
            if (!$pengeluaranExists) {
                $result['status'] = false;
                $result['missing_pengeluaran'][] = $bulan;
            }
    
            // Jika penerimaan = 1, cek juga jenis berkas penerimaan
            if ($opd->status_penerimaan == 1) {
                $penerimaanExists = DB::table('fungsional')
                    ->where('kd_opd1', $kd_opd1)
                    ->where('kd_opd2', $kd_opd2)
                    ->where('kd_opd3', $kd_opd3)
                    ->where('kd_opd4', $kd_opd4)
                    ->where('kd_opd5', $kd_opd5)
                    ->where('tahun', $tahun)
                    ->whereRaw("EXTRACT(MONTH FROM tanggal_upload) = ?", [$bulan])
                    ->where('jenis_berkas', 'Penerimaan')
                    ->whereNotNull('diterima')
                    ->whereNull('deleted_at')
                    ->exists();
    
                if (!$penerimaanExists) {
                    $result['status'] = false;
                    $result['missing_penerimaan'][] = $bulan;
                }
            }
        }
    
        return $result;
    }
    
    public function apiCekDataPerBulan(Request $request)
    {
        $validated = $request->validate([
            "kd_opd1" => "required",
            "kd_opd2" => "required",
            "kd_opd3" => "required",
            "kd_opd4" => "required",
            "kd_opd5" => "required",
        ]);
    
        return response()->json(
            $this->cekDataPerBulan(
                $request->kd_opd1,
                $request->kd_opd2,
                $request->kd_opd3,
                $request->kd_opd4,
                $request->kd_opd5
            )
        );
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
        $originalFilePath = $uploaded->storeAs("fungsional_original", $saveName, "public");
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
                'kategori'          => 'fungsional',
                'tte'               => 'Error',
                'status'            => 0,
                'tgl_tte'           => now(),
                'keterangan'        => "Gagal tandatangan dokumen fungsional - $errorMsg",
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
        $berkas_lain = LaporanFungsionalModel::find($request->id);
        $berkas_lain->update([
            'berkas_tte'          => $result['file_path'],
        ]);

        // ================================
        // LOG JIKA TTE SUKSES
        // ================================
        LogTTEModel::create([
            'id_berkas'         => $request->id,
            'kategori'          => 'fungsional',
            'tte'               => 'Yes',
            'status'            => 1,
            'tgl_tte'           => now(),
            'keterangan'        => 'Berhasil tandatangan dokumen fungsional',
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
        // Ambil data + relasi pengirim & operator
        $data = LaporanFungsionalModel::with(['pengirim', 'operator'])
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
    
                // Nama penandatangan berdasarkan operator (atau pengirim jika berbeda)
                'penandatangan' => $data->operator->name 
                                    ?? $data->pengirim->name 
                                    ?? '-',
    
                // Nama file/dokumen
                'nama_dokumen'  => $data->nama_file ?? '-',
                'file_asli'     => $data->nama_file_asli ?? '-',
    
                // Status TTE berdasarkan berkas_tte null / tidak
                'status_tte'    => $data->berkas_tte 
                                    ? 'TTE Selesai' 
                                    : 'Belum TTE',
    
                // File hasil TTE
                'file_sdh_tte'  => $data->berkas_tte ?? '-', 
    
                // Tanggal TTE dianggap dari field diterima
                'tanggal_tte'   => $data->diterima ?? $data->tanggal_upload ?? '-',
    
                // Status proses dari operator & supervisor
                'status_proses' => $data->proses ?? '-',
                'supervisor'    => $data->supervisor_proses ?? '-',
    
                // Raw data utuh
                'raw'           => $data,
            ]
        ]);
    }
    
    
}
