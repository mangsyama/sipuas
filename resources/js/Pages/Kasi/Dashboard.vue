<script setup>
import { ref, computed } from 'vue';
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
    LayoutList
} from '@lucide/vue';

const props = defineProps({
    initialReports: {
        type: Array,
        default: null
    }
});

const activeTab = ref('ALL');
const searchQuery = ref('');
const viewMode = ref('TABLE'); // 'TABLE' (default) or 'FEED'

const reports = ref(props.initialReports || []);

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
                            Verifikasi shift dinas staf dan kelola saldo mutasi poin KPI unit secara transparan.
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
                        <span class="text-[11px] text-slate-400 block">Menunggu pencocokan shift staf</span>
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

            <!-- Content Card Container with Dual View Switcher -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <!-- Toolbar: Filter Tabs, Search, & View Mode Switcher -->
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    <!-- Tab Filters -->
                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl w-full sm:w-auto overflow-x-auto">
                        <button
                            @click="activeTab = 'ALL'"
                            :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap', activeTab === 'ALL' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white']"
                        >
                            Semua Feed ({{ stats.total }})
                        </button>
                        <button
                            @click="activeTab = 'PENDING'"
                            :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap', activeTab === 'PENDING' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white']"
                        >
                            Perlu Verifikasi ({{ stats.pending }})
                        </button>
                        <button
                            @click="activeTab = 'VERIFIED'"
                            :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap', activeTab === 'VERIFIED' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white']"
                        >
                            Selesai ({{ stats.verified }})
                        </button>
                    </div>

                    <!-- Right Controls: Search Box & View Mode Switcher -->
                    <div class="flex items-center gap-2.5 w-full lg:w-auto">
                        <div class="relative flex-1 sm:w-64">
                            <Search class="h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari tiket / uraian / staf..."
                                class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs outline-none focus:outline-none focus:ring-0 focus:border-slate-300 dark:focus:border-slate-700"
                            />
                        </div>

                        <!-- Segmented View Mode Switcher -->
                        <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl shrink-0 border border-slate-200/60 dark:border-slate-700/60">
                            <button
                                type="button"
                                @click="viewMode = 'TABLE'"
                                title="Tampilan Tabel Standar (Default)"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer outline-none focus:outline-none focus:ring-0',
                                    viewMode === 'TABLE' 
                                        ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                                        : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                                ]"
                            >
                                <TableIcon class="h-3.5 w-3.5" />
                                <span class="hidden sm:inline">Tabel</span>
                            </button>
                            <button
                                type="button"
                                @click="viewMode = 'FEED'"
                                title="Tampilan Kartu / Feed Visual"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer outline-none focus:outline-none focus:ring-0',
                                    viewMode === 'FEED' 
                                        ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                                        : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                                ]"
                            >
                                <LayoutList class="h-3.5 w-3.5" />
                                <span class="hidden sm:inline">Kartu</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TIPE 1: VIEW TABEL NORMAL (DEFAULT) -->
                <div v-if="viewMode === 'TABLE'" class="pt-2 animate-spa-fade-in">
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="py-3.5 px-4 whitespace-nowrap">No. Tiket</th>
                                    <th class="py-3.5 px-4 whitespace-nowrap">Waktu & Shift</th>
                                    <th class="py-3.5 px-4 min-w-[260px]">Isi Laporan Pasien</th>
                                    <th class="py-3.5 px-4 whitespace-nowrap">Sasaran / Staf</th>
                                    <th class="py-3.5 px-4 text-center whitespace-nowrap">Sentimen AI</th>
                                    <th class="py-3.5 px-4 text-center whitespace-nowrap">Status Verifikasi</th>
                                    <th class="py-3.5 px-4 text-right whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                                <tr 
                                    v-for="item in filteredReports" 
                                    :key="item.id" 
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition duration-150"
                                >
                                    <!-- No. Tiket -->
                                    <td class="py-3.5 px-4 whitespace-nowrap font-mono">
                                        <span class="font-extrabold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-white/10 px-2.5 py-1 rounded-md border border-emerald-200/60 dark:border-white/10 text-[11px] inline-block">
                                            {{ item.id }}
                                        </span>
                                    </td>

                                    <!-- Waktu & Shift -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="space-y-0.5">
                                            <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 text-[11px]">
                                                <Clock class="h-3 w-3 text-slate-400 shrink-0" />
                                                <span>{{ item.created_at }}</span>
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-medium">
                                                {{ item.shift_info }}
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Isi Laporan Pasien (Tampil Penuh Tanpa Truncate) -->
                                    <td class="py-3.5 px-4 min-w-[320px]">
                                        <div class="space-y-1">
                                            <p class="text-slate-800 dark:text-slate-200 font-medium whitespace-normal leading-relaxed text-xs">
                                                "{{ item.isi_laporan }}"
                                            </p>
                                            <span class="text-[10px] text-slate-400 font-semibold block">
                                                Pelapor: {{ item.reporter_name || 'Anonim' }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Sasaran / Staf -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span v-if="item.target_object" class="font-bold text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-md text-[11px] inline-block">
                                            {{ item.target_object }}
                                        </span>
                                        <span v-else class="text-slate-400 italic text-[11px]">-</span>
                                    </td>

                                    <!-- Sentimen AI -->
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <div class="flex flex-col items-center gap-1">
                                            <span :class="[
                                                'px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase border inline-block',
                                                item.ai_sentiment === 'NEGATIF' ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:border-rose-800' :
                                                (item.ai_sentiment === 'POSITIF' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-white/10 dark:text-white dark:border-white/20' : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800')
                                            ]">
                                                {{ item.ai_sentiment }}
                                            </span>
                                            <span class="text-[9px] text-slate-400 font-semibold truncate max-w-[120px]">
                                                {{ item.ai_category }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Status Verifikasi -->
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span v-if="item.status === 'PENDING'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200/80 dark:border-amber-900/50">
                                            <AlertCircle class="h-3 w-3" />
                                            <span>Perlu Verifikasi</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-900/50" :title="item.verified_by ? `Divalidasi oleh ${item.verified_by}` : ''">
                                            <CheckCircle2 class="h-3 w-3" />
                                            <span>Terverifikasi</span>
                                        </span>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <Link
                                            :href="route('kasi.verify', { id: item.id })"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-sm"
                                        >
                                            <span>Verifikasi</span>
                                            <ArrowUpRight class="h-3.5 w-3.5" />
                                        </Link>
                                    </td>
                                </tr>

                                <tr v-if="filteredReports.length === 0">
                                    <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                                        Tidak ada laporan yang sesuai dengan pencarian atau filter tab.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TIPE 2: VIEW KARTU / FEED (SEPERTI SEBELUMNYA) -->
                <div v-else class="space-y-3 pt-2 animate-spa-fade-in">
                    <div
                        v-for="item in filteredReports"
                        :key="item.id"
                        class="bg-slate-50/60 dark:bg-slate-950/60 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 hover:border-emerald-500/50 transition duration-200"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/60 dark:border-slate-800">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-extrabold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-white/10 px-2.5 py-1 rounded-lg border border-emerald-200/60 dark:border-white/10">
                                    {{ item.id }}
                                </span>
                                <span class="text-xs text-slate-400 flex items-center gap-1">
                                    <Clock class="h-3.5 w-3.5" />
                                    {{ item.created_at }}
                                </span>
                                <span v-if="item.shift_info" class="text-[10px] text-slate-400 bg-slate-200/60 dark:bg-slate-800 px-2 py-0.5 rounded">
                                    {{ item.shift_info }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span
                                    :class="[
                                        'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border',
                                        item.ai_sentiment === 'NEGATIF' ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:border-rose-800' :
                                        (item.ai_sentiment === 'POSITIF' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-white/10 dark:text-white dark:border-white/20' : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800')
                                    ]"
                                >
                                    AI: {{ item.ai_sentiment }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-200/80 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ item.ai_category }}
                                </span>
                            </div>
                        </div>

                        <!-- Content Text -->
                        <div class="py-3 space-y-1.5">
                            <div v-if="item.target_object" class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                Sasaran: <span class="text-emerald-600 dark:text-emerald-400">{{ item.target_object }}</span>
                            </div>
                            <p class="text-sm text-slate-800 dark:text-slate-200 leading-relaxed font-medium">
                                "{{ item.isi_laporan }}"
                            </p>
                            <span class="text-[10px] text-slate-400 font-semibold block">
                                Pelapor: {{ item.reporter_name || 'Anonim' }}
                            </span>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                            <div class="text-xs text-slate-400 flex items-center gap-1">
                                <span>Status:</span>
                                <span v-if="item.status === 'PENDING'" class="font-bold text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                    <AlertCircle class="h-3.5 w-3.5" /> Belum Divalidasi Kasi
                                </span>
                                <span v-else class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                    <CheckCircle2 class="h-3.5 w-3.5" /> Divalidasi oleh {{ item.verified_by }}
                                </span>
                            </div>

                            <Link
                                :href="route('kasi.verify', { id: item.id })"
                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 text-xs font-bold flex items-center justify-center gap-1.5 transition shadow-sm"
                            >
                                <span>Verifikasi Laporan & Shift</span>
                                <ArrowUpRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>

                    <div v-if="filteredReports.length === 0" class="py-12 text-center text-slate-400 font-medium">
                        Tidak ada laporan yang sesuai dengan pencarian atau filter tab.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
