<script setup>
import { ref, computed } from 'vue';
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
    FileSpreadsheet,
    ArrowLeft
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
        default: () => ({ period: '30d', period_label: '30 Hari Terakhir', room_id: null })
    }
});

const searchQuery = ref('');
const selectedPeriod = ref(props.filters.period || '30d');
const selectedRoomId = ref(props.filters.room_id || '');

const periods = [
    { key: 'today', label: 'Hari Ini' },
    { key: '7d', label: '7 Hari' },
    { key: '30d', label: '30 Hari' },
    { key: 'this_month', label: 'Bulan Ini' },
    { key: 'all', label: 'Semua' },
];

const applyFilter = (periodKey) => {
    selectedPeriod.value = periodKey;
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
        room_id: selectedRoomId.value || undefined
    }, {
        preserveState: true,
        replace: true
    });
};

const filteredKasiData = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return props.kasiData;
    return props.kasiData.filter(k => 
        (k.kasi_name && k.kasi_name.toLowerCase().includes(q)) ||
        (k.unit_name && k.unit_name.toLowerCase().includes(q))
    );
});
</script>

<template>
    <Head title="Responsivitas & Akuntabilitas Kasi" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel (Asli Utuh) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3.5">
                    <Link
                        :href="route('executive.dashboard')"
                        class="h-10 w-10 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center transition shrink-0"
                        title="Kembali ke Command Center"
                    >
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Laporan Responsivitas Kepala Seksi
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Pemantauan kecepatan respons & tingkat verifikasi aduan unit oleh Kepala Ruangan / Kasi.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('reports.index')"
                        class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-2 shadow-sm transition"
                    >
                        <FileSpreadsheet class="h-4 w-4" />
                        <span>Ekspor Laporan (PDF/Excel)</span>
                    </Link>
                </div>
            </div>

            <!-- Filter Toolbar (Container Terpisah Rapi) -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/80 p-4 sm:p-5 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3.5">
                <!-- Period Selector Buttons -->
                <div class="flex items-center flex-wrap gap-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mr-1 flex items-center gap-1">
                        <Calendar class="h-3.5 w-3.5 text-emerald-500" />
                        Periode:
                    </span>
                    <button
                        v-for="p in periods"
                        :key="p.key"
                        type="button"
                        @click="applyFilter(p.key)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer',
                            selectedPeriod === p.key
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
                        ]"
                    >
                        {{ p.label }}
                    </button>
                </div>

                <!-- Search and Room Dropdown -->
                <div class="flex items-center gap-3">
                    <div class="relative w-full sm:w-60">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari kasi atau unit..."
                            class="w-full h-9 pl-9 pr-3 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="relative min-w-[180px]">
                        <select
                            v-model="selectedRoomId"
                            @change="applyRoomFilter"
                            class="w-full h-9 pl-3 pr-8 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-emerald-500 appearance-none"
                        >
                            <option value="">Semua Ruangan RS</option>
                            <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </select>
                        <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
                    </div>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Aduan Unit</span>
                        <div class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">{{ summary.total_reports }}</div>
                        <span class="text-[11px] text-slate-400">Seluruh unit pada periode ini</span>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center justify-center">
                        <Building2 class="h-5 w-5" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Telah Diverifikasi</span>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ summary.total_verified }}</div>
                        <span class="text-[11px] text-slate-400">Poin KPI staf telah diberikan</span>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <CheckCircle2 class="h-5 w-5" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Menunggu Tindak Lanjut</span>
                        <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-0.5">{{ summary.total_pending }}</div>
                        <span class="text-[11px] text-slate-400">Menunggu verifikasi Kasi</span>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <Clock class="h-5 w-5" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Rata-rata Tingkat Verifikasi</span>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ summary.avg_verification_rate }}%</div>
                        <span class="text-[11px] text-slate-400">Persentase verifikasi RS</span>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                </div>
            </div>

            <!-- Content Card Container Table -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Tabel Pemantauan Akuntabilitas Kasi Unit</h3>
                    <span class="text-xs text-slate-400">Total: {{ filteredKasiData.length }} Unit Ruangan</span>
                </div>

                <!-- Rumus Responsivitas -->
                <div class="bg-slate-50/80 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3.5 flex items-start gap-3 text-xs text-slate-700 dark:text-slate-300">
                    <BarChart3 class="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                    <div class="leading-relaxed">
                        <strong class="text-slate-900 dark:text-white block font-bold mb-0.5">Rumus Tingkat Responsivitas Supervisor:</strong>
                        Tingkat Respons (%) = (Jumlah Laporan Divalidasi / Total Laporan Masuk Unit) × 100%. Kasi dengan tingkat verifikasi di bawah <strong>80%</strong> akan diberi penanda perhatian (*Warning Highlight*).
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 uppercase tracking-wider font-extrabold border-b border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <tr>
                                <th class="py-3.5 px-4">Nama Supervisor & Unit</th>
                                <th class="py-3.5 px-4 text-center">Total Aduan</th>
                                <th class="py-3.5 px-4 text-center">Divalidasi</th>
                                <th class="py-3.5 px-4 text-center">Menunggu</th>
                                <th class="py-3.5 px-4 text-center">Kecepatan Respons</th>
                                <th class="py-3.5 px-4 text-center">Tingkat Respons (%)</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                            <tr v-if="filteredKasiData.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    Tidak ada data unit yang cocok dengan filter atau pencarian saat ini.
                                </td>
                            </tr>
                            <tr
                                v-for="kasi in filteredKasiData"
                                :key="kasi.unit_id"
                                :class="[
                                    'transition',
                                    kasi.status === 'CRITICAL' ? 'bg-rose-50/40 dark:bg-rose-950/20' : (kasi.status === 'WARNING' ? 'bg-amber-50/40 dark:bg-amber-950/20' : 'hover:bg-slate-50/60 dark:hover:bg-slate-950/40')
                                ]"
                            >
                                <td class="py-4 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ kasi.kasi_name }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                        <Building2 class="h-3 w-3" />
                                        {{ kasi.unit_name }}
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-center font-black">{{ kasi.total_reports }}</td>
                                <td class="py-4 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400">{{ kasi.verified_reports }}</td>
                                <td class="py-4 px-4 text-center font-bold text-amber-600 dark:text-amber-400">{{ kasi.pending_reports }}</td>
                                <td class="py-4 px-4 text-center">
                                    <span class="font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                                        {{ kasi.avg_response_hours }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center font-bold">
                                    {{ kasi.verification_percentage }}%
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase inline-block',
                                            kasi.status === 'EXCELLENT'
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                : (kasi.status === 'WARNING' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300')
                                        ]"
                                    >
                                        {{ kasi.status === 'EXCELLENT' ? 'Prima' : (kasi.status === 'WARNING' ? 'Perhatian' : 'Kritis') }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
