<?php

if (isset($_GET['tahun'])) {
    $tahun = $_GET['tahun'];
} else {
    $tahun = date('Y');
}

$kalender = CAL_GREGORIAN;
$bulan = date('m');
$tahun = date('Y');
$hari = cal_days_in_month($kalender, $bulan, $tahun);
if (isset($_GET['filter'])) {
    $start = $_GET['start_date'];
    $end = $_GET['end_date'];
    $skpd = isset($_GET['skpd']) ? $_GET['skpd'] : null;
} else {
    $start = $tahun . '-' . $bulan . '-' . '01';
    $end = $tahun . '-' . $bulan . '-' . $hari;
    $skpd = isset($_GET['skpd']) ? $_GET['skpd'] : null;
}
?>

<html>

<head>
    <title>Laporan Pajak</title>
    <style>
        table {
            font-family: "Calibri";
            font-size: 12px;
        }

        body {
            font-family: "Calibri";
            font-size: 12px;
        }

        th,
        td {
            padding: 5px;
        }
    </style>
</head>

<body>
    <table cellspacing="0" border="1" width="100%">

        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Alamat</th>
                <th>Nama OPD</th>
                <th>Keterangan</th>
                <th>Rekening Pengembalian</th>
                <th>Jumlah Pengembalian</th>
                <th>Tanggal Setor</th>
                <th>Jumlah Disetor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
$query = "SELECT a.NIK, a.NAMA, a.ALAMAT, b.NM_OPD, a.NM_REKENING AS KET_PENGEMBALIAN,
a.JML_PENGEMBALIAN, a.TGL_SETOR, a.JML_YG_DISETOR, a.KETERANGAN AS KET_TAMBAHAN, a.TGL_REKAM,
CASE
   WHEN a.JML_PENGEMBALIAN - a.JML_YG_DISETOR <= 0
      THEN 'SDH BAYAR'
   WHEN a.JML_PENGEMBALIAN - a.JML_YG_DISETOR = JML_PENGEMBALIAN
      THEN 'BLM BAYAR'
   ELSE 'KRG BAYAR'
END AS KETERANGAN
FROM DATA_PENGEMBALIAN a INNER JOIN REF_OPD b
ON a.KD_OPD1 = b.KD_OPD1
AND a.KD_OPD2 = b.KD_OPD2
AND a.KD_OPD3 = b.KD_OPD3
AND a.KD_OPD4 = b.KD_OPD4
AND a.KD_OPD5 = b.KD_OPD5
WHERE a.TGL_REKAM >= TO_DATE('$start', 'YYYY-MM-DD') AND a.TGL_REKAM <= TO_DATE('$end', 'YYYY-MM-DD')";

if (!empty($skpd)) {
    $kode_opd = array_map(function ($part) {
        return $part === '' ? NULL : $part;
    }, explode('-', $skpd));

    $KD_OPD1 = ($kode_opd[0] ?? NULL);
    $KD_OPD2 = ($kode_opd[1] ?? NULL);
    $KD_OPD3 = ($kode_opd[2] ?? NULL);
    $KD_OPD4 = ($kode_opd[3] ?? NULL);
    $KD_OPD5 = ($kode_opd[4] ?? NULL);

$query .= " AND a.KD_OPD1 = '$KD_OPD1'  AND a.KD_OPD2 = '$KD_OPD2'  AND a.KD_OPD3 = '$KD_OPD3'  AND a.KD_OPD4 = '$KD_OPD4'  AND a.KD_OPD5 = '$KD_OPD5'";
}

$data_pengembalian = $this->db->query($query)->result_array();

$no = 1;
foreach($data_pengembalian as $pengembalian){
?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $pengembalian['NIK'] ?></td>
                <td><?= $pengembalian['NAMA'] ?></td>
                <td><?= $pengembalian['ALAMAT'] ?></td>
                <td><?= $pengembalian['NM_OPD'] ?></td>
                <td><?= $pengembalian['KET_TAMBAHAN'] ?></td>
                <td><?= $pengembalian['KET_PENGEMBALIAN'] ?></td>
                <td><?= number_format($pengembalian['JML_PENGEMBALIAN'], 0, ',', '.') ?></td>
                <td><?= $pengembalian['TGL_SETOR'] ?></td>
                <td><?= number_format($pengembalian['JML_YG_DISETOR'], 0, ',', '.') ?></td>
                <td><?= $pengembalian['KETERANGAN'] ?></td>

            </tr>

            <?php } ?>
        </tbody>
    </table>
    <script>
        window.print();
    </script>

</body>

</html>
