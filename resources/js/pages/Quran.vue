<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import {
    BookOpen,
    Search,
    Play,
    Pause,
    Volume2,
    Settings,
    ChevronRight,
    Star,
    X as CloseIcon,
} from 'lucide-vue-next';

const surahs = ref<any[]>([]);
const filteredSurahs = ref<any[]>([]);
const searchQuery = ref('');
const isLoading = ref(true);
const selectedSurah = ref<any>(null);
const isPlaying = ref(false);
const audioRef = ref<HTMLAudioElement | null>(null);
const showReadModal = ref(false);
const surahDetail = ref<any>(null);
const isDetailLoading = ref(false);

const fetchSurahs = async () => {
    try {
        const res = await fetch('/api/islamic/initial');
        const json = await res.json();
        if (json.status) {
            surahs.value = json.data.surahs;
            filteredSurahs.value = surahs.value;
        }
    } catch (e) {
        console.error('Failed to fetch surahs', e);
    } finally {
        isLoading.value = false;
    }
};

const handleSearch = () => {
    const q = searchQuery.value.toLowerCase();
    filteredSurahs.value = surahs.value.filter(
        (s) =>
            s.name_latin.toLowerCase().includes(q) ||
            s.translation.toLowerCase().includes(q),
    );
};

const toggleAudio = (surah: any) => {
    if (selectedSurah.value?.number === surah.number && isPlaying.value) {
        audioRef.value?.pause();
        isPlaying.value = false;
        return;
    }

    selectedSurah.value = surah;
    if (!audioRef.value) {
        audioRef.value = new Audio(surah.audio_url);
    } else {
        audioRef.value.src = surah.audio_url;
    }

    audioRef.value.play();
    isPlaying.value = true;
    audioRef.value.onended = () => (isPlaying.value = false);
};

const readSurah = async (surah: any) => {
    selectedSurah.value = surah;
    showReadModal.value = true;
    isDetailLoading.value = true;
    try {
        const res = await fetch(`/api/islamic/surah/${surah.number}`);
        const json = await res.json();
        if (json.status) {
            surahDetail.value = json.data;
        }
    } catch (e) {
        console.error('Failed to fetch surah detail', e);
    } finally {
        isDetailLoading.value = false;
    }
};

onMounted(fetchSurahs);
watch(searchQuery, handleSearch);
</script>

<template>
    <PublicLayout>
        <Head title="Al-Qur'an Digital - 114 Surat" />

        <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-12">
                <div
                    class="flex flex-col items-center justify-between gap-8 md:flex-row"
                >
                    <div>
                        <h1
                            class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white"
                        >
                            Al-Qur'an Digital
                        </h1>
                        <p class="mt-2 text-slate-500">
                            Baca dan dengarkan 114 Surat dalam Al-Qur'an.
                        </p>
                    </div>

                    <div class="relative w-full md:w-96">
                        <Search
                            class="absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-slate-400"
                        />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama surat..."
                            class="w-full rounded-2xl border-none bg-white py-4 pr-4 pl-12 shadow-sm transition-all focus:ring-2 focus:ring-emerald-500 dark:bg-slate-800"
                        />
                    </div>
                </div>
            </div>

            <div
                v-if="isLoading"
                class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="i in 9"
                    :key="i"
                    class="h-32 animate-pulse rounded-3xl bg-slate-100 dark:bg-slate-800"
                ></div>
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="surah in filteredSurahs"
                    :key="surah.number"
                    class="group relative overflow-hidden rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition-all hover:border-emerald-500 hover:shadow-xl hover:shadow-emerald-900/5 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-emerald-500"
                >
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-lg font-bold text-emerald-600 transition-all group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-900/30"
                            >
                                {{ surah.number }}
                            </div>
                            <div>
                                <h3
                                    class="font-bold text-slate-900 transition-colors group-hover:text-emerald-600 dark:text-white"
                                >
                                    {{ surah.name_latin }}
                                </h3>
                                <p
                                    class="text-xs font-bold tracking-widest text-slate-400 uppercase"
                                >
                                    {{ surah.revelation }} •
                                    {{ surah.number_of_ayahs }} Ayat
                                </p>
                            </div>
                        </div>
                        <h2
                            class="font-arabic text-2xl font-bold text-emerald-600"
                        >
                            {{ surah.name }}
                        </h2>
                    </div>

                    <p
                        class="mb-6 text-sm text-slate-500 italic dark:text-slate-400"
                    >
                        "{{ surah.translation }}"
                    </p>

                    <div
                        class="flex items-center justify-between border-t border-slate-50 pt-4 dark:border-slate-700/50"
                    >
                        <button
                            @click="toggleAudio(surah)"
                            class="flex items-center gap-2 text-sm font-bold text-slate-400 transition-colors hover:text-emerald-600"
                        >
                            <component
                                :is="
                                    selectedSurah?.number === surah.number &&
                                    isPlaying
                                        ? Pause
                                        : Play
                                "
                                class="h-4 w-4"
                            />
                            {{
                                selectedSurah?.number === surah.number &&
                                isPlaying
                                    ? 'Pause'
                                    : 'Dengarkan'
                            }}
                        </button>
                        <button
                            @click="readSurah(surah)"
                            class="cursor-pointer rounded-xl bg-emerald-50 px-8 py-3 text-sm font-bold text-emerald-600 transition-all hover:bg-emerald-600 hover:text-white dark:bg-emerald-900/30"
                        >
                            Baca Surat
                        </button>
                    </div>

                    <div
                        class="pointer-events-none absolute -right-4 -bottom-4 opacity-0 transition-opacity group-hover:opacity-5"
                    >
                        <BookOpen class="h-24 w-24" />
                    </div>
                </div>
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
                                    {{ selectedSurah?.number_of_ayahs }} Ayat
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
                        <div v-else-if="surahDetail" class="space-y-12">
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
                                v-for="ayat in surahDetail.ayat"
                                :key="ayat.nomor"
                                class="group relative border-b border-slate-50 pb-12 last:border-none dark:border-slate-800/50"
                            >
                                <div
                                    class="mb-6 flex items-start justify-between gap-8"
                                >
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-emerald-100 text-xs font-bold text-emerald-600 dark:border-emerald-900/30"
                                    >
                                        {{ ayat.nomor }}
                                    </div>
                                    <h2
                                        class="font-arabic dir-rtl text-right text-4xl leading-[1.8] text-slate-800 lg:text-5xl dark:text-white"
                                        style="font-family: 'Amiri', serif"
                                    >
                                        {{ ayat.ar }}
                                    </h2>
                                </div>
                                <div class="space-y-4 pl-14">
                                    <p
                                        class="mb-2 text-sm font-medium text-emerald-600 italic dark:text-emerald-400"
                                    >
                                        {{ ayat.tr }}
                                    </p>
                                    <p
                                        class="leading-relaxed text-slate-600 dark:text-slate-400"
                                    >
                                        {{ ayat.idn }}
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
                            @click="toggleAudio(selectedSurah)"
                            class="flex items-center gap-3 rounded-2xl bg-emerald-600 px-8 py-3 font-bold text-white shadow-lg shadow-emerald-100 transition-all hover:bg-emerald-700"
                        >
                            <component
                                :is="isPlaying ? Pause : Play"
                                class="h-5 w-5"
                            />
                            {{ isPlaying ? 'Pause Audio' : 'Putar Murottal' }}
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </PublicLayout>
</template>
