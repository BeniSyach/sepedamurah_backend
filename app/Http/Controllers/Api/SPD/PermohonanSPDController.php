<?php

namespace App\Http\Controllers\Api\SPD;

use App\Http\Controllers\Controller;
use App\Models\PermohonanSPDModel;
use Illuminate\Http\Request;
use App\Http\Resources\PermohonanSPDResource;
use App\Models\AksesOperatorModel;
use Illuminate\Support\Facades\DB;

class PermohonanSPDController extends Controller
{
    /**
     * List permohonan SPD (pagination + search)
     */
    public function index(Request $request)
    {
        $query = PermohonanSpdModel::query()
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
            'ID_PENGIRIM' => 'required|integer',
            'NAMA_PENGIRIM' => 'required|string|max:255',
            'ID_OPERATOR' => 'nullable|integer',
            'NAMA_OPERATOR' => 'nullable|string|max:255',
            'JENIS_BERKAS' => 'required|string|max:100',
            'NAMA_FILE' => 'required|string|max:255',
            'NAMA_FILE_ASLI' => 'nullable|string|max:255',
            'TANGGAL_UPLOAD' => 'nullable|date',
            'KODE_FILE' => 'nullable|string|max:100',
            'DITERIMA' => 'nullable|date',
            'DITOLAK' => 'nullable|date',
            'ALASAN_TOLAK' => 'nullable|string|max:500',
            'PROSES' => 'nullable|string|max:50',
            'SUPERVISOR_PROSES' => 'nullable|string|max:50',
            'KD_OPD1' => 'nullable|string|max:5',
            'KD_OPD2' => 'nullable|string|max:5',
            'KD_OPD3' => 'nullable|string|max:5',
            'KD_OPD4' => 'nullable|string|max:5',
            'KD_OPD5' => 'nullable|string|max:5',
        ]);

        try {
            // Ambil ID dari sequence Oracle
            $id = DB::connection('oracle')->selectOne('SELECT NO_PERMOHONAN_SPD.NEXTVAL AS ID FROM dual')->ID;

            $permohonan = PermohonanSPDModel::create(array_merge($validated, [
                'ID' => $id,
                'DATE_CREATED' => now(),
            ]));

            return new PermohonanSPDResource($permohonan);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
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
        $permohonan = PermohonanSPDModel::where('ID', $id)
                                        ->whereNull('DELETED_AT')
                                        ->first();

        if (!$permohonan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'ID_PENGIRIM' => 'required|integer',
            'NAMA_PENGIRIM' => 'required|string|max:255',
            'ID_OPERATOR' => 'nullable|integer',
            'NAMA_OPERATOR' => 'nullable|string|max:255',
            'JENIS_BERKAS' => 'required|string|max:100',
            'NAMA_FILE' => 'required|string|max:255',
            'NAMA_FILE_ASLI' => 'nullable|string|max:255',
            'TANGGAL_UPLOAD' => 'nullable|date',
            'KODE_FILE' => 'nullable|string|max:100',
            'DITERIMA' => 'nullable|date',
            'DITOLAK' => 'nullable|date',
            'ALASAN_TOLAK' => 'nullable|string|max:500',
            'PROSES' => 'nullable|string|max:50',
            'SUPERVISOR_PROSES' => 'nullable|string|max:50',
            'KD_OPD1' => 'nullable|string|max:5',
            'KD_OPD2' => 'nullable|string|max:5',
            'KD_OPD3' => 'nullable|string|max:5',
            'KD_OPD4' => 'nullable|string|max:5',
            'KD_OPD5' => 'nullable|string|max:5',
        ]);

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
        $permohonan = PermohonanSPDModel::where('ID', $id)
                                        ->whereNull('DELETED_AT')
                                        ->first();

        if (!$permohonan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $permohonan->DELETED_AT = now();
        $permohonan->save();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }
}
