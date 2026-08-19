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
    History
} from '@lucide/vue';

const activeTab = ref('ALL');
const searchQuery = ref('');

const reports = ref([
    {
        id: 'LP-2026-08-001',
        created_at: '2026-08-06 14:30',
        unit: 'Instalasi Farmasi',
        isi_laporan: 'Ambil obat lama sekali, sudah antre 2 jam petugasnya malah asyik ngobrol dan lambat melayani.',
        ai_sentiment: 'NEGATIF',
        ai_category: 'Waktu Tunggu & Pelayanan',
        ai_score: -5,
        status: 'PENDING',
        verified_by: null
    },
    {
        id: 'LP-2026-08-002',
        created_at: '2026-08-06 13:15',
        unit: 'Instalasi Farmasi',
        isi_laporan: 'Perawat Sinta sangat ramah dan sigap membantu mengurus administrasi resep obat ibu saya.',
        ai_sentiment: 'POSITIF',
        ai_category: 'Pelayanan Ramah',
        ai_score: 5,
        status: 'VERIFIED',
        verified_by: 'Kasi Farmasi (Ahmad, S.Farm)'
    },
    {
        id: 'LP-2026-08-003',
        created_at: '2026-08-06 11:00',
        unit: 'Instalasi Farmasi',
        isi_laporan: 'Ruang tunggu farmasi sangat panas dan AC tidak dingin sama sekali sejak pagi.',
        ai_sentiment: 'NEGATIF',
        ai_category: 'Sarana & Prasarana',
        ai_score: -5,
        status: 'PENDING',
        verified_by: null
    },
    {
        id: 'LP-2026-08-004',
        created_at: '2026-08-05 16:45',
        unit: 'Instalasi Farmasi',
        isi_laporan: 'Penyampaian informasi dosis obat oleh petugas apoteker jelas dan mudah dipahami.',
        ai_sentiment: 'POSITIF',
        ai_category: 'Edukasi Pasien',
        ai_score: 5,
        status: 'VERIFIED',
        verified_by: 'Kasi Farmasi (Ahmad, S.Farm)'
    }
]);

const filteredReports = computed(() => {
    return reports.value.filter(r => {
        const matchesTab = activeTab.value === 'ALL' || 
            (activeTab.value === 'PENDING' && r.status === 'PENDING') ||
            (activeTab.value === 'VERIFIED' && r.status === 'VERIFIED');
        const matchesSearch = r.isi_laporan.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            r.id.toLowerCase().includes(searchQuery.value.toLowerCase());
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
            <!-- Header Panel (WA Gateway Style) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <FileText class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Feed Aduan Masuk Unit
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20">
                                MODUL KASI
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Verifikasi shift staf dan kelola poin KPI unit secara real-time.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3">
                    <Link
                        :href="route('kasi.logbook')"
                        class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-2 transition shadow-sm"
                    >
                        <History class="h-4 w-4 text-emerald-500" />
                        <span>Buka Digital Logbook Staf</span>
                    </Link>
                </div>
            </div>

            <!-- KPI Cards Header -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Aduan Unit</span>
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <FileText class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.total }}</div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Seluruh laporan masuk di unit ini</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-amber-200/80 dark:border-amber-900/40 rounded-2xl p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Perlu Verifikasi</span>
                        <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">
                            <AlertCircle class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">{{ stats.pending }}</div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Menunggu pencocokan shift staf</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Telah Divalidasi</span>
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.verified }}</div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Poin KPI staf telah di-update</span>
                </div>
            </div>

            <!-- Content Card Container (WA Gateway Style) -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl w-full sm:w-auto">
                        <button
                            @click="activeTab = 'ALL'"
                            :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition w-full sm:w-auto', activeTab === 'ALL' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white']"
                        >
                            Semua Feed ({{ stats.total }})
                        </button>
                        <button
                            @click="activeTab = 'PENDING'"
                            :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition w-full sm:w-auto', activeTab === 'PENDING' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white']"
                        >
                            Perlu Verifikasi ({{ stats.pending }})
                        </button>
                        <button
                            @click="activeTab = 'VERIFIED'"
                            :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition w-full sm:w-auto', activeTab === 'VERIFIED' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white']"
                        >
                            Selesai ({{ stats.verified }})
                        </button>
                    </div>

                    <div class="relative w-full sm:w-72">
                        <Search class="h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari isi laporan / ID..."
                            class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <!-- Feed Items -->
                <div class="space-y-3 pt-2">
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
                            </div>

                            <div class="flex items-center gap-2">
                                <span
                                    :class="[
                                        'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border',
                                        item.ai_sentiment === 'NEGATIF' ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:border-rose-800' : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-white/10 dark:text-white dark:border-white/20'
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
                        <div class="py-3">
                            <p class="text-sm text-slate-800 dark:text-slate-200 leading-relaxed font-medium">
                                "{{ item.isi_laporan }}"
                            </p>
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
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
