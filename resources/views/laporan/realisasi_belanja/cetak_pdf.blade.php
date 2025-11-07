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
                            <h4 class="text-center text-uppercase font-weight-light text-inline"><b>LAPORAN REALISASI
                                    BELANJA</b></h4><br>
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
                            <h6 class="text-center font-weight-light text-inline">
                                <?= buatPeriode($tahun, $bulan, $months) ?></h6>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="content">
                <table class="new-table">
                    <thead>
                        <tr>
                            <td class="bold uppercase" rowspan="2">
                                <h6><strong>No.</strong></h6>
                            </td>
                            <td class="bold uppercase" rowspan="2">
                                <h6><strong>Kode Rekening</strong></h6>
                            </td>
                            <td class="bold uppercase" rowspan="2">
                                <h6><strong>Jenis Belanja</strong></h6>
                            </td>
                            <td class="bold uppercase" colspan="3">
                                <h6><strong>Realisasi</strong></h6>
                            </td>
                        </tr>

                        <tr>
                            <td class="bold uppercase">
                                <h6><strong>s.d Bulan Lalu</strong></h6>
                            </td>
                            <td class="bold uppercase">
                                <h6><strong>Bulan Ini</strong></h6>
                            </td>
                            <td class="bold uppercase">
                                <h6><strong>s.d Bulan Ini</strong></h6>
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                             $no = 1;
                             $total_belanja_sd_bulan_lalu = 0;
                                        $total_belanja_bulan_ini = 0;
                                        $total_belanja_sd_bulan_ini = 0;
                             foreach ($result as $rb) {
                                $belanja_sd_bulan_lalu = 0;
                                for ($i = 1; $i < $bulan; $i++) {
                                    $belanja_sd_bulan_lalu += $rb['BELANJA_' . strtoupper(date('M', mktime(0, 0, 0, $i, 10)))];
                                }
                                $belanja_bulan_ini = $rb['BELANJA_' . strtoupper(date('M', mktime(0, 0, 0, $bulan, 10)))];
                                $belanja_sd_bulan_ini = $belanja_sd_bulan_lalu + $belanja_bulan_ini;
                                $total_belanja_sd_bulan_lalu += $belanja_sd_bulan_lalu;
                                $total_belanja_bulan_ini += $belanja_bulan_ini;
                                $total_belanja_sd_bulan_ini += $belanja_sd_bulan_ini;
                            ?>
                        <tr>
                            <td>
                                <h6><?= $no++ ?></h6>
                            </td>
                            <td>
                                <?php
                                // Filter data yang tidak kosong
                                $refs = array_filter([$rb['KD_REF1'], $rb['KD_REF2'], $rb['KD_REF3']]);
                                
                                // Gabungkan data dengan titik sebagai pemisah
                                echo '<h6>' . implode('.', $refs) . '</h6>';
                                ?>
                            </td>
                            <td>
                                <h6><?= $rb['JENIS_BELANJA'] ?></h6>
                            </td>
                            <td>
                                <h6><?= 'Rp ' . number_format($belanja_sd_bulan_lalu, 2, ',', '.') ?></h6>
                            </td>
                            <td>
                                <h6><?= 'Rp ' . number_format($belanja_bulan_ini, 2, ',', '.') ?></h6>
                            </td>
                            <td>
                                <h6><?= 'Rp ' . number_format($belanja_sd_bulan_ini, 2, ',', '.') ?></h6>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan='3'>
                                <center>
                                    <h6>Total</h6>
                                </center>
                            </td>
                            <td>
                                <h6><?php echo 'Rp ' . number_format($total_belanja_sd_bulan_lalu, 2, ',', '.'); ?></h6>
                            </td>
                            <td>
                                <h6><?php echo 'Rp ' . number_format($total_belanja_bulan_ini, 2, ',', '.'); ?></h6>
                            </td>
                            <td>
                                <h6><?php echo 'Rp ' . number_format($total_belanja_sd_bulan_ini, 2, ',', '.'); ?></h6>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>
</body>

</html>
