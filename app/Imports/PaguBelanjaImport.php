<?php

namespace App\Imports;

use App\Models\PaguBelanjaModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class PaguBelanjaImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading
{
    protected int $kdBerapax;

    public function __construct(int $kdBerapax)
    {
        $this->kdBerapax = $kdBerapax;

        // 🔥 MATIKAN AUTO FORMAT HEADER
        HeadingRowFormatter::default('none');
    }

    public function collection(Collection $rows)
    {
        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'tahun_rek' => $row['TAHUN_REK'] ?? null,
                'kd_urusan' => $row['KD_URUSAN'] ?? null,
                'kd_prog1'  => $row['KD_PROG1'] ?? null,
                'kd_prog2'  => $row['KD_PROG2'] ?? null,
                'kd_prog3'  => $row['KD_PROG3'] ?? null,
                'kd_keg1'   => $row['KD_KEG1'] ?? null,
                'kd_keg2'   => $row['KD_KEG2'] ?? null,
                'kd_keg3'   => $row['KD_KEG3'] ?? null,
                'kd_keg4'   => $row['KD_KEG4'] ?? null,
                'kd_keg5'   => $row['KD_KEG5'] ?? null,
                'kd_subkeg1'=> $row['KD_SUBKEG1'] ?? null,
                'kd_subkeg2'=> $row['KD_SUBKEG2'] ?? null,
                'kd_subkeg3'=> $row['KD_SUBKEG3'] ?? null,
                'kd_subkeg4'=> $row['KD_SUBKEG4'] ?? null,
                'kd_subkeg5'=> $row['KD_SUBKEG5'] ?? null,
                'kd_subkeg6'=> $row['KD_SUBKEG6'] ?? null,
                'kd_rekening1'=> $row['KD_REKENING1'] ?? null,
                'kd_rekening2'=> $row['KD_REKENING2'] ?? null,
                'kd_rekening3'=> $row['KD_REKENING3'] ?? null,
                'kd_rekening4'=> $row['KD_REKENING4'] ?? null,
                'kd_rekening5'=> $row['KD_REKENING5'] ?? null,
                'kd_rekening6'=> $row['KD_REKENING6'] ?? null,
                'kd_opd1' => $row['KD_OPD1'] ?? null,
                'kd_opd2' => $row['KD_OPD2'] ?? null,
                'kd_opd3' => $row['KD_OPD3'] ?? null,
                'kd_opd4' => $row['KD_OPD4'] ?? null,
                'kd_opd5' => $row['KD_OPD5'] ?? null,
                'kd_opd6' => $row['KD_OPD6'] ?? null,
                'kd_opd7' => $row['KD_OPD7'] ?? null,
                'kd_opd8' => $row['KD_OPD8'] ?? null,
                'kd_bu1'  => $row['KD_BU1'] ?? null,
                'kd_bu2'  => $row['KD_BU2'] ?? null,
                'kd_relasi' => $row['KD_RELASI'] ?? null,
                'kd_berapax' => $this->kdBerapax,
                'jumlah_pagu' => $row['JUMLAH_PAGU'] ?? 0,
                'is_deleted' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($data)) {
            PaguBelanjaModel::insert($data);
        }
    }

    public function chunkSize(): int
    {
        return 1000; // boleh dinaikkan
    }
}
