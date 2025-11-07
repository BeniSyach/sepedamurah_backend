<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Realisasi Belanja</title>
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
                <img src="https://i.ibb.co.com/ChGfL1y/logo-pemkab.png" width="100" height="100"><br>
                <h4 class="text-uppercase font-weight-light"><b>PEMERINTAHAN KABUPATEN SERDANG BEDAGAI</b></h4>
                <h4 class="text-uppercase font-weight-light"><b>LAPORAN REALISASI BELANJA</b></h4>
                <?php
                $months = [
                    '01' => 'Januari',
                    '02' => 'Februari',
                    '03' => 'Maret',
                    '04' => 'April',
                    '05' => 'Mei',
                    '06' => 'Juni',
                    '07' => 'Juli',
                    '08' => 'Agustus',
                    '09' => 'September',
                    '10' => 'Oktober',
                    '11' => 'November',
                    '12' => 'Desember',
                ];
                function buatPeriode($tahun, $bulan, $months)
                {
                    $periodeAwal = "01 Januari $tahun";
                    $bulanNama = $months[$bulan];
                    $periodeAkhir = "31 $bulanNama $tahun";
                
                    return "Periode $periodeAwal s.d. $periodeAkhir";
                }
                ?>
                <p><?= buatPeriode($tahun, $bulan, $months) ?></p>
            </div>
            <div class="content">
                <table class="new-table">
                    <thead>
                        <tr>
                            <th class="bold uppercase">No.</th>
                            <th class="bold uppercase">Kode Rekening</th>
                            <th class="bold uppercase">Jenis Belanja</th>
                            <th class="bold uppercase">s.d Bulan Lalu</th>
                            <th class="bold uppercase">Bulan Ini</th>
                            <th class="bold uppercase">s.d Bulan Ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                             $no = 1;
                             foreach ($result as $rb) {
                                $belanja_sd_bulan_lalu = 0;
                                for ($i = 1; $i < $bulan; $i++) {
                                    $belanja_sd_bulan_lalu += $rb['BELANJA_' . strtoupper(date('M', mktime(0, 0, 0, $i, 10)))];
                                }
                                $belanja_bulan_ini = $rb['BELANJA_' . strtoupper(date('M', mktime(0, 0, 0, $bulan, 10)))];
                                $belanja_sd_bulan_ini = $belanja_sd_bulan_lalu + $belanja_bulan_ini;
                            ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <?php
                                // Filter data yang tidak kosong
                                $refs = array_filter([$rb['KD_REF1'], $rb['KD_REF2'], $rb['KD_REF3']]);
                                
                                // Gabungkan data dengan titik sebagai pemisah
                                echo implode('.', $refs);
                                ?>
                            </td>
                            <td><?= $rb['JENIS_BELANJA'] ?></td>
                            <td><?= 'Rp ' . number_format($belanja_sd_bulan_lalu, 2, ',', '.') ?></td>
                            <td><?= 'Rp ' . number_format($belanja_bulan_ini, 2, ',', '.') ?></td>
                            <td><?= 'Rp ' . number_format($belanja_sd_bulan_ini, 2, ',', '.') ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>

</html>
