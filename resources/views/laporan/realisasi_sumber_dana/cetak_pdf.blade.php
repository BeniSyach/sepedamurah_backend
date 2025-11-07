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
                                    SUMBER DANA</b></h4><br>
                            <h6 class="text-center text-uppercase font-weight-light text-inline">BERDASARKAN KLASIFIKASI
                                SUMBER DANA</h6><br>
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
                <table class="new-table">
                    <thead>
                        <tr>
                            <td class="bold uppercase">
                                <h6><strong>No.</strong></h6>
                            </td>
                            <td class="bold uppercase">
                                <h6><strong>Kode Rekening</strong></h6>
                            </td>
                            <td class="bold uppercase">
                                <h6><strong>Uraian</strong></h6>
                            </td>
                            <td class="bold uppercase">
                                <h6><strong>Pagu Anggaran</strong></h6>
                            </td>
                            <td class="bold uppercase">
                                <h6><strong>Saldo Awal Tahun Lalu</strong></h6>
                            </td>
                            <td class="bold uppercase">
                                <h6><strong>Dana Masuk</strong></h6>
                            </td>
                            <td class="bold uppercase">
                                <h6><strong>Realisasi Belanja SKPD</strong></h6>
                            </td>
                            <td class="bold uppercase">
                                <h6><strong>Sisa Sumber Dana</strong></h6>
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                             $no = 1;
                              $total_pagu = 0;
                                        $total_saldo = 0;
                                        $total_dana_masuk = 0;
                                        $total_belanja = 0;
                                        $total_sisa = 0;
                             foreach ($result as $sd) {
                                $total_pagu += $sd['PAGU'];
                                $total_saldo += $sd['JUMLAH_SILPA'];
                                $total_dana_masuk += $sd['SUMBER_DANA'];
                                $total_belanja += $sd['BELANJA'];
                                $total_sisa += $sd['SISA'];
                            ?>
                        <tr>
                            <td>
                                <h6><?= $no++ ?></h6>
                            </td>
                            <td>
                                <?php
                                $refs = array_filter([$sd['KD_REF1'], $sd['KD_REF2'], $sd['KD_REF3'], $sd['KD_REF4'], $sd['KD_REF5'], $sd['KD_REF6']]);
                                echo '<h6>' . implode('.', $refs) . '</h6>'; ?>
                            </td>
                            <td>
                                <h6><?= $sd['NM_SUMBER'] ?></h6>
                            </td>
                            <td>
                                <h6><?php echo 'Rp ' . number_format($sd['PAGU'], 2, ',', '.'); ?></h6>
                            </td>
                            <td>
                                <h6><?= 'Rp ' . number_format($sd['JUMLAH_SILPA'], 2, ',', '.') ?></h6>
                            </td>
                            <td>
                                <h6><?= 'Rp ' . number_format($sd['SUMBER_DANA'], 2, ',', '.') ?></h6>
                            </td>
                            <td>
                                <h6><?= 'Rp ' . number_format($sd['BELANJA'], 2, ',', '.') ?></h6>
                            </td>
                            <td>
                                <h6><?= 'Rp ' . number_format($sd['SISA'], 2, ',', '.') ?></h6>
                            </td>
                        </tr>

                        <?php } ?>

                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan='3'>
                                <center>
                                    <h5>Total</h5>
                                </center>
                            </td>
                            <td>
                                <h6><?php echo 'Rp ' . number_format($total_pagu, 2, ',', '.'); ?></h6>
                            </td>
                            <td>
                                <h6><?php echo 'Rp ' . number_format($total_saldo, 2, ',', '.'); ?></h6>
                            </td>
                            <td>
                                <h6><?php echo 'Rp ' . number_format($total_dana_masuk, 2, ',', '.'); ?></h6>
                            </td>
                            <td>
                                <h6><?php echo 'Rp ' . number_format($total_belanja, 2, ',', '.'); ?></h6>
                            </td>
                            <td>
                                <h6><?php echo 'Rp ' . number_format($total_sisa, 2, ',', '.'); ?></h6>
                            </td>

                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>
</body>

</html>
