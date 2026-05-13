<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Setting;

class TelegramService
{
    protected $token;
    protected $chatId;

    public function __construct()
    {
        $this->token = Setting::get('telegram_bot_token', config('services.telegram.bot_token'));
        $this->chatId = Setting::get('telegram_chat_id', config('services.telegram.chat_id'));
    }

    public function sendMessage($message, $chatId = null, $replyMarkup = null)
    {
        $chatId = $chatId ?: $this->chatId;
        if (!$this->token || !$chatId) return false;

        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        
        $params = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        $response = Http::withOptions([
            'verify' => false,
        ])->post($url, $params);

        return $response->successful();
    }
}
