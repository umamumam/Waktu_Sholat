<?php

namespace App\Http\Controllers;

use App\Services\MyQuranService;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IslamicApiController extends Controller
{
    protected MyQuranService $myQuranService;

    public function __construct(MyQuranService $myQuranService)
    {
        $this->myQuranService = $myQuranService;
    }

    /**
     * Get initial data for the landing page.
     */
    public function getInitialData(Request $request)
    {
        $cityId = $request->input('city_id') ?: Setting::get('default_city_id', '1638');
        $now = now();
        $year = $now->year;
        $month = str_pad($now->month, 2, '0', STR_PAD_LEFT);
        $today = $now->format('Y-m-d');

        $data = [
            'prayer_times' => null,
            'monthly_schedule' => [],
            'calendar' => null,
            'holiday' => 'Hari Biasa',
            'surahs' => []
        ];

        try {
            // 1. Prayer Times
            try {
                $prayerSchedule = $this->myQuranService->getTodaySchedule($cityId);
                $jadwal = $prayerSchedule['data']['jadwal'] ?? [];
                // In v3 today endpoint, jadwal is an object with today's date as key
                $data['prayer_times'] = !empty($jadwal) ? reset($jadwal) : null;
                
                // Add city info
                $data['city'] = [
                    'id' => $cityId,
                    'lokasi' => $prayerSchedule['data']['kabko'] ?? Setting::get('default_city', 'Pati'),
                    'provinsi' => $prayerSchedule['data']['prov'] ?? ''
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to fetch today schedule: ' . $e->getMessage());
                $data['city'] = [
                    'id' => $cityId,
                    'lokasi' => Setting::get('default_city', 'Pati')
                ];
            }

            // 2. Monthly Schedule
            try {
                $monthlySchedule = $this->myQuranService->getMonthlySchedule($cityId, $year, $month);
                $allDays = $monthlySchedule['data']['jadwal'] ?? [];
                $fullMonthData = [];

                foreach ($allDays as $dateKey => $day) {
                    // Add date key to the object for frontend compatibility
                    $day['date'] = $dateKey;
                    $fullMonthData[] = $day;
                }
                $data['monthly_schedule'] = $fullMonthData;
            } catch (\Exception $e) {
                Log::warning('Failed to fetch monthly schedule: ' . $e->getMessage());
            }

            // 3. Calendar
            try {
                $calendar = $this->myQuranService->getTodayCalendar();
                $data['calendar'] = $calendar['data'] ?? null;
            } catch (\Exception $e) {
                Log::warning('Failed to fetch calendar: ' . $e->getMessage());
            }

            // 4. Holiday
            try {
                $holiday = $this->myQuranService->getTodayHoliday();
                $data['holiday'] = $holiday['message'] ?? 'Hari Biasa';
            } catch (\Exception $e) {
                Log::warning('Failed to fetch holiday: ' . $e->getMessage());
            }

            // 5. Surahs
            try {
                $surahs = $this->myQuranService->getAllSurahs();
                $data['surahs'] = $surahs['data'] ?? [];
            } catch (\Exception $e) {
                Log::warning('Failed to fetch surahs: ' . $e->getMessage());
            }

            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Critical failure in getInitialData: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data utama.'
            ], 500);
        }
    }

    /**
     * Search for cities.
     */
    public function searchCities(Request $request)
    {
        $keyword = $request->get('keyword');
        if (empty($keyword)) {
            return response()->json(['status' => false, 'message' => 'Keyword is required.'], 400);
        }

        return response()->json($this->myQuranService->searchCities($keyword));
    }

    /**
     * Get surah detail.
     */
    public function getSurahDetail($number)
    {
        return response()->json($this->myQuranService->getSurahDetail($number));
    }

    /**
     * Geocode coordinates to find city.
     */
    public function geocode(Request $request)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if (!$lat || !$lon) {
            return response()->json(['status' => false, 'message' => 'Latitude and longitude are required.'], 400);
        }

        return response()->json($this->myQuranService->geocode($lat, $lon));
    }

    /**
     * Get Qibla direction.
     */
    public function getQibla(Request $request)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if (!$lat || !$lon) {
            return response()->json(['status' => false, 'message' => 'Latitude and longitude are required.'], 400);
        }

        return response()->json($this->myQuranService->getQibla($lat, $lon));
    }

    /**
     * Get a random hadis.
     */
    public function getRandomHadis()
    {
        return response()->json($this->myQuranService->getRandomHadis());
    }

    /**
     * Get prayer schedule for specific city and date.
     */
    public function getSchedule(Request $request)
    {
        $cityId = $request->get('city_id');
        $date = $request->get('date', now()->format('Y-m-d'));

        if (!$cityId) {
            return response()->json(['status' => false, 'message' => 'City ID is required.'], 400);
        }

        return response()->json($this->myQuranService->getPrayerSchedule($cityId, $date));
    }
}
