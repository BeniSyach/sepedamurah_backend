<?php

namespace App\Http\Controllers\Api\LaporanFungsional;

use App\Http\Controllers\Controller;
use App\Models\LaporanFungsionalModel;
use Illuminate\Http\Request;
use App\Http\Resources\LaporanFungsionalResource;
use App\Models\AksesOperatorModel;

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
                      $query->whereNull('diterima')->whereNull('ditolak');
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
                        $query->whereNull('diterima')->whereNull('ditolak');
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_pengirim' => 'required|integer',
            'kd_opd1' => 'nullable|string|max:10',
            'kd_opd2' => 'nullable|string|max:10',
            'kd_opd3' => 'nullable|string|max:10',
            'kd_opd4' => 'nullable|string|max:10',
            'kd_opd5' => 'nullable|string|max:10',
            'nama_pengirim' => 'required|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'jenis_berkas' => 'required|string|max:50',
            'nama_file' => 'required|string|max:255',
            'nama_file_asli' => 'nullable|string|max:255',
            'tanggal_upload' => 'nullable|date',
            'kode_file' => 'nullable|string|max:50',
            'tahun' => 'required|string|max:4',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string',
            'proses' => 'nullable|string|max:50',
            'supervisor_proses' => 'nullable|string|max:255',
            'berkas_tte' => 'nullable|string|max:255',
        ]);

        try {
            $laporan = LaporanFungsionalModel::create(array_merge($validated, [
                'date_created' => now(),
            ]));

            return new LaporanFungsionalResource($laporan);

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
        $laporan = LaporanFungsionalModel::where('id', $id)
                                         ->whereNull('deleted_at')
                                         ->first();

        if (!$laporan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'kd_opd1' => 'nullable|string|max:10',
            'kd_opd2' => 'nullable|string|max:10',
            'kd_opd3' => 'nullable|string|max:10',
            'kd_opd4' => 'nullable|string|max:10',
            'kd_opd5' => 'nullable|string|max:10',
            'nama_pengirim' => 'required|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'jenis_berkas' => 'required|string|max:50',
            'nama_file' => 'required|string|max:255',
            'nama_file_asli' => 'nullable|string|max:255',
            'tanggal_upload' => 'nullable|date',
            'kode_file' => 'nullable|string|max:50',
            'tahun' => 'required|string|max:4',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string',
            'proses' => 'nullable|string|max:50',
            'supervisor_proses' => 'nullable|string|max:255',
            'berkas_tte' => 'nullable|string|max:255',
        ]);

        try {
            $laporan->update($validated);

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
        $laporan = LaporanFungsionalModel::where('id', $id)
                                         ->whereNull('deleted_at')
                                         ->first();

        if (!$laporan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $laporan->deleted_at = now();
        $laporan->save();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }
}
