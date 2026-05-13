<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MyQuranService
{
    protected string $baseUrl = 'https://api.myquran.com/v3';

    /**
     * Get all cities/kabkota.
     */
    public function getAllCities()
    {
        return Cache::remember('all_cities', 86400, function () {
            $response = Http::get("{$this->baseUrl}/sholat/kabkota/semua");
            return $response->json();
        });
    }

    /**
     * Search cities by keyword (GET).
     */
    public function searchCities(string $keyword)
    {
        $response = Http::get("{$this->baseUrl}/sholat/kabkota/cari/{$keyword}");
        return $response->json();
    }

    /**
     * Search cities by keyword (POST).
     */
    public function searchCitiesPost(string $keyword)
    {
        $response = Http::post("{$this->baseUrl}/sholat/kabkota/cari", [
            'keyword' => $keyword
        ]);
        return $response->json();
    }

    /**
     * Get prayer schedule for a city and date.
     */
    public function getPrayerSchedule(string $cityId, string $date)
    {
        $response = Http::get("{$this->baseUrl}/sholat/jadwal/{$cityId}/{$date}");
        return $response->json();
    }

    /**
     * Get today's prayer schedule with timezone.
     */
    public function getTodaySchedule(string $cityId, string $tz = 'Asia/Jakarta')
    {
        $response = Http::get("{$this->baseUrl}/sholat/jadwal/{$cityId}/today", [
            'tz' => $tz
        ]);
        return $response->json();
    }

    /**
     * Get monthly prayer schedule.
     */
    public function getMonthlySchedule(string $cityId, string $year, string $month)
    {
        $response = Http::get("{$this->baseUrl}/sholat/jadwal/{$cityId}/{$year}-{$month}");
        return $response->json();
    }

    /**
     * Get today's Hijri date and calendar info.
     */
    public function getTodayCalendar()
    {
        return Cache::remember('today_calendar', 3600, function () {
            $response = Http::get("{$this->baseUrl}/cal/today");
            return $response->json();
        });
    }

    /**
     * Get today's holiday status.
     */
    public function getTodayHoliday()
    {
        return Cache::remember('today_holiday', 3600, function () {
            $response = Http::get("{$this->baseUrl}/cal/holidays/today");
            return $response->json();
        });
    }

    /**
     * Get list of all surahs.
     */
    public function getAllSurahs()
    {
        return Cache::remember('all_surahs', 86400, function () {
            $response = Http::get("{$this->baseUrl}/quran");
            return $response->json();
        });
    }

    /**
     * Get surah detail.
     */
    public function getSurahDetail(int $number)
    {
        return Cache::remember("surah_{$number}", 86400, function () use ($number) {
            $response = Http::get("{$this->baseUrl}/quran/{$number}");
            return $response->json();
        });
    }

    /**
     * Get Qibla direction for coordinates.
     */
    public function getQibla(string $lat, string $lon)
    {
        $response = Http::get("{$this->baseUrl}/qibla/{$lat},{$lon}");
        return $response->json();
    }

    /**
     * Get a random hadis.
     */
    public function getRandomHadis()
    {
        return Cache::remember('random_hadis', 300, function () {
            $response = Http::get("{$this->baseUrl}/hadis/enc/random");
            return $response->json();
        });
    }

    /**
     * Geocode coordinates.
     */
    public function geocode(string $lat, string $lon)
    {
        $response = Http::post("{$this->baseUrl}/tools/geocode", [
            'query' => "{$lat},{$lon}"
        ]);
        return $response->json();
    }

    /**
     * Get a random ayah.
     */
    public function getRandomAyah()
    {
        $response = Http::get("{$this->baseUrl}/quran/random");
        return $response->json();
    }
}
