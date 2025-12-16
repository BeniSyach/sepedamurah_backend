<?php

namespace App\Http\Controllers\Api\Laporan;

use App\Http\Controllers\Controller;
use App\Http\Resources\LaporanDPAResource;
use App\Models\AksesOperatorModel;
use App\Models\LaporanDPAModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LaporanDPAController extends Controller
{
    public function index(Request $request)
    {
        $search = strtolower($request->search);
        $perPage = $request->per_page ?? 10;
        $menu = $request->get('menu');
        $userId = $request->get('user_id');

        $query = LaporanDPAModel::with(['dpa', 'user', 'operator'])
        ->leftJoin('ref_opd', function ($join) {
            $join->on('laporan_dpa.kd_opd1', '=', 'ref_opd.kd_opd1')
                 ->on('laporan_dpa.kd_opd2', '=', 'ref_opd.kd_opd2')
                 ->on('laporan_dpa.kd_opd3', '=', 'ref_opd.kd_opd3')
                 ->on('laporan_dpa.kd_opd4', '=', 'ref_opd.kd_opd4')
                 ->on('laporan_dpa.kd_opd5', '=', 'ref_opd.kd_opd5');
        })
        ->whereNull('laporan_dpa.deleted_at')
        ->select([
            'laporan_dpa.*',
            'ref_opd.nm_opd',
        ]);

        if ($menu) {
            if ($menu === 'laporan_dpa') {
                $query->when($userId, function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->whereNull('diterima')
                ->whereNull('ditolak');
            }

            if ($menu === 'operator_laporan_dpa') {
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
                $query->whereNull('id_operator')
                      ->where('proses', '1')
                      ->whereNull('diterima')
                      ->whereNull('ditolak');
            }

            if ($menu === 'operator_laporan_DPA_diterima') {
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

            if ($menu === 'operator_laporan_DPA_ditolak') {
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

            if ($menu === 'berkas_masuk_laporan_dpa') {
                $query->whereNull('proses')
                      ->whereNull('diterima')
                      ->whereNull('ditolak');
            }

            if ($menu === 'laporan_DPA_diterima') {
                $query->when($userId, function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->whereNotNull('diterima');
            }

            if ($menu === 'laporan_DPA_ditolak') {
                $query->when($userId, function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->whereNotNull('ditolak');
            }
        }
    
        if ($search) {
            $search = strtolower($search);
        
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER("LAPORAN_DPA"."NAMA_OPERATOR") LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER("LAPORAN_DPA"."FILE") LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER("LAPORAN_DPA"."PROSES") LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER("LAPORAN_DPA"."SUPERVISOR_PROSES") LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER("REF_OPD"."NM_OPD") LIKE ?', ["%{$search}%"]);
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
    
            // 🔥 validasi file
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480', // 20MB
        ]);

        // =====================================================
        // 🔍 CEK APAKAH DPA SUDAH ADA & SUDAH DIVERIFIKASI
        // =====================================================
        $exists = LaporanDPAModel::where('kd_opd1', $validated['kd_opd1'])
        ->where('kd_opd2', $validated['kd_opd2'])
        ->where('kd_opd3', $validated['kd_opd3'])
        ->where('kd_opd4', $validated['kd_opd4'])
        ->where('kd_opd5', $validated['kd_opd5'])
        ->where('dpa_id',  $validated['dpa_id'])
        ->where('tahun',   $validated['tahun'])
        ->whereNotNull('diterima') // 🔥 sudah diverifikasi
        ->exists();

        if ($exists) {
            return response()->json([
                'status'  => false,
                'message' => 'DPA sudah di-upload dan diverifikasi, tidak bisa upload ulang'
            ], 422);
        }
        
        // 🔥 Upload file jika ada
        if ($request->hasFile('file')) {
            // simpan file ke storage/app/public/laporan_dpa
            $path = $request->file('file')->store('laporan_dpa', 'public');

            // simpan path ke database
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
            ->whereNull('laporan_dpa.deleted_at')
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
            'dpa_id' => 'nullable|integer',
            // 🔥 file upload
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string',
            'nama_operator' => 'nullable|string',
            'id_operator' => 'nullable|integer',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string',
        ]);
    
        // 🔥 Jika ada file baru yang diupload
        if ($request->hasFile('file')) {

            // 🔥 Hapus file lama (jika ada)
            if ($lap->file && Storage::disk('public')->exists($lap->file)) {
                Storage::disk('public')->delete($lap->file);
            }

            // Simpan file baru ke storage/app/public/laporan_dpa
            $path = $request->file('file')->store('laporan_dpa', 'public');

            // Simpan path ke database
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
        $updated = LaporanDPAModel::whereIn('id', $ids)->update([
            'proses' => 1,                     // status diterima
            'ditolak' => null,                 // pastikan ditolak kosong
            'alasan_tolak' => null,            // hapus alasan tolak
            'supervisor_proses' => $supervisor,
        ]);
    
        return response()->json([
            'success' => true,
            'message' => "Berhasil menerima $updated berkas Laporan DPA.",
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
        $updated = LaporanDPAModel::whereIn('id', $ids)->update([
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
        $laporanDPA = LaporanDPAModel::findOrFail($id);

        $filePath = $laporanDPA->file;

        // Cek apakah file ada di disk public
        $disk = Storage::disk('public');
        if (!$disk->exists($filePath)) {
            abort(404, "File tidak ditemukan");
        }

        // Download file dengan nama asli
        return response()->download($disk->path($filePath), basename($filePath));
    }
}
