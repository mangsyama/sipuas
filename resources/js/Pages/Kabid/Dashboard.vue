<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Activity, 
    FileText, 
    TrendingUp, 
    CheckCircle2, 
    Clock, 
    Calendar,
    BarChart3,
    Smile,
    Filter,
    ArrowUpRight,
    RotateCcw,
    Layers,
    ShieldAlert
} from '@lucide/vue';
import TrendAreaChart from '@/Components/Charts/TrendAreaChart.vue';
import SentimentDonutChart from '@/Components/Charts/SentimentDonutChart.vue';
import CategoryBarChart from '@/Components/Charts/CategoryBarChart.vue';

const props = defineProps({
    executiveStats: {
        type: Object,
        default: () => ({})
    },
    redZoneUnits: {
        type: Array,
        default: () => []
    },
    trendChart: {
        type: Object,
        default: () => ({ labels: [], datasets: [] })
    },
    categoryChart: {
        type: Object,
        default: () => ({ labels: [], positive: [], negative: [] })
    },
    sentimentChart: {
        type: Object,
        default: () => ({ labels: [], data: [0, 0, 0], percentages: [0, 0, 0] })
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

// Filter state
const selectedPeriod = ref(props.filters.period || 'all');
const selectedRoomId = ref(props.filters.room_id || '');
const isCustomDateModalOpen = ref(false);
const customStartDate = ref(props.filters.start_date || '');
const customEndDate = ref(props.filters.end_date || '');

const periods = [
    { key: 'all', label: 'Semua' },
    { key: 'today', label: 'Hari Ini' },
    { key: '7d', label: '7 Hari' },
    { key: '30d', label: '30 Hari' },
    { key: 'this_month', label: 'Bulan Ini' },
    { key: 'this_year', label: 'Tahun Ini' },
];

const applyFilter = (periodKey = selectedPeriod.value) => {
    selectedPeriod.value = periodKey;
    if (periodKey === 'custom') {
        isCustomDateModalOpen.value = true;
        return;
    }

    router.get(route('kabid.dashboard'), {
        period: periodKey,
        room_id: selectedRoomId.value || undefined
    }, {
        preserveState: true,
        replace: true
    });
};

const applyRoomFilter = () => {
    router.get(route('kabid.dashboard'), {
        period: selectedPeriod.value,
        room_id: selectedRoomId.value || undefined,
        start_date: selectedPeriod.value === 'custom' ? customStartDate.value : undefined,
        end_date: selectedPeriod.value === 'custom' ? customEndDate.value : undefined
    }, {
        preserveState: true,
        replace: true
    });
};

const submitCustomDateFilter = () => {
    if (!customStartDate.value || !customEndDate.value) return;
    isCustomDateModalOpen.value = false;
    router.get(route('kabid.dashboard'), {
        period: 'custom',
        start_date: customStartDate.value,
        end_date: customEndDate.value,
        room_id: selectedRoomId.value || undefined
    }, {
        preserveState: true,
        replace: true
    });
};

const resetFilter = () => {
    selectedPeriod.value = 'all';
    selectedRoomId.value = '';
    customStartDate.value = '';
    customEndDate.value = '';
    router.get(route('kabid.dashboard'), {}, {
        preserveState: true,
        replace: true
    });
};

const executiveStats = computed(() => props.executiveStats || {
    total_rs_reports: 0,
    total_verified: 0,
    total_pending: 0,
    avg_kasi_response_hours: '-',
    completion_rate: 100,
    satisfaction_index: '100%',
    total_kpi_points: 0,
    positive_count: 0,
    positive_percent: 0,
    negative_count: 0,
    negative_percent: 0,
    neutral_count: 0,
    neutral_percent: 0,
});

const redZoneUnits = computed(() => props.redZoneUnits || []);
const activeRedZoneUnits = computed(() => {
    return (props.redZoneUnits || []).filter(u => u.complaints > 0);
});

const sentimentPercentages = computed(() => {
    const total = executiveStats.value.total_rs_reports || 0;
    if (total === 0) return [0, 0, 0];
    return [
        Math.round(((executiveStats.value.positive_count || 0) / total) * 100),
        Math.round(((executiveStats.value.negative_count || 0) / total) * 100),
        Math.round(((executiveStats.value.neutral_count || 0) / total) * 100)
    ];
});

const currentRoomName = computed(() => {
    if (selectedRoomId.value) {
        const found = props.rooms.find(r => String(r.id) === String(selectedRoomId.value));
        if (found) return found.name;
    }
    return 'Seluruh Unit RS';
});
</script>

<template>
    <Head title="Dashboard Kabid RS" />

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

            <!-- Filter Toolbar (Sleek Executive Filter Bar with White Border) -->
            <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 px-4 py-3 sm:px-5 sm:py-3.5 rounded-2xl shadow-sm flex flex-wrap items-center justify-between gap-3">
                <!-- Left: Clean Segmented Period Tabs (No scroll bug, medium font) -->
                <div class="inline-flex items-center p-1 bg-slate-100 dark:bg-slate-800/90 rounded-xl border border-slate-200/60 dark:border-slate-700/60 gap-1 flex-wrap">
                    <button
                        v-for="p in periods"
                        :key="p.key"
                        type="button"
                        @click="applyFilter(p.key)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-150 cursor-pointer whitespace-nowrap',
                            selectedPeriod === p.key
                                ? 'bg-emerald-600 text-white shadow-sm font-semibold'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-700/80'
                        ]"
                    >
                        {{ p.label }}
                    </button>
                    <button
                        type="button"
                        @click="applyFilter('custom')"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-150 cursor-pointer whitespace-nowrap flex items-center gap-1',
                            selectedPeriod === 'custom'
                                ? 'bg-emerald-600 text-white shadow-sm font-semibold'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-700/80'
                        ]"
                    >
                        <Calendar class="h-3 w-3" />
                        <span>Kustom Tanggal</span>
                    </button>
                </div>

                <!-- Right: Room Selector & Reset Action -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <div v-if="rooms && rooms.length > 0">
                        <select
                            v-model="selectedRoomId"
                            @change="applyRoomFilter"
                            class="h-[38px] px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/90 border border-slate-200/60 dark:border-slate-700/60 text-slate-700 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-none"
                        >
                            <option value="">Semua Unit & Ruangan RS</option>
                            <option v-for="r in rooms" :key="r.id" :value="r.id">
                                {{ r.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Reset Action Button (Matching height, font-medium) -->
                    <button
                        v-if="selectedPeriod !== 'all' || selectedRoomId || customStartDate || customEndDate"
                        @click="resetFilter"
                        type="button"
                        class="h-[38px] inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 bg-slate-100 hover:bg-rose-50 dark:bg-slate-800/90 dark:hover:bg-rose-950/40 border border-slate-200/60 dark:border-slate-700/60 transition cursor-pointer shadow-none"
                        title="Reset filter"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span>Reset</span>
                    </button>
                </div>
            </div>

            <!-- Top 4 KPI Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- Metric 1: Total RS Reports -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Suara Pasien RS</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white leading-tight">
                            {{ executiveStats.total_rs_reports }}
                        </div>
                        <div class="flex items-center gap-2 text-[11px] text-slate-400">
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ executiveStats.total_verified }} Diverifikasi</span>
                            <span>•</span>
                            <span class="text-amber-600 dark:text-amber-400 font-bold">{{ executiveStats.total_pending }} Menunggu</span>
                        </div>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <FileText class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <!-- Metric 2: Satisfaction Index -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Indeks Kepuasan RS</span>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                            {{ executiveStats.satisfaction_index }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">{{ executiveStats.positive_count }} Apresiasi positif diterima</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <TrendingUp class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <!-- Metric 3: Average Response Time -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Rata-Rata Respons Kasi</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white leading-tight">
                            {{ executiveStats.avg_kasi_response_hours }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">Durasi riil tindak lanjut laporan</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-950/40">
                        <Clock class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>

                <!-- Metric 4: Completion Rate -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Tingkat Penyelesaian</span>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                            {{ executiveStats.completion_rate }}%
                        </div>
                        <span class="text-[11px] text-slate-400 block">Laporan telah selesai ditindaklanjuti</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-white" />
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
                                    Volume masukan masuk vs laporan terselesaikan di seluruh RS
                                </p>
                            </div>
                        </div>
                        <span class="hidden sm:inline-flex px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                            {{ filters.period_label }}
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
                            :data="[executiveStats.positive_count, executiveStats.negative_count, executiveStats.neutral_count]"
                            :percentages="sentimentPercentages"
                            :center-text="executiveStats.satisfaction_index"
                            center-subtext="Kepuasan"
                            :height="170"
                        />
                    </div>
                </div>
            </div>

            <!-- Secondary Analytics Row: Problem Categories & Red Zone Unit Map (6 cols / 6 cols) -->
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
                            :href="route('kabid.kasi-responsiveness')"
                            class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline flex items-center gap-1"
                        >
                            Detail Akuntabilitas
                            <ArrowUpRight class="h-3 w-3" />
                        </Link>
                    </div>

                    <div v-if="activeRedZoneUnits.length === 0" class="py-12 text-center space-y-2">
                        <div class="h-10 w-10 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                        <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Semua Unit Bersih & Prima</p>
                        <p class="text-[11px] text-slate-400">Tidak ada aduan keluhan pada periode ini.</p>
                    </div>

                    <div v-else class="space-y-3 max-h-[260px] overflow-y-auto pr-1">
                        <div
                            v-for="unit in activeRedZoneUnits.slice(0, 6)"
                            :key="unit.unit_id"
                            class="p-3 rounded-xl bg-slate-50/70 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-2"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200 truncate">
                                    {{ unit.unit }}
                                </span>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-[11px] text-slate-400 font-medium">Respon: {{ unit.avg_response }}</span>
                                    <span class="font-bold text-rose-600 dark:text-rose-400">{{ unit.complaints }} Keluhan</span>
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
                                    :style="{ width: unit.complaints > 0 ? `${Math.min(100, Math.max(2, unit.percentage))}%` : '0%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Date Range Filter Modal -->
        <div
            v-if="isCustomDateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-md w-full p-6 space-y-4 animate-spa-fade-in">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <Calendar class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pilih Rentang Tanggal Khusus</h3>
                    </div>
                    <button
                        type="button"
                        @click="isCustomDateModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Tanggal Mulai
                        </label>
                        <input
                            v-model="customStartDate"
                            type="date"
                            class="w-full h-10 px-3 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Tanggal Selesai
                        </label>
                        <input
                            v-model="customEndDate"
                            type="date"
                            class="w-full h-10 px-3 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="isCustomDateModalOpen = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitCustomDateFilter"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition"
                    >
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
