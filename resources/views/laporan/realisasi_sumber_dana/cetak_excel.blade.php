<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Realisasi Sumber Dana</title>
    <style>
        @page {
            size: landscape;
            margin: 5mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 20px;
        }

        .header,
        .content {
            text-align: center;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 5px;
        }

        .content-wrapper {
            border: 2px solid #000;
            padding: 20px;
            margin: 5px;
        }

        .new-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 2px solid #000;
        }

        .new-table td,
        .new-table th {
            padding: 10px;
            border: 1px solid #000;
        }

        .new-table .bold {
            background-color: #f0f0f0;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .new-table .bold.uppercase {
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f0f0f0;
            text-align: center;
        }

        h4,
        p {
            margin: 5px 0;
            /* Mengatur jarak antar elemen */
        }
    </style>
</head>

<body>
    <div class="content-wrapper">
        <section class="content">
            <div class="header">
                <img src="https://i.ibb.co.com/ChGfL1y/logo-pemkab.png" width="60" height="70"><br>
                <h4 class="text-uppercase font-weight-light"><b>PEMERINTAHAN KABUPATEN SERDANG BEDAGAI</b></h4>
                <h4 class="text-uppercase font-weight-light"><b>LAPORAN REALISASI ANGGARAN BELANJA DAN PENGELUARAN
                        PEMBIAYAAN</b></h4>
                <p class="text-uppercase font-weight-light">BERDASARKAN KLASIFIKASI SUMBER DANA</p>
                <?php
                function buatPeriode($tahun)
                {
                    $periodeAwal = "01 Januari $tahun";
                    $periodeAkhir = "31 Desember $tahun";
                
                    return "Periode $periodeAwal s.d. $periodeAkhir";
                }
                ?>
                <p><?= buatPeriode($tahun) ?></p>
            </div>
            <div class="content">
                <table class="new-table">
                    <thead>
                        <tr>
                            <th class="bold uppercase">No.</th>
                            <th class="bold uppercase">Kode Rekening</th>
                            <th class="bold uppercase">Uraian</th>
                            <th class="bold uppercase">Pagu Anggaran</th>
                            <th class="bold uppercase">Saldo Awal Tahun Lalu</th>
                            <th class="bold uppercase">Dana Masuk</th>
                            <th class="bold uppercase">Realisasi Belanja SKPD</th>
                            <th class="bold uppercase">Sisa Sumber Dana</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                             $no = 1;
                             foreach ($result as $sd) {
                            ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <?php
                                $refs = array_filter([$sd['KD_REF1'], $sd['KD_REF2'], $sd['KD_REF3'], $sd['KD_REF4'], $sd['KD_REF5'], $sd['KD_REF6']]);
                                echo implode('.', $refs); ?>
                            </td>
                            <td><?= $sd['NM_SUMBER'] ?></td>
                            <td><?php echo 'Rp ' . number_format($sd['PAGU'], 2, ',', '.'); ?></td>
                            <td><?= 'Rp ' . number_format($sd['JUMLAH_SILPA'], 2, ',', '.') ?></td>
                            <td><?= 'Rp ' . number_format($sd['SUMBER_DANA'], 2, ',', '.') ?></td>
                            <td><?= 'Rp ' . number_format($sd['BELANJA'], 2, ',', '.') ?></td>
                            <td><?= 'Rp ' . number_format($sd['SISA'], 2, ',', '.') ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>

</html>
