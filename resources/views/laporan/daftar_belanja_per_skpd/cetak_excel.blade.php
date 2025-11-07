<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Daftar Belanja Per SKPD</title>
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
                <h4 class="text-uppercase font-weight-light"><b>LAPORAN DAFTAR BELANJA PER SKPD</b></h4>
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
                <table>
                    <tr>
                        <td>SKPD</td>
                        <td>:</td>
                        <td>[3-27.0-00.0-00.01 Dinas Pertanian]</td>
                    </tr>
                </table>
                <table class="new-table">
                    <thead>
                        <tr>
                            <th class="bold uppercase">No. </th>
                            <th class="bold uppercase">Tanggal</th>
                            <th class="bold uppercase">Sumber Dana</th>
                            <th class="bold uppercase">Jenis Belanja</th>
                            <th class="bold uppercase">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                             $no = 1;
                             $totalJumlah = 0;
                             foreach ($result as $row) {
                                $totalJumlah += $row['JUMLAH'];
                            ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['TANGGAL']) ?></td>
                            <td><?= htmlspecialchars($row['SUMBER_DANA']) ?></td>
                            <td><?= htmlspecialchars($row['JENIS_BELANJA']) ?></td>
                            <td><?= 'Rp ' . number_format($row['JUMLAH'], 2, ',', '.') ?></td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <td colspan="4" class="text-right bold uppercase">
                                <h6><strong>Total</strong></h6>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                <h6><?= 'Rp ' . number_format($totalJumlah, 2, ',', '.') ?></h6>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>

</html>
