<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { 
    Moon, 
    Menu, 
    X,
    User,
    LayoutDashboard,
    Clock,
    BookOpen,
    Compass,
    ChevronRight
} from 'lucide-vue-next';

const isMenuOpen = ref(false);
const page = usePage();

const navLinks = [
    { name: 'Beranda', href: '/', icon: LayoutDashboard },
    { name: 'Jadwal Sholat', href: '/sholat', icon: Clock },
    { name: 'Al-Qur\'an', href: '/quran', icon: BookOpen },
    { name: 'Kiblat', href: '/kiblat', icon: Compass },
];
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] text-slate-800 font-sans selection:bg-emerald-100 dark:bg-[#0F172A] dark:text-slate-200">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200/50 dark:bg-slate-900/80 dark:border-slate-800/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <Link href="/" class="flex items-center gap-2">
                        <div class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-200 dark:shadow-none">
                            <Moon class="text-white w-6 h-6 fill-current" />
                        </div>
                        <span class="text-xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
                            MuslimDaily
                        </span>
                    </Link>

                    <!-- Desktop Nav -->
                    <div class="hidden md:flex items-center space-x-8">
                        <Link 
                            v-for="link in navLinks" 
                            :key="link.href" 
                            :href="link.href"
                            :class="[
                                'transition-colors font-medium',
                                $page.url === link.href ? 'text-emerald-600 font-semibold border-b-2 border-emerald-600 pb-1' : 'text-slate-500 hover:text-emerald-600'
                            ]"
                        >
                            {{ link.name }}
                        </Link>
                        
                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-800"></div>
                        
                        <Link v-if="!$page.props.auth.user" href="/login" class="px-6 py-2 bg-emerald-600 text-white rounded-full font-semibold hover:bg-emerald-700 transition-all shadow-md shadow-emerald-100">
                            Masuk
                        </Link>
                        <Link v-else href="/dashboard" class="flex items-center gap-2 text-slate-700 dark:text-slate-300 font-semibold">
                            <User class="w-5 h-5" />
                            <span>{{ $page.props.auth.user.name }}</span>
                        </Link>
                    </div>

                    <!-- Mobile Menu Button -->
                    <div class="md:hidden flex items-center">
                        <button @click="isMenuOpen = !isMenuOpen" class="p-2 text-slate-500">
                            <Menu v-if="!isMenuOpen" class="w-8 h-8" />
                            <X v-else class="w-8 h-8" />
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div v-if="isMenuOpen" class="md:hidden bg-white border-b dark:bg-slate-900">
                <div class="px-4 pt-2 pb-6 space-y-2">
                    <Link 
                        v-for="link in navLinks" 
                        :key="link.href" 
                        :href="link.href"
                        @click="isMenuOpen = false"
                        :class="[
                            'block px-3 py-2 rounded-lg',
                            $page.url === link.href ? 'text-emerald-600 font-bold bg-emerald-50 dark:bg-emerald-900/20' : 'text-slate-600 dark:text-slate-400'
                        ]"
                    >
                        {{ link.name }}
                    </Link>
                    <Link v-if="!$page.props.auth.user" href="/login" class="block px-3 py-2 text-emerald-600 font-bold">Masuk</Link>
                    <Link v-else href="/dashboard" class="block px-3 py-2 text-slate-600">Dashboard</Link>
                </div>
            </div>
        </nav>

        <slot />

        <!-- Mobile Bottom Nav -->
        <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-2xl border-t border-slate-200 px-8 py-5 flex justify-between items-center z-50 dark:bg-slate-900/95 dark:border-slate-800">
            <Link v-for="link in navLinks" :key="link.href" :href="link.href" class="flex flex-col items-center gap-1.5 transition-all" :class="$page.url === link.href ? 'text-emerald-600' : 'text-slate-400'">
                <component :is="link.icon" class="w-6 h-6" />
                <span class="text-[10px] font-black uppercase tracking-tighter">{{ link.name.split(' ')[0] }}</span>
            </Link>
        </div>

        <!-- Professional Footer -->
        <footer class="bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 pt-20 pb-12 mb-24 md:mb-0">
            <div class="max-w-7xl mx-auto px-4 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
                    <!-- Brand Section -->
                    <div class="col-span-1 lg:col-span-1">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-100">
                                <Moon class="text-white w-6 h-6 fill-current" />
                            </div>
                            <span class="text-2xl font-black bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">MuslimDaily</span>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed mb-6">
                            MuslimDaily adalah platform digital yang dirancang untuk membantu umat Muslim dalam menjalankan ibadah harian dengan lebih teratur dan khusyuk melalui teknologi modern yang akurat.
                        </p>
                        <div class="flex items-center gap-4">
                            <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all cursor-pointer">
                                <span class="font-bold text-xs">FB</span>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all cursor-pointer">
                                <span class="font-bold text-xs">IG</span>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all cursor-pointer">
                                <span class="font-bold text-xs">TW</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="text-slate-900 dark:text-white font-bold mb-6 uppercase tracking-widest text-xs">Navigasi Utama</h4>
                        <ul class="space-y-4">
                            <li v-for="link in navLinks" :key="link.href">
                                <Link :href="link.href" class="text-slate-500 hover:text-emerald-600 text-sm transition-colors flex items-center gap-2 group">
                                    <ChevronRight class="w-3 h-3 opacity-0 group-hover:opacity-100 -ml-4 group-hover:ml-0 transition-all" /> 
                                    {{ link.name }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Resources -->
                    <div>
                        <h4 class="text-slate-900 dark:text-white font-bold mb-6 uppercase tracking-widest text-xs">Sumber Data</h4>
                        <ul class="space-y-4">
                            <li><a href="https://bimasislam.kemenag.go.id" target="_blank" class="text-slate-500 hover:text-emerald-600 text-sm transition-colors flex items-center gap-3">
                                <div class="w-6 h-6 bg-slate-50 dark:bg-slate-800 rounded p-1"><img src="https://bimasislam.kemenag.go.id/web/images/logo.png" class="h-full grayscale" alt="Kemenag"></div>
                                Kemenag RI
                            </a></li>
                            <li><a href="https://myquran.com" target="_blank" class="text-slate-500 hover:text-emerald-600 text-sm transition-colors flex items-center gap-3">
                                <div class="w-6 h-6 bg-slate-50 dark:bg-slate-800 rounded p-1"><BookOpen class="h-full text-slate-400" /></div>
                                MyQuran v3 API
                            </a></li>
                        </ul>
                    </div>

                    <!-- App Badges -->
                    <div>
                        <h4 class="text-slate-900 dark:text-white font-bold mb-6 uppercase tracking-widest text-xs">Dapatkan di Smartphone</h4>
                        <div class="space-y-3">
                            <button class="w-full bg-slate-900 text-white rounded-xl py-3 px-4 flex items-center gap-3 hover:bg-black transition-all border border-slate-800">
                                <div class="w-8 h-8 bg-white/10 rounded flex items-center justify-center">A</div>
                                <div class="text-left">
                                    <p class="text-[10px] uppercase opacity-60 leading-none">Download on</p>
                                    <p class="font-bold text-sm leading-none">App Store</p>
                                </div>
                            </button>
                            <button class="w-full bg-slate-900 text-white rounded-xl py-3 px-4 flex items-center gap-3 hover:bg-black transition-all border border-slate-800">
                                <div class="w-8 h-8 bg-white/10 rounded flex items-center justify-center">G</div>
                                <div class="text-left">
                                    <p class="text-[10px] uppercase opacity-60 leading-none">Get it on</p>
                                    <p class="font-bold text-sm leading-none">Google Play</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-12 border-t border-slate-50 dark:border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6">
                    <p class="text-slate-400 text-xs font-medium">
                        © 2026 MuslimDaily. Semua hak cipta dilindungi undang-undang. Crafted for the Ummah.
                    </p>
                    <div class="flex items-center gap-8 text-xs font-bold text-slate-400 uppercase tracking-widest">
                        <a href="#" class="hover:text-emerald-600 transition-colors">Kebijakan Privasi</a>
                        <a href="#" class="hover:text-emerald-600 transition-colors">Syarat & Ketentuan</a>
                        <a href="#" class="hover:text-emerald-600 transition-colors">Donasi Kami</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    overflow-x: hidden;
}

.font-arabic {
    font-family: 'Amiri', serif;
}

.dir-rtl {
    direction: rtl;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #CBD5E1;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94A3B8;
}

.dark ::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>
