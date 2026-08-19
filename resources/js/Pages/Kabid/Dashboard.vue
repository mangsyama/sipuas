<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Activity, 
    FileText, 
    TrendingUp, 
    TrendingDown, 
    AlertTriangle, 
    CheckCircle2, 
    Clock, 
    Building2, 
    Users, 
    Award,
    Calendar,
    BarChart3,
    Sparkles
} from '@lucide/vue';

const period = ref('30_DAYS');

const executiveStats = ref({
    total_rs_reports: 248,
    avg_kasi_response_hours: '1.8 Jam',
    satisfaction_index: '88.5%',
    total_kpi_points: 1420
});

const redZoneUnits = ref([
    { unit: 'Instalasi Farmasi', complaints: 42, percentage: 84, status: 'HIGH_RISK' },
    { unit: 'Instalasi Gawat Darurat (IGD)', complaints: 35, percentage: 70, status: 'MEDIUM_RISK' },
    { unit: 'Kasir & Pendaftaran', complaints: 28, percentage: 56, status: 'MEDIUM_RISK' },
    { unit: 'Poliklinik Rawat Jalan', complaints: 18, percentage: 36, status: 'LOW_RISK' },
    { unit: 'Ruang Rawat Inap', complaints: 12, percentage: 24, status: 'LOW_RISK' }
]);
</script>

<template>
    <Head title="Executive Command Center" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel (WA Gateway Style) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Activity class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Executive Command Center
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20">
                                KABID PELAYANAN
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Pemantauan Peta Zona Merah Unit, Responsivitas Kasi, dan Kinerja Rumah Sakit Secara Real-Time.
                        </p>
                    </div>
                </div>

                <!-- Period Filter Buttons in Header -->
                <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-800 p-1.5 rounded-xl border border-slate-200 dark:border-slate-700">
                    <button
                        @click="period = '7_DAYS'"
                        :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition', period === '7_DAYS' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white']"
                    >
                        7 Hari
                    </button>
                    <button
                        @click="period = '30_DAYS'"
                        :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition', period === '30_DAYS' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white']"
                    >
                        30 Hari Terakhir
                    </button>
                </div>
            </div>

            <!-- Top Executive KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Aduan RS</span>
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <FileText class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ executiveStats.total_rs_reports }}</div>
                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1 block">92% Telah ditindaklanjuti</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata2 Kecepatan Respons Kasi</span>
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Clock class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ executiveStats.avg_kasi_response_hours }}</div>
                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1 block">Target &lt; 2.0 Jam terpenuhi</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Indeks Kepuasan RS</span>
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Activity class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">{{ executiveStats.satisfaction_index }}</div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Berdasarkan rasio sentimen pujian</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Akumulasi Poin KPI</span>
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Award class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ executiveStats.total_kpi_points }}</div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Total pencapaian seluruh unit</span>
                </div>
            </div>

            <!-- Content Card Containers (2 Cols) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Peta Zona Merah Unit (8 cols) -->
                <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <AlertTriangle class="h-5 w-5 text-rose-500" />
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">Peta Zona Merah Unit (Komplain Highest)</h3>
                        </div>
                        <span class="text-xs text-slate-400 font-medium">Berdasarkan Volume Aduan</span>
                    </div>

                    <div class="space-y-4 pt-1">
                        <div v-for="unit in redZoneUnits" :key="unit.unit" class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                    <Building2 class="h-4 w-4 text-slate-400" />
                                    {{ unit.unit }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-rose-600 dark:text-rose-400">{{ unit.complaints }} Aduan</span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                                            unit.status === 'HIGH_RISK' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' :
                                            unit.status === 'MEDIUM_RISK' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                        ]"
                                    >
                                        {{ unit.status === 'HIGH_RISK' ? 'Zona Merah' : unit.status === 'MEDIUM_RISK' ? 'Zona Kuning' : 'Zona Hijau' }}
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-950 rounded-full h-2.5 overflow-hidden">
                                <div
                                    :class="[
                                        'h-full rounded-full transition-all duration-500',
                                        unit.status === 'HIGH_RISK' ? 'bg-rose-500' : unit.status === 'MEDIUM_RISK' ? 'bg-amber-500' : 'bg-emerald-500'
                                    ]"
                                    :style="{ width: unit.percentage + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sentiment Ratio & Direct Navigation Links (4 cols) -->
                <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-6">
                    <div>
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                            <Sparkles class="h-5 w-5 text-emerald-500" />
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">Rasio Sentimen AI</h3>
                        </div>

                        <div class="space-y-3">
                            <div class="bg-emerald-50/60 dark:bg-white/5 p-4 rounded-xl border border-emerald-100 dark:border-white/10 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 block font-medium">Sentimen Positif (Pujian)</span>
                                    <span class="text-xl font-extrabold text-emerald-700 dark:text-emerald-300">182 Laporan</span>
                                </div>
                                <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">73.4%</span>
                            </div>

                            <div class="bg-rose-50/60 dark:bg-rose-950/20 p-4 rounded-xl border border-rose-100 dark:border-rose-900/40 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 block font-medium">Sentimen Negatif (Keluhan)</span>
                                    <span class="text-xl font-extrabold text-rose-700 dark:text-rose-400">66 Laporan</span>
                                </div>
                                <span class="text-sm font-bold text-rose-600 dark:text-rose-400">26.6%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Navigation Links -->
                    <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <Link
                            :href="route('executive.kasi-responsiveness')"
                            class="w-full py-2.5 px-4 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-extrabold flex items-center justify-between transition border border-slate-200/60 dark:border-slate-700"
                        >
                            <span>Laporan Responsivitas Kasi</span>
                            <BarChart3 class="h-4 w-4 text-emerald-500" />
                        </Link>
                        <Link
                            :href="route('executive.leaderboard')"
                            class="w-full py-2.5 px-4 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-extrabold flex items-center justify-between transition border border-slate-200/60 dark:border-slate-700"
                        >
                            <span>Leaderboard Kinerja Staf</span>
                            <Award class="h-4 w-4 text-emerald-500" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
