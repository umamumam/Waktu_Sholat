<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { 
    Users, 
    MapPin, 
    BookOpen, 
    MoreHorizontal, 
    Edit, 
    Trash2, 
    PlayCircle, 
    CheckCircle,
    LayoutDashboard,
    Search
} from 'lucide-vue-next';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const stats = [
    { name: 'Total Pengguna', value: '2,450', icon: Users, color: 'text-blue-600', bg: 'bg-blue-100' },
    { name: 'Lokasi Terdaftar', value: '120', icon: MapPin, color: 'text-emerald-600', bg: 'bg-emerald-100' },
    { name: 'Total Surat', value: '114', icon: BookOpen, color: 'text-amber-600', bg: 'bg-amber-100' },
];

const prayerManagement = [
    { lokasi: 'Jakarta', tanggal: '13 Ramadhan', imsak: '04:23', subuh: '04:33', dzuhur: '12:01', ashar: '15:15', maghrib: '18:05', isya: '19:18' },
    { lokasi: 'Jakarta', tanggal: '14 Ramadhan', imsak: '04:23', subuh: '04:33', dzuhur: '12:01', ashar: '15:15', maghrib: '18:05', isya: '19:18' },
    { lokasi: 'Jakarta', tanggal: '15 Ramadhan', imsak: '04:23', subuh: '04:33', dzuhur: '12:01', ashar: '15:15', maghrib: '18:05', isya: '19:18' },
];

const quranManagement = [
    { name: 'Al-Fatihah', verses: '7 Ayat', revelation: 'Makkiyah' },
    { name: 'Al-Baqarah', verses: '286 Ayat', revelation: 'Madaniyah' },
    { name: 'Ali \'Imran', verses: '200 Ayat', revelation: 'Madaniyah' },
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="p-6 space-y-8 bg-slate-50/50 dark:bg-transparent min-h-screen">
        <!-- Welcome Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Welcome back, Administrator!</h1>
                <p class="text-slate-500">Monitor and manage your MuslimDaily application data here.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative hidden lg:block">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input type="text" placeholder="Global Search..." class="pl-10 pr-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <button class="bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-emerald-200 dark:shadow-none hover:bg-emerald-700 transition-colors">Generate Report</button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div v-for="stat in stats" :key="stat.name" class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm transition-all hover:shadow-md hover:scale-[1.02]">
                <div class="flex justify-between items-start mb-4">
                    <div :class="['p-3 rounded-2xl', stat.bg]">
                        <component :is="stat.icon" :class="['w-6 h-6', stat.color]" />
                    </div>
                    <button class="text-slate-400 hover:text-slate-600 transition-colors">
                        <MoreHorizontal class="w-5 h-5" />
                    </button>
                </div>
                <div>
                    <p class="text-slate-500 text-sm font-medium">{{ stat.name }}</p>
                    <h3 class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ stat.value }}</h3>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-50 dark:border-slate-700/50 flex items-center gap-2">
                    <span class="text-emerald-600 text-xs font-bold">+12.5%</span>
                    <span class="text-slate-400 text-xs tracking-tight">dari bulan lalu</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Prayer Times Table -->
            <div class="lg:col-span-8">
                <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-50 dark:border-slate-700/50 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Manajemen Jadwal Waktu Sholat</h2>
                        <button class="text-emerald-600 text-sm font-bold hover:underline">Lihat Semua</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/50 dark:bg-slate-700/20 text-slate-400 text-[10px] font-black uppercase tracking-widest">
                                    <th class="px-6 py-4">Lokasi</th>
                                    <th class="px-6 py-4">Tanggal</th>
                                    <th class="px-6 py-4">Imsak</th>
                                    <th class="px-6 py-4">Subuh</th>
                                    <th class="px-6 py-4">Maghrib</th>
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                                <tr v-for="(item, i) in prayerManagement" :key="i" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-700 dark:text-slate-300">{{ item.lokasi }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-500">{{ item.tanggal }}</td>
                                    <td class="px-6 py-4 font-mono text-sm">{{ item.imsak }}</td>
                                    <td class="px-6 py-4 font-mono text-sm">{{ item.subuh }}</td>
                                    <td class="px-6 py-4 font-mono font-bold text-emerald-600">{{ item.maghrib }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <button class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                                                <Edit class="w-4 h-4" />
                                            </button>
                                            <button class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quran Management -->
            <div class="lg:col-span-4">
                <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden h-full flex flex-col">
                    <div class="p-6 border-b border-slate-50 dark:border-slate-700/50">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Kelola Al-Qur'an</h2>
                    </div>
                    <div class="p-6 space-y-4 flex-grow">
                        <div v-for="surah in quranManagement" :key="surah.name" class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/30 rounded-2xl border border-transparent hover:border-emerald-200 transition-all cursor-pointer group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white dark:bg-slate-700 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                                    <BookOpen class="w-5 h-5 text-emerald-600" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200">{{ surah.name }}</h4>
                                    <p class="text-[10px] text-slate-400 uppercase font-bold tracking-tighter">{{ surah.verses }} • {{ surah.revelation }}</p>
                                </div>
                            </div>
                            <button class="opacity-0 group-hover:opacity-100 p-2 bg-white dark:bg-slate-600 rounded-lg shadow-sm transition-all">
                                <PlayCircle class="w-5 h-5 text-emerald-600" />
                            </button>
                        </div>
                    </div>
                    <div class="p-6 border-t border-slate-50 dark:border-slate-700/50">
                        <button class="w-full py-3 bg-emerald-600 text-white rounded-2xl font-bold shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all">Update Index Surat</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
