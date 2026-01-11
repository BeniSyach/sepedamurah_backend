<?php

namespace App\Http\Controllers\Api\Laporan;

use App\Http\Controllers\Controller;
use App\Http\Resources\LaporanAssetBendaharaResource;
use App\Models\LaporanAssetBendaharaModel;
use App\Models\AksesOperatorModel;
use App\Models\RefAssetBendaharaModel;
use App\Models\User;
use App\Models\UsersPermissionModel;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LaporanAssetBendaharaController extends Controller
{
    public function index(Request $request)
    {
        $search  = strtolower($request->search);
        $perPage = $request->per_page ?? 10;
        $menu    = $request->get('menu');
        $userId  = $request->get('user_id');

        $query = LaporanAssetBendaharaModel::with(['refAssetBendahara', 'user', 'operator'])
            ->leftJoin('ref_opd', function ($join) {
                $join->on('laporan_asset_bendahara.kd_opd1', '=', 'ref_opd.kd_opd1')
                     ->on('laporan_asset_bendahara.kd_opd2', '=', 'ref_opd.kd_opd2')
                     ->on('laporan_asset_bendahara.kd_opd3', '=', 'ref_opd.kd_opd3')
                     ->on('laporan_asset_bendahara.kd_opd4', '=', 'ref_opd.kd_opd4')
                     ->on('laporan_asset_bendahara.kd_opd5', '=', 'ref_opd.kd_opd5');
            })
            ->whereNull('laporan_asset_bendahara.deleted_at')
            ->select([
                'laporan_asset_bendahara.*',
                'ref_opd.nm_opd',
            ]);

        /* ======================
         |  FILTER MENU
         ======================*/
        if ($menu) {

            // 📌 Bendahara - draft
            if ($menu === 'laporan_asset_bendahara') {
                if ($userId = $request->get('user_id')) {
                    // $q->where('id_pengirim', $userId);
                    $query->where('laporan_asset_bendahara.kd_opd1', $request->get('kd_opd1'));
                    $query->where('laporan_asset_bendahara.kd_opd2', $request->get('kd_opd2'));
                    $query->where('laporan_asset_bendahara.kd_opd3', $request->get('kd_opd3'));
                    $query->where('laporan_asset_bendahara.kd_opd4', $request->get('kd_opd4'));
                    $query->where('laporan_asset_bendahara.kd_opd5', $request->get('kd_opd5'));
                }
                // $query->when($userId, function ($q) use ($userId) {
                //     $q->where('user_id', $userId);
                // })
                      $query->whereNull('diterima');
                      $query->whereNull('ditolak');
            }

            // 📌 Operator - berkas masuk
            if ($menu === 'operator_laporan_asset') {
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

            // 📌 Operator - diterima
            if ($menu === 'operator_laporan_asset_diterima') {
                $operatorSkpd = AksesOperatorModel::where('id_operator', $userId)->get();

                if ($operatorSkpd->count()) {
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

                $query->whereNotNull('diterima');
            }

            // 📌 Operator - ditolak
            if ($menu === 'operator_laporan_asset_ditolak') {
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

            if ($menu === 'berkas_masuk_laporan_asset') {
                $query->whereNull('proses')
                      ->whereNull('diterima')
                      ->whereNull('ditolak');
            }

            // 📌 Bendahara - diterima
            if ($menu === 'laporan_asset_diterima') {
                if ($userId = $request->get('user_id')) {
                    // $q->where('id_pengirim', $userId);
                    $query->where('laporan_asset_bendahara.kd_opd1', $request->get('kd_opd1'));
                    $query->where('laporan_asset_bendahara.kd_opd2', $request->get('kd_opd2'));
                    $query->where('laporan_asset_bendahara.kd_opd3', $request->get('kd_opd3'));
                    $query->where('laporan_asset_bendahara.kd_opd4', $request->get('kd_opd4'));
                    $query->where('laporan_asset_bendahara.kd_opd5', $request->get('kd_opd5'));
                }
                // $query->when($userId, function ($q) use ($userId) {
                //     $q->where('user_id', $userId);
                // })
                $query->whereNotNull('diterima');
            }

            // 📌 Bendahara - ditolak
            if ($menu === 'laporan_asset_ditolak') {
                if ($userId = $request->get('user_id')) {
                    // $q->where('id_pengirim', $userId);
                    $query->where('laporan_asset_bendahara.kd_opd1', $request->get('kd_opd1'));
                    $query->where('laporan_asset_bendahara.kd_opd2', $request->get('kd_opd2'));
                    $query->where('laporan_asset_bendahara.kd_opd3', $request->get('kd_opd3'));
                    $query->where('laporan_asset_bendahara.kd_opd4', $request->get('kd_opd4'));
                    $query->where('laporan_asset_bendahara.kd_opd5', $request->get('kd_opd5'));
                }
                // $query->when($userId, function ($q) use ($userId) {
                //     $q->where('user_id', $userId);
                // })
                $query->whereNotNull('ditolak');
            }
        }

        /* ======================
         |  SEARCH
         ======================*/
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER("LAPORAN_ASSET_BENDAHARA"."NAMA_OPERATOR") LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER("LAPORAN_ASSET_BENDAHARA"."FILE") LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER("REF_OPD"."NM_OPD") LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        // 📌 Ambil pagination dulu
        $data = $query->orderBy('id', 'desc')->paginate($perPage);

        // 📌 Tambahkan SKPD dari accessor
        $data->getCollection()->transform(function ($item) {
            $item->skpd = $item->skpd; // memanggil accessor getSkpdAttribute
            return $item;
        });
        

        return LaporanAssetBendaharaResource::collection($data);
    }

    /* ======================
     |  STORE
     ======================*/
    public function store(Request $request, TelegramService $telegram)
    {
        $validated = $request->validate([
            'kd_opd1'      => 'required',
            'kd_opd2'      => 'required',
            'kd_opd3'      => 'required',
            'kd_opd4'      => 'required',
            'kd_opd5'      => 'required',
            'ref_asset_id' => 'required|integer',
            'user_id'      => 'required|integer',
            'tahun'        => 'required|integer',
            'file'         => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
        ]);

        // 📂 Upload file
        if ($request->hasFile('file')) {
            $validated['file'] = $request->file('file')
                ->store('laporan_asset_bendahara', 'public');
        }

        $data = LaporanAssetBendaharaModel::create($validated);
        if ($data) {

            $jenis_laporan = RefAssetBendaharaModel::where('id', $validated['ref_asset_id'])->value('nm_asset_bendahara');

            $supervisors = UsersPermissionModel::with('user')
            ->where('users_rule_id', 4)
            ->get();

            foreach ($supervisors as $supervisor) {
                $chatId = $supervisor->user->chat_id ?? null;

                if ($chatId) {
                    $telegram->sendLaporan($chatId, $jenis_laporan);
                }
            }
        } else {
            // Jika gagal create
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data.'
            ], 500);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Data berhasil dibuat',
            'data'    => $data
        ], 201);
    }

    /* ======================
     |  SHOW
     ======================*/
    public function show($id)
    {
        $data = LaporanAssetBendaharaModel::with('refAssetBendahara')
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
            'data'   => $data
        ]);
    }

    /* ======================
     |  UPDATE
     ======================*/
    public function update(Request $request, $id, TelegramService $telegram)
    {
        $lap = LaporanAssetBendaharaModel::where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$lap) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $old_diterima = $lap->diterima;
        $old_ditolak  = $lap->ditolak;

        $validated = $request->validate([
            'proses'            => 'nullable|string',
            'supervisor_proses' => 'nullable|string',
            'file'              => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
            'diterima'          => 'nullable|date',
            'ditolak'           => 'nullable|date',
            'alasan_tolak'      => 'nullable|string',
            'nama_operator'     => 'nullable|string',
            'id_operator'       => 'nullable|integer',
        ]);

        // 🔁 Replace file
        if ($request->hasFile('file')) {

            if ($lap->file && Storage::disk('public')->exists($lap->file)) {
                Storage::disk('public')->delete($lap->file);
            }

            $validated['file'] = $request->file('file')
                ->store('laporan_asset_bendahara', 'public');
        }

        $lap->update($validated);
        $lap->refresh();

         // TRIGGER TERIMA
         if (is_null($old_diterima) && !is_null($lap->diterima)) {
            $jenis_laporan = RefAssetBendaharaModel::where('id', $lap->ref_asset_id)->value('nm_asset_bendahara');
            $user_id = $lap->user_id;
            $user = User::where('id', $user_id)->first();
            $chatId = $user->chat_id ?? null;
            if ($chatId) {
                $telegram->sendLaporanDiterima($chatId, $jenis_laporan);
            }
        }

        // TRIGGER TOLAK
        if (is_null($old_ditolak) && !is_null($lap->ditolak)) {
            $jenis_laporan = RefAssetBendaharaModel::where('id', $lap->ref_asset_id)->value('nm_asset_bendahara');
            $user_id = $lap->user_id;
            $user = User::where('id', $user_id)->first();
            $chatId = $user->chat_id ?? null;
            $ket = $request->alasan_tolak ?? '-';
            if ($chatId) {
                $telegram->sendLaporanDitolak($chatId, $jenis_laporan, $ket);
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Data berhasil diperbarui',
            'data'    => $lap
        ]);
    }

    /* ======================
     |  DESTROY
     ======================*/
    public function destroy($id)
    {
        $lap = LaporanAssetBendaharaModel::find($id);

        if (!$lap) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $lap->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    /**
     * Menerima banyak Laporan Asset Bendahara sekaligus
     */
    public function terimaMulti(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:laporan_asset_bendahara,id',
            'supervisor_proses' => 'required|string'
        ]);

        $ids = $validated['ids'];
        $supervisor = $validated['supervisor_proses'];

        $updated = LaporanAssetBendaharaModel::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->update([
                'proses'            => 1,
                'diterima'          => now(),
                'ditolak'           => null,
                'alasan_tolak'      => null,
                'supervisor_proses' => $supervisor,
            ]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil menerima {$updated} berkas Laporan Asset Bendahara.",
            'updated' => $updated
        ]);
    }

    /**
     * Menolak banyak Laporan Asset Bendahara sekaligus
     */
    public function tolakMulti(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:laporan_asset_bendahara,id',
            'alasan' => 'required|string|max:500',
            'supervisor_proses' => 'required|string'
        ]);

        $ids = $validated['ids'];
        $alasan = $validated['alasan'];
        $supervisor = $validated['supervisor_proses'];

        $updated = LaporanAssetBendaharaModel::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->update([
                'proses'            => 1,
                'ditolak'           => now(),
                'diterima'          => null,
                'alasan_tolak'      => $alasan,
                'supervisor_proses' => $supervisor,
            ]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil menolak {$updated} berkas Laporan Asset Bendahara.",
            'updated' => $updated
        ]);
    }

    /* ======================
     |  DOWNLOAD FILE
     ======================*/
    public function downloadBerkas($id)
    {
        $lap = LaporanAssetBendaharaModel::findOrFail($id);

        if (!$lap->file || !Storage::disk('public')->exists($lap->file)) {
            abort(404, 'File tidak ditemukan');
        }

        return response()->download(
            Storage::disk('public')->path($lap->file),
            basename($lap->file)
        );
    }
}
