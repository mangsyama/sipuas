<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReportDetailModal from '@/Components/ReportDetailModal.vue';
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
    ShieldCheck, 
    Paperclip, 
    Download 
} from '@lucide/vue';

const props = defineProps({
    staffLogbooks: {
        type: Array,
        default: null
    }
});

const staffLogbooks = ref(props.staffLogbooks || []);
const selectedLogForModal = ref(null);
const showDetailModal = ref(false);

const openReportDetail = (log) => {
    selectedLogForModal.value = log;
    showDetailModal.value = true;
};
</script>

<template>
    <Head title="Logbook Kinerja Staf" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <History class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Digital Logbook Staf Unit
                        </h2>
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
                            <span class="font-bold text-emerald-700 dark:text-emerald-300 text-sm mt-0.5 block">{{ staff.praise_count }} Laporan</span>
                        </div>
                        <div class="bg-rose-50/60 dark:bg-rose-950/20 p-3 rounded-xl border border-rose-100 dark:border-rose-900/40">
                            <span class="text-slate-500 dark:text-slate-400 block font-medium">Total Keluhan / Komplain:</span>
                            <span class="font-bold text-rose-700 dark:text-rose-400 text-sm mt-0.5 block">{{ staff.complaint_count }} Laporan</span>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800 col-span-2 sm:col-span-1">
                            <span class="text-slate-500 dark:text-slate-400 block font-medium">Update Terakhir:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-200 mt-0.5 block">{{ staff.last_update }}</span>
                        </div>
                    </div>

                    <!-- History Log Table -->
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2.5">Riwayat Transaksi Poin Terakhir:</span>
                        <div class="space-y-2.5">
                            <div
                                v-for="(log, idx) in staff.history"
                                :key="(log.id || 'log') + '-' + idx"
                                class="p-3 sm:p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-950/60 border border-slate-200/70 dark:border-slate-800/80 hover:border-emerald-500/40 dark:hover:border-emerald-500/30 transition flex items-center justify-between gap-2.5 sm:gap-4 min-w-0"
                            >
                                <!-- Sisi Kiri: Badge Poin + Informasi Transaksi -->
                                <div class="flex items-center gap-2.5 sm:gap-3.5 min-w-0 flex-1">
                                    <!-- Badge Poin Kotak Rounded -->
                                    <div :class="[
                                        'h-10 w-10 sm:h-11 sm:w-11 rounded-xl flex items-center justify-center font-black text-xs sm:text-sm shrink-0',
                                        log.points > 0 
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' 
                                            : 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800'
                                    ]">
                                        {{ log.points > 0 ? '+' + log.points : log.points }}
                                    </div>

                                    <!-- Informasi & Detail Catatan -->
                                    <div class="min-w-0 flex-1 space-y-0.5">
                                        <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate" :title="log.note || 'Penilaian kinerja staf pelayanan'">
                                            {{ log.note || 'Penilaian kinerja staf pelayanan' }}
                                        </p>

                                        <p class="text-[10.5px] sm:text-[11px] text-slate-400 truncate">
                                            <span>Diverifikasi: <strong class="text-slate-600 dark:text-slate-300 font-medium">{{ log.verifier_name || 'Supervisor Kasi' }}</strong></span>
                                            <span class="mx-1.5">•</span>
                                            <span class="text-slate-400 font-medium">{{ log.date }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Sisi Kanan: Tombol Tetap Berada di Kanan (Icon Saja di Mobile) -->
                                <div class="flex items-center shrink-0">
                                    <button
                                        v-if="log.report_detail"
                                        type="button"
                                        @click="openReportDetail(log)"
                                        class="inline-flex items-center justify-center gap-1.5 p-2 sm:px-3.5 sm:py-2 rounded-xl text-xs font-bold bg-white dark:bg-slate-900 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-300 dark:hover:border-emerald-700 transition cursor-pointer select-none whitespace-nowrap"
                                        title="Buka detail bukti laporan & verifikasi"
                                    >
                                        <FileText class="h-4 w-4 shrink-0" />
                                        <span class="hidden sm:inline">Bukti Laporan</span>
                                    </button>
                                    <span
                                        v-else
                                        class="text-[10px] sm:text-[11px] font-semibold text-slate-400 bg-slate-100 dark:bg-slate-800/80 px-2 py-1.5 sm:px-3 rounded-xl border border-slate-200/50 dark:border-slate-700/50 whitespace-nowrap"
                                        title="Penilaian Manual"
                                    >
                                        <span class="sm:hidden">Manual</span>
                                        <span class="hidden sm:inline">Penilaian Manual</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Detail Bukti Laporan & Mutasi KPI -->
            <ReportDetailModal
                :show="showDetailModal"
                :report="selectedLogForModal?.report_detail"
                :kpi-info="selectedLogForModal"
                @close="showDetailModal = false"
            />
        </div>
    </AuthenticatedLayout>
</template>
