<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    FileText, 
    TrendingUp, 
    TrendingDown, 
    Award, 
    Users, 
    Clock, 
    Search,
    History,
    ShieldCheck
} from '@lucide/vue';

const props = defineProps({
    staffLogbooks: {
        type: Array,
        default: null
    }
});

const staffLogbooks = ref(props.staffLogbooks || []);
</script>

<template>
    <Head title="Logbook Kinerja Staf" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel (WA Gateway Style - Navbar handles Back button) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <History class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Digital Logbook Staf Unit
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20">
                                LOGBOOK KPI
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Akumulasi kinerja, riwayat apresiasi pujian, dan pemotongan poin KPI staf unit secara rinci.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Staf Logbook Cards -->
            <div v-if="staffLogbooks.length === 0" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-12 text-center shadow-sm space-y-2">
                <div class="h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                    <Users class="h-6 w-6" />
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Data Staf Terdaftar</h3>
                <p class="text-xs text-slate-400">Data rekam jejak KPI staf unit akan muncul di sini setelah staf ditambahkan.</p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="staff in staffLogbooks"
                    :key="staff.id"
                    class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 rounded-full bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center font-bold text-lg border border-emerald-100 dark:border-white/20">
                                {{ staff.name.charAt(0) }}
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ staff.name }}</h3>
                                <p class="text-xs text-slate-400">NIP: {{ staff.nip }} • {{ staff.role }}</p>
                            </div>
                        </div>

                        <!-- KPI Score Badge -->
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <span class="text-xs text-slate-400 font-medium block">Total Poin KPI</span>
                                <span class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ staff.total_points }} Poin</span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Summary -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                        <div class="bg-emerald-50/60 dark:bg-white/5 p-3 rounded-xl border border-emerald-100 dark:border-white/10">
                            <span class="text-slate-500 dark:text-slate-400 block font-medium">Total Apresiasi Pujian:</span>
                            <span class="font-bold text-emerald-700 dark:text-emerald-300 text-sm mt-0.5 block">+{{ staff.praise_count }} Laporan</span>
                        </div>
                        <div class="bg-rose-50/60 dark:bg-rose-950/20 p-3 rounded-xl border border-rose-100 dark:border-rose-900/40">
                            <span class="text-slate-500 dark:text-slate-400 block font-medium">Total Pemotongan Komplain:</span>
                            <span class="font-bold text-rose-700 dark:text-rose-400 text-sm mt-0.5 block">-{{ staff.complaint_count }} Laporan</span>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800 col-span-2 sm:col-span-1">
                            <span class="text-slate-500 dark:text-slate-400 block font-medium">Update Terakhir:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-200 mt-0.5 block">{{ staff.last_update }}</span>
                        </div>
                    </div>

                    <!-- History Log Table -->
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Riwayat Transaksi Poin Terakhir:</span>
                        <div class="space-y-2">
                            <div
                                v-for="log in staff.history"
                                :key="log.id"
                                class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800 text-xs"
                            >
                                <div class="flex items-center gap-3">
                                    <span :class="['px-2 py-0.5 rounded font-mono font-bold text-[10px]', log.points > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-white/10 dark:text-white' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300']">
                                        {{ log.points > 0 ? '+' + log.points : log.points }} Poin
                                    </span>
                                    <span class="text-slate-700 dark:text-slate-200 font-medium">{{ log.note }}</span>
                                </div>
                                <span class="text-[11px] text-slate-400 font-mono">{{ log.date }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
