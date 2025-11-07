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

        .text-center {
            text-align: center;
        }

        .font-weight-light {
            font-weight: 300;
        }

        .text-inline {
            display: inline;
        }

        td {
            padding: 5px;
        }

        table td h6 {
            font-weight: normal;
            margin: 0;
        }

        table {
            border-collapse: collapse;
        }

        /* Bingkai di sekitar konten */
        .content-wrapper {
            border: 2px solid #000;
            /* Ketebalan dan warna bingkai */
            padding: 20px;
            /* Ruang antara konten dan bingkai */
            margin: 5px;
            /* Jarak di sekitar bingkai */
        }

        /* Gaya untuk tabel baru */
        .new-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 2px solid #000;
        }

        .new-table td {
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
            padding: 10px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="content-wrapper"> <!-- Pembungkus dengan bingkai -->
        <section class="content">
            <div class="header">
                <table width="100%">
                    <tr align="center">
                        <td><img src="https://i.ibb.co.com/ChGfL1y/logo-pemkab.png" width="60" height="70"></td>
                        <td>
                            <h4 class="text-center text-uppercase font-weight-light text-inline"><b>PEMERINTAHAN
                                    KABUPATEN SERDANG BEDAGAI</b></h4><br>
                            <h4 class="text-center text-uppercase font-weight-light text-inline"><b>LAPORAN DAFTAR
                                    BELANJA PER SKPD</b></h4><br>
                            <?php
                            function buatPeriode($tahun)
                            {
                                $periodeAwal = "01 Januari $tahun";
                                $periodeAkhir = "31 Desember $tahun";
                            
                                return "Periode $periodeAwal s.d. $periodeAkhir";
                            }
                            ?>
                            <h6 class="text-center font-weight-light text-inline"><?= buatPeriode($tahun) ?></h6>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="content">
                <table style="border-collapse: collapse;">
                    <tr>
                        <td>
                            <h6 style="margin: 0; padding: 0; text-align: left;">SKPD</h6>
                        </td>
                        <td>
                            <h6 style="margin: 0; padding: 0; text-align: left;">:</h6>
                        </td>
                        <td>
                            <h6 style="margin: 0; padding: 0; text-align: left;">
                                <?= '[ ' . $SKPD['KD_OPD1'] . '.' . $SKPD['KD_OPD2'] . '.' . $SKPD['KD_OPD3'] . '.' . $SKPD['KD_OPD4'] . '.' . $SKPD['KD_OPD5'] . ' ' . $SKPD['NM_OPD'] . ' ]' ?>
                            </h6>
                        </td>
                    </tr>
                </table>

                <!-- Tabel baru dengan gaya khusus -->
                <table class="new-table">
                    <tr>
                        <td class="bold uppercase">
                            <h6><strong>No. </strong></h6>
                        </td>
                        <td class="bold uppercase">
                            <h6><strong>Tanggal</strong></h6>
                        </td>
                        <td class="bold uppercase">
                            <h6><strong>Sumber Dana</strong></h6>
                        </td>
                        <td class="bold uppercase">
                            <h6><strong>Jenis Belanja</strong></h6>
                        </td>
                        <td class="bold uppercase">
                            <h6><strong>Jumlah</strong></h6>
                        </td>
                    </tr>
                    <?php
                             $no = 1;
                             $totalJumlah = 0;
                             foreach ($result as $row) {
                                $totalJumlah += $row['JUMLAH'];
                            ?>
                    <tr>
                        <td>
                            <h6><?= htmlspecialchars($no++) ?></h6>
                        </td>
                        <td>
                            <h6><?= htmlspecialchars($row['TANGGAL']) ?></h6>
                        </td>
                        <td>
                            <h6><?= htmlspecialchars($row['SUMBER_DANA']) ?></h6>
                        </td>
                        <td>
                            <h6><?= htmlspecialchars($row['JENIS_BELANJA']) ?></h6>
                        </td>
                        <td>
                            <h6><?= 'Rp ' . number_format($row['JUMLAH'], 2, ',', '.') ?></h6>
                        </td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <td colspan="4" class="text-right bold uppercase">
                            <h6><strong>Total</strong></h6>
                        </td>
                        <td>
                            <h6><?= 'Rp ' . number_format($totalJumlah, 2, ',', '.') ?></h6>
                        </td>
                    </tr>
                </table>
            </div>
        </section>
    </div>
</body>

</html>
