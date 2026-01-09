<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    private $botToken;
    private $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/";
    }

    public function sendMessage($chat_id, $message)
    {
        return Http::asForm()->post($this->apiUrl . "sendMessage", [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ]);
    }

    // ==== SPD ====

    public function sendSpdFromSupervisor($chat_id)
    {
        $message = "📄 Permohonan SPD 📄\n\nHalo! Kami ingin menginformasikan bahwa *ADA PERMOHONAN MASUK SPD*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendSpdDitekenBud($chat_id)
    {
        $message = "📄 Permohonan SPD DITANDATANGAI BUD 📄\n\nHalo! Kami ingin menginformasikan bahwa *PERMOHONAN SPD TELAH DITANDA TANGANI BUD*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendSpdDiverifikasi($chat_id)
    {
        $message = "📄 Permohonan SPD DIVERIFIKASI 📄\n\nHalo! Kami ingin menginformasikan bahwa *PERMOHONAN SPD DIVERIFIKASI*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendSpdDitolak($chat_id, $alasan)
    {
        $message = "📄 Permohonan SPD DITOLAK 📄\n\nHalo! Kami ingin menginformasikan bahwa *PERMOHONAN SPD DITOLAK DENGAN ALASAN : $alasan*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    // ==== SP2D ====

    public function sendSp2dFromBendahara($chat_id, $no_spm, $keperluan)
    {
        $message = "📄 Permohonan SP2D 📄\n\nHalo! Kami ingin menginformasikan bahwa *ADA SPM MASUK DENGAN NO. $no_spm* dengan uraian keperluan : *$keperluan*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendSp2dTolak($chat_id, $no_spm, $ket)
    {
        $message = "📄 Permohonan SP2D DITOLAK 📄\n\nHalo! Kami ingin menginformasikan bahwa *SPM DENGAN NO. $no_spm DITOLAK DENGAN ALASAN : $ket*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendSp2dTerima($chat_id, $no_spm)
    {
        $message = "📄 Permohonan SP2D DIVERIFIKASI 📄\n\nHalo! Kami ingin menginformasikan bahwa *SPM DENGAN NO. $no_spm TELAH DIVERIFIKASI*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendSp2dToOperator($chat_id, $no_spm)
    {
        $message = "📄 Permohonan SP2D 📄\n\nHalo! Kami ingin menginformasikan bahwa *PROSES SPM YANG TELAH DI DISPOSISI NO. $no_spm TELAH DIVERIFIKASI*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    // ==== Fungsional Penerimaan ====

    public function sendFungsionalToSupervisor($chat_id)
    {
        // Pesan yang ingin dikirimkan
        $message = "📄 Permohonan FUNGSIONAL PENERIMAAN 📄\n\nHalo! Kami ingin menginformasikan bahwa *ADA MASUK LAPORAN FUNGSIONAL PENERIMAAN*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendFungsionalDiverifikasi($chat_id)
    {
        $message = "📄 Permohonan FUNGSIONAL PENERIMAAN DIVERIFIKASI 📄\n\nHalo! Kami ingin menginformasikan bahwa *LAPORAN FUNGSIONAL PENERIMAAN DIVERFIKASI*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendFungsionalDitolak($chat_id, $alasan)
    {
        $message = "📄 Permohonan FUNGSIONAL PENERIMAAN DITOLAK 📄\n\nHalo! Kami ingin menginformasikan bahwa *LAPORAN FUNGSIONAL PENERIMAAN DITOLAK DENGAN ALASAN : $alasan*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    // ==== Fungsional Pengeluaran ====

    public function sendFungsionalPengeluaranToSupervisor($chat_id)
    {
        $message = "📄 Permohonan FUNGSIONAL PENGELUARAN 📄\n\nHalo! Kami ingin menginformasikan bahwa *ADA MASUK LAPORAN FUNGSIONAL PENGELUARAN*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendFungsionalPengeluaranDiverifikasi($chat_id)
    {
        $message = "📄 Permohonan FUNGSIONAL PENGELUARAN DIVERIFIKASI 📄\n\nHalo! Kami ingin menginformasikan bahwa *LAPORAN FUNGSIONAL PENGELUARAN DIVERFIKASI*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";


        return $this->sendMessage($chat_id, $message);
    }

    public function sendFungsionalPengeluaranDitolak($chat_id, $alasan)
    {
        $message = "📄 Permohonan FUNGSIONAL PENGELUARAN DITOLAK 📄\n\nHalo! Kami ingin menginformasikan bahwa *LAPORAN FUNGSIONAL PENGELUARAN DITOLAK DENGAN ALASAN : $alasan*. Pesan ini dikirim secara otomatis melalui aplikasi sepedamurah. Jika ada pertanyaan lebih lanjut atau Anda memerlukan bantuan, silakan hubungi kami melalui aplikasi atau nomor kontak yang tersedia.\n\nTerima kasih atas perhatian Anda! 🚴‍♂️✨\n\n🌐 Aplikasi sepedamurah\n🌍 Kunjungi website kami di: https://sepedamurah.serdangbedagaikab.go.id\n🏛️ BPKAD Kabupaten Serdang Bedagai";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendLaporan($chat_id, $jenis_laporan)
    {
        $message = "📄 Ada Laporan Masuk $jenis_laporan";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendLaporanDiterima($chat_id, $jenis_laporan)
    {
        $message = "📄 Laporan anda *$jenis_laporan* Telah diterima \nTTD\nBPKAD SERDANG BEDAGAI";

        return $this->sendMessage($chat_id, $message);
    }

    public function sendLaporanDitolak($chat_id, $jenis_laporan, $alasan)
    {
        $message = "📄 Laporan anda *$jenis_laporan* Telah ditolak dengan alasan:\n *$alasan* \n \nTTD\nBPKAD SERDANG BEDAGAI";

        return $this->sendMessage($chat_id, $message);
    }
}
