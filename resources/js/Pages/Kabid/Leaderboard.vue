<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { 
    Award, 
    TrendingUp, 
    AlertTriangle,
    Users,
    ThumbsUp,
    AlertCircle,
    CheckCircle2,
    Sparkles
} from '@lucide/vue';

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({
            total_staff: 30,
            total_praises: 0,
            total_complaints: 0,
            avg_points: 100,
            coaching_count: 0
        })
    },
    topPerformers: {
        type: Array,
        default: () => []
    },
    bottomPerformers: {
        type: Array,
        default: () => []
    }
});
</script>

<template>
    <Head title="Leaderboard Kinerja Staf RS" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel (Sesuai Desain Standar Leaderboard & KasiResponsiveness) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Award class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Leaderboard & Peringkat Staf RS
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Peringkat apresiasi pujian pasien tertinggi dan daftar indikasi pembinaan teknis staf unit rumah sakit.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Top 4 KPI Metrics Grid (Mini Dashboard Sesuai Desain Kabid) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- Metric 1: Total Staf Pelayanan -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Staf Pelayanan</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white leading-tight">
                            {{ summary.total_staff }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">Seluruh unit pelayanan RS</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <Users class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <!-- Metric 2: Total Apresiasi Pasien -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Apresiasi Pujian</span>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                            +{{ summary.total_praises }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">Pujian kepuasan pasien</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <Award class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <!-- Metric 3: Rata-rata Poin KPI -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Rata-rata Poin KPI</span>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                            {{ summary.avg_points }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">Baseline standar: 100 poin</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <TrendingUp class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <!-- Metric 4: Staf Dalam Pembinaan -->
                <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Indikasi Pembinaan</span>
                        <div class="text-3xl font-black text-amber-600 dark:text-amber-400 leading-tight">
                            {{ summary.coaching_count }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">Staf dengan catatan keluhan</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-950/40">
                        <AlertTriangle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </div>

            <!-- Content Cards Container Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                <!-- Top Performers Section (7 cols) -->
                <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/60 rounded-2xl p-6 shadow-sm space-y-4">
                    <!-- Standard Container Header (Persis Seperti di Kasi Responsiveness) -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                <Award class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                    Top Performers Staf Unit
                                </h3>
                                <p class="text-[11px] text-slate-400">
                                    Peringkat staf terbaik berdasarkan akumulasi apresiasi dan kepuasan pasien.
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                            Terbaik RS
                        </span>
                    </div>

                    <div v-if="topPerformers.length === 0" class="py-12 text-center space-y-2">
                        <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                            <Award class="h-5 w-5" />
                        </div>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Belum ada peringkat staf</p>
                        <p class="text-[11px] text-slate-400">Peringkat performa akan terbentuk setelah ada verifikasi laporan apresiasi staf.</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="staf in topPerformers"
                            :key="staf.rank"
                            class="p-4 rounded-xl bg-slate-50/80 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800/80 space-y-2.5 transition-all duration-150 hover:shadow-xs"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <!-- Medal Rank Circle (Tanpa Border) -->
                                    <div
                                        class="h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm shadow-xs shrink-0"
                                        :class="[
                                            staf.rank === 1 ? 'bg-amber-400 text-amber-950' :
                                            staf.rank === 2 ? 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-200' :
                                            staf.rank === 3 ? 'bg-amber-700 text-amber-100' :
                                            'bg-emerald-600 text-white'
                                        ]"
                                    >
                                        #{{ staf.rank }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                                                {{ staf.name }}
                                            </h4>
                                            <span
                                                class="text-[10px] px-2 py-0.5 rounded-full font-extrabold"
                                                :class="[
                                                    staf.rank === 1 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-200 dark:border-amber-800' :
                                                    staf.rank === 2 ? 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700' :
                                                    staf.rank === 3 ? 'bg-amber-900/20 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-800/40' :
                                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                ]"
                                            >
                                                {{ staf.badge }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex-wrap">
                                            <span class="font-medium text-slate-600 dark:text-slate-300">
                                                {{ staf.unit }}
                                            </span>
                                            <span class="text-slate-300 dark:text-slate-700">•</span>
                                            <span>NIP: {{ staf.nip }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 justify-between sm:justify-end shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                                    <div class="text-left sm:text-right">
                                        <span class="text-[10px] text-slate-400 block font-medium uppercase tracking-wider">Apresiasi</span>
                                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                            <ThumbsUp class="h-3 w-3" />
                                            {{ staf.praise_count }} Pujian
                                        </span>
                                    </div>
                                    <div class="text-right min-w-[75px]">
                                        <span class="text-[10px] text-slate-400 block font-medium uppercase tracking-wider">Poin KPI</span>
                                        <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ staf.points }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Real Praise Highlight Snippet -->
                            <div class="bg-white dark:bg-slate-900/80 border border-slate-200/60 dark:border-slate-800 rounded-xl p-2.5 flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300">
                                <Sparkles class="h-3.5 w-3.5 text-amber-500 shrink-0 mt-0.5" />
                                <span class="text-[11.5px] italic leading-relaxed break-words whitespace-normal">
                                    "{{ staf.praise_highlight }}"
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Performers Section (5 cols) -->
                <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/60 rounded-2xl p-6 shadow-sm space-y-4">
                    <!-- Standard Container Header (Persis Seperti di Kasi Responsiveness) -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                                <AlertTriangle class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                    Indikasi Pembinaan Teknis
                                </h3>
                                <p class="text-[11px] text-slate-400">
                                    Daftar staf dengan catatan keluhan pelayanan yang memerlukan supervisi.
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/60">
                            Perlu Perhatian
                        </span>
                    </div>

                    <div v-if="bottomPerformers.length === 0" class="py-12 text-center space-y-2">
                        <div class="h-10 w-10 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                        <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Tidak Ada Indikasi Pembinaan</p>
                        <p class="text-[11px] text-slate-400">Semua staf unit berkinerja baik dan bebas dari keluhan berulang.</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="staf in bottomPerformers"
                            :key="staf.rank"
                            class="p-4 rounded-xl bg-rose-50/40 dark:bg-rose-950/20 border border-rose-100/80 dark:border-rose-900/40 space-y-2.5 transition-all duration-150 hover:shadow-xs"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-rose-500 text-white flex items-center justify-center font-black text-sm shadow-xs shrink-0">
                                        !
                                    </div>
                                    <div>
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                                            {{ staf.name }}
                                        </h4>
                                        <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 flex-wrap">
                                            <span class="font-medium text-slate-600 dark:text-slate-300">
                                                {{ staf.unit }}
                                            </span>
                                            <span class="text-slate-300 dark:text-slate-700">•</span>
                                            <span>NIP: {{ staf.nip }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <span class="text-[10px] text-slate-400 block font-medium uppercase tracking-wider">Sisa Poin</span>
                                    <span class="text-base font-black text-rose-600 dark:text-rose-400">{{ staf.points }}</span>
                                </div>
                            </div>

                            <!-- Real Complaint Snippet Box -->
                            <div class="bg-white/90 dark:bg-slate-900/90 border border-rose-200/60 dark:border-rose-900/60 rounded-xl p-2.5 space-y-1 text-xs">
                                <div class="flex items-start gap-1.5 text-rose-700 dark:text-rose-400 font-semibold text-[11.5px]">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0 mt-0.5" />
                                    <span class="break-words whitespace-normal leading-relaxed">Catatan Aduan: {{ staf.reason }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 pl-5 break-words whitespace-normal leading-relaxed">
                                    Rekomendasi: {{ staf.note }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
