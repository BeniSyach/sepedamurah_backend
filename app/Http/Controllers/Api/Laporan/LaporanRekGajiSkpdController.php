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
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER("LAPORAN_REK_GAJI_SKPD"."NAMA_OPERATOR") LIKE ?', ["%$search%"])
                  ->orWhereRaw('LOWER("LAPORAN_REK_GAJI_SKPD"."FILE") LIKE ?', ["%$search%"])
                  ->orWhereRaw('LOWER("REF_OPD"."NM_OPD") LIKE ?', ["%$search%"]);
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
