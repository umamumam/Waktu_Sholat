<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { 
    BookOpen, 
    FolderGit2, 
    LayoutGrid, 
    Clock, 
    Compass, 
    Home,
    MapPin 
} from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Beranda',
        href: '/',
        icon: Home,
    },
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Jadwal Sholat',
        href: '/sholat',
        icon: Clock,
    },
    {
        title: 'Al-Qur\'an',
        href: '/quran',
        icon: BookOpen,
    },
    {
        title: 'Kiblat',
        href: '/kiblat',
        icon: Compass,
    },
];

const cityInfo = ref({ lokasi: 'Mendeteksi...' });

onMounted(async () => {
    try {
        const res = await fetch('/api/islamic/initial');
        const json = await res.json();
        if (json.status && json.data.city) {
            cityInfo.value = json.data.city;
        }
    } catch (e) {
        cityInfo.value = { lokasi: 'Indonesia' };
    }
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div class="px-4 py-2 mt-2">
                <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/50">
                    <MapPin class="w-4 h-4 text-emerald-600" />
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black uppercase text-emerald-600 tracking-tighter leading-none mb-1">Lokasi Aktif</span>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200 leading-none truncate">{{ cityInfo.lokasi }}</span>
                    </div>
                </div>
            </div>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
