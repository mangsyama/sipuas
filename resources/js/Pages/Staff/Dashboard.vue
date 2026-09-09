<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Award,
    ThumbsUp,
    AlertCircle,
    Calendar,
    FileText,
    History,
    LayoutDashboard
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
                            +{{ staff.praise_count }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">
                            Memberi reward +poin kinerja
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
                            -{{ staff.complaint_count }}
                        </div>
                        <span class="text-[11px] text-slate-400 block">
                            Kelalaian SOP terverifikasi Kasi
                        </span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-rose-50 dark:bg-rose-950/40">
                        <AlertCircle class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                    </div>
                </div>
            </div>

            <!-- 3. Tab Navigasi & Konten Riwayat -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm space-y-5">
                <!-- Navigation Tabs (Segmented Control yang Responsif & Bebas Scroll) -->
                <div class="grid grid-cols-3 gap-1 sm:gap-1.5 p-1 sm:p-1.5 bg-slate-100 dark:bg-slate-800/60 rounded-2xl border border-slate-200/60 dark:border-slate-800">
                    <!-- Tab 1: Logbook -->
                    <button
                        type="button"
                        @click="activeTab = 'kpi'"
                        :class="[
                            'py-2 px-1 sm:py-2.5 sm:px-4 rounded-xl text-xs font-bold flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 transition cursor-pointer select-none text-center',
                            activeTab === 'kpi' 
                                ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        <div class="flex items-center gap-1 sm:gap-1.5 min-w-0">
                            <History class="h-3.5 w-3.5 sm:h-4 sm:w-4 shrink-0" />
                            <span class="truncate">
                                <span class="sm:hidden">Logbook</span>
                                <span class="hidden sm:inline">Logbook Poin</span>
                            </span>
                        </div>
                        <span :class="[
                            'text-[10px] font-black px-1.5 py-0.5 rounded-full shrink-0 transition',
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
                            'py-2 px-1 sm:py-2.5 sm:px-4 rounded-xl text-xs font-bold flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 transition cursor-pointer select-none text-center',
                            activeTab === 'reports' 
                                ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        <div class="flex items-center gap-1 sm:gap-1.5 min-w-0">
                            <FileText class="h-3.5 w-3.5 sm:h-4 sm:w-4 shrink-0" />
                            <span class="truncate">
                                <span class="sm:hidden">Aduan</span>
                                <span class="hidden sm:inline">Aduan Terkait</span>
                            </span>
                        </div>
                        <span :class="[
                            'text-[10px] font-black px-1.5 py-0.5 rounded-full shrink-0 transition',
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
                            'py-2 px-1 sm:py-2.5 sm:px-4 rounded-xl text-xs font-bold flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 transition cursor-pointer select-none text-center',
                            activeTab === 'attendance' 
                                ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        <div class="flex items-center gap-1 sm:gap-1.5 min-w-0">
                            <Calendar class="h-3.5 w-3.5 sm:h-4 sm:w-4 shrink-0" />
                            <span class="truncate">
                                <span class="sm:hidden">Presensi</span>
                                <span class="hidden sm:inline">Riwayat Presensi</span>
                            </span>
                        </div>
                        <span :class="[
                            'text-[10px] font-black px-1.5 py-0.5 rounded-full shrink-0 transition',
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
                    <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                        <div 
                            v-for="log in kpiLogs" 
                            :key="log.id" 
                            class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 rounded-xl px-2.5 transition"
                        >
                            <div class="flex items-start gap-3">
                                <div :class="[
                                    'h-9 w-9 rounded-xl flex items-center justify-center font-black text-xs shrink-0 shadow-xs mt-0.5',
                                    log.action_type === 'PENAMBAHAN' 
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' 
                                        : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800'
                                ]">
                                    {{ log.points > 0 ? '+' + log.points : log.points }}
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 leading-snug">
                                        {{ log.note || 'Penilaian verifikasi kinerja staf pelayanan' }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-400">
                                        <span>Tiket: <strong class="text-slate-600 dark:text-slate-300">{{ log.ticket_number }}</strong></span>
                                        <span>•</span>
                                        <span>Diverifikasi: {{ log.verifier_name }}</span>
                                    </div>
                                </div>
                            </div>
                            <span class="text-[11px] font-medium text-slate-400 pl-12 sm:pl-0 sm:whitespace-nowrap">
                                {{ log.date }}
                            </span>
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
                                <div class="font-bold text-slate-800 dark:text-slate-200">Catatan Supervisor Kasi:</div>
                                <div>{{ rep.supervisor_notes }}</div>
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

        </div>
    </AuthenticatedLayout>
</template>
