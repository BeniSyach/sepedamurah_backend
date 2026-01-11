<?php

namespace App\Http\Controllers\Api\SPD;

use App\Http\Controllers\Controller;
use App\Models\SPDTerkirimModel;
use Illuminate\Http\Request;
use App\Http\Resources\SPDTerkirimResource;
use App\Models\AksesKuasaBUDModel;
use Illuminate\Support\Facades\Storage;
use App\Models\LogTTEModel;
use App\Models\User;
use App\Services\TelegramService;
use App\Services\TTE_BSRE;
use Illuminate\Support\Facades\Auth;

class SPDTerkirimController extends Controller
{
    /**
     * List SPD Terkirim (pagination + search)
     */
    public function index(Request $request)
    {
        $query = SPDTerkirimModel::with('permohonan')
            ->whereNull('deleted_at');
    
        // Filter menu
        if ($menu = $request->get('menu')) {

            if ($menu == 'spd_belum_paraf') {
                $operatorSkpd = AksesKuasaBUDModel::where('id_kbud', $request->get('user_id'))->get();
                if ($operatorSkpd) {
                 $query->where(function ($q) use ($operatorSkpd) {
                     foreach ($operatorSkpd as $op) {
                         $q->orWhere(function ($q2) use ($op) {
                             $q2->where('spd_terkirim.kd_opd1', $op->kd_opd1)
                                ->where('spd_terkirim.kd_opd2', $op->kd_opd2)
                                ->where('spd_terkirim.kd_opd3', $op->kd_opd3)
                                ->where('spd_terkirim.kd_opd4', $op->kd_opd4)
                                ->where('spd_terkirim.kd_opd5', $op->kd_opd5);
                         });
                     }
                 });
                 
                }
                 // ambil data yg belum diperiksa operator
                 $query->whereNull('paraf_kbud');
            }

            if ($menu == 'spd_sudah_paraf') {
                $operatorSkpd = AksesKuasaBUDModel::where('id_kbud', $request->get('user_id'))->get();
                if ($operatorSkpd) {
                 $query->where(function ($q) use ($operatorSkpd) {
                     foreach ($operatorSkpd as $op) {
                         $q->orWhere(function ($q2) use ($op) {
                             $q2->where('spd_terkirim.kd_opd1', $op->kd_opd1)
                                ->where('spd_terkirim.kd_opd2', $op->kd_opd2)
                                ->where('spd_terkirim.kd_opd3', $op->kd_opd3)
                                ->where('spd_terkirim.kd_opd4', $op->kd_opd4)
                                ->where('spd_terkirim.kd_opd5', $op->kd_opd5);
                         });
                     }
                 });
                 
                }
                 // ambil data yg belum diperiksa operator
                 $query->whereNotNull('paraf_kbud');
            }

            if ($menu == 'spd_tte') {
                $query->where(function ($q) {
                    $q->where('tte', '!=', 'Yes')
                      ->orWhereNull('tte')
                      ->orWhere('tte', '=', '0')
                      ->orWhere('tte', '=', '');
                });
                $query->where('paraf_kbud', '1');
            }

            if ($menu === 'spd_ditandatangani_bud') {

                if ($userId = $request->get('user_id')) {
                    // $query->where('id_penerima', $userId);
                    $query->where('kd_opd1', $request->get('kd_opd1'));
                    $query->where('kd_opd2', $request->get('kd_opd2'));
                    $query->where('kd_opd3', $request->get('kd_opd3'));
                    $query->where('kd_opd4', $request->get('kd_opd4'));
                    $query->where('kd_opd5', $request->get('kd_opd5'));

                }
            
                // ✅ FILTER TAHUN DARI tanggal_upload (JIKA ADA)
                if ($request->filled('tahun')) {
                    $query->whereRaw(
                        'EXTRACT(YEAR FROM tanggal_upload) = ?',
                        [$request->tahun]
                    );
                }
                $query->where('publish', '1');
            }
            
        }
    
        // Filter search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_penerima', 'like', "%{$search}%")
                  ->orWhere('nama_operator', 'like', "%{$search}%")
                  ->orWhere('namafile', 'like', "%{$search}%");
            });
        }
    
        // Pagination
        $data = $query->orderBy('created_at', 'desc')
                      ->paginate($request->get('per_page', 10));
    
        // Attach skpd secara manual
        $data->getCollection()->transform(function ($item) {
            $item->setRelation('skpd', $item->skpd()); // panggil method manual
            return $item;
        });
    
        return SPDTerkirimResource::collection($data);
    }
    

    /**
     * Store SPD Terkirim baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_berkas' => 'nullable|integer',
            'id_penerima' => 'required|integer',
            'nama_penerima' => 'required|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'nama_file' => 'required|string|max:255',
            'nama_file_asli' => 'required|file|mimes:pdf|max:5120', // wajib PDF, max 5MB
            'nama_file_lampiran' => 'nullable|string|max:255',
            'tanggal_upload' => 'nullable|date',
            'keterangan' => 'nullable|string|max:500',
            'paraf_kbud' => 'nullable|string|max:50',
            'tgl_paraf' => 'nullable|date',
            'tte' => 'nullable|string|max:255',
            'passpharase' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'tgl_tte' => 'nullable|date',
            'id_penandatangan' => 'nullable|integer',
            'nama_penandatangan' => 'nullable|string|max:255',
            'kd_opd1' => 'nullable|string|max:5',
            'kd_opd2' => 'nullable|string|max:5',
            'kd_opd3' => 'nullable|string|max:5',
            'kd_opd4' => 'nullable|string|max:5',
            'kd_opd5' => 'nullable|string|max:5',
            'file_tte' => 'nullable|string|max:255',
            'publish' => 'nullable|boolean',
        ]);
    
        try {
            // === 1️⃣ Simpan file PDF ke storage ===
            $file = $request->file('nama_file_asli');
            $tanggalFolder = now()->format('Ymd'); // contoh: 20251108
            $folder = "spd_terkirim/{$tanggalFolder}";
    
            // Simpan di storage/app/public/spd_terkirim/20251108/
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs($folder, $filename, 'public');
    
            // === 2️⃣ Simpan data ke database ===
            $spd = SPDTerkirimModel::create([
                'id_berkas' => $validated['id_berkas'] ?? null,
                'id_penerima' => $validated['id_penerima'],
                'nama_penerima' => $validated['nama_penerima'],
                'id_operator' => $validated['id_operator'] ?? null,
                'nama_operator' => $validated['nama_operator'] ?? null,
                'namafile' => $validated['nama_file'],
                'nama_file_asli' => $path, // path hasil upload
                'nama_file_lampiran' => $validated['nama_file_lampiran'] ?? null,
                'tanggal_upload' => now(),
                'keterangan' => $validated['keterangan'] ?? null,
                'paraf_kbud' => $validated['paraf_kbud'] ?? null,
                'tgl_paraf' => $validated['tgl_paraf'] ?? null,
                'tte' => $validated['tte'] ?? null,
                'passpharase' => $validated['passpharase'] ?? null,
                'status' => $validated['status'] ?? null,
                'tgl_tte' => $validated['tgl_tte'] ?? null,
                'id_penandatangan' => $validated['id_penandatangan'] ?? null,
                'nama_penandatangan' => $validated['nama_penandatangan'] ?? null,
                'kd_opd1' => $validated['kd_opd1'] ?? null,
                'kd_opd2' => $validated['kd_opd2'] ?? null,
                'kd_opd3' => $validated['kd_opd3'] ?? null,
                'kd_opd4' => $validated['kd_opd4'] ?? null,
                'kd_opd5' => $validated['kd_opd5'] ?? null,
                'file_tte' => $validated['file_tte'] ?? null,
                'publish' => $validated['publish'] ?? false,
                'created_at' => now(),
            ]);
    
            return new SPDTerkirimResource($spd);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    /**
     * Detail SPD Terkirim
     */
    public function show($id)
    {
        $spd = SPDTerkirimModel::where('id', $id)
                               ->whereNull('deleted_at')
                               ->first();

        if (!$spd) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new SPDTerkirimResource($spd);
    }

    /**
     * Update SPD Terkirim
     */
    public function update(Request $request, $id, TelegramService $telegram)
    {
        $spd = SPDTerkirimModel::where('id', $id)
            ->whereNull('deleted_at')
            ->first();
    
        if (!$spd) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $old_publish  = $spd->publish;
    
        // Validasi input
        $validated = $request->validate([
            'id_berkas' => 'nullable|integer',
            'id_penerima' => 'nullable|integer',
            'nama_penerima' => 'nullable|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'nama_file' => 'nullable|string|max:255',
            'nama_file_asli' => 'nullable|file|mimes:pdf|max:5120', // max 5MB
            'nama_file_lampiran' => 'nullable|string|max:255',
            'tanggal_upload' => 'nullable|date',
            'keterangan' => 'nullable|string|max:500',
            'paraf_kbud' => 'nullable|string|max:50',
            'tgl_paraf' => 'nullable|date',
            'tte' => 'nullable|string|max:255',
            'passpharase' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'tgl_tte' => 'nullable|date',
            'id_penandatangan' => 'nullable|integer',
            'nama_penandatangan' => 'nullable|string|max:255',
            'kd_opd1' => 'nullable|string|max:5',
            'kd_opd2' => 'nullable|string|max:5',
            'kd_opd3' => 'nullable|string|max:5',
            'kd_opd4' => 'nullable|string|max:5',
            'kd_opd5' => 'nullable|string|max:5',
            'file_tte' => 'nullable|file|mimes:pdf|max:5120', // file tte opsional
            'publish' => 'nullable',
        ]);
    
        $disk = Storage::disk('public');
    
        // === Handle upload nama_file_asli ===
        if ($request->hasFile('nama_file_asli')) {
            // Hapus file lama jika ada
            if ($spd->nama_file_asli && $disk->exists($spd->nama_file_asli)) {
                $disk->delete($spd->nama_file_asli);
            }
    
            // Simpan file baru
            $file = $request->file('nama_file_asli');
            $folder = 'spd_terkirim/' . now()->format('Ymd');
            $path = $file->store($folder, 'public');
            $validated['nama_file_asli'] = $path;
        } else {
            unset($validated['nama_file_asli']); // jangan timpa nilai lama
        }
    
        // === Handle upload file_tte ===
        if ($request->hasFile('file_tte')) {
            // Hapus file TTE lama jika ada
            if ($spd->file_tte && $disk->exists($spd->file_tte)) {
                $disk->delete($spd->file_tte);
            }
    
            $fileTte = $request->file('file_tte');
            $folderTte = 'spd_tte/' . now()->format('Ymd');
            $pathTte = $fileTte->store($folderTte, 'public');
            $validated['file_tte'] = $pathTte;
        } else {
            unset($validated['file_tte']);
        }
    
        try {
            $spd->update(array_merge($validated, [
                'tanggal_upload' => $validated['tanggal_upload'] ?? now(),
                'updated_at' => now(),
            ]));

           // TRIGGER TERIMA
           if ( ($request->publish == 1) && ($old_publish != 1) ) {
            $id_penerima = $spd->id_penerima;
            $user = User::where('id', $id_penerima)->first();
            $chatId = $user->chat_id ?? null;
            if ($chatId) {
                $telegram->sendSpdDitekenBud($chatId);
            }
        }
    
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new SPDTerkirimResource($spd),
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
     * Soft delete SPD Terkirim
     */
    public function destroy($id)
    {
        $spd = SPDTerkirimModel::where('id', $id)
                               ->whereNull('deleted_at')
                               ->first();

        if (!$spd) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $spd->deleted_at = now();
        $spd->save();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }

    public function downloadBerkas(int $id)
    {
        // Ambil data permohonan SPD berdasarkan id
        $permohonan = SPDTerkirimModel::findOrFail($id);

        $filePath = $permohonan->nama_file_asli; // misal: spd_terkirim/20251107/testing.pdf

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
        // Ambil data spdTerkirim SPD berdasarkan id
        $spdTerkirim = SPDTerkirimModel::findOrFail($id);

        $filePath = $spdTerkirim->file_tte; 

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
        $originalFilePath = $uploaded->storeAs("SPD_original", $saveName, "public");
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
                'kategori'          => 'SPD',
                'tte'               => 'Error',
                'status'            => 0,
                'tgl_tte'           => now(),
                'keterangan'        => "Gagal tandatangan dokumen SPD - $errorMsg",
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
        // UPDATE DATA SPD JIKA SUKSES
        // ================================
        $SPD = SPDTerkirimModel::find($request->id);
        $SPD->update([
            'tte'               => "Yes",
            'tgl_tte'           => now(),
            'status'            => 1,
            'id_penandatangan'  => $user->id,
            'nama_penandatangan'=> $user->name,
            'file_tte'          => $result['file_path'],
        ]);

        // ================================
        // LOG JIKA TTE SUKSES
        // ================================
        LogTTEModel::create([
            'id_berkas'         => $request->id,
            'kategori'          => 'SPD',
            'tte'               => 'Yes',
            'status'            => 1,
            'tgl_tte'           => now(),
            'keterangan'        => 'Berhasil tandatangan dokumen SPD',
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
}
