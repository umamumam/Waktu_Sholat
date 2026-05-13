<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { 
    Clock, 
    MapPin, 
    Search, 
    Calendar,
    Table,
    Download
} from 'lucide-vue-next';

const city = ref({ id: '1301', lokasi: 'KOTA JAKARTA' });
const monthlySchedule = ref<any[]>([]);
const showSearch = ref(false);
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const currentMonth = ref(new Date().getMonth() + 1);
const currentYear = ref(new Date().getFullYear());

const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const fetchSchedule = async () => {
    try {
        const monthStr = currentMonth.value.toString().padStart(2, '0');
        const res = await fetch(`/api/islamic/initial?city_id=${city.value.id}`);
        const json = await res.json();
        if (json.status) {
            monthlySchedule.value = Object.values(json.data.monthly_schedule);
        }
    } catch (e) {
        console.error('Failed to fetch schedule', e);
    }
};

const handleSearch = async () => {
    if (searchQuery.value.length < 3) {
        searchResults.value = [];
        return;
    }
    try {
        const res = await fetch(`/api/islamic/search-city?keyword=${searchQuery.value}`);
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
    showSearch.value = false;
    searchQuery.value = '';
    searchResults.value = [];
    fetchSchedule();
};

onMounted(fetchSchedule);
watch(searchQuery, handleSearch);
</script>

<template>
    <PublicLayout>
        <Head title="Jadwal Sholat Lengkap" />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-12">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 text-emerald-600 mb-2 cursor-pointer group" @click="showSearch = !showSearch">
                            <MapPin class="w-5 h-5" />
                            <span class="font-bold tracking-wide uppercase text-sm group-hover:underline">{{ city.lokasi }}</span>
                            <Search class="w-4 h-4 opacity-50" />
                        </div>
                        <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">Jadwal Sholat Bulanan</h1>
                        <p class="text-slate-500 mt-2">Waktu sholat akurat untuk wilayah {{ city.lokasi }} dan sekitarnya.</p>
                    </div>

                    <div class="flex gap-4">
                        <select v-model="currentMonth" @change="fetchSchedule" class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm font-bold">
                            <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
                        </select>
                        <button class="bg-emerald-600 text-white px-6 py-2 rounded-xl font-bold flex items-center gap-2 hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100">
                            <Download class="w-4 h-4" /> Export PDF
                        </button>
                    </div>
                </div>

                <!-- Search Dropdown -->
                <div v-if="showSearch" class="mt-4 w-full max-w-sm bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 p-4 absolute z-20">
                    <div class="relative mb-4">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                        <input v-model="searchQuery" type="text" placeholder="Cari Kota/Kabupaten..." class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-700 border-none focus:ring-2 focus:ring-emerald-500 transition-all">
                    </div>
                    <div class="max-h-60 overflow-y-auto space-y-1">
                        <button v-for="res in searchResults" :key="res.id" @click="selectCity(res)" class="w-full text-left px-4 py-3 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-sm font-medium transition-colors">
                            {{ res.lokasi }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 text-xs font-bold uppercase tracking-wider">
                                <th class="px-6 py-5">Tanggal</th>
                                <th class="px-6 py-5">Imsak</th>
                                <th class="px-6 py-5">Subuh</th>
                                <th class="px-6 py-5">Terbit</th>
                                <th class="px-6 py-5">Dhuha</th>
                                <th class="px-6 py-5">Dzuhur</th>
                                <th class="px-6 py-5">Ashar</th>
                                <th class="px-6 py-5 text-emerald-600">Maghrib</th>
                                <th class="px-6 py-5">Isya</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                            <tr v-for="(day, index) in monthlySchedule" :key="index" class="hover:bg-emerald-50/30 dark:hover:bg-emerald-900/10 transition-colors">
                                <td class="px-6 py-4 font-bold">{{ day.tanggal }}</td>
                                <td class="px-6 py-4 font-mono">{{ day.imsak }}</td>
                                <td class="px-6 py-4 font-mono font-bold">{{ day.subuh }}</td>
                                <td class="px-6 py-4 font-mono text-slate-400">{{ day.terbit }}</td>
                                <td class="px-6 py-4 font-mono text-slate-400">{{ day.dhuha }}</td>
                                <td class="px-6 py-4 font-mono font-bold">{{ day.dzuhur }}</td>
                                <td class="px-6 py-4 font-mono font-bold">{{ day.ashar }}</td>
                                <td class="px-6 py-4 font-mono font-black text-emerald-600">{{ day.maghrib }}</td>
                                <td class="px-6 py-4 font-mono font-bold">{{ day.isya }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </PublicLayout>
</template>
