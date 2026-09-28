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
    AlertCircle,
    Users,
    UserCheck,
    ThumbsUp,
    ThumbsDown,
    Ban,
    FileText,
    Activity
} from '@lucide/vue';

const props = defineProps({
    kasiOfficers: {
        type: Array,
        default: () => []
    },
    unitData: {
        type: Array,
        default: () => []
    },
    kasiData: {
        type: Array,
        default: () => []
    },
    summary: {
        type: Object,
        default: () => ({ 
            total_reports: 0, 
            total_verified: 0, 
            total_pending: 0, 
            avg_verification_rate: 100,
            total_kasi_count: 0
        })
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

const activeTab = ref('OFFICERS'); // 'OFFICERS' | 'UNITS'
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

    router.get(route('kabid.kasi-responsiveness'), {
        period: periodKey,
        room_id: selectedRoomId.value || undefined
    }, {
        preserveState: true,
        replace: true
    });
};

const applyRoomFilter = () => {
    router.get(route('kabid.kasi-responsiveness'), {
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
    router.get(route('kabid.kasi-responsiveness'), {
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
    router.get(route('kabid.kasi-responsiveness'), {}, {
        preserveState: true,
        replace: true
    });
};

// Data Kasi Officers Filtered
const filteredOfficers = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    return (props.kasiOfficers || []).filter(k => {
        const matchesSearch = !q || 
            (k.name && k.name.toLowerCase().includes(q)) ||
            (k.nip && k.nip.toLowerCase().includes(q)) ||
            (k.username && k.username.toLowerCase().includes(q));
        return matchesSearch;
    });
});

// Data Unit Ruangan Filtered
const effectiveUnits = computed(() => props.unitData?.length ? props.unitData : props.kasiData || []);
const filteredUnits = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    return effectiveUnits.value.filter(u => {
        const matchesSearch = !q || 
            (u.unit_name && u.unit_name.toLowerCase().includes(q)) ||
            (u.verifier_display && u.verifier_display.toLowerCase().includes(q));
        const matchesStatus = statusFilter.value === 'ALL' || u.status === statusFilter.value;
        return matchesSearch && matchesStatus;
    });
});

// Interactive Pagination Logic
const currentPage = ref(1);
const perPage = ref(10);

watch([searchQuery, statusFilter, activeTab], () => {
    currentPage.value = 1;
});

const currentDataset = computed(() => {
    return activeTab.value === 'OFFICERS' ? filteredOfficers.value : filteredUnits.value;
});

const totalPages = computed(() => {
    return Math.ceil(currentDataset.value.length / perPage.value) || 1;
});

const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return currentDataset.value.slice(start, start + perPage.value);
});

const startItemIndex = computed(() => {
    if (currentDataset.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const endItemIndex = computed(() => {
    return Math.min(currentPage.value * perPage.value, currentDataset.value.length);
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
            <!-- Header Panel -->
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
                            Pemantauan kinerja verifikasi dan kecepatan tanggap Tim Kepala Seksi (Kasi) terhadap aduan di seluruh unit rumah sakit.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Top 4 KPI Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- Metric 1: Total Aduan RS -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Aduan Masuk</span>
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
                        <span class="text-[11px] text-slate-400 block">Selesai ditindaklanjuti Kasi</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <!-- Metric 3: Menunggu Tindak Lanjut -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Menunggu Verifikasi</span>
                        <div class="text-3xl font-black text-amber-600 dark:text-amber-400 leading-tight">
                            {{ summary.total_pending }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">Antrean tindak lanjut Kasi</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-950/40">
                        <Clock class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>

                <!-- Metric 4: Total Kepala Seksi Terdaftar -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Tim Kepala Seksi</span>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                            {{ summary.total_kasi_count || (props.kasiOfficers ? props.kasiOfficers.length : 4) }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">Verifikator aktif rumah sakit</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <Users class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>
            </div>

            <!-- Unified Table Card Wrapper -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/60 rounded-2xl shadow-sm overflow-hidden mb-4">
                
                <!-- Tab Switching Bar -->
                <div class="px-5 pt-4 border-b border-slate-100 dark:border-slate-800/60 flex items-center gap-2 overflow-x-auto">
                    <button
                        type="button"
                        @click="activeTab = 'OFFICERS'"
                        :class="[
                            'px-4 py-2.5 rounded-t-xl text-xs font-bold flex items-center gap-2 border-b-2 transition cursor-pointer whitespace-nowrap',
                            activeTab === 'OFFICERS'
                                ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/20'
                                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                        ]"
                    >
                        <UserCheck class="h-4 w-4" />
                        <span>Tim Kepala Seksi (Kasi)</span>
                        <span :class="[
                            'px-2 py-0.5 rounded-full text-[10px] font-extrabold',
                            activeTab === 'OFFICERS' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'
                        ]">
                            {{ props.kasiOfficers?.length || 0 }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'UNITS'"
                        :class="[
                            'px-4 py-2.5 rounded-t-xl text-xs font-bold flex items-center gap-2 border-b-2 transition cursor-pointer whitespace-nowrap',
                            activeTab === 'UNITS'
                                ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/20'
                                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                        ]"
                    >
                        <Building2 class="h-4 w-4" />
                        <span>Responsivitas per Unit / Ruangan</span>
                        <span :class="[
                            'px-2 py-0.5 rounded-full text-[10px] font-extrabold',
                            activeTab === 'UNITS' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'
                        ]">
                            {{ effectiveUnits.length }}
                        </span>
                    </button>
                </div>

                <!-- Search & Filters Bar -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-5 border-b border-slate-100 dark:border-slate-800/60">
                    <!-- Left: Search Box -->
                    <div class="relative w-full lg:w-72 xl:w-80">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="activeTab === 'OFFICERS' ? 'Cari nama Kasi atau NIP...' : 'Cari unit ruangan atau Kasi...'"
                            class="w-full h-10 pl-10 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150 shadow-none"
                        />
                    </div>

                    <!-- Right Controls: Period, Room & Status Filter Dropdowns + Reset -->
                    <div class="flex items-center gap-2.5 w-full lg:w-auto justify-start lg:justify-end flex-wrap">
                        <!-- Period Selector Dropdown -->
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

                        <!-- Room Selector Dropdown (Khusus Tab Unit) -->
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

                        <!-- Status Filter Dropdown (Khusus Tab Unit) -->
                        <div v-if="activeTab === 'UNITS'" class="relative flex-1 sm:flex-initial min-w-[130px]">
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

                <!-- Info Callout -->
                <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/30 dark:bg-slate-900/30">
                    <div class="bg-slate-50/80 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 flex items-start gap-2.5 text-xs text-slate-700 dark:text-slate-300">
                        <BarChart3 class="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                        <div class="leading-relaxed text-[11.5px] space-y-1">
                            <template v-if="activeTab === 'OFFICERS'">
                                <div>
                                    <strong class="text-slate-900 dark:text-white font-bold">Kinerja Tim Kepala Seksi:</strong>
                                    Rekapitulasi total aduan yang divalidasi, rata-rata kecepatan tanggap (selisih waktu aduan masuk s/d diverifikasi), dan unit ruangan yang ditangani oleh masing-masing user Kasi pada periode ini.
                                </div>
                            </template>
                            <template v-else>
                                <div>
                                    <strong class="text-slate-900 dark:text-white font-bold">Tingkat Responsivitas per Unit:</strong>
                                    Tingkat Respons (%) = (Jumlah Laporan Divalidasi / Total Laporan Masuk Unit) × 100%. Menampilkan data Kasi yang memproses verifikasi di unit tersebut.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- TAB 1: DAFTAR USER KEPALA SEKSI (KASI OFFICERS)               -->
                <!-- ============================================================= -->
                <div v-if="activeTab === 'OFFICERS'" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                <th class="px-4 py-4 text-center w-12">NO</th>
                                <th class="px-6 py-4">NAMA KEPALA SEKSI</th>
                                <th class="px-6 py-4 text-center">TOTAL VERIFIKASI</th>
                                <th class="px-6 py-4 text-center">KECEPATAN RESPONS</th>
                                <th class="px-6 py-4">UNIT DITANGANI</th>
                                <th class="px-6 py-4 text-center">AKTIVITAS TERAKHIR</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                            <tr
                                v-for="(kasi, idx) in paginatedData"
                                :key="kasi.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors duration-150"
                            >
                                <td class="px-4 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-400 dark:text-slate-500">
                                    {{ (currentPage - 1) * perPage + idx + 1 }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ kasi.name ? kasi.name.charAt(0) : 'K' }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white text-xs">
                                                {{ kasi.name }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                                                NIP: {{ kasi.nip }} • @{{ kasi.username }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800">
                                        <CheckCircle2 class="h-3.5 w-3.5" />
                                        <span>{{ kasi.verified_count }} Aduan</span>
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700/60">
                                        <Clock class="h-3 w-3 text-slate-400 shrink-0" />
                                        <span>{{ kasi.avg_response_time }}</span>
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div v-if="kasi.handled_rooms && kasi.handled_rooms.length > 0" class="flex flex-wrap gap-1.5 max-w-sm">
                                        <span
                                            v-for="(roomName, rIdx) in kasi.handled_rooms"
                                            :key="rIdx"
                                            class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                                        >
                                            {{ roomName }}
                                        </span>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 italic">Belum ada unit ditangani</span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-slate-500 dark:text-slate-400 font-medium">
                                    {{ kasi.last_verified_at }}
                                </td>
                            </tr>

                            <tr v-if="paginatedData.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3 text-slate-400">
                                        <Users class="h-12 w-12 text-slate-200 dark:text-slate-700" />
                                        <span class="text-sm font-medium">Tidak ada data Kepala Seksi yang cocok dengan filter atau pencarian</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ============================================================= -->
                <!-- TAB 2: DAFTAR RESPONSIVITAS PER RUANGAN / UNIT RS             -->
                <!-- ============================================================= -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                <th class="px-4 py-4 text-center w-12">NO</th>
                                <th class="px-6 py-4">UNIT RUANGAN RS</th>
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
                                v-for="(unit, idx) in paginatedData"
                                :key="unit.unit_id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors duration-150"
                            >
                                <td class="px-4 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-400 dark:text-slate-500">
                                    {{ (currentPage - 1) * perPage + idx + 1 }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">
                                        {{ unit.unit_name }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-xs font-black text-slate-900 dark:text-white">
                                        {{ unit.total_reports }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ unit.verified_reports }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400">
                                        {{ unit.pending_reports }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700/60">
                                        <Clock class="h-3 w-3 text-slate-400 shrink-0" />
                                        <span>{{ unit.avg_response_hours }}</span>
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <div class="w-16 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden hidden sm:block">
                                            <div
                                                class="h-full rounded-full transition-all duration-300"
                                                :class="unit.verification_percentage >= 80 ? 'bg-emerald-500' : (unit.verification_percentage >= 50 ? 'bg-amber-500' : 'bg-rose-500')"
                                                :style="{ width: `${unit.verification_percentage}%` }"
                                            ></div>
                                        </div>
                                        <span
                                            class="text-xs font-black"
                                            :class="unit.verification_percentage >= 80 ? 'text-emerald-600 dark:text-emerald-400' : (unit.verification_percentage >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400')"
                                        >
                                            {{ unit.verification_percentage }}%
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span
                                        v-if="unit.status === 'EXCELLENT'"
                                        class="min-w-[100px] px-3 py-1.5 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 border bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50"
                                    >
                                        <CheckCircle2 class="h-3.5 w-3.5 shrink-0" />
                                        <span>Prima</span>
                                    </span>
                                    <span
                                        v-else-if="unit.status === 'WARNING'"
                                        class="min-w-[100px] px-3 py-1.5 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 border bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/50"
                                    >
                                        <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                                        <span>Perhatian</span>
                                    </span>
                                    <span
                                        v-else
                                        class="min-w-[100px] px-3 py-1.5 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 border bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50"
                                    >
                                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                        <span>Kritis</span>
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="paginatedData.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3 text-slate-400">
                                        <Building2 class="h-12 w-12 text-slate-200 dark:text-slate-700" />
                                        <span class="text-sm font-medium">Tidak ada data unit yang cocok dengan filter atau pencarian</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer / Interactive Pagination -->
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
                            {{ startItemIndex }}–{{ endItemIndex }} dari {{ currentDataset.length }}
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
