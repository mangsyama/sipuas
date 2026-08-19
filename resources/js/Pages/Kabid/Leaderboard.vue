<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Award, 
    TrendingUp, 
    TrendingDown, 
    Download, 
    Sparkles, 
    CheckCircle2, 
    AlertTriangle,
    ShieldCheck
} from '@lucide/vue';

const topPerformers = ref([
    { rank: 1, name: 'Citra Lestari, A.Md.AK', unit: 'Instalasi Farmasi', points: 110, praise_count: 12, badge: '🥇 Top Performer #1' },
    { rank: 2, name: 'dr. Anindya Putri, Sp.A', unit: 'Poliklinik Anak', points: 105, praise_count: 11, badge: '🥈 Top Performer #2' },
    { rank: 3, name: 'Ners Rian Hidayat, S.Kep', unit: 'Ruang Rawat Inap B', points: 98, praise_count: 10, badge: '🥉 Top Performer #3' },
    { rank: 4, name: 'Sinta Dewi, A.Md.Farm', unit: 'Instalasi Farmasi', points: 90, praise_count: 8, badge: 'Top #4' },
    { rank: 5, name: 'Eka Widya, A.Md.Rad', unit: 'Instalasi Radiologi', points: 88, praise_count: 7, badge: 'Top #5' }
]);

const bottomPerformers = ref([
    { rank: 1, name: 'Doni Pratama', unit: 'Kasir & Pendaftaran', complaint_deductions: 5, points: 40, note: 'Diperlukan Pembinaan Waktu Antrean' },
    { rank: 2, name: 'Budi Santoso, S.Farm', unit: 'Instalasi Farmasi', complaint_deductions: 3, points: 55, note: 'Diperlukan Evaluasi Kecepatan Resep' },
    { rank: 3, name: 'Hendra Setiawan', unit: 'Sarpras IPSRS', complaint_deductions: 3, points: 60, note: 'Diperlukan Pembinaan Respons AC' }
]);
</script>

<template>
    <Head title="Leaderboard Kinerja Staf" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel (WA Gateway Style - Navbar handles Back button) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Award class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Leaderboard & Peringkat Staf RS
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20">
                                APRESIASI & PEMBINAAN
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Peringkat apresiasi pujian pasien tertinggi dan daftar indikasi pembinaan teknis staf unit.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 text-xs font-bold flex items-center gap-2 shadow-sm transition">
                        <Download class="h-4 w-4" />
                        <span>Ekspor Leaderboard (PDF/Excel)</span>
                    </button>
                </div>
            </div>

            <!-- Content Cards Container Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Top Performers Section (7 cols) -->
                <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <Award class="h-5 w-5 text-amber-500" />
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">Top 5 Performers (Apresiasi Pujian Pasien)</h3>
                        </div>
                        <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">Terbaik RS</span>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="staf in topPerformers"
                            :key="staf.rank"
                            class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-extrabold text-sm shadow shrink-0">
                                    {{ staf.rank }}
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        <span>{{ staf.name }}</span>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 font-extrabold">
                                            {{ staf.badge }}
                                        </span>
                                    </h4>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ staf.unit }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <span class="text-[11px] text-slate-400 block font-medium">Apresiasi</span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">+{{ staf.praise_count }} Pujian</span>
                                </div>
                                <div class="text-right min-w-[80px]">
                                    <span class="text-[11px] text-slate-400 block font-medium">Poin KPI</span>
                                    <span class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400">{{ staf.points }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Performers Section (5 cols) -->
                <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <AlertTriangle class="h-5 w-5 text-rose-500" />
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">Indikasi Pembinaan Teknis</h3>
                        </div>
                        <span class="text-xs text-rose-600 dark:text-rose-400 font-bold">Perlu Perhatian</span>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="staf in bottomPerformers"
                            :key="staf.rank"
                            class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl bg-rose-50/40 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/40 gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-rose-500 text-white flex items-center justify-center font-extrabold text-sm shadow shrink-0">
                                    !
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ staf.name }}</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ staf.unit }}</p>
                                    <p class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold mt-0.5">{{ staf.note }}</p>
                                </div>
                            </div>

                            <div class="text-right min-w-[70px]">
                                <span class="text-[11px] text-slate-400 block font-medium">Sisa Poin</span>
                                <span class="text-base font-extrabold text-rose-600 dark:text-rose-400">{{ staf.points }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
