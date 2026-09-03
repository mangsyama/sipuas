<script setup>
import { ref, computed } from 'vue';
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

const props = defineProps({
    executiveStats: {
        type: Object,
        default: null
    },
    redZoneUnits: {
        type: Array,
        default: null
    }
});

const period = ref('30_DAYS');

const executiveStats = computed(() => props.executiveStats || {
    total_rs_reports: 0,
    avg_kasi_response_hours: '-',
    satisfaction_index: '100%',
    total_kpi_points: 0,
    positive_count: 0,
    positive_percent: 0,
    negative_count: 0,
    negative_percent: 0
});

const redZoneUnits = computed(() => props.redZoneUnits || []);
</script>

<template>
    <Head title="Executive Command Center" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Activity class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Executive Command Center
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Pemantauan menyeluruh kualitas pelayanan seluruh unit rumah sakit berbasis agregasi data AI real-time.
                        </p>
                    </div>
                </div>

                <!-- Live Period Indicator -->
                <div class="flex items-center gap-2">
                    <span class="px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                        <Calendar class="h-3.5 w-3.5 text-emerald-600" />
                        Periode Berjalan (Bulan Ini)
                    </span>
                </div>
            </div>

            <!-- Top 4 KPI Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- Metric 1: Total RS Reports -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Suara Pasien RS</span>
                        <div class="h-9 w-9 rounded-xl flex items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <FileText class="h-4 w-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            {{ executiveStats.total_rs_reports }}
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Seluruh aduan & apresiasi masuk</p>
                    </div>
                </div>

                <!-- Metric 2: Satisfaction Index -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Indeks Kepuasan RS</span>
                        <div class="h-9 w-9 rounded-xl flex items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <TrendingUp class="h-4 w-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight">
                            {{ executiveStats.satisfaction_index }}
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Berdasarkan klasifikasi sentimen AI</p>
                    </div>
                </div>

                <!-- Metric 3: Avg Kasi Response -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-Rata Respons Kasi</span>
                        <div class="h-9 w-9 rounded-xl flex items-center justify-center bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">
                            <Clock class="h-4 w-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            {{ executiveStats.avg_kasi_response_hours }}
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Kecepatan verifikasi tindak lanjut</p>
                    </div>
                </div>

                <!-- Metric 4: Total KPI Points -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Akumulasi Poin Staf</span>
                        <div class="h-9 w-9 rounded-xl flex items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Award class="h-4 w-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight">
                            {{ executiveStats.total_kpi_points }}
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Total saldo performa staf rumah sakit</p>
                    </div>
                </div>
            </div>

            <!-- Main Content: Red Zone Unit Map & Sentiment Breakdown (8 cols / 4 cols) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Red Zone Map Table (8 cols) -->
                <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <AlertTriangle class="h-4 w-4 text-rose-500" />
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">Peta Zona Merah Unit (Komplain Highest)</h3>
                        </div>
                        <span class="text-xs text-slate-400 font-medium">Berdasarkan Volume Aduan</span>
                    </div>

                    <div v-if="redZoneUnits.length === 0 || redZoneUnits.every(u => u.complaints === 0)" class="py-12 text-center space-y-2">
                        <div class="h-10 w-10 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                        <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Semua Unit Bersih & Prima</p>
                        <p class="text-[11px] text-slate-400">Tidak ada unit berisiko zona merah/kuning saat ini.</p>
                    </div>

                    <div v-else class="space-y-4 pt-1">
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
                                    <span class="text-xl font-extrabold text-emerald-700 dark:text-emerald-300">{{ executiveStats.positive_count }} Laporan</span>
                                </div>
                                <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ executiveStats.positive_percent }}%</span>
                            </div>

                            <div class="bg-rose-50/60 dark:bg-rose-950/20 p-4 rounded-xl border border-rose-100 dark:border-rose-900/40 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 block font-medium">Sentimen Negatif (Keluhan)</span>
                                    <span class="text-xl font-extrabold text-rose-700 dark:text-rose-400">{{ executiveStats.negative_count }} Laporan</span>
                                </div>
                                <span class="text-sm font-bold text-rose-600 dark:text-rose-400">{{ executiveStats.negative_percent }}%</span>
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
