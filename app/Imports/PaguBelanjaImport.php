<?php

namespace App\Imports;

use App\Models\PaguBelanjaModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class PaguBelanjaImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading
{
    protected int $kdBerapax;

    public function __construct(int $kdBerapax)
    {
        $this->kdBerapax = $kdBerapax;
    }

    public function collection(Collection $rows)
    {
        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'tahun_rek' => $row['tahun_rek'],
                'kd_urusan' => $row['kd_urusan'],
                'kd_prog1' => $row['kd_prog1'],
                'kd_prog2' => $row['kd_prog2'],
                'kd_prog3' => $row['kd_prog3'],
                'kd_keg1' => $row['kd_keg1'],
                'kd_keg2' => $row['kd_keg2'],
                'kd_keg3' => $row['kd_keg3'],
                'kd_keg4' => $row['kd_keg4'],
                'kd_keg5' => $row['kd_keg5'],
                'kd_subkeg1' => $row['kd_subkeg1'],
                'kd_subkeg2' => $row['kd_subkeg2'],
                'kd_subkeg3' => $row['kd_subkeg3'],
                'kd_subkeg4' => $row['kd_subkeg4'],
                'kd_subkeg5' => $row['kd_subkeg5'],
                'kd_subkeg6' => $row['kd_subkeg6'],
                'kd_rekening1' => $row['kd_rekening1'],
                'kd_rekening2' => $row['kd_rekening2'],
                'kd_rekening3' => $row['kd_rekening3'],
                'kd_rekening4' => $row['kd_rekening4'],
                'kd_rekening5' => $row['kd_rekening5'],
                'kd_rekening6' => $row['kd_rekening6'],
                'kd_opd1' => $row['kd_opd1'],
                'kd_opd2' => $row['kd_opd2'],
                'kd_opd3' => $row['kd_opd3'],
                'kd_opd4' => $row['kd_opd4'],
                'kd_opd5' => $row['kd_opd5'],
                'kd_opd6' => $row['kd_opd6'],
                'kd_opd7' => $row['kd_opd7'],
                'kd_opd8' => $row['kd_opd8'],
                'kd_bu1' => $row['kd_bu1'],
                'kd_bu2' => $row['kd_bu2'],
                'kd_relasi' => $row['kd_relasi'] ?? null,
                'kd_berapax' => $this->kdBerapax,
                'jumlah_pagu' => $row['jumlah_pagu'],
                'is_deleted' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 🔥 BULK INSERT (CEPAT BANGET)
        PaguBelanjaModel::insert($data);
    }

    public function chunkSize(): int
    {
        return 1000; // boleh dinaikkan
    }
}
