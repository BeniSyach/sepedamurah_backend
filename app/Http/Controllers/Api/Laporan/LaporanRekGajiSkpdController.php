<?php

namespace App\Http\Controllers\Api\Laporan;

use App\Http\Controllers\Controller;
use App\Http\Resources\LaporanRekGajiSkpdResource;
use App\Models\AksesOperatorModel;
use App\Models\LaporanRekGajiSkpdModel;
use App\Models\RefRekonsiliasiGajiSkpdModel;
use App\Models\User;
use App\Models\UsersPermissionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanRekGajiSkpdController extends Controller
{
    public function index(Request $request)
    {
        $search  = strtolower($request->search);
        $perPage = $request->per_page ?? 10;
        $menu    = $request->get('menu');
        $userId  = $request->get('user_id');

        $query = LaporanRekGajiSkpdModel::with(['rekGaji', 'user', 'operator'])
            ->leftJoin('ref_opd', function ($join) {
                $join->on('laporan_rek_gaji_skpd.kd_opd1', '=', 'ref_opd.kd_opd1')
                     ->on('laporan_rek_gaji_skpd.kd_opd2', '=', 'ref_opd.kd_opd2')
                     ->on('laporan_rek_gaji_skpd.kd_opd3', '=', 'ref_opd.kd_opd3')
                     ->on('laporan_rek_gaji_skpd.kd_opd4', '=', 'ref_opd.kd_opd4')
                     ->on('laporan_rek_gaji_skpd.kd_opd5', '=', 'ref_opd.kd_opd5');
            })
            ->whereNull('laporan_rek_gaji_skpd.deleted_at')
            ->select([
                'laporan_rek_gaji_skpd.*',
                'ref_opd.nm_opd',
            ]);

        /* ===============================
         * FILTER MENU
         * =============================== */
        if ($menu) {

            // SKPD - menunggu verifikasi
            if ($menu === 'laporan_rek_gaji') {
                if ($userId = $request->get('user_id')) {
                    $query->where('laporan_rek_gaji_skpd.kd_opd1', $request->get('kd_opd1'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd2', $request->get('kd_opd2'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd3', $request->get('kd_opd3'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd4', $request->get('kd_opd4'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd5', $request->get('kd_opd5'));
                }
                $query->whereNull('diterima');
                $query->whereNull('ditolak');
            }

            // OPERATOR - berkas masuk
            if ($menu === 'operator_laporan_rek_gaji') {
                $operator = AksesOperatorModel::where('id_operator', $userId)->first();
                if ($operator) {
                    $query->where([
                        'laporan_rek_gaji_skpd.kd_opd1' => $operator->kd_opd1,
                        'laporan_rek_gaji_skpd.kd_opd2' => $operator->kd_opd2,
                        'laporan_rek_gaji_skpd.kd_opd3' => $operator->kd_opd3,
                        'laporan_rek_gaji_skpd.kd_opd4' => $operator->kd_opd4,
                        'laporan_rek_gaji_skpd.kd_opd5' => $operator->kd_opd5,
                    ]);
                }

                $query->whereNull('id_operator')
                      ->where('proses', '1')
                      ->whereNull('diterima')
                      ->whereNull('ditolak');
            }

            // OPERATOR - diterima
            if ($menu === 'operator_laporan_rek_gaji_diterima') {
                $akses = AksesOperatorModel::where('id_operator', $userId)->get();
                $query->where(function ($q) use ($akses) {
                    foreach ($akses as $op) {
                        $q->orWhere(function ($q2) use ($op) {
                            $q2->where([
                                'laporan_rek_gaji_skpd.kd_opd1' => $op->kd_opd1,
                                'laporan_rek_gaji_skpd.kd_opd2' => $op->kd_opd2,
                                'laporan_rek_gaji_skpd.kd_opd3' => $op->kd_opd3,
                                'laporan_rek_gaji_skpd.kd_opd4' => $op->kd_opd4,
                                'laporan_rek_gaji_skpd.kd_opd5' => $op->kd_opd5,
                            ]);
                        });
                    }
                })
                ->whereNotNull('diterima');
            }

            // OPERATOR - ditolak
            if ($menu === 'operator_laporan_rek_gaji_ditolak') {
                $operatorSkpd = AksesOperatorModel::where('id_operator', $userId)->get();
                if ($operatorSkpd) {
                    $query->where(function ($q) use ($operatorSkpd) {
                        foreach ($operatorSkpd as $op) {
                            $q->orWhere(function ($q2) use ($op) {
                                $q2->where('laporan_rek_gaji_skpd.kd_opd1', $op->kd_opd1)
                                    ->where('laporan_rek_gaji_skpd.kd_opd2', $op->kd_opd2)
                                    ->where('laporan_rek_gaji_skpd.kd_opd3', $op->kd_opd3)
                                    ->where('laporan_rek_gaji_skpd.kd_opd4', $op->kd_opd4)
                                    ->where('laporan_rek_gaji_skpd.kd_opd5', $op->kd_opd5);
                            });
                        }
                    });
                }
                $query->whereNotNull('ditolak');
            }

            if ($menu === 'berkas_masuk_laporan_rek_gaji_ditolak') {
                $query->whereNull('proses')
                      ->whereNull('diterima')
                      ->whereNull('ditolak');
            }

             // 📌 Bendahara - diterima
             if ($menu === 'laporan_rek_gaji_diterima') {
                if ($userId = $request->get('user_id')) {
                    // $q->where('id_pengirim', $userId);
                    $query->where('laporan_rek_gaji_skpd.kd_opd1', $request->get('kd_opd1'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd2', $request->get('kd_opd2'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd3', $request->get('kd_opd3'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd4', $request->get('kd_opd4'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd5', $request->get('kd_opd5'));
                }
                // $query->when($userId, function ($q) use ($userId) {
                //     $q->where('user_id', $userId);
                // })
                $query->whereNotNull('diterima');
            }

            // 📌 Bendahara - ditolak
            if ($menu === 'laporan_rek_gaji_ditolak') {
                if ($userId = $request->get('user_id')) {
                    // $q->where('id_pengirim', $userId);
                    $query->where('laporan_rek_gaji_skpd.kd_opd1', $request->get('kd_opd1'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd2', $request->get('kd_opd2'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd3', $request->get('kd_opd3'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd4', $request->get('kd_opd4'));
                    $query->where('laporan_rek_gaji_skpd.kd_opd5', $request->get('kd_opd5'));
                }
                // $query->when($userId, function ($q) use ($userId) {
                //     $q->where('user_id', $userId);
                // })
                $query->whereNotNull('ditolak');
            }
        }

        /* ===============================
         * SEARCH
         * =============================== */
        if ($search) {
            $search = strtolower($search);
        
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER("LAPORAN_REK_GAJI_SKPD"."NAMA_OPERATOR") LIKE ?', ["%$search%"])
                  ->orWhereRaw('LOWER("LAPORAN_REK_GAJI_SKPD"."FILE") LIKE ?', ["%$search%"])
                  ->orWhereRaw('LOWER("REF_OPD"."NM_OPD") LIKE ?', ["%$search%"])
                  ->orWhereHas('rekGaji', function ($q2) use ($search) {
                      $q2->whereRaw('LOWER("NM_REKONSILIASI_GAJI_SKPD") LIKE ?', ["%$search%"]);
                  });
            });
        }
        if ($request->filled('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        $data = $query->orderBy('id', 'desc')->paginate($perPage);

        $data->getCollection()->transform(function ($item) {
            $item->skpd = $item->skpd;
            return $item;
        });

        return LaporanRekGajiSkpdResource::collection($data);
    }

    /* ===============================
     * STORE
     * =============================== */
    public function store(Request $request, TelegramService $telegram)
    {
        $validated = $request->validate([
            'kd_opd1' => 'required',
            'kd_opd2' => 'required',
            'kd_opd3' => 'required',
            'kd_opd4' => 'required',
            'kd_opd5' => 'required',
            'rek_gaji_id' => 'required|integer',
            'user_id' => 'required|integer',
            'tahun' => 'required|integer',
            'bulan' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
        ]);

        // Cegah upload ulang yg sudah diterima
        $exists = LaporanRekGajiSkpdModel::where([
            'kd_opd1' => $validated['kd_opd1'],
            'kd_opd2' => $validated['kd_opd2'],
            'kd_opd3' => $validated['kd_opd3'],
            'kd_opd4' => $validated['kd_opd4'],
            'kd_opd5' => $validated['kd_opd5'],
            'rek_gaji_id' => $validated['rek_gaji_id'],
            'tahun' => $validated['tahun'],
        ])
        ->whereNotNull('diterima')
        ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Rekonsiliasi gaji sudah diverifikasi'
            ], 422);
        }

        if ($request->hasFile('file')) {
            $validated['file'] = $request->file('file')
                ->store('laporan_rek_gaji', 'public');
        }

        $now = Carbon::now();

        if (empty($validated['bulan'])) {
            // 🔥 Jika bulan tidak ada → pakai waktu sekarang
            $tanggal_upload = $now;
        } else {
        $tanggal_upload = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            "{$validated['tahun']}-{$validated['bulan']}-01 " . $now->format('H:i:s')
        );
        }
        $validated['created_at'] = $tanggal_upload;

        $data = LaporanRekGajiSkpdModel::create($validated);

        // Notif supervisor
        $jenis = RefRekonsiliasiGajiSkpdModel::where('id', $validated['rek_gaji_id'])->value('nm_rekonsiliasi_gaji_skpd');

        $supervisors = UsersPermissionModel::with('user')
            ->where('users_rule_id', 4)
            ->get();

        foreach ($supervisors as $spv) {
            if ($spv->user->chat_id ?? null) {
                $telegram->sendLaporan($spv->user->chat_id, $jenis);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Laporan Rekonsiliasi Gaji berhasil dikirim',
            'data' => $data
        ], 201);
    }

    /* ===============================
     * UPDATE / VERIFIKASI
     * =============================== */
    public function update(Request $request, $id, TelegramService $telegram)
    {
        $lap = LaporanRekGajiSkpdModel::where('id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $oldTerima = $lap->diterima;
        $oldTolak  = $lap->ditolak;

        $validated = $request->validate([
            'proses' => 'nullable|string',
            'supervisor_proses' => 'nullable|string',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
        ]);

        if ($request->hasFile('file')) {
            if ($lap->file && Storage::disk('public')->exists($lap->file)) {
                Storage::disk('public')->delete($lap->file);
            }
            $validated['file'] = $request->file('file')
                ->store('laporan_rek_gaji', 'public');
        }

        $lap->update($validated);
        $lap->refresh();

        // NOTIF DITERIMA
        if (is_null($oldTerima) && $lap->diterima) {
            $user = User::find($lap->user_id);
            if ($user?->chat_id) {
                $telegram->sendLaporanDiterima($user->chat_id, 'Rekonsiliasi Gaji');
            }
        }

        // NOTIF DITOLAK
        if (is_null($oldTolak) && $lap->ditolak) {
            $user = User::find($lap->user_id);
            if ($user?->chat_id) {
                $telegram->sendLaporanDitolak(
                    $user->chat_id,
                    'Rekonsiliasi Gaji',
                    $lap->alasan_tolak ?? '-'
                );
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil diperbarui',
            'data' => $lap
        ]);
    }

    public function destroy($id)
    {
        LaporanRekGajiSkpdModel::where('id', $id)
            ->update(['deleted_at' => now()]);

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    private function getDashboardRekonGajiPivot(
        $tahun,
        $kd_opd1 = null,
        $kd_opd2 = null,
        $kd_opd3 = null,
        $kd_opd4 = null,
        $kd_opd5 = null
    ) {
        $rows = DB::select("
            SELECT
                REF_OPD.NM_OPD AS SKPD,
    
                AKSES_REK_GAJI_SKPD.KD_OPD1,
                AKSES_REK_GAJI_SKPD.KD_OPD2,
                AKSES_REK_GAJI_SKPD.KD_OPD3,
                AKSES_REK_GAJI_SKPD.KD_OPD4,
                AKSES_REK_GAJI_SKPD.KD_OPD5,
    
                REF_REKONSILIASI_GAJI_SKPD.NM_REKONSILIASI_GAJI_SKPD AS REFERENSI,
    
                CASE
                    WHEN AKSES_REK_GAJI_SKPD.ID IS NULL THEN 2
                    WHEN LAPORAN_REK_GAJI_SKPD.ID IS NOT NULL THEN 1
                    ELSE 0
                END AS STATUS_LAPORAN
    
            FROM REF_REKONSILIASI_GAJI_SKPD
    
            JOIN AKSES_REK_GAJI_SKPD
                ON REF_REKONSILIASI_GAJI_SKPD.ID = AKSES_REK_GAJI_SKPD.REK_GAJI_ID
               AND AKSES_REK_GAJI_SKPD.TAHUN = :tahun_akses
               AND AKSES_REK_GAJI_SKPD.DELETED_AT IS NULL
    
            JOIN REF_OPD
                ON AKSES_REK_GAJI_SKPD.KD_OPD1 = REF_OPD.KD_OPD1
               AND AKSES_REK_GAJI_SKPD.KD_OPD2 = REF_OPD.KD_OPD2
               AND AKSES_REK_GAJI_SKPD.KD_OPD3 = REF_OPD.KD_OPD3
               AND AKSES_REK_GAJI_SKPD.KD_OPD4 = REF_OPD.KD_OPD4
               AND AKSES_REK_GAJI_SKPD.KD_OPD5 = REF_OPD.KD_OPD5
               AND REF_OPD.DELETED_AT IS NULL
    
            LEFT JOIN LAPORAN_REK_GAJI_SKPD
                ON LAPORAN_REK_GAJI_SKPD.REK_GAJI_ID = REF_REKONSILIASI_GAJI_SKPD.ID
               AND LAPORAN_REK_GAJI_SKPD.KD_OPD1 = AKSES_REK_GAJI_SKPD.KD_OPD1
               AND LAPORAN_REK_GAJI_SKPD.KD_OPD2 = AKSES_REK_GAJI_SKPD.KD_OPD2
               AND LAPORAN_REK_GAJI_SKPD.KD_OPD3 = AKSES_REK_GAJI_SKPD.KD_OPD3
               AND LAPORAN_REK_GAJI_SKPD.KD_OPD4 = AKSES_REK_GAJI_SKPD.KD_OPD4
               AND LAPORAN_REK_GAJI_SKPD.KD_OPD5 = AKSES_REK_GAJI_SKPD.KD_OPD5
               AND LAPORAN_REK_GAJI_SKPD.TAHUN = :tahun_laporan
               AND LAPORAN_REK_GAJI_SKPD.DITERIMA IS NOT NULL
               AND LAPORAN_REK_GAJI_SKPD.DELETED_AT IS NULL
    
            WHERE REF_REKONSILIASI_GAJI_SKPD.DELETED_AT IS NULL
    
            " . (
                $kd_opd1 ? " AND AKSES_REK_GAJI_SKPD.KD_OPD1 = :kd_opd1 " : ""
            ) . (
                $kd_opd2 ? " AND AKSES_REK_GAJI_SKPD.KD_OPD2 = :kd_opd2 " : ""
            ) . (
                $kd_opd3 ? " AND AKSES_REK_GAJI_SKPD.KD_OPD3 = :kd_opd3 " : ""
            ) . (
                $kd_opd4 ? " AND AKSES_REK_GAJI_SKPD.KD_OPD4 = :kd_opd4 " : ""
            ) . (
                $kd_opd5 ? " AND AKSES_REK_GAJI_SKPD.KD_OPD5 = :kd_opd5 " : ""
            ) . "
    
            ORDER BY
                REF_OPD.NM_OPD,
                REF_REKONSILIASI_GAJI_SKPD.CREATED_AT
        ", array_filter([
            'tahun_akses' => $tahun,
            'tahun_laporan' => $tahun,
            'kd_opd1' => $kd_opd1,
            'kd_opd2' => $kd_opd2,
            'kd_opd3' => $kd_opd3,
            'kd_opd4' => $kd_opd4,
            'kd_opd5' => $kd_opd5,
        ]));
    
        $result = [];
        $referensiList = [];
    
        foreach ($rows as $row) {
    
            $referensi = $row->referensi;
    
            if (!in_array($referensi, $referensiList)) {
                $referensiList[] = $referensi;
            }
    
            $key =
                trim($row->kd_opd1) . '.' .
                trim($row->kd_opd2) . '.' .
                trim($row->kd_opd3) . '.' .
                trim($row->kd_opd4) . '.' .
                trim($row->kd_opd5);
    
            if (!isset($result[$key])) {
                $result[$key] = [
                    'skpd' => $row->skpd,
                    'kd_opd1' => $row->kd_opd1,
                    'kd_opd2' => $row->kd_opd2,
                    'kd_opd3' => $row->kd_opd3,
                    'kd_opd4' => $row->kd_opd4,
                    'kd_opd5' => $row->kd_opd5,
                ];
            }
    
            $result[$key][$referensi] = (int)$row->status_laporan;
        }
    
        foreach ($result as &$item) {
            foreach ($referensiList as $ref) {
                if (!isset($item[$ref])) {
                    $item[$ref] = 0;
                }
            }
        }
    
        return [
            'referensi' => $referensiList,
            'rows' => array_values($result),
        ];
    }
    
    public function getDashboardRekonGaji(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
    
        $kd_opd1 = $request->kd_opd1;
        $kd_opd2 = $request->kd_opd2;
        $kd_opd3 = $request->kd_opd3;
        $kd_opd4 = $request->kd_opd4;
        $kd_opd5 = $request->kd_opd5;
    
        $currentYear = date('Y');
    
        $tahunList = [];
        for ($i = $currentYear - 3; $i <= $currentYear + 3; $i++) {
            $tahunList[] = (string)$i;
        }
    
        $dataAsset = $this->getDashboardRekonGajiPivot(
            $tahun,
            $kd_opd1,
            $kd_opd2,
            $kd_opd3,
            $kd_opd4,
            $kd_opd5
        );
    
        return response()->json([
            'success' => true,
            'data' => [
                'tahun_list' => $tahunList,
                'tahun_selected' => $tahun,
                'referensi' => $dataAsset['referensi'],
                'rows' => $dataAsset['rows'],
            ]
        ]);
    }

    public function downloadBerkas($id)
    {
        $lap = LaporanRekGajiSkpdModel::findOrFail($id);
        $disk = Storage::disk('public');

        if (!$disk->exists($lap->file)) {
            abort(404, 'File tidak ditemukan');
        }

        return response()->download(
            $disk->path($lap->file),
            basename($lap->file)
        );
    }
}
