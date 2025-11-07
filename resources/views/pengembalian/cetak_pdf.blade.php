<html>

<head>
    <title>Data Pengembalian</title>
    <style>
        body {
            font-family: "Calibri", sans-serif;
        }

        .table {
            font-size: large;
        }

        .table td,
        .table th {
            vertical-align: middle;

        }

        .table thead th {
            vertical-align: middle;
        }

        @media (max-width: 767px) {

            .table td,
            .table th {
                padding: 0 20px 0 20px;

            }

        }

        .fix {
            position: fixed;
            bottom: 0px;
            width: 100%;
            margin-bottom: 0;
        }
    </style>

</head>

<body>
    <table border="0" class="table">
        <tr>
            <td align="left"><img src="<?= base_url('assets/img/logo_pemkab.png') ?>" style="max-width:30%"></td>
            <td colspan=6 height="21" align="left">
                <h2 style="margin-left:-80px">DATA PENGEMBALIAN</h2>
            </td>
        </tr>
        <tr>
            <td align="left" colspan="8"><br></td>
        </tr>

        <tr>
            <td height="21" align="center"><br></td>
            <td align="center"><br></td>
            <td align="center"><br></td>
            <td align="center"><br></td>
            <td align="center"><br></td>
            <td align="center"><br></td>
            <td align="center"><br></td>
            <td align="center"><br></td>
        </tr>
        <tr valign="top">
            <td align="left" valign="top" style="display: table-cell;
  line-height: 1.2em;
  vertical-align:top;">
                Nama Rekening</td>
            <td align="left" valign="top" style="display: table-cell;
  line-height: 1.2em;
  vertical-align:top;">:
            </td>
            <td align="left" colspan="6"><?= $rekening ?></td>
        </tr>
        <tr>
            <td align="left" colspan="8"><br></td>
        </tr>

        <tr>
            <td align="left">NIK</td>
            <td align="left">:</td>
            <td align="left" colspan="6"><?= $nik ?> </td>
        </tr>
        <tr>
            <td align="left" colspan="8"><br></td>
        </tr>


        <tr>
            <td align="left">Atas Nama</td>
            <td align="left">:</td>
            <td align="left" colspan="6"><?= $nama ?> </td>
        </tr>
        <tr>
            <td align="left" colspan="8"><br></td>
        </tr>

        <tr>
            <td align="left" width="300">Jumlah yang harus dikembalikan</td>
            <td align="left">:</td>
            <td align="left" colspan="6"><?= $jumlah ?></td>
        </tr>
        <tr>
            <td align="left" colspan="8"><br></td>
        </tr>

        <tr>
            <td align="left">Terbilang</td>
            <td align="left">:</td>
            <td align="left" colspan="6"><?= $terbilang ?> Rupiah</td>
        </tr>
        <tr>
            <td align="left" colspan="8"><br></td>
        </tr>

        <tr>
            <td align="left">Keterangan</td>
            <td align="left">:</td>
            <td align="left" colspan="6"><?= $keterangan ?></td>
        </tr>

        <tr>
            <td align="left" colspan="8"><br></td>
        </tr>
        <tr>
            <td align="left" colspan="8"><br></td>
        </tr>
        <tr>
            <td align="left" colspan="8"><br></td>
        </tr>
        <tr>
            <td align="left"></td>
            <td align="left"></td>
            <td align="right" colspan="6">Sei Rampah, <?= $tanggal ?></td>
        </tr>
        <!--<tr>
        <td align="left" colspan="8"><br></td>
    </tr>
    <tr>
        <td align="left"><br></td><td align="left"></td>
        <td align="right" colspan="6">Penyetor : <?= $penyetor ?></td>
    </tr>
    <tr>
        <td align="left"><br></td><td align="left"></td>
        <td align="right" colspan="6">Alamat : <?= $alamat ?></td>
    </tr>-->

        <tr>
            <td align="left" colspan="6">No STS : <?php
            $sts = "$no_billing";
            // Mengambil 4 digit pertama
            $part1 = substr($sts, 0, 4);
            // Mengambil digit ke-5 sampai ke-11
            $part2 = substr($sts, 4, 7);
            // Mengambil sisanya
            $part3 = substr($sts, -10);
            // Menampilkan dengan titik sebagai pemisah
            $angka_format = $part1 . '.' . $part2 . '.' . $part3;
            echo '<b>' . $angka_format . '</b>'; ?>
            </td>
            <td align="left"></td>
            <td align="left"></td>
        </tr>
        <tr>
            <td align="left" colspan="8"><br></td>
        </tr>

        <tr>
            <td align="left" colspan="8"><br></td>
        </tr>

        <tr>
            <td align="center" colspan="8"><img src="<?= base_url('assets/img/logo_sepeda.png') ?>"
                    style="max-width:18%"></td>
        </tr>


    </table>
    <!-- <img src="<?php echo base_url() . 'assets/img/footer-bg.png'; ?>" class="fix"/> -->


</body>

</html>
