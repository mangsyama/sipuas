<script setup>
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    BarChart3, 
    CheckCircle2, 
    Clock, 
    Building2, 
    Search, 
    Calendar,
    ChevronDown,
    ShieldCheck,
    RotateCcw,
    ChevronLeft,
    ChevronRight,
    AlertTriangle,
    AlertCircle
} from '@lucide/vue';

const props = defineProps({
    kasiData: {
        type: Array,
        default: () => []
    },
    summary: {
        type: Object,
        default: () => ({ total_reports: 0, total_verified: 0, total_pending: 0, avg_verification_rate: 100 })
    },
    rooms: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({ period: '30d', period_label: '30 Hari Terakhir', room_id: null, start_date: null, end_date: null })
    }
});

const searchQuery = ref('');
const selectedPeriod = ref(props.filters.period || 'all');
const selectedRoomId = ref(props.filters.room_id || '');
const isCustomDateModalOpen = ref(false);
const customStartDate = ref(props.filters.start_date || '');
const customEndDate = ref(props.filters.end_date || '');

const periods = [
    { key: 'all', label: 'Semua Periode' },
    { key: 'today', label: 'Hari Ini' },
    { key: '7d', label: '7 Hari Terakhir' },
    { key: '30d', label: '30 Hari Terakhir' },
    { key: 'this_month', label: 'Bulan Ini' },
    { key: 'this_year', label: 'Tahun Ini' },
];

const applyFilter = (periodKey = selectedPeriod.value) => {
    selectedPeriod.value = periodKey;
    if (periodKey === 'custom') {
        isCustomDateModalOpen.value = true;
        return;
    }

    router.get(route('executive.kasi-responsiveness'), {
        period: periodKey,
        room_id: selectedRoomId.value || undefined
    }, {
        preserveState: true,
        replace: true
    });
};

const applyRoomFilter = () => {
    router.get(route('executive.kasi-responsiveness'), {
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
    router.get(route('executive.kasi-responsiveness'), {
        period: 'custom',
        start_date: customStartDate.value,
        end_date: customEndDate.value,
        room_id: selectedRoomId.value || undefined
    }, {
        preserveState: true,
        replace: true
    });
};

const closeCustomDateModal = () => {
    isCustomDateModalOpen.value = false;
    if (!customStartDate.value || !customEndDate.value) {
        selectedPeriod.value = props.filters.period || 'all';
    }
};

const statusFilter = ref('ALL');

const resetFilter = () => {
    selectedPeriod.value = 'all';
    selectedRoomId.value = '';
    searchQuery.value = '';
    statusFilter.value = 'ALL';
    customStartDate.value = '';
    customEndDate.value = '';
    router.get(route('executive.kasi-responsiveness'), {}, {
        preserveState: true,
        replace: true
    });
};

const filteredKasiData = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    return props.kasiData.filter(k => {
        const matchesSearch = !q || 
            (k.kasi_name && k.kasi_name.toLowerCase().includes(q)) ||
            (k.unit_name && k.unit_name.toLowerCase().includes(q));
        const matchesStatus = statusFilter.value === 'ALL' || k.status === statusFilter.value;
        return matchesSearch && matchesStatus;
    });
});

// Interactive Pagination Logic (Sesuai Feed.vue)
const currentPage = ref(1);
const perPage = ref(10);

watch([searchQuery, statusFilter], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => {
    return Math.ceil(filteredKasiData.value.length / perPage.value) || 1;
});

const paginatedKasiData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredKasiData.value.slice(start, start + perPage.value);
});

const startItemIndex = computed(() => {
    if (filteredKasiData.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const endItemIndex = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredKasiData.value.length);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};
</script>

<template>
    <Head title="Responsivitas & Akuntabilitas Kasi" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel (Sesuai Desain Leaderboard) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <ShieldCheck class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Laporan Responsivitas Kepala Seksi
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Pemantauan kecepatan respons & tingkat verifikasi aduan unit oleh Kepala Ruangan / Kasi.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Top 4 KPI Metrics Grid (Mini Dashboard Sesuai Dashboard Kabid) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- Metric 1: Total Aduan Unit -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Aduan Unit</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white leading-tight">
                            {{ summary.total_reports }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">Seluruh unit pada periode ini</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <Building2 class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <!-- Metric 2: Telah Diverifikasi -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Telah Diverifikasi</span>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                            {{ summary.total_verified }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">Poin KPI staf telah diberikan</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <!-- Metric 3: Menunggu Tindak Lanjut -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Menunggu Tindak Lanjut</span>
                        <div class="text-3xl font-black text-amber-600 dark:text-amber-400 leading-tight">
                            {{ summary.total_pending }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">Menunggu verifikasi Kasi</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-950/40">
                        <Clock class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>

                <!-- Metric 4: Rata-rata Tingkat Verifikasi -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Rata-rata Tingkat Verifikasi</span>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                            {{ summary.avg_verification_rate }}%
                        </div>
                        <span class="text-[11px] text-slate-400 block">Persentase verifikasi RS</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <ShieldCheck class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>
            </div>

            <!-- Unified Table Card Wrapper (Sesuai Standar Feed.vue) -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/60 rounded-2xl shadow-sm overflow-hidden mb-4">
                <!-- Search & Filters Bar (Search, Dropdown Periode, Dropdown Ruangan RS, Dropdown Status, Reset) -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-5 border-b border-slate-100 dark:border-slate-800/60">
                    <!-- Left: Search Box -->
                    <div class="relative w-full lg:w-72 xl:w-80">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama kasi atau unit ruangan..."
                            class="w-full h-10 pl-10 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150 shadow-none"
                        />
                    </div>

                    <!-- Right Controls: Period, Room & Status Filter Dropdowns + Reset -->
                    <div class="flex items-center gap-2.5 w-full lg:w-auto justify-start lg:justify-end flex-wrap">
                        <!-- Period Selector Dropdown (Semua, Hari ini, dll) -->
                        <div class="relative flex-1 sm:flex-initial min-w-[140px]">
                            <select
                                v-model="selectedPeriod"
                                @change="applyFilter(selectedPeriod)"
                                class="w-full sm:w-auto h-10 pl-3.5 pr-8 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer appearance-none shadow-none"
                            >
                                <option v-for="p in periods" :key="p.key" :value="p.key">
                                    {{ p.label }}
                                </option>
                                <option value="custom">📅 Kustom Tanggal...</option>
                            </select>
                            <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
                        </div>

                        <!-- Room Selector Dropdown -->
                        <div v-if="rooms && rooms.length > 0" class="relative flex-1 sm:flex-initial min-w-[150px]">
                            <select
                                v-model="selectedRoomId"
                                @change="applyRoomFilter"
                                class="w-full sm:w-auto h-10 pl-3.5 pr-8 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer appearance-none shadow-none"
                            >
                                <option value="">Semua Ruangan RS</option>
                                <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                            <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
                        </div>

                        <!-- Status Filter Dropdown -->
                        <div class="relative flex-1 sm:flex-initial min-w-[130px]">
                            <select
                                v-model="statusFilter"
                                class="w-full sm:w-auto h-10 pl-3.5 pr-8 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-none appearance-none transition-all duration-150"
                            >
                                <option value="ALL">Semua Status</option>
                                <option value="EXCELLENT">Prima (≥ 80%)</option>
                                <option value="WARNING">Perhatian (50 - 79%)</option>
                                <option value="CRITICAL">Kritis (&lt; 50%)</option>
                            </select>
                            <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
                        </div>

                        <!-- Reset Filter Button -->
                        <button
                            v-if="selectedPeriod !== 'all' || selectedRoomId || customStartDate || customEndDate || searchQuery || statusFilter !== 'ALL'"
                            @click="resetFilter"
                            type="button"
                            class="h-10 px-3 rounded-xl text-xs font-medium text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 bg-slate-100 hover:bg-rose-50 dark:bg-slate-800/90 dark:hover:bg-rose-950/40 border border-slate-200/60 dark:border-slate-700/60 transition cursor-pointer flex items-center gap-1.5 shrink-0"
                            title="Reset filter"
                        >
                            <RotateCcw class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">Reset</span>
                        </button>
                    </div>
                </div>

                <!-- Box Rumus Responsivitas Ringkas & Elegan -->
                <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/30 dark:bg-slate-900/30">
                    <div class="bg-slate-50/80 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 flex items-start gap-2.5 text-xs text-slate-700 dark:text-slate-300">
                        <BarChart3 class="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                        <div class="leading-relaxed text-[11.5px]">
                            <strong class="text-slate-900 dark:text-white font-bold">Rumus Tingkat Responsivitas Kepala Seksi (Kasi):</strong>
                            Tingkat Respons (%) = (Jumlah Laporan Divalidasi / Total Laporan Masuk Unit) × 100%. Kasi dengan tingkat verifikasi di bawah <strong class="text-rose-600 dark:text-rose-400 font-bold">80%</strong> diberi penanda perhatian (<em>Warning Highlight</em>).
                        </div>
                    </div>
                </div>

                <!-- Table View (Standar Resmi RS Seperti di Feed.vue) -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                <th class="px-4 py-4 text-center w-12">NO</th>
                                <th class="px-6 py-4">NAMA KEPALA SEKSI / RUANGAN & UNIT</th>
                                <th class="px-6 py-4 text-center">TOTAL ADUAN</th>
                                <th class="px-6 py-4 text-center">DIVALIDASI</th>
                                <th class="px-6 py-4 text-center">MENUNGGU</th>
                                <th class="px-6 py-4 text-center">KECEPATAN RESPONS</th>
                                <th class="px-6 py-4 text-center">TINGKAT RESPONS (%)</th>
                                <th class="px-6 py-4 text-center">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                            <tr
                                v-for="(kasi, idx) in paginatedKasiData"
                                :key="kasi.unit_id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors duration-150"
                            >
                                <td class="px-4 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-400 dark:text-slate-500">
                                    {{ (currentPage - 1) * perPage + idx + 1 }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">
                                        {{ kasi.kasi_name }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 font-medium">
                                        <Building2 class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                        <span>{{ kasi.unit_name }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-xs font-black text-slate-900 dark:text-white">
                                        {{ kasi.total_reports }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ kasi.verified_reports }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400">
                                        {{ kasi.pending_reports }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700/60">
                                        <Clock class="h-3 w-3 text-slate-400 shrink-0" />
                                        <span>{{ kasi.avg_response_hours }}</span>
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <div class="w-16 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden hidden sm:block">
                                            <div
                                                class="h-full rounded-full transition-all duration-300"
                                                :class="kasi.verification_percentage >= 80 ? 'bg-emerald-500' : (kasi.verification_percentage >= 50 ? 'bg-amber-500' : 'bg-rose-500')"
                                                :style="{ width: `${kasi.verification_percentage}%` }"
                                            ></div>
                                        </div>
                                        <span
                                            class="text-xs font-black"
                                            :class="kasi.verification_percentage >= 80 ? 'text-emerald-600 dark:text-emerald-400' : (kasi.verification_percentage >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400')"
                                        >
                                            {{ kasi.verification_percentage }}%
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span
                                        v-if="kasi.status === 'EXCELLENT'"
                                        class="min-w-[110px] px-3 py-1.5 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 border bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50"
                                    >
                                        <CheckCircle2 class="h-3.5 w-3.5 shrink-0" />
                                        <span>Prima</span>
                                    </span>
                                    <span
                                        v-else-if="kasi.status === 'WARNING'"
                                        class="min-w-[110px] px-3 py-1.5 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 border bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/50"
                                    >
                                        <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                                        <span>Perhatian</span>
                                    </span>
                                    <span
                                        v-else
                                        class="min-w-[110px] px-3 py-1.5 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 border bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50"
                                    >
                                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                        <span>Kritis</span>
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="filteredKasiData.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3 text-slate-400">
                                        <Building2 class="h-12 w-12 text-slate-200 dark:text-slate-700" />
                                        <span class="text-sm font-medium">Tidak ada data unit yang cocok dengan filter atau pencarian saat ini</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer / Interactive Pagination (Sesuai Feed.vue) -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <!-- Left: Per-Page Selector -->
                    <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                        <span class="text-[11px] font-medium">Tampilkan</span>
                        <select
                            v-model="perPage"
                            @change="currentPage = 1"
                            class="h-7 py-0 pl-2 pr-6 text-[11px] font-normal rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 cursor-pointer transition"
                        >
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                        <span class="text-[11px] font-medium">data per halaman</span>
                    </div>

                    <!-- Right: Compact Range & Navigation Buttons -->
                    <div class="flex items-center gap-3">
                        <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                            {{ startItemIndex }}–{{ endItemIndex }} dari {{ filteredKasiData.length }}
                        </span>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="h-7 w-7 rounded-lg flex items-center justify-center border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150 cursor-pointer"
                                aria-label="Halaman sebelumnya"
                            >
                                <ChevronLeft class="h-3.5 w-3.5" />
                            </button>
                            <button
                                type="button"
                                @click="goToPage(currentPage + 1)"
                                :disabled="currentPage >= totalPages"
                                class="h-7 w-7 rounded-lg flex items-center justify-center border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150 cursor-pointer"
                                aria-label="Halaman berikutnya"
                            >
                                <ChevronRight class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Date Range Filter Modal (Persis Seperti di Dashboard) -->
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
                        @click="closeCustomDateModal"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
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
                        @click="closeCustomDateModal"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitCustomDateFilter"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition cursor-pointer"
                    >
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
