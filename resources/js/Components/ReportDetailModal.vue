<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import {
    FileText,
    Download,
    Eye,
    X
} from '@lucide/vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    report: {
        type: Object,
        default: null,
    },
    kpiInfo: {
        type: Object,
        default: null,
    },
});

defineEmits(['close']);

const page = usePage();

const currentUser = computed(() => page.props.auth?.user);
const isStaff = computed(() => {
    const role = currentUser.value?.role;
    return !role || role === 'STAFF';
});

const displayReporterName = computed(() => {
    if (isStaff.value) {
        return 'Pasien / Pengunjung (Dirahasiakan)';
    }
    if (props.report?.is_anonymous) {
        return 'Anonim (Pasien / Pengunjung)';
    }
    return props.report?.reporter_name || 'Pasien / Pengunjung';
});

const effectiveSupervisorNotes = computed(() => {
    return props.report?.supervisor_notes || props.kpiInfo?.note || null;
});

const isImage = (att) => {
    return att?.mime_type?.startsWith('image/') || /\.(jpg|jpeg|png|webp|gif)$/i.test(att?.file_name || '');
};
</script>

<template>
    <Modal :show="show" max-width="2xl" :close-on-click-outside="false" @close="$emit('close')">
        <div v-if="report || kpiInfo" class="flex flex-col flex-1 overflow-hidden min-h-screen sm:min-h-0 sm:max-h-[90vh]">
            <!-- Modal Header (Solid Green SIPUAS, Tanpa Ikon di Judul) -->
            <div class="px-5 sm:px-6 py-4 bg-emerald-600 dark:bg-emerald-700 text-white flex items-center justify-between shrink-0">
                <div class="min-w-0 pr-2">
                    <h3 class="text-base sm:text-lg font-bold text-white leading-tight truncate">
                        Detail Bukti Laporan & Mutasi KPI
                    </h3>
                    <p class="text-xs text-emerald-100 mt-0.5 truncate">
                        Bukti rekam laporan pasien dan catatan resmi evaluasi kinerja
                    </p>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="p-2 rounded-xl text-white/80 hover:text-white hover:bg-white/20 transition cursor-pointer shrink-0"
                    title="Tutup (Esc)"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Modal Content (Scrollable, Hirarki Kronologis Terstruktur: Sebab -> Akibat) -->
            <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1 sm:max-h-[calc(90vh-140px)]">
                
                <!-- ============================================================= -->
                <!-- BAGIAN 1: LAPORAN / ADUAN PASIEN (KASUS KEJADIAN)            -->
                <!-- ============================================================= -->
                <div v-if="report" class="p-4 sm:p-5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-4">
                    <!-- Header Bagian 1 (Tanpa Angka Sesuai Permintaan) -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800/80 pb-3">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 block">
                                Data Laporan Pelayanan
                            </span>
                            <span class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white tracking-wider font-['Poppins',sans-serif] block mt-0.5">
                                #{{ report.ticket_number }}
                            </span>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-200/80 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700">
                            Aduan Pasien
                        </span>
                    </div>

                    <!-- Grid Metadata Laporan Pasien -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400 font-medium block text-[11px]">Unit Pelayanan:</span>
                            <p class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 font-['Poppins',sans-serif]">
                                {{ report.room_name || 'Unit Pelayanan' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-slate-400 font-medium block text-[11px]">Identitas Pelapor:</span>
                            <p class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 font-['Poppins',sans-serif]">
                                {{ displayReporterName }}
                            </p>
                        </div>
                        <div>
                            <span class="text-slate-400 font-medium block text-[11px]">Waktu Kejadian:</span>
                            <p class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5 font-['Poppins',sans-serif]">
                                {{ report.created_at || '-' }}
                            </p>
                        </div>
                    </div>

                    <!-- Uraian Teks Aduan Pasien -->
                    <div class="p-3.5 sm:p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/90 space-y-2.5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                Uraian Aduan Pasien / Pengunjung
                            </span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="report.ai_sentiment"
                                    :class="[
                                        'inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wide border',
                                        report.ai_sentiment === 'POSITIF'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800'
                                            : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800'
                                    ]"
                                >
                                    Sentimen: {{ report.ai_sentiment }}
                                </span>
                                <span
                                    v-if="report.ai_category"
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800"
                                >
                                    {{ report.ai_category }}
                                </span>
                            </div>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-800 dark:text-slate-200 italic leading-relaxed whitespace-pre-line break-words break-all">
                            "{{ report.isi_laporan }}"
                        </p>
                    </div>

                    <!-- Lampiran Bukti dari Pelapor (Memanjang Horizontal di Desktop, Responsif di Mobile) -->
                    <div v-if="report?.evidence_attachments && report.evidence_attachments.length > 0" class="space-y-2 pt-1">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">
                            Lampiran Bukti dari Pelapor
                        </span>
                        <div class="space-y-1.5">
                            <div
                                v-for="att in report.evidence_attachments"
                                :key="att.id"
                                class="flex items-center justify-between gap-2.5 p-2.5 sm:p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 text-xs transition hover:border-slate-300 dark:hover:border-slate-700"
                            >
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <div v-if="isImage(att)" class="h-8 w-8 sm:h-9 sm:w-9 rounded-lg overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700 bg-slate-100">
                                        <img :src="att.url" :alt="att.file_name" class="h-full w-full object-cover" />
                                    </div>
                                    <div v-else class="h-8 w-8 sm:h-9 sm:w-9 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0">
                                        <FileText class="h-4 w-4" />
                                    </div>
                                    <div class="truncate min-w-0 flex-1">
                                        <p class="font-semibold text-slate-800 dark:text-slate-200 text-xs truncate leading-snug" :title="att.file_name">
                                            {{ att.file_name }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">{{ att.file_size }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <a
                                        :href="att.url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-[11px] transition"
                                        title="Buka / Pratinjau di tab baru"
                                    >
                                        <Eye class="h-3 w-3 text-slate-500 dark:text-slate-400" />
                                        <span>Lihat</span>
                                    </a>
                                    <a
                                        :href="att.url"
                                        target="_blank"
                                        download
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-700 hover:bg-slate-800 text-white font-semibold text-[11px] transition"
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

                <!-- ============================================================= -->
                <!-- BAGIAN 2: TINDAKAN & KEPUTUSAN ATASAN (HASIL VERIFIKASI)     -->
                <!-- ============================================================= -->
                <div v-if="kpiInfo || effectiveSupervisorNotes" class="p-4 sm:p-5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-4">
                    <!-- Header Bagian 2 (Tanpa Angka Sesuai Permintaan) -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800/80 pb-3">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 block">
                                {{ report ? 'Tindak Lanjut & Keputusan Atasan' : 'Evaluasi Kinerja Staf' }}
                            </span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span
                                    v-if="kpiInfo"
                                    class="text-base sm:text-lg font-extrabold tracking-wider font-['Poppins',sans-serif]"
                                    :class="kpiInfo.points > 0 ? 'text-emerald-600 dark:text-emerald-400' : (kpiInfo.points < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-600 dark:text-slate-300')"
                                >
                                    {{ kpiInfo.points > 0 ? '+' + kpiInfo.points : kpiInfo.points }} Poin
                                </span>
                            </div>
                        </div>

                        <span
                            v-if="kpiInfo"
                            :class="[
                                'px-3 py-1 rounded-full text-[11px] font-extrabold border',
                                kpiInfo.points > 0 
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700' 
                                : (kpiInfo.points < 0 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border-rose-300 dark:border-rose-700' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700')
                            ]"
                        >
                            {{ kpiInfo.points > 0 ? 'Reward Poin Apresiasi' : (kpiInfo.points < 0 ? 'Sanksi Pemotongan Poin' : 'Poin Netral') }}
                        </span>
                    </div>

                    <!-- Grid Data Verifikator & Waktu Eksekusi -->
                    <div v-if="kpiInfo" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400 font-medium block text-[11px]">Verifikator:</span>
                            <p class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 font-['Poppins',sans-serif]">
                                {{ kpiInfo.verifier_name || 'Supervisor Kasi' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-slate-400 font-medium block text-[11px]">Waktu Verifikasi:</span>
                            <p class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5 font-['Poppins',sans-serif]">
                                {{ kpiInfo.date }}
                            </p>
                        </div>
                    </div>

                    <!-- Catatan Resmi Kasi -->
                    <div v-if="effectiveSupervisorNotes" class="p-3.5 sm:p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/90 space-y-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block border-b border-slate-100 dark:border-slate-800 pb-2">
                            Catatan Resmi Kasi
                        </span>
                        <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed pl-0.5 whitespace-pre-line break-words break-all">
                            {{ effectiveSupervisorNotes }}
                        </p>
                    </div>

                    <!-- Lampiran Berkas Verifikasi (Memanjang Horizontal di Desktop, Responsif di Mobile) -->
                    <div v-if="report?.verification_attachments && report.verification_attachments.length > 0" class="space-y-2 pt-1">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">
                            Lampiran Berkas Verifikasi
                        </span>
                        <div class="space-y-1.5">
                            <div
                                v-for="att in report.verification_attachments"
                                :key="att.id"
                                class="flex items-center justify-between gap-2.5 p-2.5 sm:p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 text-xs transition hover:border-slate-300 dark:hover:border-slate-700"
                            >
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900/40 flex items-center justify-center shrink-0">
                                        <FileText class="h-4 w-4" />
                                    </div>
                                    <div class="truncate min-w-0 flex-1">
                                        <p class="font-semibold text-slate-800 dark:text-slate-200 text-xs truncate leading-snug" :title="att.file_name">
                                            {{ att.file_name }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">{{ att.file_size }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <a
                                        :href="att.url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-[11px] transition"
                                        title="Buka / Pratinjau di tab baru"
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

            <!-- Modal Footer -->
            <div class="px-5 sm:px-6 py-3.5 pb-8 sm:pb-3.5 bg-slate-50/70 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end shrink-0">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl font-bold text-xs bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition cursor-pointer select-none text-center font-['Poppins',sans-serif]"
                >
                    Tutup
                </button>
            </div>
        </div>
    </Modal>
</template>
