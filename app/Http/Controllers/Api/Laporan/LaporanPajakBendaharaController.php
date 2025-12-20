<?php

namespace App\Http\Controllers\Api\Laporan;

use App\Http\Controllers\Controller;
use App\Models\LaporanPajakBendaharaModel;
use App\Models\AksesOperatorModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LaporanPajakBendaharaController extends Controller
{
    public function index(Request $request)
    {
        $search  = strtolower($request->search);
        $perPage = $request->per_page ?? 10;
        $menu    = $request->get('menu');
        $userId  = $request->get('user_id');

        $query = LaporanPajakBendaharaModel::with(['refPajakBendahara'])
            ->leftJoin('ref_opd', function ($join) {
                $join->on('laporan_pajak_bendahara.kd_opd1', '=', 'ref_opd.kd_opd1')
                     ->on('laporan_pajak_bendahara.kd_opd2', '=', 'ref_opd.kd_opd2')
                     ->on('laporan_pajak_bendahara.kd_opd3', '=', 'ref_opd.kd_opd3')
                     ->on('laporan_pajak_bendahara.kd_opd4', '=', 'ref_opd.kd_opd4')
                     ->on('laporan_pajak_bendahara.kd_opd5', '=', 'ref_opd.kd_opd5');
            })
            ->whereNull('laporan_pajak_bendahara.deleted_at')
            ->select([
                'laporan_pajak_bendahara.*',
                'ref_opd.nm_opd',
            ]);

        /* =======================
         |  FILTER MENU
         =======================*/
        if ($menu) {

            // 📌 Bendahara (draft)
            if ($menu === 'laporan_pajak_bendahara') {
                $query->where('user_id', $userId)
                      ->whereNull('diterima')
                      ->whereNull('ditolak');
            }

            // 📌 Operator - berkas masuk
            if ($menu === 'operator_laporan_pajak') {
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
            if ($menu === 'operator_laporan_pajak_diterima') {
                $operatorSkpd = AksesOperatorModel::where('id_operator', $userId)->get();

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

                $query->whereNotNull('diterima');
            }

            // 📌 Operator - ditolak
            if ($menu === 'operator_laporan_pajak_ditolak') {
                $query->whereNotNull('ditolak');
            }

            // 📌 Bendahara - diterima
            if ($menu === 'laporan_pajak_diterima') {
                $query->where('user_id', $userId)
                      ->whereNotNull('diterima');
            }

            // 📌 Bendahara - ditolak
            if ($menu === 'laporan_pajak_ditolak') {
                $query->where('user_id', $userId)
                      ->whereNotNull('ditolak');
            }
        }

        /* =======================
         |  SEARCH
         =======================*/
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER("LAPORAN_PAJAK_BENDAHARA"."NAMA_OPERATOR") LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER("LAPORAN_PAJAK_BENDAHARA"."FILE") LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER("REF_OPD"."NM_OPD") LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    /* =======================
     |  STORE
     =======================*/
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_opd1'        => 'required',
            'kd_opd2'        => 'required',
            'kd_opd3'        => 'required',
            'kd_opd4'        => 'required',
            'kd_opd5'        => 'required',
            'ref_pajak_id'   => 'required|integer',
            'user_id'        => 'required|integer',
            'tahun'          => 'required|integer',
            'file'           => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
        ]);

        // 🔥 Upload file
        if ($request->hasFile('file')) {
            $validated['file'] = $request->file('file')
                ->store('laporan_pajak_bendahara', 'public');
        }

        $data = LaporanPajakBendaharaModel::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Data berhasil dibuat',
            'data'    => $data
        ], 201);
    }

    /* =======================
     |  SHOW
     =======================*/
    public function show($id)
    {
        $data = LaporanPajakBendaharaModel::with('refPajakBendahara')
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

    /* =======================
     |  UPDATE
     =======================*/
    public function update(Request $request, $id)
    {
        $lap = LaporanPajakBendaharaModel::where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$lap) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

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

        // 🔥 File baru
        if ($request->hasFile('file')) {
            if ($lap->file && Storage::disk('public')->exists($lap->file)) {
                Storage::disk('public')->delete($lap->file);
            }

            $validated['file'] = $request->file('file')
                ->store('laporan_pajak_bendahara', 'public');
        }

        $lap->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Data berhasil diperbarui',
            'data'    => $lap
        ]);
    }

    /* =======================
     |  DESTROY (SOFT DELETE)
     =======================*/
    public function destroy($id)
    {
        $lap = LaporanPajakBendaharaModel::find($id);

        if (!$lap) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $lap->delete();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    /**
     * Menerima banyak Laporan Pajak Bendahara sekaligus
     */
    public function terimaMulti(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:laporan_pajak_bendahara,id',
            'supervisor_proses' => 'required|string'
        ]);

        $ids = $validated['ids'];
        $supervisor = $validated['supervisor_proses'];

        $updated = LaporanPajakBendaharaModel::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->update([
                'proses'             => 1,
                'diterima'           => now(),
                'ditolak'            => null,
                'alasan_tolak'       => null,
                'supervisor_proses'  => $supervisor,
            ]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil menerima {$updated} berkas Laporan Pajak Bendahara.",
            'updated' => $updated
        ]);
    }

    /**
     * Menolak banyak Laporan Pajak Bendahara sekaligus
     */
    public function tolakMulti(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:laporan_pajak_bendahara,id',
            'alasan' => 'required|string|max:500',
            'supervisor_proses' => 'required|string'
        ]);

        $ids = $validated['ids'];
        $alasan = $validated['alasan'];
        $supervisor = $validated['supervisor_proses'];

        $updated = LaporanPajakBendaharaModel::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->update([
                'proses'             => 1,
                'ditolak'            => now(),
                'diterima'           => null,
                'alasan_tolak'       => $alasan,
                'supervisor_proses'  => $supervisor,
            ]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil menolak {$updated} berkas Laporan Pajak Bendahara.",
            'updated' => $updated
        ]);
    }

    /* =======================
     |  DOWNLOAD FILE
     =======================*/
    public function downloadBerkas($id)
    {
        $lap = LaporanPajakBendaharaModel::findOrFail($id);

        if (!Storage::disk('public')->exists($lap->file)) {
            abort(404, 'File tidak ditemukan');
        }

        return response()->download(
            Storage::disk('public')->path($lap->file),
            basename($lap->file)
        );
    }
}
