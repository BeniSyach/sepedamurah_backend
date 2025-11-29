<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class TelegramBotController extends Controller
{
    private $botToken;
    private $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot" . $this->botToken . "/";
    }

    public function webhook(Request $request)
    {
        $update = $request->all();
    
        if (!isset($update["message"])) {
            return response()->json(['status' => 'no message']);
        }
    
        $message = $update["message"];
        $chatId  = $message["chat"]["id"];
        $text    = $message["text"] ?? "";
    
        // Deteksi nomor HP format 628xxxx
        $phone = $this->extractPhoneNumber($text);
    
        if ($phone) {
            // Cari user yang not deleted
            $user = User::withoutGlobalScopes()->where('no_hp', $phone)->first();
    
            if ($user) {
                // Simpan chat_id
                $user->chat_id = $chatId;
                $user->deleted = 0; // opsional kalau ingin otomatis aktif lagi
                $user->save();
    
                $reply = "Nomor berhasil disimpan!";
            } else {
                $reply = "Nomor tidak ditemukan di database.";
            }
        } else {
            $reply = "Kirim nomor HP diawali 628...";
        }
    
        $this->sendMessage($chatId, $reply);
    
        return response()->json(['status' => 'ok']);
    }
    

    // Kirim pesan
    private function sendMessage($chatId, $text)
    {
        file_get_contents($this->apiUrl . "sendMessage?chat_id={$chatId}&text=" . urlencode($text));
    }

    // Deteksi nomor HP
    private function extractPhoneNumber($text)
    {
        if (preg_match("/^628\d{8,12}$/", $text, $match)) {
            return $match[0];
        }
        return null;
    }
}
