<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReportDetailModal from '@/Components/ReportDetailModal.vue';
import {
    Award,
    ThumbsUp,
    AlertCircle,
    Calendar,
    FileText,
    History,
    LayoutDashboard,
    Paperclip,
    Download,
    Eye
} from '@lucide/vue';

const props = defineProps({
    staff: {
        type: Object,
        required: true,
    },
    attendanceToday: {
        type: Object,
        default: null,
    },
    kpiLogs: {
        type: Array,
        default: () => [],
    },
    relatedReports: {
        type: Array,
        default: () => [],
    },
    recentAttendances: {
        type: Array,
        default: () => [],
    }
});

const activeTab = ref('kpi'); // 'kpi', 'reports', 'attendance'
const selectedLogForModal = ref(null);
const showDetailModal = ref(false);

const openReportDetail = (log) => {
    selectedLogForModal.value = log;
    showDetailModal.value = true;
};

const getCategoryLabel = (log) => {
    if (!log) return 'Penilaian Kinerja';
    const raw = log.kpi_category;
    if (raw) {
        const upper = String(raw).toUpperCase();
        const map = {
            'KERAMAHAN': 'Keramahan',
            'KEDISIPLINAN': 'Kedisiplinan',
            'SOP_PELAYANAN': 'Kepatuhan SOP',
            'INTEGRITAS': 'Integritas',
        };
        return map[upper] || raw;
    }
    if (log.points > 0) return 'Apresiasi Pelayanan';
    if (log.points < 0) return 'Evaluasi Pelayanan';
    return 'Penilaian Kinerja';
};

const isImage = (att) => {
    return att?.mime_type?.startsWith('image/') || /\.(jpg|jpeg|png|webp|gif)$/i.test(att?.file_name || '');
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">

            <!-- 1. Header Panel (Standar SIPUAS) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <LayoutDashboard class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Dashboard Kinerja Staf
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Ringkasan performa poin KPI, apresiasi pujian pasien, dan pemantauan riwayat unit pelayanan.
                        </p>
                    </div>
                </div>
            </div>

            <!-- 2. Kartu Metrik Kinerja (Grid Responsif 3 Kolom - Standar SIPUAS Sesuai Dashboard Kasi) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Saldo Poin KPI -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Saldo Poin Kinerja (KPI)</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            {{ staff.total_points }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">
                            Standar nilai awal: 100 Poin
                        </span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <Award class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <!-- Apresiasi Pujian Pasien -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Apresiasi Pujian Pasien</span>
                        <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-tight">
                            {{ staff.praise_count }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">
                            Total laporan apresiasi diterima
                        </span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                        <ThumbsUp class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>

                <!-- Keluhan / Evaluasi -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Keluhan / Evaluasi SOP</span>
                        <div class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 leading-tight">
                            {{ staff.complaint_count }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">
                            Total laporan keluhan terverifikasi
                        </span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-rose-50 dark:bg-rose-950/40">
                        <AlertCircle class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                    </div>
                </div>
            </div>

            <!-- 3. Tab Navigasi & Konten Riwayat -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm space-y-5">
                <!-- Navigation Tabs (Segmented Control yang Responsif & Bertumpuk di Mobile) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5 p-1.5 bg-slate-100 dark:bg-slate-800/60 rounded-2xl border border-slate-200/60 dark:border-slate-800">
                    <!-- Tab 1: Logbook -->
                    <button
                        type="button"
                        @click="activeTab = 'kpi'"
                        :class="[
                            'w-full py-2.5 px-3.5 sm:px-4 rounded-xl text-xs font-bold flex flex-row items-center justify-between sm:justify-center gap-2 transition cursor-pointer select-none text-left sm:text-center',
                            activeTab === 'kpi' 
                                ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        <div class="flex items-center gap-2 sm:gap-1.5 min-w-0">
                            <History class="h-4 w-4 shrink-0" />
                            <span class="truncate">Logbook Poin</span>
                        </div>
                        <span :class="[
                            'text-[11px] sm:text-[10px] font-black px-2 sm:px-1.5 py-0.5 rounded-full shrink-0 transition',
                            activeTab === 'kpi'
                                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800'
                                : 'bg-slate-200/70 dark:bg-slate-700/60 text-slate-600 dark:text-slate-400'
                        ]">
                            {{ kpiLogs.length }}
                        </span>
                    </button>

                    <!-- Tab 2: Aduan -->
                    <button
                        type="button"
                        @click="activeTab = 'reports'"
                        :class="[
                            'w-full py-2.5 px-3.5 sm:px-4 rounded-xl text-xs font-bold flex flex-row items-center justify-between sm:justify-center gap-2 transition cursor-pointer select-none text-left sm:text-center',
                            activeTab === 'reports' 
                                ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        <div class="flex items-center gap-2 sm:gap-1.5 min-w-0">
                            <FileText class="h-4 w-4 shrink-0" />
                            <span class="truncate">Aduan Terkait</span>
                        </div>
                        <span :class="[
                            'text-[11px] sm:text-[10px] font-black px-2 sm:px-1.5 py-0.5 rounded-full shrink-0 transition',
                            activeTab === 'reports'
                                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800'
                                : 'bg-slate-200/70 dark:bg-slate-700/60 text-slate-600 dark:text-slate-400'
                        ]">
                            {{ relatedReports.length }}
                        </span>
                    </button>

                    <!-- Tab 3: Presensi -->
                    <button
                        type="button"
                        @click="activeTab = 'attendance'"
                        :class="[
                            'w-full py-2.5 px-3.5 sm:px-4 rounded-xl text-xs font-bold flex flex-row items-center justify-between sm:justify-center gap-2 transition cursor-pointer select-none text-left sm:text-center',
                            activeTab === 'attendance' 
                                ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        <div class="flex items-center gap-2 sm:gap-1.5 min-w-0">
                            <Calendar class="h-4 w-4 shrink-0" />
                            <span class="truncate">Riwayat Presensi</span>
                        </div>
                        <span :class="[
                            'text-[11px] sm:text-[10px] font-black px-2 sm:px-1.5 py-0.5 rounded-full shrink-0 transition',
                            activeTab === 'attendance'
                                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800'
                                : 'bg-slate-200/70 dark:bg-slate-700/60 text-slate-600 dark:text-slate-400'
                        ]">
                            {{ recentAttendances.length }}
                        </span>
                    </button>
                </div>

                <!-- TAB 1: LOGBOOK RIWAYAT POIN KPI -->
                <div v-if="activeTab === 'kpi'" class="space-y-3">
                    <div v-if="kpiLogs.length === 0" class="text-center py-10 sm:py-14 space-y-2.5">
                        <div class="h-12 w-12 rounded-2xl bg-slate-50 dark:bg-slate-800/80 text-slate-400 dark:text-slate-500 flex items-center justify-center mx-auto border border-slate-100 dark:border-slate-800 shadow-xs">
                            <History class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Belum ada riwayat mutasi poin kinerja</p>
                            <p class="text-[11px] text-slate-400 max-w-sm mx-auto leading-relaxed">Saldo poin Anda saat ini masih utuh pada standar awal nilai 100 poin.</p>
                        </div>
                    </div>
                    <div v-else class="space-y-2.5">
                        <div 
                            v-for="log in kpiLogs" 
                            :key="log.id" 
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
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate" :title="getCategoryLabel(log)">
                                        {{ getCategoryLabel(log) }}
                                    </h4>

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

                <!-- TAB 2: ADUAN & LAPORAN TERKAIT -->
                <div v-if="activeTab === 'reports'" class="space-y-3">
                    <div v-if="relatedReports.length === 0" class="text-center py-10 sm:py-14 space-y-2.5">
                        <div class="h-12 w-12 rounded-2xl bg-slate-50 dark:bg-slate-800/80 text-slate-400 dark:text-slate-500 flex items-center justify-center mx-auto border border-slate-100 dark:border-slate-800 shadow-xs">
                            <FileText class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Tidak ada aduan langsung terkait akun Anda</p>
                            <p class="text-[11px] text-slate-400 max-w-sm mx-auto leading-relaxed">Pertahankan standar keramahan dan ketepatan SOP pelayanan!</p>
                        </div>
                    </div>
                    <div v-else class="space-y-3">
                        <div 
                            v-for="rep in relatedReports" 
                            :key="rep.id" 
                            class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-2.5"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    #{{ rep.id }}
                                </span>
                                <span class="text-[11px] text-slate-400">{{ rep.verified_at }}</span>
                            </div>
                            <p class="text-xs text-slate-700 dark:text-slate-300 italic leading-relaxed">
                                "{{ rep.isi_laporan }}"
                            </p>
                            <div v-if="rep.supervisor_notes" class="text-[11px] text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200/60 dark:border-slate-800 space-y-0.5">
                                <div class="font-bold text-slate-800 dark:text-slate-200">Catatan Kasi:</div>
                                <div>{{ rep.supervisor_notes }}</div>
                            </div>

                            <!-- Berkas Lampiran Verifikasi jika ada -->
                            <div v-if="rep.has_attachment && rep.attachments?.length" class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 space-y-2">
                                <div class="text-[11px] font-bold text-slate-700 dark:text-slate-200">
                                    Lampiran Berkas Verifikasi:
                                </div>
                                <div class="space-y-1.5">
                                    <div 
                                        v-for="att in rep.attachments" 
                                        :key="att.id"
                                        class="flex items-center justify-between gap-2.5 p-2 sm:p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 text-xs w-full transition hover:border-slate-300 dark:hover:border-slate-700"
                                    >
                                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                            <div v-if="isImage(att)" class="h-8 w-8 sm:h-9 sm:w-9 rounded-lg overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700 bg-slate-100">
                                                <img :src="att.url" :alt="att.file_name" class="h-full w-full object-cover" />
                                            </div>
                                            <div v-else class="h-8 w-8 sm:h-9 sm:w-9 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900/40 flex items-center justify-center shrink-0">
                                                <FileText class="h-4 w-4" />
                                            </div>
                                            <div class="truncate min-w-0 flex-1">
                                                <p class="font-semibold text-slate-800 dark:text-slate-200 truncate text-xs leading-snug" :title="att.file_name">
                                                    {{ att.file_name }}
                                                </p>
                                                <p class="text-[10px] text-slate-400 mt-0.5">{{ att.file_size }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <a 
                                                :href="att.url" 
                                                target="_blank" 
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-[11px] transition"
                                                title="Lihat berkas"
                                            >
                                                <Eye class="h-3 w-3 text-slate-500 dark:text-slate-400" />
                                                <span>Lihat</span>
                                            </a>
                                            <a 
                                                :href="att.url" 
                                                target="_blank" 
                                                download 
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] transition shadow-2xs"
                                                title="Unduh berkas"
                                            >
                                                <Download class="h-3 w-3" />
                                                <span>Unduh</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: RIWAYAT PRESENSI -->
                <div v-if="activeTab === 'attendance'" class="space-y-3">
                    <div v-if="recentAttendances.length === 0" class="text-center py-10 sm:py-14 space-y-2.5">
                        <div class="h-12 w-12 rounded-2xl bg-slate-50 dark:bg-slate-800/80 text-slate-400 dark:text-slate-500 flex items-center justify-center mx-auto border border-slate-100 dark:border-slate-800 shadow-xs">
                            <Calendar class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Belum ada riwayat presensi tercatat</p>
                            <p class="text-[11px] text-slate-400 max-w-sm mx-auto leading-relaxed">Lakukan clock in melalui menu Presensi saat memulai jadwal kerja.</p>
                        </div>
                    </div>
                    <div v-else>
                        <!-- Mobile View: Clean Stacked Cards -->
                        <div class="block sm:hidden space-y-2.5">
                            <div 
                                v-for="att in recentAttendances" 
                                :key="att.id" 
                                class="p-3.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-2"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-slate-900 dark:text-white">{{ att.duty_date }}</span>
                                    <span :class="[
                                        'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                        att.status === 'ON_DUTY' 
                                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800' 
                                            : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                                    ]">
                                        {{ att.status === 'ON_DUTY' ? 'Sedang Bertugas' : 'Selesai' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-3 gap-2 text-[11px] pt-1 border-t border-slate-100 dark:border-slate-800/80">
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Sesi</span>
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ att.shift_name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Masuk</span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ att.check_in_at }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">Pulang</span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ att.check_out_at }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop View: Clean Table -->
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 uppercase text-[10px] font-bold">
                                        <th class="py-3 px-3">Tanggal</th>
                                        <th class="py-3 px-3">Sesi</th>
                                        <th class="py-3 px-3">Jam Masuk</th>
                                        <th class="py-3 px-3">Jam Pulang</th>
                                        <th class="py-3 px-3 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                                    <tr v-for="att in recentAttendances" :key="att.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition">
                                        <td class="py-3 px-3 font-semibold text-slate-900 dark:text-white">{{ att.duty_date }}</td>
                                        <td class="py-3 px-3 font-bold text-emerald-600 dark:text-emerald-400">{{ att.shift_name }}</td>
                                        <td class="py-3 px-3 font-medium">{{ att.check_in_at }}</td>
                                        <td class="py-3 px-3 font-medium">{{ att.check_out_at }}</td>
                                        <td class="py-3 px-3 text-right">
                                            <span :class="[
                                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                                att.status === 'ON_DUTY' 
                                                    ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800' 
                                                    : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                                            ]">
                                                {{ att.status === 'ON_DUTY' ? 'Sedang Bertugas' : 'Selesai' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
