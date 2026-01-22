<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TTE_BSRE
{
    /**
     * Kirim PDF ke API BSrE untuk ditandatangani
     */
    public function signPdf($filePath, $nik, $passphrase, $tampilan, $namaFile, $dokumenId)
    {
        // URL API
        $url = "http://10.114.253.33/api/sign/pdf";

        try {
            if (!file_exists($filePath) || is_dir($filePath)) {
                throw new \Exception("File PDF tidak valid: $filePath");
            }
            // Request ke BSRE
            $response = Http::withBasicAuth('esign','qwerty')
                ->timeout(60)
                ->attach(
                    'file',
                    file_get_contents($filePath),
                    basename($filePath)
                )->post($url, [
                    'nik'        => $nik,
                    'passphrase' => $passphrase,
                    'tampilan'   => $tampilan,
                    'location'   => 'Kabupaten Serdang Bedagai',
                    'reason'     => 'Dokumen ini Telah ditandatangani secara elektronik',
                ]);

            // Jika error koneksi
            if ($response->failed()) {
                $raw = $response->body();

                // Coba decode
                $decoded = json_decode($raw, true);
                return [
                    'status' => 'error',
                    'message' => 'Gagal terhubung ke server BSRE',
                    'detail'      => $decoded ?? $raw,   // Jika gagal decode → pakai raw
                    'http_status' => $response->status(),
                ];
            }

            $data = json_decode($response->body(), true);

            // Jika API BSRE mengembalikan error JSON
            if (isset($data['error'])) {
                return [
                    'status' => 'error',
                    'message' => $data['error'],
                    'status_code' => $data['status_code'] ?? null,
                ];
            }

            // Nama file hasil TTE
            $signedFileName = "signed-" . substr($namaFile, 0, 10) . "-{$dokumenId}.pdf";
            $savePath = "pdf_tte/" . $signedFileName;

            // Simpan PDF hasil TTE ke storage/app/pdf_tte/
            Storage::disk('public')->put($savePath, $response->body());

            return [
                'status' => 'success',
                'message' => 'Berhasil TTE dan file tersimpan',
                'file_path' => $savePath,
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => "Exception: " . $e->getMessage()
            ];
        }
    }
}
