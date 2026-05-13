<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { 
    Compass, 
    MapPin, 
    Navigation,
    Info,
    RotateCcw
} from 'lucide-vue-next';

const lat = ref(0);
const lon = ref(0);
const qiblaData = ref<any>(null);
const isLoading = ref(true);

const detectLocation = () => {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(async (position) => {
            lat.value = position.coords.latitude;
            lon.value = position.coords.longitude;
            fetchQibla();
        });
    }
};

const fetchQibla = async () => {
    try {
        const res = await fetch(`/api/islamic/qibla?lat=${lat.value}&lon=${lon.value}`);
        const json = await res.json();
        if (json.status) {
            qiblaData.value = json.data;
        }
    } catch (e) {
        console.error('Failed to fetch qibla', e);
    } finally {
        isLoading.value = false;
    }
};

onMounted(detectLocation);
</script>

<template>
    <PublicLayout>
        <Head title="Penunjuk Arah Kiblat" />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-12 text-center max-w-2xl mx-auto">
                <div class="w-20 h-20 bg-emerald-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-emerald-200">
                    <Compass class="text-white w-10 h-10 animate-float" />
                </div>
                <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">Arah Kiblat</h1>
                <p class="text-slate-500 mt-2 text-lg">Cari tahu arah Ka'bah dari lokasi Anda saat ini secara real-time.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="bg-white dark:bg-slate-800 p-8 lg:p-12 rounded-[3rem] border border-slate-100 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-12">
                            <div>
                                <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">Lokasi Anda</p>
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Koordinat Terdeteksi</h3>
                            </div>
                            <button @click="detectLocation" class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all">
                                <RotateCcw class="w-6 h-6" />
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-12">
                            <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-800">
                                <p class="text-xs font-bold text-slate-400 uppercase mb-2">Latitude</p>
                                <p class="text-xl font-mono font-bold">{{ lat.toFixed(6) }}</p>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-800">
                                <p class="text-xs font-bold text-slate-400 uppercase mb-2">Longitude</p>
                                <p class="text-xl font-mono font-bold">{{ lon.toFixed(6) }}</p>
                            </div>
                        </div>

                        <div v-if="qiblaData" class="bg-emerald-600 text-white p-8 rounded-[2rem] shadow-xl shadow-emerald-200 dark:shadow-none">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="p-3 bg-white/20 rounded-xl">
                                    <Navigation class="w-6 h-6" />
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-emerald-200 uppercase tracking-widest">Derajat Kiblat</p>
                                    <h4 class="text-3xl font-black">{{ qiblaData.direction.toFixed(2) }}°</h4>
                                </div>
                            </div>
                            <p class="text-emerald-100 text-sm opacity-90 leading-relaxed font-medium">
                                Arahkan perangkat Anda ke arah {{ qiblaData.direction.toFixed(0) }} derajat dari titik utara untuk menghadap Ka'bah.
                            </p>
                        </div>
                    </div>

                    <div class="absolute -right-20 -bottom-20 opacity-5 pointer-events-none group-hover:scale-110 transition-transform duration-1000">
                        <Compass class="w-80 h-80" />
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="flex gap-6">
                        <div class="w-14 h-14 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center shrink-0">
                            <Info class="text-emerald-600 w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="text-xl font-bold mb-2">Bagaimana cara kerjanya?</h4>
                            <p class="text-slate-500 dark:text-slate-400 leading-relaxed">Sistem kami menggunakan koordinat GPS Anda untuk menghitung sudut lingkaran besar (Great Circle) menuju Ka'bah di Mekah menggunakan rumus Haversine.</p>
                        </div>
                    </div>
                    <div class="flex gap-6">
                        <div class="w-14 h-14 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center shrink-0">
                            <MapPin class="text-emerald-600 w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="text-xl font-bold mb-2">Akurasi Lokasi</h4>
                            <p class="text-slate-500 dark:text-slate-400 leading-relaxed">Untuk hasil terbaik, pastikan Anda memberikan izin akses lokasi dan berada di luar ruangan dengan pandangan langit yang jelas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </PublicLayout>
</template>
