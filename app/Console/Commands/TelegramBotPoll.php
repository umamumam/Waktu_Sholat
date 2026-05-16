<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\MyQuranService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TelegramBotPoll extends Command
{
    protected $signature = 'app:telegram-bot-poll';
    protected $description = 'Poll Telegram for updates and respond';

    public function handle(MyQuranService $apiService, TelegramService $telegramService)
    {
        $token = Setting::get('telegram_bot_token', config('services.telegram.bot_token'));
        if (!$token) {
            $this->error("Telegram token not set. Please set 'telegram_bot_token' in settings table or 'TELEGRAM_BOT_TOKEN' in .env");
            return;
        }

        $offset = Cache::get('telegram_last_update_id', 0);
        $url = "https://api.telegram.org/bot{$token}/getUpdates";

        $this->info("Starting polling...");

        $mainMenu = [
            'keyboard' => [
                [['text' => '📅 Jadwal Sholat'], ['text' => '📍 Cek Lokasi']],
                [['text' => '📖 Al-Quran'], ['text' => '🎲 Ayat Acak']],
                [['text' => '🔄 Ubah Lokasi']]
            ],
            'resize_keyboard' => true
        ];

        while (true) {
            try {
                // --- Notification Logic ---
                $cityId = Setting::get('default_city_id', config('services.telegram.default_city_id', '1638'));
                $targetChatId = Setting::get('telegram_chat_id', config('services.telegram.chat_id', '1138408697'));
                $now = now();
                $todayKey = $now->format('Y-m-d');
                
                // Cache prayer times for notification check to avoid constant API calls
                $prayerData = Cache::remember("notif_prayer_times_{$cityId}_{$todayKey}", 3600, function () use ($apiService, $cityId) {
                    return $apiService->getTodaySchedule($cityId);
                });

                if ($prayerData && isset($prayerData['data']['jadwal'])) {
                    // MyQuran v3 today schedule is an object with date as key
                    $jadwalObj = $prayerData['data']['jadwal'];
                    $jadwal = reset($jadwalObj); // Get the first (and only) entry
                    
                    if ($jadwal) {
                        $prayers = ['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya'];
                        
                        foreach ($prayers as $p) {
                            if (!isset($jadwal[$p])) continue;
                            
                            $timeStr = $jadwal[$p];
                            $prayerTime = Carbon::createFromFormat('H:i', $timeStr);
                            $diffInMinutes = $now->diffInMinutes($prayerTime, false);

                            // Check if it's 15 minutes before and we haven't sent a notification for this prayer today
                            $notifKey = "notif_sent_{$p}_{$todayKey}";
                            if ($diffInMinutes <= 15 && $diffInMinutes > 0 && !Cache::has($notifKey)) {
                                $cityName = Setting::get('default_city', config('services.telegram.default_city', 'Pati'));
                                $msg = "🔔 <b>Pengingat Azan</b>\n\nWaktu <b>" . strtoupper($p) . "</b> untuk wilayah <b>{$cityName}</b> akan tiba dalam 15 menit ({$timeStr}).\n\nMari bersiap-siap untuk menunaikan sholat.";
                                $telegramService->sendMessage($msg, $targetChatId, $mainMenu);
                                Cache::put($notifKey, true, 3600);
                            }

                            // Check if it's EXACTLY prayer time
                            $azanNotifKey = "azan_notif_sent_{$p}_{$todayKey}";
                            if ($now->format('H:i') === $timeStr && !Cache::has($azanNotifKey)) {
                                $cityName = Setting::get('default_city', config('services.telegram.default_city', 'Pati'));
                                $msg = "🕌 <b>Waktu Azan Telah Tiba!</b>\n\nSekarang sudah masuk waktu sholat <b>" . strtoupper($p) . "</b> untuk wilayah <b>{$cityName}</b> dan sekitarnya ({$timeStr}).\n\nSelamat menunaikan ibadah sholat.";
                                $telegramService->sendMessage($msg, $targetChatId, $mainMenu);
                                Cache::put($azanNotifKey, true, 3600);
                            }
                        }
                    }
                }
                // --- End Notification Logic ---

                $response = Http::withOptions([
                    'verify' => false,
                ])->get($url, [
                    'offset' => $offset + 1,
                ]);

                if ($response->successful()) {
                    $updates = $response->json()['result'];
                    foreach ($updates as $update) {
                        if (!isset($update['message'])) continue;
                        
                        $offset = $update['update_id'];
                        Cache::put('telegram_last_update_id', $offset);

                        $chatId = $update['message']['chat']['id'];
                        $text = $update['message']['text'] ?? '';
                        $userStateKey = "tg_user_state_{$chatId}";
                        $userState = Cache::get($userStateKey);

                        if ($text === '/start') {
                            Cache::forget($userStateKey);
                            $msg = "Assalamu'alaikum! 🙏\nSelamat datang di Bot <b>WaktuSholatku</b>.\n\nSaya akan memberikan jadwal sholat dan pengingat 15 menit sebelum azan secara otomatis.\n\nSilakan pilih menu:";
                            $telegramService->sendMessage($msg, $chatId, $mainMenu);
                        } elseif ($text === '📅 Jadwal Sholat' || $text === '/jadwal') {
                            $cityId = Setting::get('default_city_id', config('services.telegram.default_city_id', '1638'));
                            $city = Setting::get('default_city', config('services.telegram.default_city', 'Pati'));
                            $data = $apiService->getTodaySchedule($cityId);

                            if ($data && isset($data['data']['jadwal'])) {
                                $jadwalObj = $data['data']['jadwal'];
                                $j = reset($jadwalObj);
                                $msg = "📅 <b>Jadwal Sholat {$city}</b>\n";
                                $msg .= "Tanggal: {$j['tanggal']}\n\n";
                                $msg .= "Imsak: <code>{$j['imsak']}</code>\n";
                                $msg .= "Subuh: <code>{$j['subuh']}</code>\n";
                                $msg .= "Dzuhur: <code>{$j['dzuhur']}</code>\n";
                                $msg .= "Ashar: <code>{$j['ashar']}</code>\n";
                                $msg .= "Maghrib: <code>{$j['maghrib']}</code>\n";
                                $msg .= "Isya: <code>{$j['isya']}</code>";
                                
                                $telegramService->sendMessage($msg, $chatId, $mainMenu);
                            } else {
                                $telegramService->sendMessage("Maaf, gagal mengambil jadwal sholat untuk {$city}.", $chatId, $mainMenu);
                            }
                        } elseif ($text === '📍 Cek Lokasi') {
                            $city = Setting::get('default_city', config('services.telegram.default_city', 'Pati'));
                            $telegramService->sendMessage("📍 Lokasi aktif saat ini: <b>{$city}</b>\nSemua jadwal dan notifikasi mengacu pada lokasi ini.", $chatId, $mainMenu);
                        } elseif ($text === '📖 Al-Quran') {
                            $surahs = $apiService->getAllSurahs();
                            $msg = "📖 <b>Daftar Surat Al-Quran</b>\n\n";
                            if (isset($surahs['data'])) {
                                foreach (array_slice($surahs['data'], 0, 10) as $s) {
                                    $msg .= "{$s['number']}. {$s['name_latin']} ({$s['name']})\n";
                                }
                                $msg .= "\n<i>Kunjungi website untuk membaca surat lainnya secara lengkap!</i>";
                            } else {
                                $msg = "Maaf, gagal mengambil daftar surat.";
                            }
                            $telegramService->sendMessage($msg, $chatId, $mainMenu);
                        } elseif ($text === '🎲 Ayat Acak') {
                            $res = $apiService->getRandomAyah();
                            if ($res['status'] && isset($res['data'])) {
                                $ayah = $res['data'];
                                $msg = "🎲 <b>Ayat Acak Hari Ini</b>\n\n";
                                $msg .= "<i>\"" . ($ayah['text']['id'] ?? '...') . "\"</i>\n\n";
                                $msg .= "— QS. {$ayah['surah']['name_latin']}: {$ayah['number']}";
                                $telegramService->sendMessage($msg, $chatId, $mainMenu);
                            } else {
                                $telegramService->sendMessage("Maaf, gagal mengambil ayat acak.", $chatId, $mainMenu);
                            }
                        } elseif ($text === '🔄 Ubah Lokasi') {
                            Cache::put($userStateKey, 'wait_for_city', 300);
                            $telegramService->sendMessage("🔄 <b>Ubah Lokasi</b>\n\nSilakan ketik nama kota yang baru (contoh: Jakarta, Bandung, atau Pati):", $chatId, ['force_reply' => true]);
                        } elseif ($userState === 'wait_for_city') {
                            $searchRes = $apiService->searchCitiesPost($text);
                            if ($searchRes['status'] && !empty($searchRes['data'])) {
                                $cities = $searchRes['data'];
                                if (count($cities) > 1) {
                                    $msg = "🔍 <b>Ditemukan beberapa lokasi</b> untuk \"{$text}\".\n\nSilakan klik salah satu tombol di bawah untuk memilih:";
                                    $keyboard = [];
                                    foreach (array_slice($cities, 0, 10) as $c) {
                                        $keyboard[] = [['text' => "📍 " . $c['lokasi']]];
                                    }
                                    Cache::put($userStateKey, 'wait_for_city_selection', 300);
                                    $telegramService->sendMessage($msg, $chatId, [
                                        'keyboard' => $keyboard,
                                        'resize_keyboard' => true,
                                        'one_time_keyboard' => true
                                    ]);
                                } else {
                                    $firstCity = $cities[0];
                                    Setting::set('default_city', $firstCity['lokasi']);
                                    Setting::set('default_city_id', $firstCity['id']);
                                    Cache::forget($userStateKey);
                                    Cache::forget("notif_prayer_times_{$firstCity['id']}_" . now()->format('Y-m-d'));
                                    $telegramService->sendMessage("✅ Lokasi berhasil diubah ke: <b>{$firstCity['lokasi']}</b>\n\nWebsite dan Notifikasi sekarang akan menggunakan lokasi ini.", $chatId, $mainMenu);
                                }
                            } else {
                                $telegramService->sendMessage("❌ Kota '{$text}' tidak ditemukan. Silakan coba nama kota lain:", $chatId, ['force_reply' => true]);
                            }
                        } elseif ($userState === 'wait_for_city_selection') {
                            $cityName = str_replace('📍 ', '', $text);
                            $searchRes = $apiService->searchCitiesPost($cityName);
                            if ($searchRes['status'] && !empty($searchRes['data'])) {
                                // Try to find exact match from the button text
                                $selected = null;
                                foreach ($searchRes['data'] as $c) {
                                    if ($c['lokasi'] === $cityName) {
                                        $selected = $c;
                                        break;
                                    }
                                }
                                $selected = $selected ?: $searchRes['data'][0];
                                
                                Setting::set('default_city', $selected['lokasi']);
                                Setting::set('default_city_id', $selected['id']);
                                Cache::forget($userStateKey);
                                Cache::forget("notif_prayer_times_{$selected['id']}_" . now()->format('Y-m-d'));
                                $telegramService->sendMessage("✅ Lokasi berhasil diubah ke: <b>{$selected['lokasi']}</b>\n\nWebsite dan Notifikasi sekarang akan menggunakan lokasi ini.", $chatId, $mainMenu);
                            } else {
                                Cache::forget($userStateKey);
                                $telegramService->sendMessage("Terjadi kesalahan saat memilih lokasi. Silakan coba lagi dari menu Ubah Lokasi.", $chatId, $mainMenu);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("Polling error: " . $e->getMessage());
                sleep(10);
            }
            sleep(2);
        }
    }
}
