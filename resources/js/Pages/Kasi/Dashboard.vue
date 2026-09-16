<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    FileText, 
    AlertCircle, 
    CheckCircle2, 
    Clock, 
    Sparkles, 
    LayoutDashboard, 
    Smile, 
    Frown, 
    Meh,
    BarChart3,
    TrendingUp,
    ArrowUpRight,
    Layers,
    Inbox,
    ThumbsUp,
    ThumbsDown,
    MessageSquare,
    ArrowRight,
    RotateCcw
} from '@lucide/vue';
import TrendAreaChart from '@/Components/Charts/TrendAreaChart.vue';
import SentimentDonutChart from '@/Components/Charts/SentimentDonutChart.vue';
import CategoryBarChart from '@/Components/Charts/CategoryBarChart.vue';

const props = defineProps({
    unitStats: {
        type: Object,
        default: () => ({
            total: 0,
            pending: 0,
            verified: 0,
            positive: 0,
            negative: 0,
            neutral: 0,
            satisfaction_index: '100%',
            avg_response_hours: '-',
            unit_name: 'Ruangan Pelayanan'
        })
    },
    unitTrend: {
        type: Object,
        default: () => ({ labels: [], incoming: [], verified: [] })
    },
    categoryChart: {
        type: Object,
        default: () => ({ labels: [], positive: [], negative: [] })
    },
    topCategories: {
        type: Array,
        default: () => []
    },
    recentReports: {
        type: Array,
        default: () => []
    },
    rooms: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({ period: 'all', period_label: 'Semua Periode', room_id: null })
    }
});

const selectedPeriod = ref(props.filters.period || 'all');
const selectedRoomId = ref(props.filters.room_id || '');

const periods = [
    { key: 'all', label: 'Semua' },
    { key: 'today', label: 'Hari Ini' },
    { key: '7d', label: '7 Hari' },
    { key: '30d', label: '30 Hari' },
    { key: 'this_month', label: 'Bulan Ini' },
];

const applyPeriodFilter = (pKey) => {
    selectedPeriod.value = pKey;
    router.get(route('kasi.dashboard'), {
        period: pKey,
        room_id: selectedRoomId.value || undefined
    }, {
        preserveState: true,
        replace: true
    });
};

const applyRoomFilter = () => {
    router.get(route('kasi.dashboard'), {
        period: selectedPeriod.value,
        room_id: selectedRoomId.value || undefined
    }, {
        preserveState: true,
        replace: true
    });
};

const resetFilter = () => {
    selectedPeriod.value = 'all';
    selectedRoomId.value = '';
    router.get(route('kasi.dashboard'), {}, {
        preserveState: true,
        replace: true
    });
};

// Trend datasets for Chart.js
const unitTrendDatasets = computed(() => [
    {
        label: 'Aduan Masuk',
        data: props.unitTrend.incoming || [],
        borderColor: '#059669',
        backgroundColor: 'rgba(16, 185, 129, 0.15)',
        fill: true,
        tension: 0.35,
    },
    {
        label: 'Telah Diverifikasi',
        data: props.unitTrend.verified || [],
        borderColor: '#0284c7',
        backgroundColor: 'rgba(2, 132, 199, 0.12)',
        fill: true,
        tension: 0.35,
    }
]);

const sentimentPercentages = computed(() => {
    const total = props.unitStats.total || 0;
    if (total === 0) return [0, 0, 0];
    return [
        Math.round(((props.unitStats.positive || 0) / total) * 100),
        Math.round(((props.unitStats.negative || 0) / total) * 100),
        Math.round(((props.unitStats.neutral || 0) / total) * 100)
    ];
});
</script>

<template>
    <Head title="Dashboard Kasi" />

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

            <!-- Filter Toolbar (Sleek Executive Filter Bar) -->
            <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 px-4 py-3 sm:px-5 sm:py-3.5 rounded-2xl shadow-sm flex flex-wrap items-center justify-between gap-3">
                <!-- Left: Clean Segmented Period Tabs (No scroll bug, medium font) -->
                <div class="inline-flex items-center p-1 bg-slate-100 dark:bg-slate-800/90 rounded-xl border border-slate-200/60 dark:border-slate-700/60 gap-1">
                    <button
                        v-for="p in periods"
                        :key="p.key"
                        type="button"
                        @click="applyPeriodFilter(p.key)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-150 cursor-pointer whitespace-nowrap',
                            selectedPeriod === p.key
                                ? 'bg-emerald-600 text-white shadow-sm font-semibold'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-700/80'
                        ]"
                    >
                        {{ p.label }}
                    </button>
                </div>

                <!-- Right: Room Selector & Reset Action -->
                <div class="flex items-center gap-2.5">
                    <!-- Elevated Room Selector (Matching height, font-medium, no shadow, native browser arrow) -->
                    <div v-if="rooms && rooms.length > 0">
                        <select
                            v-model="selectedRoomId"
                            @change="applyRoomFilter"
                            class="h-[38px] px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/90 border border-slate-200/60 dark:border-slate-700/60 text-slate-700 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-none"
                        >
                            <option value="">Semua Ruangan RS</option>
                            <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </select>
                    </div>

                    <!-- Reset Action Button (Matching height, font-medium) -->
                    <button
                        v-if="selectedPeriod !== 'all' || selectedRoomId"
                        @click="resetFilter"
                        type="button"
                        class="h-[38px] inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 bg-slate-100 hover:bg-rose-50 dark:bg-slate-800/90 dark:hover:bg-rose-950/40 border border-slate-200/60 dark:border-slate-700/60 transition cursor-pointer shadow-none"
                        title="Reset filter ke Semua Periode"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span>Reset</span>
                    </button>
                </div>
            </div>

            <!-- Top 4 KPI Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Aduan Unit -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Suara Masuk</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white leading-tight">{{ unitStats.total }}</div>
                        <span class="text-[11px] text-slate-400 block">{{ unitStats.verified }} telah selesai ditindaklanjuti</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <FileText class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <!-- Card 2: Perlu Verifikasi -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 block">Menunggu Verifikasi</span>
                        <div class="text-3xl font-black text-amber-600 dark:text-amber-400 leading-tight">{{ unitStats.pending }}</div>
                        <span class="text-[11px] text-slate-400 block">Antrean perlu verifikasi shift</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-950/40">
                        <AlertCircle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>

                <!-- Card 3: Kecepatan Respons -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Rata-Rata Respons</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white leading-tight">
                            {{ unitStats.avg_response_hours }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">Kecepatan verifikasi tindak lanjut</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-blue-50 dark:bg-blue-950/40">
                        <Clock class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>

                <!-- Card 4: Indeks Kepuasan Pasien Ruangan -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Indeks Kepuasan Ruangan</span>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                            {{ unitStats.satisfaction_index }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">{{ unitStats.positive }} Apresiasi pujian diterima</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                        <TrendingUp class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
            </div>

            <!-- Unit Executive Analytics Suite (Charts) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Trend Chart for Ruangan (8 cols) -->
                <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 shrink-0">
                                <TrendingUp class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white leading-tight">
                                    Tren Aktivitas Suara Pasien & Verifikasi Ruangan
                                </h3>
                                <p class="text-[11px] text-slate-400 font-medium">
                                    Volume masukan masuk vs verifikasi terselesaikan
                                </p>
                            </div>
                        </div>
                        <span class="hidden sm:inline-flex px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                            {{ filters.period_label }}
                        </span>
                    </div>

                    <div class="pt-2">
                        <TrendAreaChart
                            :labels="unitTrend.labels"
                            :datasets="unitTrendDatasets"
                            :height="240"
                        />
                    </div>
                </div>

                <!-- Sentiment for Ruangan (4 cols) -->
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
                                    Tingkat kepuasan & apresiasi di ruangan ini
                                </p>
                            </div>
                        </div>

                        <SentimentDonutChart
                            :labels="['Positif', 'Negatif', 'Netral']"
                            :data="[unitStats.positive, unitStats.negative, unitStats.neutral]"
                            :percentages="sentimentPercentages"
                            :center-text="unitStats.satisfaction_index"
                            center-subtext="Kepuasan"
                            :height="170"
                        />
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Category Distribution Chart & Compact Recent Activity Table -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Col 1: Category Distribution Chart (6 cols) -->
                <div class="lg:col-span-6 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 shrink-0">
                                <BarChart3 class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white leading-tight">
                                    Sebaran Topik Masalah & Apresiasi
                                </h3>
                                <p class="text-[11px] text-slate-400 font-medium">
                                    Pujian, Keluhan & Saran per Kategori
                                </p>
                            </div>
                        </div>
                        <span class="text-xs text-slate-400 font-medium">Top Kategori</span>
                    </div>

                    <div v-if="!categoryChart.labels || categoryChart.labels.length === 0" class="h-[260px] flex flex-col items-center justify-center text-center text-xs text-slate-400 space-y-2">
                        <Layers class="h-8 w-8 text-slate-300 dark:text-slate-700" />
                        <p>Belum ada data kategori masukan pada ruangan ini.</p>
                    </div>
                    <div v-else class="pt-1">
                        <CategoryBarChart
                            :labels="categoryChart.labels"
                            :positive="categoryChart.positive"
                            :negative="categoryChart.negative"
                            :neutral="categoryChart.neutral"
                            :height="260"
                        />
                    </div>
                </div>

                <!-- Col 2: Compact Recent Activity Table (6 cols) -->
                <div class="lg:col-span-6 bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-4">
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
                                    5 suara pasien terbaru di ruangan ini
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('kasi.feed')"
                            class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline flex items-center gap-1"
                        >
                            <span>Buka Antrean</span>
                            <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <!-- Compact Fixed Height Table Container -->
                    <div v-if="recentReports.length === 0" class="h-[260px] flex flex-col items-center justify-center text-center text-xs text-slate-400 space-y-2">
                        <Inbox class="h-8 w-8 text-slate-300 dark:text-slate-700" />
                        <p>Belum ada laporan masuk pada ruangan ini.</p>
                    </div>
                    <div v-else class="h-[260px] overflow-y-auto pr-1 divide-y divide-slate-100 dark:divide-slate-800/80">
                        <div
                            v-for="item in recentReports"
                            :key="item.id"
                            class="py-2.5 px-3 rounded-xl hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition flex items-center justify-between gap-3 group"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="shrink-0 space-y-0.5">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block tracking-tight">
                                        #{{ item.id }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 block">
                                        {{ item.created_at_human }}
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-800 dark:text-slate-200 truncate font-medium max-w-[240px] sm:max-w-[320px]" :title="item.isi_laporan">
                                        "{{ item.isi_laporan }}"
                                    </p>
                                    <div class="flex items-center gap-1.5 text-[10px] text-slate-400 mt-0.5 truncate">
                                        <span class="font-medium text-slate-500 dark:text-slate-400">{{ item.ai_category }}</span>
                                        <span>•</span>
                                        <span class="truncate">{{ item.target_object }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span v-if="item.ai_sentiment === 'POSITIF'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    <ThumbsUp class="h-2.5 w-2.5" />
                                    Pujian
                                </span>
                                <span v-else-if="item.ai_sentiment === 'NEGATIF'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                    <ThumbsDown class="h-2.5 w-2.5" />
                                    Keluhan
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    <MessageSquare class="h-2.5 w-2.5" />
                                    Saran
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
