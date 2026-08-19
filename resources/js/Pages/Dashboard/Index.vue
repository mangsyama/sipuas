<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    FileText, 
    Activity, 
    TrendingUp, 
    TrendingDown,
    AlertCircle, 
    ArrowUpRight,
    Settings,
    Clock,
    CheckCircle2,
    ShieldCheck,
    Sparkles,
    Building2,
    Users,
    Award,
    BarChart3,
    QrCode,
    UserCheck,
    History
} from '@lucide/vue';

const props = defineProps({
    user: {
        type: Object,
        default: () => null
    }
});

const statsData = ref([
    { label: 'Total Laporan Pasien', value: '248', desc: 'Seluruh aduan & pujian masuk', icon: FileText, color: 'text-emerald-600 dark:text-white', bg: 'bg-emerald-50 dark:bg-white/10' },
    { label: 'Apresiasi Pujian', value: '182', desc: '73.4% dari total laporan', icon: TrendingUp, color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-950/40' },
    { label: 'Komplain Pelayanan', value: '66', desc: '26.6% memerlukan verifikasi', icon: TrendingDown, color: 'text-rose-600 dark:text-rose-400', bg: 'bg-rose-50 dark:bg-rose-950/40' },
    { label: 'Indeks Kepuasan RS', value: '88.5%', desc: 'Tingkat kepuasan pasien', icon: Activity, color: 'text-emerald-600 dark:text-white', bg: 'bg-emerald-50 dark:bg-white/10' },
]);

const recentFeed = ref([
    {
        id: 'LP-2026-08-001',
        time: 'Hari Ini, 14:30',
        unit: 'Instalasi Farmasi',
        content: 'Ambil obat lama sekali, sudah antre 2 jam petugasnya malah asyik ngobrol dan lambat melayani.',
        sentiment: 'NEGATIF',
        category: 'Waktu Tunggu & Pelayanan',
        status: 'PENDING'
    },
    {
        id: 'LP-2026-08-002',
        time: 'Hari Ini, 13:15',
        unit: 'Instalasi Farmasi',
        content: 'Perawat Sinta sangat ramah dan sigap membantu mengurus administrasi resep obat ibu saya.',
        sentiment: 'POSITIF',
        category: 'Pelayanan Ramah',
        status: 'VERIFIED'
    },
    {
        id: 'LP-2026-08-003',
        time: 'Hari Ini, 11:00',
        unit: 'Instalasi Gawat Darurat (IGD)',
        content: 'Dokter jaga di IGD sangat cepat tanggap memberikan pertolongan medis pertama.',
        sentiment: 'POSITIF',
        category: 'Kecepatan Medis',
        status: 'VERIFIED'
    }
]);

const redZoneBreakdown = ref([
    { unit: 'Instalasi Farmasi', count: 42, percent: 84, status: 'HIGH_RISK' },
    { unit: 'Instalasi Gawat Darurat (IGD)', count: 35, percent: 70, status: 'MEDIUM_RISK' },
    { unit: 'Kasir & Pendaftaran', count: 28, percent: 56, status: 'MEDIUM_RISK' },
    { unit: 'Poliklinik Rawat Jalan', count: 18, percent: 36, status: 'LOW_RISK' }
]);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Welcome Card (Header Lama) -->
            <div class="p-[1px] rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-800 dark:bg-none dark:bg-slate-800 shadow-sm">
                <div class="overflow-hidden bg-gradient-to-r from-emerald-600 to-emerald-800 dark:from-slate-900 dark:to-slate-900 rounded-[15px] text-white p-6 sm:p-8 relative flex items-center justify-between gap-4 sm:gap-6">
                    <!-- Text Info -->
                    <div class="relative z-10 flex-1 min-w-0 pr-20 sm:pr-24">
                        <h3 class="text-2xl font-black tracking-tight mb-1">SIPUAS</h3>
                        <p class="text-emerald-100 dark:text-slate-300 text-sm font-medium leading-relaxed break-words">
                            Sistem Integrasi Pelayanan Publik & Akuntabilitas Staf
                        </p>
                    </div>

                    <!-- Right White Logo -->
                    <div class="absolute right-6 sm:right-8 top-1/2 -translate-y-1/2 z-10 flex items-center justify-center h-16 w-16 opacity-85 hover:opacity-100 transition-opacity pointer-events-none select-none">
                        <img src="/images/logo-sidebar.png" alt="SIPUAS" class="h-full w-full object-contain brightness-0 invert" />
                    </div>

                    <!-- Decorative background patterns -->
                    <div class="absolute inset-0 opacity-10 dark:opacity-5 pointer-events-none overflow-hidden select-none">
                        <div class="absolute -right-28 -top-28 w-80 h-80 border-2 border-white rounded-[80px] rotate-[15deg]"></div>
                        <div class="absolute -right-40 -top-40 w-80 h-80 border-2 border-white rounded-[100px] rotate-[15deg]"></div>
                    </div>
                </div>
            </div>

            <!-- Top Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div 
                    v-for="stat in statsData" 
                    :key="stat.label"
                    class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between"
                >
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">{{ stat.label }}</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stat.value }}</div>
                        <span class="text-[11px] text-slate-400 block">{{ stat.desc }}</span>
                    </div>
                    <div :class="['h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0', stat.bg]">
                        <component :is="stat.icon" :class="['h-6 w-6', stat.color]" />
                    </div>
                </div>
            </div>

            <!-- Module Quick Shortcuts Bar (PRD Navigation Grid) -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Akses Cepat Modul Sistem SIPUAS</h3>
                    <span class="text-xs text-slate-400 font-medium">Berdasarkan Peran PRD</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <Link
                        :href="route('report.create')"
                        class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-500/50 transition text-center space-y-2 group"
                    >
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                            <QrCode class="h-5 w-5" />
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Form Pasien</div>
                        <div class="text-[10px] text-slate-400">Public Guest QR</div>
                    </Link>

                    <Link
                        :href="route('kasi.dashboard')"
                        class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-500/50 transition text-center space-y-2 group"
                    >
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                            <FileText class="h-5 w-5" />
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Feed Aduan Kasi</div>
                        <div class="text-[10px] text-slate-400">Verifikasi Unit</div>
                    </Link>

                    <Link
                        :href="route('kasi.logbook')"
                        class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-500/50 transition text-center space-y-2 group"
                    >
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                            <History class="h-5 w-5" />
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Digital Logbook</div>
                        <div class="text-[10px] text-slate-400">Poin KPI Staf</div>
                    </Link>

                    <Link
                        :href="route('executive.dashboard')"
                        class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-500/50 transition text-center space-y-2 group"
                    >
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                            <Activity class="h-5 w-5" />
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Command Center</div>
                        <div class="text-[10px] text-slate-400">Zona Merah RS</div>
                    </Link>

                    <Link
                        :href="route('executive.kasi-responsiveness')"
                        class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-500/50 transition text-center space-y-2 group"
                    >
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                            <BarChart3 class="h-5 w-5" />
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Respons Kasi</div>
                        <div class="text-[10px] text-slate-400">Akuntabilitas</div>
                    </Link>

                    <Link
                        :href="route('executive.leaderboard')"
                        class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-500/50 transition text-center space-y-2 group"
                    >
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                            <Award class="h-5 w-5" />
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Leaderboard</div>
                        <div class="text-[10px] text-slate-400">Peringkat Staf</div>
                    </Link>
                </div>
            </div>

            <!-- Content Rows Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Left: Recent Feed (8 cols) -->
                <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">Feed Aduan Pasien Terbaru (Real-Time AI)</h3>
                        <Link
                            :href="route('kasi.dashboard')"
                            class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1"
                        >
                            <span>Lihat Semua Feed</span>
                            <ArrowUpRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="feed in recentFeed"
                            :key="feed.id"
                            class="bg-slate-50/70 dark:bg-slate-950/60 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 space-y-2"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ feed.id }}</span>
                                    <span class="text-slate-400">• {{ feed.time }}</span>
                                </div>
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                                        feed.sentiment === 'NEGATIF' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : 'bg-emerald-100 text-emerald-800 dark:bg-white/10 dark:text-white'
                                    ]"
                                >
                                    {{ feed.sentiment }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed font-medium">
                                "{{ feed.content }}"
                            </p>
                            <div class="flex items-center justify-between pt-1 text-[11px] text-slate-400">
                                <span>Unit: <strong class="text-slate-700 dark:text-slate-300">{{ feed.unit }}</strong></span>
                                <span>Kategori: <strong class="text-slate-700 dark:text-slate-300">{{ feed.category }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Breakdown Zona Merah RS (4 cols) -->
                <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">Peta Zona Merah Unit</h3>
                        <Link :href="route('executive.dashboard')" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline">Detail</Link>
                    </div>

                    <div class="space-y-3">
                        <div v-for="unit in redZoneBreakdown" :key="unit.unit" class="space-y-1 text-xs">
                            <div class="flex justify-between font-medium">
                                <span class="text-slate-700 dark:text-slate-300 truncate">{{ unit.unit }}</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400 shrink-0">{{ unit.count }} Aduan</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-950 rounded-full h-2 overflow-hidden">
                                <div
                                    :class="[
                                        'h-full rounded-full',
                                        unit.status === 'HIGH_RISK' ? 'bg-rose-500' : unit.status === 'MEDIUM_RISK' ? 'bg-amber-500' : 'bg-emerald-500'
                                    ]"
                                    :style="{ width: unit.percent + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
