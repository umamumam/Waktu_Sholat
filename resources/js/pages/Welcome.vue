<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import {
    Moon,
    Sun,
    Clock,
    MapPin,
    Search,
    BookOpen,
    ChevronRight,
    Volume2,
    Play,
    Pause,
    Table,
    Navigation,
    Calendar,
    X as CloseIcon,
} from 'lucide-vue-next';

interface PrayerTimes {
    imsak: string;
    subuh: string;
    terbit: string;
    dhuha: string;
    dzuhur: string;
    ashar: string;
    maghrib: string;
    isya: string;
    tanggal: string;
}

interface Surah {
    number: number;
    name: string;
    name_latin: string;
    number_of_ayahs: number;
    translation: string;
    revelation: string;
}

const prayerTimes = ref<PrayerTimes | null>(null);
const weeklySchedule = ref<any[]>([]);
const city = ref({ id: '1301', lokasi: 'KOTA JAKARTA' });
const hijrDate = ref({ day: '', month: '', year: '' });
const surahs = ref<Surah[]>([]);
const selectedSurah = ref<any>(null);
const randomHadis = ref<any>(null);
const isLoading = ref(true);
const nextPrayer = ref<{ name: string; time: string; diff: string }>({
    name: '',
    time: '',
    diff: '',
});
const currentTime = ref(new Date());
const showSearch = ref(false);
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const holidayStatus = ref('Memuat...');
const currentAudioUrl = computed(() => selectedSurah.value?.audio_url || '');
const showReadModal = ref(false);
const isDetailLoading = ref(false);
let timerInterval: any = null;

const prayerList = [
    { key: 'imsak', name: 'Imsak', icon: Moon },
    { key: 'subuh', name: 'Subuh', icon: Sun },
    { key: 'terbit', name: 'Terbit', icon: Sun },
    { key: 'dzuhur', name: 'Dzuhur', icon: Sun },
    { key: 'ashar', name: 'Ashar', icon: Clock },
    { key: 'maghrib', name: 'Maghrib', icon: Moon },
    { key: 'isya', name: 'Isya', icon: Moon },
];

const fetchInitialData = async () => {
    try {
        const res = await fetch(
            `/api/islamic/initial?city_id=${city.value.id}`,
        );
        const json = await res.json();
        if (json.status) {
            const data = json.data;
            prayerTimes.value = data.prayer_times;
            const today = new Date().toISOString().split('T')[0];
            weeklySchedule.value = (data.monthly_schedule || [])
                .filter((d: any) => d.date >= today)
                .slice(0, 7);
            hijrDate.value = {
                day: data.calendar.hijr.day,
                month: data.calendar.hijr.monthName,
                year: data.calendar.hijr.year,
            };
            holidayStatus.value = data.holiday;
            surahs.value = data.surahs;

            if (surahs.value.length > 0) {
                fetchSurahDetail(1);
            }

            calculateNextPrayer();
        }
    } catch (e) {
        console.error('Failed to fetch initial data', e);
    }
};

const fetchSurahDetail = async (id: number) => {
    isDetailLoading.value = true;
    try {
        const res = await fetch(`/api/islamic/surah/${id}`);
        const json = await res.json();
        if (json.status) {
            selectedSurah.value = json.data;
        }
    } catch (e) {
        console.error('Failed to fetch surah detail', e);
    } finally {
        isDetailLoading.value = false;
    }
};

const openReader = () => {
    if (selectedSurah.value) {
        showReadModal.value = true;
    }
};

const fetchRandomHadis = async () => {
    try {
        const res = await fetch('/api/islamic/hadis-random');
        const json = await res.json();
        if (json.status) {
            randomHadis.value = json.data;
        }
    } catch (e) {
        console.error('Failed to fetch random hadis', e);
    }
};

const detectLocation = () => {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(async (position) => {
            const { latitude, longitude } = position.coords;
            try {
                const res = await fetch(`/api/islamic/geocode`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            ) as any
                        )?.content,
                    },
                    body: JSON.stringify({ lat: latitude, lon: longitude }),
                });
                const json = await res.json();
                if (json.status && json.data) {
                    const addr = json.data.address;
                    // Prioritize city or county (Regency/Kabupaten) over smaller divisions
                    const cityName =
                        addr.city ||
                        addr.county ||
                        addr.city_district ||
                        addr.town ||
                        addr.suburb;

                    if (cityName) {
                        // Clean up the city name (remove "Kabupaten" or "Kota" prefix if any)
                        const cleanCityName = cityName.replace(/Kabupaten|Kota|Kab\.|Kodya/gi, '').trim();
                        const searchRes = await fetch(
                            `/api/islamic/search-city?keyword=${cleanCityName}`,
                        );
                        const searchJson = await searchRes.json();
                        if (searchJson.status && searchJson.data.length > 0) {
                            // Find the best match
                            const foundCity = searchJson.data[0];
                            city.value = foundCity;
                            localStorage.setItem('selected_city', JSON.stringify(foundCity));
                            fetchInitialData();
                        }
                    }
                }
            } catch (e) {
                console.error('Location detection failed', e);
            }
        });
    }
};

const handleSearch = async () => {
    if (searchQuery.value.length < 3) {
        searchResults.value = [];
        return;
    }
    try {
        const res = await fetch(
            `/api/islamic/search-city?keyword=${searchQuery.value}`,
        );
        const json = await res.json();
        if (json.status) {
            searchResults.value = json.data;
        }
    } catch (e) {
        console.error('Search failed', e);
    }
};

const selectCity = (c: any) => {
    city.value = c;
    localStorage.setItem('selected_city', JSON.stringify(c));
    showSearch.value = false;
    searchQuery.value = '';
    searchResults.value = [];
    fetchInitialData();
};

const calculateNextPrayer = () => {
    if (!prayerTimes.value) return;

    const now = new Date();
    const times = [
        { name: 'Imsak', time: prayerTimes.value.imsak },
        { name: 'Subuh', time: prayerTimes.value.subuh },
        { name: 'Dzuhur', time: prayerTimes.value.dzuhur },
        { name: 'Ashar', time: prayerTimes.value.ashar },
        { name: 'Maghrib', time: prayerTimes.value.maghrib },
        { name: 'Isya', time: prayerTimes.value.isya },
    ];

    let found = false;
    for (const pt of times) {
        const [hours, minutes] = pt.time.split(':');
        const ptDate = new Date(
            now.getFullYear(),
            now.getMonth(),
            now.getDate(),
            parseInt(hours),
            parseInt(minutes),
            0,
        );

        if (ptDate > now) {
            const diffMs = ptDate.getTime() - now.getTime();
            const h = Math.floor(diffMs / 3600000);
            const m = Math.floor((diffMs % 3600000) / 60000);
            const s = Math.floor((diffMs % 60000) / 1000);

            nextPrayer.value = {
                name: pt.name.toUpperCase(),
                time: pt.time,
                diff: `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`,
            };
            found = true;
            break;
        }
    }

    if (!found && prayerTimes.value) {
        // If all prayers today have passed, the next one is Subuh tomorrow
        const subuhParts = prayerTimes.value.subuh.split(':');
        const nextDate = new Date(currentTime.value);
        nextDate.setHours(parseInt(subuhParts[0]), parseInt(subuhParts[1]), 0, 0);
        nextDate.setDate(nextDate.getDate() + 1); // Tomorrow

        const diffMs = nextDate.getTime() - currentTime.value.getTime();
        const h = Math.floor(diffMs / 3600000);
        const m = Math.floor((diffMs % 3600000) / 60000);
        const s = Math.floor((diffMs % 60000) / 1000);

        nextPrayer.value = {
            name: 'SUBUH (BESOK)',
            time: prayerTimes.value.subuh,
            diff: `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`,
        };
    } else if (!found) {
        nextPrayer.value = {
            name: 'SUBUH (BESOK)',
            time: '--:--',
            diff: '--:--:--',
        };
    }
};

const toggleAudio = () => {
    if (!audioRef.value) {
        audioRef.value = new Audio(currentAudioUrl.value);
        audioRef.value.onended = () => (isPlaying.value = false);
    }

    if (isPlaying.value) {
        audioRef.value.pause();
    } else {
        if (audioRef.value.src !== currentAudioUrl.value) {
            audioRef.value.src = currentAudioUrl.value;
        }
        audioRef.value.play();
    }
    isPlaying.value = !isPlaying.value;
};

watch(currentAudioUrl, () => {
    if (audioRef.value) {
        audioRef.value.pause();
        isPlaying.value = false;
        audioRef.value.src = currentAudioUrl.value;
    }
});

onMounted(async () => {
    const savedCity = localStorage.getItem('selected_city');
    if (savedCity) {
        city.value = JSON.parse(savedCity);
    } else {
        detectLocation();
    }

    await Promise.all([fetchInitialData(), fetchRandomHadis()]);
    isLoading.value = false;

    timerInterval = setInterval(() => {
        currentTime.value = new Date();
        calculateNextPrayer();
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const formattedDate = computed(() => {
    return currentTime.value.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const isToday = (dateStr: string) => {
    const today = new Date().toISOString().split('T')[0];
    return dateStr === today;
};

watch(searchQuery, handleSearch);
</script>

<template>
    <PublicLayout>
        <Head title="Jadwal Sholat & Al-Quran Digital" />

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <!-- Hero Section -->
            <div class="mb-12 grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Left: Today's Summary -->
                <div class="lg:col-span-8">
                    <div class="mb-8">
                        <div class="relative flex items-center justify-between">
                            <div
                                class="group mb-2 flex cursor-pointer items-center gap-2 text-emerald-600"
                                @click="showSearch = !showSearch"
                            >
                                <MapPin
                                    class="h-5 w-5 group-hover:animate-bounce"
                                />
                                <span
                                    class="text-sm font-bold tracking-wide uppercase group-hover:underline"
                                    >{{ city.lokasi }}, INDONESIA</span
                                >
                                <Search class="h-4 w-4 opacity-50" />
                            </div>

                            <!-- Search Modal/Dropdown -->
                            <div
                                v-if="showSearch"
                                class="absolute top-full z-20 mt-2 w-full max-w-sm rounded-2xl border border-slate-100 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-800"
                            >
                                <div class="relative mb-4">
                                    <Search
                                        class="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-slate-400"
                                    />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Cari Kota/Kabupaten..."
                                        class="w-full rounded-xl border-none bg-slate-50 py-3 pr-4 pl-10 transition-all focus:ring-2 focus:ring-emerald-500 dark:bg-slate-700"
                                    />
                                </div>
                                <div class="max-h-60 space-y-1 overflow-y-auto">
                                    <button
                                        v-for="res in searchResults"
                                        :key="res.id"
                                        @click="selectCity(res)"
                                        class="w-full rounded-xl px-4 py-3 text-left text-sm font-medium transition-colors hover:bg-emerald-50 dark:hover:bg-emerald-900/20"
                                    >
                                        {{ res.lokasi }}
                                    </button>
                                    <p
                                        v-if="
                                            searchResults.length === 0 &&
                                            searchQuery.length >= 3
                                        "
                                        class="py-4 text-center text-sm text-slate-400"
                                    >
                                        Kota tidak ditemukan
                                    </p>
                                </div>
                            </div>
                        </div>
                        <h1
                            class="mb-2 text-4xl font-extrabold tracking-tight text-slate-900 lg:text-5xl dark:text-white"
                        >
                            Waktu Sholat Hari Ini
                        </h1>
                        <p class="text-lg text-slate-500 dark:text-slate-400">
                            {{ formattedDate }} •
                            <span class="font-medium text-emerald-600 italic"
                                >{{ hijrDate.day }} {{ hijrDate.month }}
                                {{ hijrDate.year }} H</span
                            >
                        </p>
                    </div>

                    <!-- Next Prayer Card -->
                    <div
                        class="group relative overflow-hidden rounded-3xl bg-emerald-700 p-8 text-white shadow-2xl shadow-emerald-200 transition-all lg:p-10 dark:shadow-none"
                    >
                        <!-- Desktop Image Background (Right Side) -->
                        <div
                            class="absolute top-0 right-0 hidden h-full w-1/2 overflow-hidden lg:block"
                        >
                            <img
                                src="https://kubahmigunani.com/wp-content/uploads/2024/09/masjid-istiqlal.jpg"
                                alt="Masjid Istiqlal"
                                class="h-full w-full object-cover object-center opacity-40 mix-blend-overlay transition-transform duration-1000 group-hover:scale-110"
                            />
                            <div
                                class="absolute inset-0 bg-gradient-to-l from-transparent to-emerald-700"
                            ></div>
                        </div>

                        <!-- Mobile Image Background (Full Behind) -->
                        <div class="absolute inset-0 overflow-hidden lg:hidden">
                            <img
                                src="https://kubahmigunani.com/wp-content/uploads/2024/09/masjid-istiqlal.jpg"
                                alt="Masjid Istiqlal"
                                class="h-full w-full object-cover opacity-20 mix-blend-overlay"
                            />
                            <div
                                class="absolute inset-0 bg-emerald-700/60"
                            ></div>
                        </div>

                        <div
                            class="relative z-10 flex h-full flex-col justify-between gap-8 md:flex-row md:items-center"
                        >
                            <div class="max-w-md">
                                <p
                                    class="mb-1 text-xs font-medium tracking-widest text-emerald-100 uppercase"
                                >
                                    Waktu Sholat Berikutnya:
                                </p>
                                <h2
                                    class="mb-4 text-5xl font-black tracking-tighter lg:text-7xl"
                                >
                                    {{ nextPrayer.name }}
                                    <span class="text-emerald-300"
                                        >/ {{ nextPrayer.time }}</span
                                    >
                                </h2>
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/20 px-6 py-3 backdrop-blur-md"
                                    >
                                        <Clock
                                            class="h-6 w-6 text-emerald-100"
                                        />
                                        <div>
                                            <p
                                                class="text-[10px] font-bold text-emerald-200 uppercase"
                                            >
                                                Hitung Mundur
                                            </p>
                                            <p
                                                class="font-mono text-xl font-bold tracking-tighter"
                                            >
                                                {{ nextPrayer.diff }}
                                            </p>
                                        </div>
                                    </div>
                                    <p
                                        class="hidden content-center text-sm leading-tight font-medium text-emerald-50 opacity-90 md:block"
                                    >
                                        Nikmati ketenangan ibadah dengan<br />jadwal
                                        yang akurat setiap waktu.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Hijri Card -->
                <div class="lg:col-span-4">
                    <div class="flex h-full flex-col gap-8">
                        <!-- Date Info -->
                        <div
                            class="group flex-1 rounded-3xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-xl dark:border-slate-700 dark:bg-slate-800"
                        >
                            <div class="mb-8 flex items-center justify-between">
                                <h3
                                    class="flex items-center gap-2 text-xl font-bold text-slate-800 dark:text-white"
                                >
                                    <Calendar
                                        class="h-6 w-6 text-emerald-600"
                                    />
                                    Kalender Hijriah
                                </h3>
                                <button
                                    class="rounded-xl bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-900/30"
                                    @click="detectLocation"
                                >
                                    <Navigation class="h-4 w-4" />
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div
                                    class="flex items-center justify-between rounded-2xl border border-emerald-100 bg-emerald-50 p-5 dark:border-emerald-800/50 dark:bg-emerald-900/20"
                                >
                                    <span
                                        class="text-lg font-bold text-emerald-700 dark:text-emerald-400"
                                        >{{ hijrDate.day }}
                                        {{ hijrDate.month }}</span
                                    >
                                    <span
                                        class="text-xl font-black text-emerald-800 dark:text-emerald-300"
                                        >{{ hijrDate.year }} H</span
                                    >
                                </div>
                                <div
                                    class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 p-5 dark:border-slate-600/30 dark:bg-slate-700/50"
                                >
                                    <span
                                        class="font-semibold text-slate-600 dark:text-slate-400"
                                        >Status Hari</span
                                    >
                                    <span
                                        class="text-lg font-bold text-slate-800 dark:text-white"
                                        >{{ holidayStatus }}</span
                                    >
                                </div>
                                <button
                                    class="mt-4 flex w-full items-center justify-center gap-2 py-2 font-bold text-emerald-600 transition-all hover:gap-4"
                                >
                                    Lihat Selengkapnya
                                    <ChevronRight class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="mb-12 grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Today's Schedule -->
                <div class="lg:col-span-4">
                    <div
                        class="h-full rounded-3xl border border-slate-100 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div
                            class="mb-6 flex items-center justify-between px-2"
                        >
                            <h3 class="text-2xl font-bold">Jadwal Hari Ini</h3>
                            <div
                                class="rounded-lg bg-emerald-100 p-2 dark:bg-emerald-900/30"
                            >
                                <Clock class="h-5 w-5 text-emerald-600" />
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="prayer in prayerList"
                                :key="prayer.key"
                                :class="[
                                    'group flex cursor-pointer items-center justify-between rounded-2xl border border-transparent p-4 transition-all',
                                    nextPrayer.name.toLowerCase() === prayer.key
                                        ? 'scale-[1.02] border-emerald-500 bg-emerald-600 text-white shadow-lg shadow-emerald-200'
                                        : 'hover:border-slate-200 hover:bg-slate-50 dark:hover:border-slate-600 dark:hover:bg-slate-700',
                                ]"
                            >
                                <div class="flex items-center gap-4">
                                    <div
                                        :class="[
                                            'flex h-10 w-10 items-center justify-center rounded-xl',
                                            nextPrayer.name.toLowerCase() ===
                                            prayer.key
                                                ? 'bg-white/20'
                                                : 'bg-slate-100 group-hover:bg-emerald-100 dark:bg-slate-700 dark:group-hover:bg-emerald-900/30',
                                        ]"
                                    >
                                        <component
                                            :is="prayer.icon"
                                            :class="[
                                                'h-5 w-5',
                                                nextPrayer.name.toLowerCase() ===
                                                prayer.key
                                                    ? 'text-white'
                                                    : 'text-slate-500 group-hover:text-emerald-600 dark:text-slate-400',
                                            ]"
                                        />
                                    </div>
                                    <span class="text-lg font-bold">{{
                                        prayer.name
                                    }}</span>
                                </div>
                                <span
                                    class="font-mono text-xl font-bold tracking-tight"
                                    >{{
                                        prayerTimes
                                            ? (prayerTimes as any)[prayer.key]
                                            : '--:--'
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Table -->
                <div class="lg:col-span-8">
                    <div
                        class="h-full rounded-3xl border border-slate-100 bg-white p-6 shadow-sm lg:p-8 dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div class="mb-8 flex items-center justify-between">
                            <div>
                                <h3 class="mb-1 text-2xl font-bold">
                                    Jadwal Mingguan
                                </h3>
                                <p class="text-sm text-slate-400">
                                    Estimasi waktu sholat 7 hari kedepan
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    class="rounded-xl border border-slate-200 p-2 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700"
                                >
                                    <Table class="h-5 w-5 text-slate-500" />
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr
                                        class="border-b border-slate-100 text-xs font-bold tracking-wider text-slate-400 uppercase dark:border-slate-700"
                                    >
                                        <th class="pb-4 font-medium">
                                            Tanggal
                                        </th>
                                        <th class="pb-4 font-medium">Subuh</th>
                                        <th class="pb-4 font-medium">Dzuhur</th>
                                        <th class="pb-4 font-medium">Ashar</th>
                                        <th
                                            class="pb-4 font-medium text-emerald-600"
                                        >
                                            Maghrib
                                        </th>
                                        <th class="pb-4 font-medium">Isya</th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-50 dark:divide-slate-700/50"
                                >
                                    <tr
                                        v-for="(day, index) in weeklySchedule"
                                        :key="index"
                                        class="group transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-700/30"
                                        :class="{ 'bg-emerald-50/80 dark:bg-emerald-900/30 font-bold': isToday(day.date) }"
                                    >
                                        <td class="py-4">
                                            <div class="flex flex-col">
                                                <span
                                                    class="font-bold text-slate-700 dark:text-slate-200"
                                                    >{{
                                                        day.tanggal.split(
                                                            ',',
                                                        )[0]
                                                    }}</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400"
                                                    >{{
                                                        day.tanggal.split(
                                                            ',',
                                                        )[1]
                                                    }}</span
                                                >
                                            </div>
                                        </td>
                                        <td
                                            class="py-4 font-mono font-bold text-slate-600 dark:text-slate-300"
                                        >
                                            {{ day.subuh }}
                                        </td>
                                        <td
                                            class="py-4 font-mono font-bold text-slate-600 dark:text-slate-300"
                                        >
                                            {{ day.dzuhur }}
                                        </td>
                                        <td
                                            class="py-4 font-mono font-bold text-slate-600 dark:text-slate-300"
                                        >
                                            {{ day.ashar }}
                                        </td>
                                        <td
                                            class="py-4 font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                        >
                                            {{ day.maghrib }}
                                        </td>
                                        <td
                                            class="py-4 font-mono font-bold text-slate-600 dark:text-slate-300"
                                        >
                                            {{ day.isya }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Al-Quran Full Row -->
            <div class="grid grid-cols-1 gap-8">
                <!-- Quran Card -->
                <div
                    class="group relative flex min-h-[500px] flex-col overflow-hidden rounded-3xl bg-[#1E293B] p-8 text-white shadow-2xl transition-all hover:shadow-emerald-900/20 lg:p-12"
                >
                    <div class="relative z-10 flex h-full flex-col">
                        <div class="mb-8 flex items-start justify-between">
                            <div>
                                <div
                                    class="mb-2 flex items-center gap-2 text-emerald-400"
                                >
                                    <BookOpen class="h-6 w-6" />
                                    <span
                                        class="text-sm font-bold tracking-widest uppercase"
                                        >Digital Al-Qur'an</span
                                    >
                                </div>
                                <h3 class="text-4xl font-black tracking-tight">
                                    {{
                                        selectedSurah
                                            ? selectedSurah.name_latin
                                            : 'Loading...'
                                    }}
                                </h3>
                                <p class="font-medium text-slate-400">
                                    {{
                                        selectedSurah
                                            ? `${selectedSurah.translation} • ${selectedSurah.number_of_ayahs} Ayat`
                                            : ''
                                    }}
                                </p>
                            </div>
                            <div class="flex gap-3">
                                <button
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 transition-all hover:bg-emerald-600"
                                >
                                    <Search class="h-6 w-6" />
                                </button>
                            </div>
                        </div>

                        <div
                            class="flex flex-grow flex-col justify-center py-12 text-center"
                        >
                            <h2
                                class="font-arabic dir-rtl mb-8 text-6xl leading-relaxed drop-shadow-2xl lg:text-8xl"
                                style="font-family: 'Amiri', serif"
                            >
                                {{ selectedSurah ? selectedSurah.name : '...' }}
                            </h2>
                            <p
                                class="mx-auto max-w-4xl px-6 text-xl leading-relaxed font-medium text-slate-300 italic opacity-90 lg:text-2xl"
                            >
                                "{{
                                    selectedSurah
                                        ? selectedSurah.description.length > 250
                                            ? selectedSurah.description.substring(
                                                  0,
                                                  250,
                                              ) + '...'
                                            : selectedSurah.description
                                        : ''
                                }}"
                            </p>
                        </div>

                        <div
                            class="mt-auto flex flex-col items-center justify-between gap-8 border-t border-white/10 pt-10 md:flex-row"
                        >
                            <div class="flex items-center gap-6">
                                <button
                                    @click="toggleAudio"
                                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-600 shadow-xl shadow-emerald-900/40 transition-all group-hover:bg-emerald-500 hover:scale-110"
                                >
                                    <Pause
                                        v-if="isPlaying"
                                        class="h-8 w-8 fill-current"
                                    />
                                    <Play v-else class="h-8 w-8 fill-current" />
                                </button>
                                <div>
                                    <p
                                        class="text-sm font-bold tracking-widest text-emerald-400 uppercase"
                                    >
                                        Murottal Audio
                                    </p>
                                    <p class="text-lg font-bold">
                                        {{ selectedSurah?.name_latin }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex w-full gap-4 md:w-auto">
                                <button
                                    @click="openReader"
                                    class="flex-1 cursor-pointer rounded-2xl bg-emerald-600 px-12 py-4 text-center font-extrabold text-white shadow-xl shadow-emerald-900/60 transition-all hover:bg-emerald-700 md:flex-none"
                                >
                                    Baca Sekarang
                                </button>
                                <Link
                                    href="/quran"
                                    class="flex-1 rounded-2xl border border-white/10 bg-white/5 px-12 py-4 text-center font-extrabold text-white transition-all hover:bg-white/10 md:flex-none"
                                >
                                    Semua Surat
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Ornament -->
                    <div
                        class="pointer-events-none absolute -top-10 -right-10 opacity-5 transition-transform duration-1000 group-hover:rotate-12"
                    >
                        <Moon class="h-96 w-96" />
                    </div>
                    <div
                        class="pointer-events-none absolute bottom-0 left-0 h-1/2 w-full bg-gradient-to-t from-emerald-900/20 to-transparent"
                    ></div>
                </div>

                <!-- Read Surah Modal -->
                <div
                    v-if="showReadModal"
                    class="fixed inset-0 z-[60] flex items-center justify-center p-4 lg:p-8"
                >
                    <div
                        class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm"
                        @click="showReadModal = false"
                    ></div>
                    <div
                        class="relative flex max-h-full w-full max-w-5xl flex-col overflow-hidden rounded-[2.5rem] bg-white shadow-2xl dark:bg-slate-900"
                    >
                        <!-- Modal Header -->
                        <div
                            class="sticky top-0 z-10 flex items-center justify-between border-b bg-white p-6 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-xl font-bold text-white"
                                >
                                    {{ selectedSurah?.number }}
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-black text-slate-900 dark:text-white"
                                    >
                                        {{ selectedSurah?.name_latin }}
                                    </h3>
                                    <p
                                        class="text-xs font-bold tracking-widest text-slate-400 uppercase"
                                    >
                                        {{ selectedSurah?.translation }} •
                                        {{ selectedSurah?.number_of_ayahs }}
                                        Ayat
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="showReadModal = false"
                                class="rounded-2xl p-3 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                            >
                                <CloseIcon class="h-6 w-6" />
                            </button>
                        </div>

                        <!-- Modal Content -->
                        <div class="flex-grow overflow-y-auto p-6 lg:p-10">
                            <div v-if="isDetailLoading" class="space-y-8">
                                <div
                                    v-for="i in 5"
                                    :key="i"
                                    class="animate-pulse space-y-4"
                                >
                                    <div
                                        class="ml-auto h-10 w-3/4 rounded-xl bg-slate-100 dark:bg-slate-800"
                                    ></div>
                                    <div
                                        class="h-4 w-1/2 rounded-lg bg-slate-50 dark:bg-slate-800/50"
                                    ></div>
                                </div>
                            </div>
                            <div
                                v-else-if="selectedSurah && selectedSurah.ayahs"
                                class="space-y-12"
                            >
                                <!-- Bismillah -->
                                <div
                                    v-if="
                                        selectedSurah?.number !== 1 &&
                                        selectedSurah?.number !== 9
                                    "
                                    class="mb-16 text-center"
                                >
                                    <h2
                                        class="font-arabic text-4xl text-slate-800 dark:text-white"
                                    >
                                        بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ
                                    </h2>
                                </div>

                                <div
                                    v-for="ayat in selectedSurah.ayahs"
                                    :key="ayat.ayah_number"
                                    class="group relative border-b border-slate-50 pb-12 last:border-none dark:border-slate-800/50"
                                >
                                    <div
                                        class="mb-6 flex items-start justify-between gap-8"
                                    >
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-emerald-100 text-xs font-bold text-emerald-600 dark:border-emerald-900/30"
                                        >
                                            {{ ayat.ayah_number }}
                                        </div>
                                        <h2
                                            class="font-arabic dir-rtl text-right text-4xl leading-[1.8] text-slate-800 lg:text-5xl dark:text-white"
                                            style="font-family: 'Amiri', serif"
                                        >
                                            {{ ayat.arab }}
                                        </h2>
                                    </div>
                                    <div class="space-y-4 pl-14">
                                        <p
                                            class="leading-relaxed text-slate-600 dark:text-slate-400"
                                        >
                                            {{ ayat.translation }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div
                            class="flex justify-center gap-4 border-t bg-slate-50 p-6 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <button
                                @click="toggleAudio"
                                class="flex items-center gap-3 rounded-2xl bg-emerald-600 px-8 py-3 font-bold text-white shadow-lg shadow-emerald-100 transition-all hover:bg-emerald-700"
                            >
                                <component
                                    :is="isPlaying ? Pause : Play"
                                    class="h-5 w-5"
                                />
                                {{
                                    isPlaying ? 'Pause Audio' : 'Putar Murottal'
                                }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Random Hadis Card -->
                <div
                    v-if="randomHadis"
                    class="group relative overflow-hidden rounded-3xl border border-slate-100 bg-gradient-to-br from-white to-emerald-50 p-8 shadow-xl lg:p-12 dark:border-slate-700 dark:from-slate-800 dark:to-slate-900"
                >
                    <div class="relative z-10">
                        <div
                            class="mb-6 flex items-center gap-3 text-emerald-600"
                        >
                            <Volume2 class="h-6 w-6" />
                            <span
                                class="text-sm font-bold tracking-widest uppercase"
                                >Hadis Hari Ini</span
                            >
                        </div>
                        <div class="mx-auto max-w-5xl text-center">
                            <h3
                                class="font-arabic dir-rtl mb-8 text-3xl leading-relaxed text-slate-800 lg:text-4xl dark:text-white"
                                style="font-family: 'Amiri', serif"
                            >
                                {{ randomHadis.text.ar }}
                            </h3>
                            <div
                                class="mx-auto mb-8 h-1 w-20 rounded-full bg-emerald-600"
                            ></div>
                            <p
                                class="mb-8 text-lg leading-relaxed font-medium text-slate-600 italic lg:text-xl dark:text-slate-300"
                            >
                                "{{ randomHadis.text.id }}"
                            </p>
                            <div class="flex flex-col items-center gap-2">
                                <span
                                    class="rounded-full bg-emerald-100 px-4 py-1.5 text-sm font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                >
                                    {{ randomHadis.takhrij }}
                                </span>
                                <span class="text-sm font-medium text-slate-400"
                                    >Grade: {{ randomHadis.grade }}</span
                                >
                            </div>
                        </div>
                    </div>
                    <div
                        class="absolute top-0 right-0 -mt-16 -mr-16 h-32 w-32 rounded-full bg-emerald-200/20 blur-3xl"
                    ></div>
                    <div
                        class="absolute bottom-0 left-0 -mb-16 -ml-16 h-32 w-32 rounded-full bg-emerald-200/20 blur-3xl"
                    ></div>
                </div>
            </div>
        </main>
    </PublicLayout>
</template>

<style>
@keyframes float {
    0% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
    100% {
        transform: translateY(0px);
    }
}

.animate-float {
    animation: float 4s ease-in-out infinite;
}
</style>
