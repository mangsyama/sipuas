<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    FileText, 
    AlertCircle, 
    CheckCircle2, 
    Clock, 
    Sparkles, 
    UserCheck, 
    Search, 
    ArrowUpRight,
    Building2,
    ShieldCheck,
    History,
    Table as TableIcon,
    LayoutList,
    User,
    Smile,
    Frown,
    Meh,
    Calendar,
    MapPin,
    Phone,
    ArrowRight,
    Inbox,
    ChevronDown,
    ChevronLeft,
    ChevronRight
} from '@lucide/vue';

const props = defineProps({
    initialReports: {
        type: Array,
        default: null
    }
});

const activeTab = ref('ALL');
const searchQuery = ref('');

// Auto-select 'FEED' (Kartu) on mobile responsive screens (< 768px)
const isMobileView = () => typeof window !== 'undefined' && window.innerWidth < 768;
const viewMode = ref(isMobileView() ? 'FEED' : 'TABLE');

const reports = ref(props.initialReports || []);

// Pagination State
const currentPage = ref(1);
const perPage = ref(10);

const filteredReports = computed(() => {
    return reports.value.filter(r => {
        const matchesTab = activeTab.value === 'ALL' || 
            (activeTab.value === 'PENDING' && r.status === 'PENDING') ||
            (activeTab.value === 'VERIFIED' && r.status === 'VERIFIED');
        const q = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !q || 
            r.isi_laporan.toLowerCase().includes(q) ||
            r.id.toLowerCase().includes(q) ||
            (r.target_object && r.target_object.toLowerCase().includes(q)) ||
            (r.reporter_name && r.reporter_name.toLowerCase().includes(q));
        return matchesTab && matchesSearch;
    });
});

// Reset pagination when filters change
watch([searchQuery, activeTab], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => {
    return Math.ceil(filteredReports.value.length / perPage.value) || 1;
});

const paginatedReports = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredReports.value.slice(start, start + perPage.value);
});

const startItemIndex = computed(() => {
    if (filteredReports.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const endItemIndex = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredReports.value.length);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const handleResize = () => {
    if (isMobileView()) {
        viewMode.value = 'FEED';
    }
};

onMounted(() => {
    if (isMobileView()) {
        viewMode.value = 'FEED';
    }
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', handleResize);
    }
});

const stats = computed(() => {
    const total = reports.value.length;
    const pending = reports.value.filter(r => r.status === 'PENDING').length;
    const verified = reports.value.filter(r => r.status === 'VERIFIED').length;
    return { total, pending, verified };
});
</script>

<template>
    <Head title="Feed Aduan Unit" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <FileText class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Feed Aduan Masuk Unit
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Verifikasi aduan staf bertugas dan kelola saldo mutasi poin KPI unit secara transparan.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Top Stats Grid (Sesuai Desain & Ukuran Dashboard Utama) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Card 1: Total Aduan Unit -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Aduan Unit</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.total }}</div>
                        <span class="text-[11px] text-slate-400 block">Seluruh laporan masuk di unit ini</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <FileText class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <!-- Card 2: Perlu Verifikasi -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 block">Perlu Verifikasi</span>
                        <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 leading-tight">{{ stats.pending }}</div>
                        <span class="text-[11px] text-slate-400 block">Menunggu verifikasi petugas</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-950/40">
                        <AlertCircle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>

                <!-- Card 3: Telah Divalidasi -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Telah Divalidasi</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.verified }}</div>
                        <span class="text-[11px] text-slate-400 block">Poin KPI staf telah di-update</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                        <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
            </div>

            <!-- Unified Table Card Wrapper (Sesuai Referensi Pesupeluh) -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/60 rounded-2xl shadow-sm overflow-hidden mb-4">
                <!-- Search & Custom Tab Controls (Combined Header) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 border-b border-slate-100 dark:border-slate-800/60">
                    <!-- Left: Search Box -->
                    <div class="relative w-full sm:w-80 xl:w-96">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari tiket, pelapor, atau uraian..."
                            class="w-full h-10 pl-10 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150 shadow-none"
                        />
                    </div>

                    <!-- Right Controls: Status Filter Dropdown & View Mode Switcher -->
                    <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                        <!-- Status Filter Dropdown -->
                        <div class="relative flex-1 sm:flex-initial">
                            <select
                                v-model="activeTab"
                                class="w-full sm:w-auto h-10 pl-3.5 pr-9 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-none appearance-none transition-all duration-150"
                            >
                                <option value="ALL">Semua Feed</option>
                                <option value="PENDING">Perlu Verifikasi</option>
                                <option value="VERIFIED">Selesai</option>
                            </select>
                            <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
                        </div>

                        <!-- Segmented View Mode Switcher (Aligned to h-10) -->
                        <div class="h-10 flex items-center bg-slate-100/80 dark:bg-slate-950/45 p-1 rounded-xl shrink-0 border border-slate-200/60 dark:border-slate-800/40">
                            <button
                                type="button"
                                @click="viewMode = 'TABLE'"
                                title="Tampilan Tabel Standar"
                                :class="[
                                    'h-full flex items-center gap-1.5 px-3.5 rounded-lg text-xs transition cursor-pointer',
                                    viewMode === 'TABLE' 
                                        ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm font-semibold' 
                                        : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'
                                ]"
                            >
                                <TableIcon class="h-3.5 w-3.5" />
                                <span class="hidden sm:inline">Tabel</span>
                            </button>
                            <button
                                type="button"
                                @click="viewMode = 'FEED'"
                                title="Tampilan Kartu"
                                :class="[
                                    'h-full flex items-center gap-1.5 px-3.5 rounded-lg text-xs transition cursor-pointer',
                                    viewMode === 'FEED' 
                                        ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm font-semibold' 
                                        : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'
                                ]"
                            >
                                <LayoutList class="h-3.5 w-3.5" />
                                <span class="hidden sm:inline">Kartu</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TIPE 1: VIEW TABEL NORMAL (REFERENSI PESUPELUH) -->
                <div v-if="viewMode === 'TABLE'" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                <th class="px-4 py-4 text-center w-12">NO</th>
                                <th class="px-6 py-4">NO. TIKET / WAKTU</th>
                                <th class="px-6 py-4">PELAPOR</th>
                                <th class="px-6 py-4">SASARAN / RUANGAN</th>
                                <th class="px-6 py-4 min-w-[280px]">PENJELASAN MASALAH</th>
                                <th class="px-6 py-4 text-center">SENTIMEN AI</th>
                                <th class="px-6 py-4 text-center">STATUS</th>
                                <th class="px-6 py-4 text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                            <tr 
                                v-for="(item, idx) in paginatedReports" 
                                :key="item.id" 
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors duration-150"
                            >
                                <!-- NO -->
                                <td class="px-4 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-400 dark:text-slate-500">
                                    {{ (currentPage - 1) * perPage + idx + 1 }}
                                </td>

                                <!-- NO. TIKET / WAKTU (No. Tiket & Tanggal Jam WITA) -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">{{ item.id }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                                        {{ item.created_at_full || (item.created_at + ' WITA') }}
                                    </div>
                                </td>

                                <!-- PELAPOR (Nama & No. HP dengan Icon) -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">
                                        {{ item.reporter_name || 'Anonim' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 font-medium">
                                        <Phone class="h-3 w-3 text-slate-400 shrink-0" />
                                        <span>{{ item.reporter_phone || '-' }}</span>
                                    </div>
                                </td>

                                <!-- SASARAN / RUANGAN -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">
                                        {{ item.target_object || '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 font-medium">
                                        <MapPin class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                        <span>{{ item.unit || '-' }}</span>
                                    </div>
                                </td>

                                <!-- PENJELASAN MASALAH (Kategori ter-highlight di atas, Teks aduan di bawah) -->
                                <td class="px-6 py-4 text-xs">
                                    <div class="mb-2">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/70 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            {{ item.ai_category || 'Pelayanan' }}
                                        </span>
                                    </div>
                                    <p class="text-slate-700 dark:text-slate-300 font-medium leading-relaxed break-words max-w-lg">
                                        "{{ item.isi_laporan }}"
                                    </p>
                                </td>

                                <!-- SENTIMEN AI -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span :class="[
                                        'w-24 py-1.5 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 border uppercase',
                                        item.ai_sentiment === 'NEGATIF' ? 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50' :
                                        (item.ai_sentiment === 'POSITIF' ? 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50' : 'bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900/50')
                                    ]">
                                        <Frown v-if="item.ai_sentiment === 'NEGATIF'" class="h-3.5 w-3.5 shrink-0" />
                                        <Smile v-else-if="item.ai_sentiment === 'POSITIF'" class="h-3.5 w-3.5 shrink-0" />
                                        <Meh v-else class="h-3.5 w-3.5 shrink-0" />
                                        <span>{{ item.ai_sentiment || '-' }}</span>
                                    </span>
                                </td>

                                <!-- STATUS -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="item.status === 'PENDING'" class="min-w-[135px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 border bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/50">
                                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                        <span>Perlu Verifikasi</span>
                                    </span>
                                    <span v-else class="min-w-[135px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 border bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50" :title="item.verified_by ? `Divalidasi oleh ${item.verified_by}` : ''">
                                        <CheckCircle2 class="h-3.5 w-3.5 shrink-0" />
                                        <span>Terverifikasi</span>
                                    </span>
                                </td>

                                <!-- AKSI -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center">
                                        <Link
                                            :href="route('kasi.verify', { id: item.id })"
                                            :class="[
                                                'w-28 py-2 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 transition-all duration-150 border',
                                                item.status === 'PENDING'
                                                    ? 'bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 border-transparent shadow-sm'
                                                    : 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700'
                                            ]"
                                        >
                                            <span>{{ item.status === 'PENDING' ? 'Verifikasi' : 'Detail' }}</span><ArrowUpRight class="h-3.5 w-3.5 flex-shrink-0" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredReports.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3 text-slate-400">
                                        <FileText class="h-12 w-12 text-slate-200 dark:text-slate-700" />
                                        <span class="text-sm font-medium">Tidak ada data tiket di antrean ini</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- TIPE 2: VIEW KARTU / FEED (PERSIS FORMAT UNITHEADINDEX PESUPELUH DI SEMUA UKURAN LAYAR) -->
                <div v-else class="p-4 sm:p-5 bg-slate-50/30 dark:bg-slate-950/10 border-t border-slate-100 dark:border-slate-800/60">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="item in paginatedReports"
                            :key="'feed-card-' + item.id"
                            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm space-y-3 transition-all duration-150 flex flex-col justify-between"
                        >
                            <!-- Header: No Tiket di kiri, Status di kanan -->
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-extrabold text-xs text-slate-900 dark:text-white">{{ item.id }}</span>
                                <span
                                    :class="[
                                        'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border shrink-0',
                                        item.status === 'PENDING'
                                            ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800'
                                            : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-white/10 dark:text-white dark:border-white/20'
                                    ]"
                                >
                                    {{ item.status === 'PENDING' ? 'Perlu Verifikasi' : 'Terverifikasi' }}
                                </span>
                            </div>

                            <!-- Teks Aduan Singkat -->
                            <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                "{{ item.isi_laporan }}"
                            </p>

                            <!-- Key-Value Info Box Sesuai Pesupeluh -->
                            <div class="text-[11px] space-y-1.5 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800/50">
                                <div class="flex justify-between items-center gap-2">
                                    <span class="font-medium text-slate-400 dark:text-slate-500 shrink-0">Pelapor:</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-right truncate">
                                        {{ item.reporter_name || 'Anonim' }}
                                        <span v-if="item.reporter_phone" class="text-slate-400 font-normal text-[10px]">
                                            ({{ item.reporter_phone }})
                                        </span>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <span class="font-medium text-slate-400 dark:text-slate-500 shrink-0">Sasaran:</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-right truncate">{{ item.target_object || '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <span class="font-medium text-slate-400 dark:text-slate-500 shrink-0">Ruangan:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-right truncate">{{ item.unit || '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <span class="font-medium text-slate-400 dark:text-slate-500 shrink-0">Kategori:</span>
                                    <span class="font-bold text-emerald-700 dark:text-emerald-400 text-right truncate">{{ item.ai_category || 'Pelayanan' }}</span>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <span class="font-medium text-slate-400 dark:text-slate-500 shrink-0">Sentimen:</span>
                                    <span :class="[
                                        'px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase border inline-flex items-center gap-1',
                                        item.ai_sentiment === 'NEGATIF' ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:border-rose-800' :
                                        (item.ai_sentiment === 'POSITIF' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-white/10 dark:text-white dark:border-white/20' : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800')
                                    ]">
                                        <Frown v-if="item.ai_sentiment === 'NEGATIF'" class="h-2.5 w-2.5 shrink-0" />
                                        <Smile v-else-if="item.ai_sentiment === 'POSITIF'" class="h-2.5 w-2.5 shrink-0" />
                                        <Meh v-else class="h-2.5 w-2.5 shrink-0" />
                                        <span>{{ item.ai_sentiment || '-' }}</span>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <span class="font-medium text-slate-400 dark:text-slate-500 shrink-0">Tanggal:</span>
                                    <span class="font-medium text-slate-700 dark:text-slate-300 text-right">{{ item.created_at_full || (item.created_at + ' WITA') }}</span>
                                </div>
                            </div>

                            <!-- Tombol Aksi Membentang Penuh Sesuai Pesupeluh -->
                            <div class="flex items-center gap-2 pt-0 mt-auto">
                                <Link
                                    :href="route('kasi.verify', { id: item.id })"
                                    :class="[
                                        'flex-1 py-2.5 rounded-xl text-xs font-bold text-center flex items-center justify-center gap-1.5 transition-all duration-150 border',
                                        item.status === 'PENDING'
                                            ? 'bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 font-extrabold border-transparent shadow-sm'
                                            : 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    <span>{{ item.status === 'PENDING' ? 'Verifikasi Aduan' : 'Lihat Detail' }}</span>
                                    <ArrowUpRight class="h-3.5 w-3.5 flex-shrink-0" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredReports.length === 0" class="py-16 text-center text-slate-400">
                        <div class="flex flex-col items-center gap-3">
                            <FileText class="h-12 w-12 text-slate-200 dark:text-slate-700" />
                            <span class="text-sm font-medium">Tidak ada data tiket di antrean ini</span>
                        </div>
                    </div>
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
                        <span class="text-[11px] font-medium">data</span>
                    </div>

                    <!-- Right: Compact Range & Navigation Buttons -->
                    <div class="flex items-center gap-3">
                        <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                            {{ startItemIndex }}–{{ endItemIndex }} dari {{ filteredReports.length }}
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
                                :disabled="currentPage === totalPages"
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
    </AuthenticatedLayout>
</template>
