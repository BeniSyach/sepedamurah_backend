<?php

namespace App\Http\Controllers\Api\LaporanPembukuan\Traits;

use Illuminate\Http\Request;

trait BuildsHierarchicalLra
{
    /**
     * Tentukan kedalaman hierarki (maxLevel) berdasarkan filter yang aktif
     * 1 = Belanja
     * 2 = Urusan
     * 3 = Bidang Urusan
     * 4 = Program
     * 5 = Kegiatan
     * 6 = Sub Kegiatan
     */
    public function determineMaxLevel(Request $request, int $default = 3): int
    {
        if (!empty($request->subkegiatan['kd_subkeg1'])) {
            return 6;
        }
        if (!empty($request->kegiatan['kd_keg1'])) {
            return max(5, $default);
        }
        if (!empty($request->program['kd_prog1'])) {
            return max(4, $default);
        }
        if (!empty($request->bidang_urusan['kd_bu1'])) {
            return max(3, $default);
        }
        $rawUrusan = $request->urusan;
        $u = is_array($rawUrusan) ? ($rawUrusan['kd_urusan'] ?? '') : (string)$rawUrusan;
        if (!empty(trim($u))) {
            return max(3, $default);
        }
        return $default;
    }

    /**
     * Membangun baris berjenjang (Belanja -> Urusan -> Bidang -> Program -> Kegiatan -> Sub Kegiatan)
     */
    public function buildHierarchicalRows(array $paguItems, array $realisasiItems, int $maxLevel = 3): array
    {
        $tree = [];

        // Masukkan data Pagu
        foreach ($paguItems as $p) {
            $this->accumulateIntoTree($tree, $p, 'pagu', (float)($p->anggaran ?? $p->ANGGARAN ?? $p->total_pagu ?? $p->TOTAL_PAGU ?? 0), $maxLevel);
        }

        // Masukkan data Realisasi
        foreach ($realisasiItems as $r) {
            $lalu = (float)($r->realisasi_lalu ?? $r->REALISASI_LALU ?? 0);
            $ini = (float)($r->realisasi_ini ?? $r->REALISASI_INI ?? 0);
            $tot = (float)($r->realisasi_total ?? $r->REALISASI_TOTAL ?? ($lalu + $ini));

            $this->accumulateIntoTree($tree, $r, 'realisasi', ['lalu' => $lalu, 'ini' => $ini, 'total' => $tot], $maxLevel);
        }

        // Flatten tree
        $rows = [];
        $this->flattenTree($tree, 1, $rows);

        return $rows;
    }

    /**
     * Membangun baris berjenjang untuk LRA Sumber Dana:
     * Sumber Dana (Level 0) -> Belanja (Level 1) -> Urusan -> Bidang s.d. Sub Kegiatan
     */
    public function buildHierarchicalRowsForSumberDana(array $paguItems, array $realisasiItems, int $maxLevel = 3): array
    {
        $sdTree = [];

        // Masukkan Pagu
        foreach ($paguItems as $p) {
            $kdSd = trim($p->kode_sumber_dana ?? $p->KODE_SUMBER_DANA ?? '');
            if (!$kdSd || $kdSd === '.....' || str_starts_with($kdSd, '0.0.00')) continue;
            $nmSd = trim($p->nm_sumber_dana ?? $p->NM_SUMBER_DANA ?? "SUMBER DANA $kdSd");

            if (!isset($sdTree[$kdSd])) {
                $sdTree[$kdSd] = [
                    'kode' => $kdSd,
                    'nama' => $nmSd,
                    'pagu' => 0,
                    'lalu' => 0,
                    'ini' => 0,
                    'total' => 0,
                    'tree' => []
                ];
            }

            $anggaran = (float)($p->anggaran ?? $p->ANGGARAN ?? $p->total_pagu ?? $p->TOTAL_PAGU ?? 0);
            $sdTree[$kdSd]['pagu'] += $anggaran;
            if (empty($sdTree[$kdSd]['nama']) && !empty($nmSd)) {
                $sdTree[$kdSd]['nama'] = $nmSd;
            }

            $this->accumulateIntoTree($sdTree[$kdSd]['tree'], $p, 'pagu', $anggaran, $maxLevel);
        }

        // Masukkan Realisasi
        foreach ($realisasiItems as $r) {
            $kdSd = trim($r->kode_sumber_dana ?? $r->KODE_SUMBER_DANA ?? '');
            if (!$kdSd || $kdSd === '.....' || str_starts_with($kdSd, '0.0.00')) continue;
            $nmSd = trim($r->nm_sumber_dana ?? $r->NM_SUMBER_DANA ?? "SUMBER DANA $kdSd");

            if (!isset($sdTree[$kdSd])) {
                $sdTree[$kdSd] = [
                    'kode' => $kdSd,
                    'nama' => $nmSd,
                    'pagu' => 0,
                    'lalu' => 0,
                    'ini' => 0,
                    'total' => 0,
                    'tree' => []
                ];
            }

            $lalu = (float)($r->realisasi_lalu ?? $r->REALISASI_LALU ?? 0);
            $ini = (float)($r->realisasi_ini ?? $r->REALISASI_INI ?? 0);
            $tot = (float)($r->realisasi_total ?? $r->REALISASI_TOTAL ?? ($lalu + $ini));

            $sdTree[$kdSd]['lalu'] += $lalu;
            $sdTree[$kdSd]['ini'] += $ini;
            $sdTree[$kdSd]['total'] += $tot;
            if (empty($sdTree[$kdSd]['nama']) && !empty($nmSd)) {
                $sdTree[$kdSd]['nama'] = $nmSd;
            }

            $this->accumulateIntoTree($sdTree[$kdSd]['tree'], $r, 'realisasi', ['lalu' => $lalu, 'ini' => $ini, 'total' => $tot], $maxLevel);
        }

        // Flatten each Sumber Dana
        $result = [];
        ksort($sdTree);
        foreach ($sdTree as $sd) {
            $sdRows = [];
            $this->flattenTree($sd['tree'], 1, $sdRows);

            if (empty($sdRows)) continue;

            $anggaran = (float)$sd['pagu'];
            $lalu = (float)$sd['lalu'];
            $ini = (float)$sd['ini'];
            $tot = (float)$sd['total'];
            $sisa = $anggaran - $tot;
            $persen = $anggaran > 0 ? ($tot / $anggaran) * 100 : 0;

            if ($anggaran == 0 && $tot == 0) continue;

            $result[] = [
                'kode'             => $sd['kode'],
                'nama'             => $sd['nama'],
                'total_anggaran'   => $anggaran,
                'total_lalu'       => $lalu,
                'total_ini'        => $ini,
                'total_sampai_ini' => $tot,
                'total_sisa'       => $sisa,
                'persen'           => $persen,
                'rows'             => $sdRows
            ];
        }

        return $result;
    }

    private function accumulateIntoTree(array &$tree, $item, string $type, $amount, int $maxLevel): void
    {
        $kB = trim($item->kode_belanja ?? $item->KODE_BELANJA ?? '');
        if (!$kB || $kB === '..') return;
        $nB = trim($item->nm_belanja ?? $item->NM_BELANJA ?? "BELANJA $kB");

        $kU = trim($item->kd_urusan ?? $item->KD_URUSAN ?? '');
        $nU = trim($item->nm_urusan ?? $item->NM_URUSAN ?? "URUSAN $kU");

        $kBU = trim($item->kd_bu ?? $item->KD_BU ?? '');
        $nBU = trim($item->nm_bu ?? $item->NM_BU ?? "BIDANG $kBU");

        $kP = trim($item->kd_prog ?? $item->KD_PROG ?? '');
        $nP = trim($item->nm_program ?? $item->NM_PROGRAM ?? "PROGRAM $kP");

        $kK = trim($item->kd_keg ?? $item->KD_KEG ?? '');
        $nK = trim($item->nm_kegiatan ?? $item->NM_KEGIATAN ?? "KEGIATAN $kK");

        $kS = trim($item->kd_sub ?? $item->KD_SUB ?? $item->kd_subkeg ?? $item->KD_SUBKEG ?? '');
        $nS = trim($item->nm_subkegiatan ?? $item->NM_SUBKEGIATAN ?? "SUB $kS");

        if (!isset($tree[$kB])) {
            $tree[$kB] = ['kode' => $kB, 'nama' => $nB, 'pagu' => 0, 'lalu' => 0, 'ini' => 0, 'total' => 0, 'children' => []];
        }
        $this->addValueToNode($tree[$kB], $type, $amount);
        if (empty($tree[$kB]['nama']) && !empty($nB)) $tree[$kB]['nama'] = $nB;

        if ($maxLevel >= 2 && $kU) {
            if (!isset($tree[$kB]['children'][$kU])) {
                $tree[$kB]['children'][$kU] = ['kode' => $kU, 'nama' => $nU, 'pagu' => 0, 'lalu' => 0, 'ini' => 0, 'total' => 0, 'children' => []];
            }
            $this->addValueToNode($tree[$kB]['children'][$kU], $type, $amount);
            if (empty($tree[$kB]['children'][$kU]['nama']) && !empty($nU)) $tree[$kB]['children'][$kU]['nama'] = $nU;

            if ($maxLevel >= 3 && $kBU) {
                if (!isset($tree[$kB]['children'][$kU]['children'][$kBU])) {
                    $tree[$kB]['children'][$kU]['children'][$kBU] = ['kode' => $kBU, 'nama' => $nBU, 'pagu' => 0, 'lalu' => 0, 'ini' => 0, 'total' => 0, 'children' => []];
                }
                $this->addValueToNode($tree[$kB]['children'][$kU]['children'][$kBU], $type, $amount);
                if (empty($tree[$kB]['children'][$kU]['children'][$kBU]['nama']) && !empty($nBU)) $tree[$kB]['children'][$kU]['children'][$kBU]['nama'] = $nBU;

                if ($maxLevel >= 4 && $kP) {
                    if (!isset($tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP])) {
                        $tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP] = ['kode' => $kP, 'nama' => $nP, 'pagu' => 0, 'lalu' => 0, 'ini' => 0, 'total' => 0, 'children' => []];
                    }
                    $this->addValueToNode($tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP], $type, $amount);
                    if (empty($tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['nama']) && !empty($nP)) $tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['nama'] = $nP;

                    if ($maxLevel >= 5 && $kK) {
                        if (!isset($tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['children'][$kK])) {
                            $tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['children'][$kK] = ['kode' => $kK, 'nama' => $nK, 'pagu' => 0, 'lalu' => 0, 'ini' => 0, 'total' => 0, 'children' => []];
                        }
                        $this->addValueToNode($tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['children'][$kK], $type, $amount);
                        if (empty($tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['children'][$kK]['nama']) && !empty($nK)) $tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['children'][$kK]['nama'] = $nK;

                        if ($maxLevel >= 6 && $kS) {
                            if (!isset($tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['children'][$kK]['children'][$kS])) {
                                $tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['children'][$kK]['children'][$kS] = ['kode' => $kS, 'nama' => $nS, 'pagu' => 0, 'lalu' => 0, 'ini' => 0, 'total' => 0, 'children' => []];
                            }
                            $this->addValueToNode($tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['children'][$kK]['children'][$kS], $type, $amount);
                            if (empty($tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['children'][$kK]['children'][$kS]['nama']) && !empty($nS)) $tree[$kB]['children'][$kU]['children'][$kBU]['children'][$kP]['children'][$kK]['nama'] = $nS;
                        }
                    }
                }
            }
        }
    }

    private function addValueToNode(array &$node, string $type, $amount): void
    {
        if ($type === 'pagu') {
            $node['pagu'] += (float)$amount;
        } elseif ($type === 'realisasi') {
            $node['lalu'] += (float)($amount['lalu'] ?? 0);
            $node['ini'] += (float)($amount['ini'] ?? 0);
            $node['total'] += (float)($amount['total'] ?? 0);
        }
    }

    private function flattenTree(array $nodes, int $level, array &$rows): void
    {
        ksort($nodes);
        foreach ($nodes as $node) {
            $anggaran = (float)$node['pagu'];
            $lalu = (float)$node['lalu'];
            $ini = (float)$node['ini'];
            $total = (float)$node['total'];
            $sisa = $anggaran - $total;
            $persen = $anggaran > 0 ? ($total / $anggaran) * 100 : 0;

            // Jangan tampilkan jika anggaran dan total realisasi 0
            if ($anggaran == 0 && $total == 0) continue;

            $hasChildren = !empty($node['children']);

            $rows[] = [
                'level'            => $level,
                'kode'             => $node['kode'],
                'nama'             => $node['nama'],
                'anggaran'         => $anggaran,
                'realisasi_lalu'   => $lalu,
                'realisasi_ini'    => $ini,
                'realisasi_total'  => $total,
                'sisa'             => $sisa,
                'persen'           => $persen,
                'has_children'     => $hasChildren
            ];

            if ($hasChildren) {
                $this->flattenTree($node['children'], $level + 1, $rows);
            }
        }
    }
}
