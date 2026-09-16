<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    FileText, 
    Clock, 
    CheckCircle2, 
    Smile, 
    TrendingUp, 
    ShieldAlert, 
    BarChart3, 
    QrCode, 
    History, 
    Activity, 
    LayoutDashboard,
    Award, 
    ArrowUpRight, 
    Layers,
    Inbox,
    ThumbsUp,
    ThumbsDown,
    MessageSquare
} from '@lucide/vue';
import TrendAreaChart from '@/Components/Charts/TrendAreaChart.vue';
import SentimentDonutChart from '@/Components/Charts/SentimentDonutChart.vue';
import CategoryBarChart from '@/Components/Charts/CategoryBarChart.vue';

const props = defineProps({
    user: {
        type: Object,
        default: () => null
    },
    statsData: {
        type: Array,
        default: () => []
    },
    trendChart: {
        type: Object,
        default: () => ({ labels: [], datasets: [] })
    },
    sentimentChart: {
        type: Object,
        default: () => ({ labels: [], data: [0, 0, 0], percentages: [0, 0, 0], satisfaction_index: '100%' })
    },
    categoryChart: {
        type: Object,
        default: () => ({ labels: [], positive: [], negative: [], neutral: [] })
    },
    recentReports: {
        type: Array,
        default: () => []
    },
    redZoneBreakdown: {
        type: Array,
        default: () => []
    }
});

const statsData = computed(() => {
    if (props.statsData && props.statsData.length > 0) {
        const icons = [FileText, Clock, CheckCircle2, Smile];
        const colors = [
            'text-emerald-600 dark:text-white',
            'text-amber-600 dark:text-amber-400',
            'text-emerald-600 dark:text-emerald-400',
            'text-emerald-600 dark:text-white'
        ];
        const bgs = [
            'bg-emerald-50 dark:bg-white/10',
            'bg-amber-50 dark:bg-amber-950/40',
            'bg-emerald-50 dark:bg-emerald-950/40',
            'bg-emerald-50 dark:bg-white/10'
        ];
        return props.statsData.map((item, idx) => ({
            label: item.label,
            value: item.value,
            desc: item.desc || '',
            icon: icons[idx % icons.length],
            color: colors[idx % colors.length],
            bg: bgs[idx % bgs.length]
        }));
    }
    return [
        { label: 'Total Suara Masuk', value: '0', desc: 'Belum ada laporan', icon: FileText, color: 'text-emerald-600 dark:text-white', bg: 'bg-emerald-50 dark:bg-white/10' },
        { label: 'Menunggu Verifikasi', value: '0', desc: 'Tidak ada antrean', icon: Clock, color: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-50 dark:bg-amber-950/40' },
        { label: 'Terverifikasi (Selesai)', value: '0', desc: '100% tingkat selesai', icon: CheckCircle2, color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-950/40' },
        { label: 'Indeks Kepuasan RS', value: '100%', desc: 'Belum ada keluhan', icon: Smile, color: 'text-emerald-600 dark:text-white', bg: 'bg-emerald-50 dark:bg-white/10' },
    ];
});

const recentReports = computed(() => {
    return props.recentReports || [];
});

const activeRedZoneBreakdown = computed(() => {
    return (props.redZoneBreakdown || []).filter(u => u.count > 0);
});

const sentimentData = computed(() => {
    if (props.sentimentChart?.data && Array.isArray(props.sentimentChart.data)) {
        return props.sentimentChart.data;
    }
    return [0, 0, 0];
});

const sentimentPercentages = computed(() => {
    const data = sentimentData.value;
    const total = data.reduce((a, b) => a + b, 0);
    if (total === 0) return [0, 0, 0];
    return [
        Math.round(((data[0] || 0) / total) * 100),
        Math.round(((data[1] || 0) / total) * 100),
        Math.round(((data[2] || 0) / total) * 100)
    ];
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Welcome Card (Header) -->
            <div class="p-[1px] rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-800 dark:bg-none dark:bg-slate-800 shadow-sm">
                <div class="overflow-hidden bg-gradient-to-r from-emerald-600 to-emerald-800 dark:from-slate-900 dark:to-slate-900 rounded-[15px] text-white p-6 sm:p-8 relative flex items-center justify-between gap-4 sm:gap-6">
                    <!-- Text Info -->
                    <div class="relative z-10 flex-1 min-w-0">
                        <h3 class="text-2xl font-black tracking-tight mb-1">SIPUAS</h3>
                        <p class="text-emerald-100 dark:text-slate-300 text-sm font-medium leading-relaxed break-words">
                            Sistem Integrasi Pelayanan Publik & Akuntabilitas Staf
                        </p>
                    </div>

                    <!-- Decorative background patterns -->
                    <div class="absolute inset-0 opacity-10 dark:opacity-5 pointer-events-none overflow-hidden select-none">
                        <div class="absolute -right-28 -top-28 w-80 h-80 border-2 border-white rounded-[80px] rotate-[15deg]"></div>
                        <div class="absolute -right-40 -top-40 w-80 h-80 border-2 border-white rounded-[100px] rotate-[15deg]"></div>
                    </div>
                </div>
            </div>

            <!-- Top Stats Grid (Border Putih Bersih) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div 
                    v-for="stat in statsData" 
                    :key="stat.label"
                    class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between"
                >
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">{{ stat.label }}</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white leading-tight">{{ stat.value }}</div>
                        <span class="text-[11px] text-slate-400 block">{{ stat.desc }}</span>
                    </div>
                    <div :class="['h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0', stat.bg]">
                        <component :is="stat.icon" :class="['h-6 w-6', stat.color]" />
                    </div>
                </div>
            </div>

            <!-- Primary Analytics Row: Time-Series Trend & Sentiment Distribution (8 cols / 4 cols) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Trend Area Chart (8 cols) -->
                <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 shrink-0">
                                <TrendingUp class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white leading-tight">
                                    Tren Suara Pasien & Penyelesaian Laporan
                                </h3>
                                <p class="text-[11px] text-slate-400 font-medium">
                                    Volume masukan masuk vs laporan terselesaikan di seluruh RS (14 Hari Terakhir)
                                </p>
                            </div>
                        </div>
                        <span class="hidden sm:inline-flex px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                            14 Hari Terakhir
                        </span>
                    </div>

                    <div class="pt-2">
                        <TrendAreaChart
                            :labels="trendChart.labels"
                            :datasets="trendChart.datasets"
                            :height="240"
                        />
                    </div>
                </div>

                <!-- Sentiment Distribution Doughnut (4 cols) -->
                <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 shrink-0">
                                <Smile class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white leading-tight">
                                    Rasio Sentimen Pasien
                                </h3>
                                <p class="text-[11px] text-slate-400 font-medium">
                                    Tingkat kepuasan & apresiasi di seluruh RS
                                </p>
                            </div>
                        </div>

                        <SentimentDonutChart
                            :labels="['Positif', 'Negatif', 'Netral']"
                            :data="sentimentData"
                            :percentages="sentimentPercentages"
                            :center-text="sentimentChart.satisfaction_index || '100%'"
                            center-subtext="Kepuasan"
                            :height="170"
                        />
                    </div>
                </div>
            </div>

            <!-- Secondary Analytics Row: Problem Categories & Pemantauan Mutu (6 cols / 6 cols) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Top Categories Horizontal Bar Chart (6 cols) -->
                <div class="lg:col-span-6 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 shrink-0">
                                <BarChart3 class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white leading-tight">
                                    Kategori Masukan & Masalah Terbanyak
                                </h3>
                                <p class="text-[11px] text-slate-400 font-medium">
                                    Sebaran topik pujian, keluhan, dan saran pelayanan
                                </p>
                            </div>
                        </div>
                        <span class="text-xs text-slate-400 font-medium">Top Kategori</span>
                    </div>

                    <div class="pt-1">
                        <CategoryBarChart
                            :labels="categoryChart.labels"
                            :positive="categoryChart.positive"
                            :negative="categoryChart.negative"
                            :neutral="categoryChart.neutral"
                            :height="260"
                        />
                    </div>
                </div>

                <!-- Pemantauan Mutu Unit Pelayanan (6 cols) -->
                <div class="lg:col-span-6 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 shrink-0">
                                <ShieldAlert class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white leading-tight">
                                    Pemantauan Mutu Unit Pelayanan
                                </h3>
                                <p class="text-[11px] text-slate-400 font-medium">
                                    Status risiko zona keluhan dan kecepatan respons unit
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('executive.dashboard')"
                            class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline flex items-center gap-1"
                        >
                            Detail Eksekutif
                            <ArrowUpRight class="h-3 w-3" />
                        </Link>
                    </div>

                    <div v-if="activeRedZoneBreakdown.length === 0" class="py-12 text-center space-y-2">
                        <div class="h-10 w-10 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                        <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Semua Unit Bersih & Prima</p>
                        <p class="text-[11px] text-slate-400">Tidak ada aduan keluhan aktif pada unit saat ini.</p>
                    </div>

                    <div v-else class="space-y-3 max-h-[260px] overflow-y-auto pr-1">
                        <div
                            v-for="unit in activeRedZoneBreakdown.slice(0, 6)"
                            :key="unit.unit"
                            class="p-3 rounded-xl bg-slate-50/70 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-2"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200 truncate">
                                    {{ unit.unit }}
                                </span>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span v-if="unit.avg_response && unit.avg_response !== '-'" class="text-[11px] text-slate-400 font-medium">Respon: {{ unit.avg_response }}</span>
                                    <span class="font-bold text-rose-600 dark:text-rose-400">{{ unit.count }} Keluhan</span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-[9px] font-extrabold uppercase',
                                            unit.status === 'HIGH_RISK'
                                                ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                                : (unit.status === 'MEDIUM_RISK' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300')
                                        ]"
                                    >
                                        {{ unit.status === 'HIGH_RISK' ? 'Zona Merah' : (unit.status === 'MEDIUM_RISK' ? 'Zona Kuning' : 'Zona Hijau') }}
                                    </span>
                                </div>
                            </div>

                            <div class="w-full bg-slate-200/80 dark:bg-slate-700/60 rounded-full h-1.5 overflow-hidden">
                                <div
                                    :class="[
                                        'h-full rounded-full transition-all duration-500',
                                        unit.status === 'HIGH_RISK' ? 'bg-rose-500' : (unit.status === 'MEDIUM_RISK' ? 'bg-amber-500' : 'bg-emerald-500')
                                    ]"
                                    :style="{ width: `${Math.min(100, Math.max(5, unit.percent))}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Row: Live Real-time Feed & PRD Quick Access (8 cols / 4 cols) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Left: Recent Feed / Aktivitas Masukan Terkini (8 cols) -->
                <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 shrink-0">
                                <Inbox class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white leading-tight">
                                    Aktivitas Masukan Terkini
                                </h3>
                                <p class="text-[11px] text-slate-400 font-medium">
                                    5 suara pasien terbaru dengan analisis instan AI
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('kasi.feed')"
                            class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1"
                        >
                            <span>Buka Antrean</span>
                            <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <div v-if="recentReports.length === 0" class="py-12 flex flex-col items-center justify-center text-center text-xs text-slate-400 space-y-2">
                        <Inbox class="h-8 w-8 text-slate-300 dark:text-slate-700" />
                        <p class="font-medium">Belum ada laporan atau masukan pasien yang masuk saat ini.</p>
                    </div>

                    <div v-else class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <div
                            v-for="item in recentReports"
                            :key="item.id"
                            class="py-3 px-3 rounded-xl hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition flex items-center justify-between gap-3 group"
                        >
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="shrink-0 space-y-0.5">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block tracking-tight">
                                        #{{ item.id }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 block">
                                        {{ item.created_at_human }}
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-800 dark:text-slate-200 truncate font-medium max-w-[280px] sm:max-w-[420px]" :title="item.isi_laporan">
                                        "{{ item.isi_laporan }}"
                                    </p>
                                    <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-0.5 truncate">
                                        <span class="font-semibold text-slate-600 dark:text-slate-300">{{ item.unit }}</span>
                                        <span>•</span>
                                        <span class="font-medium text-slate-500 dark:text-slate-400">{{ item.ai_category }}</span>
                                        <template v-if="item.target_object && item.target_object !== item.unit">
                                            <span>•</span>
                                            <span class="truncate">{{ item.target_object }}</span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span v-if="item.ai_sentiment === 'POSITIF'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    <ThumbsUp class="h-3 w-3" />
                                    Pujian
                                </span>
                                <span v-else-if="item.ai_sentiment === 'NEGATIF'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                    <ThumbsDown class="h-3 w-3" />
                                    Keluhan
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    <MessageSquare class="h-3 w-3" />
                                    Saran
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Module Quick Shortcuts Bar (4 cols) -->
                <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 shrink-0">
                                <Layers class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white leading-tight">
                                    Pintasan Modul
                                </h3>
                                <p class="text-[11px] text-slate-400 font-medium">
                                    Akses cepat peran sistem SIPUAS
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <Link
                                :href="route('report.create')"
                                class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 hover:border-emerald-500/50 hover:bg-emerald-50/50 dark:hover:bg-slate-700/60 transition text-center space-y-1.5 group cursor-pointer"
                            >
                                <div class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                                    <QrCode class="h-4 w-4" />
                                </div>
                                <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">Form Pasien</div>
                                <div class="text-[9px] text-slate-400">Guest QR</div>
                            </Link>

                            <Link
                                :href="route('kasi.dashboard')"
                                class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 hover:border-emerald-500/50 hover:bg-emerald-50/50 dark:hover:bg-slate-700/60 transition text-center space-y-1.5 group cursor-pointer"
                            >
                                <div class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                                    <FileText class="h-4 w-4" />
                                </div>
                                <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">Dashboard Kasi</div>
                                <div class="text-[9px] text-slate-400">Unit Ruangan</div>
                            </Link>

                            <Link
                                :href="route('kasi.feed')"
                                class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 hover:border-emerald-500/50 hover:bg-emerald-50/50 dark:hover:bg-slate-700/60 transition text-center space-y-1.5 group cursor-pointer"
                            >
                                <div class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                                    <CheckCircle2 class="h-4 w-4" />
                                </div>
                                <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">Verifikasi Aduan</div>
                                <div class="text-[9px] text-slate-400">Antrean Kasi</div>
                            </Link>

                            <Link
                                :href="route('executive.dashboard')"
                                class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 hover:border-emerald-500/50 hover:bg-emerald-50/50 dark:hover:bg-slate-700/60 transition text-center space-y-1.5 group cursor-pointer"
                            >
                                <div class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                                    <LayoutDashboard class="h-4 w-4" />
                                </div>
                                <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">Dashboard Kabid</div>
                                <div class="text-[9px] text-slate-400">Kabid RS</div>
                            </Link>

                            <Link
                                :href="route('kasi.logbook')"
                                class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 hover:border-emerald-500/50 hover:bg-emerald-50/50 dark:hover:bg-slate-700/60 transition text-center space-y-1.5 group cursor-pointer"
                            >
                                <div class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                                    <History class="h-4 w-4" />
                                </div>
                                <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">Logbook Digital</div>
                                <div class="text-[9px] text-slate-400">Poin KPI Staf</div>
                            </Link>

                            <Link
                                :href="route('executive.leaderboard')"
                                class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 hover:border-emerald-500/50 hover:bg-emerald-50/50 dark:hover:bg-slate-700/60 transition text-center space-y-1.5 group cursor-pointer"
                            >
                                <div class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto group-hover:scale-110 transition duration-200">
                                    <Award class="h-4 w-4" />
                                </div>
                                <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">Leaderboard</div>
                                <div class="text-[9px] text-slate-400">Peringkat Kinerja</div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
