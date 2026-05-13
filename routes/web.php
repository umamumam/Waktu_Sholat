<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

use App\Http\Controllers\IslamicApiController;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::inertia('/sholat', 'Sholat')->name('sholat');
Route::inertia('/quran', 'Quran')->name('quran');
Route::inertia('/kiblat', 'Kiblat')->name('kiblat');

Route::prefix('api/islamic')->group(function () {
    Route::get('/initial', [IslamicApiController::class, 'getInitialData']);
    Route::get('/search-city', [IslamicApiController::class, 'searchCities']);
    Route::get('/surah/{number}', [IslamicApiController::class, 'getSurahDetail']);
    Route::get('/hadis-random', [IslamicApiController::class, 'getRandomHadis']);
    Route::get('/schedule', [IslamicApiController::class, 'getSchedule']);
    Route::get('/qibla', [IslamicApiController::class, 'getQibla']);
    Route::post('/geocode', [IslamicApiController::class, 'geocode']);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
