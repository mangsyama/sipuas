<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    UserCheck, 
    Clock, 
    CheckCircle2, 
    AlertCircle, 
    ShieldCheck, 
    TrendingDown, 
    TrendingUp, 
    Users, 
    MessageSquare,
    Send,
    Sparkles,
    Building2,
    Phone,
    User,
    Image,
    Maximize2,
    X,
    ExternalLink,
    AlertTriangle,
    FileText,
    ArrowLeft,
    Check,
    Calendar,
    Minus,
    Plus
} from '@lucide/vue';

const props = defineProps({
    id: {
        type: String,
        default: ''
    },
    reportDetail: {
        type: Object,
        default: null
    },
    staffMembers: {
        type: Array,
        default: () => []
    }
});

const isSubmitting = ref(false);
const showSuccessModal = ref(false);
const selectedImagePreview = ref(null);

const report = computed(() => props.reportDetail);
const isVerified = computed(() => report.value?.status === 'VERIFIED');
const staffList = ref(props.staffMembers ? props.staffMembers.map(s => ({ ...s, selected: s.selected || false })) : []);

const isFacilityComplaint = computed(() => {
    const cat = (report.value?.ai_category || '').toLowerCase();
    return cat.includes('sarana') || cat.includes('fasilitas') || cat.includes('prasarana');
});

const defaultActionType = computed(() => {
    if (report.value?.verified_action_type) {
        return report.value.verified_action_type;
    }
    // Jika keluhan sarana/fasilitas fisik dan bukan apresiasi, otomatis default ke NETRAL (0 Poin)
    if (isFacilityComplaint.value && report.value?.ai_sentiment !== 'POSITIF') {
        return 'NETRAL';
    }
    if (report.value?.ai_sentiment === 'POSITIF') {
        return 'PENAMBAHAN';
    }
    if (report.value?.ai_sentiment === 'NEGATIF') {
        return 'PEMOTONGAN';
    }
    return 'NETRAL';
});

const actionType = ref(defaultActionType.value);
const pointValue = ref(
    report.value?.verified_points !== null && report.value?.verified_points !== undefined 
        ? report.value.verified_points 
        : (actionType.value === 'NETRAL' ? 0 : 5)
);
const supervisorNotes = ref(report.value?.supervisor_notes || '');

const incrementPoint = () => {
    if (isVerified.value) return;
    if (pointValue.value < 100) {
        pointValue.value++;
    }
};

const decrementPoint = () => {
    if (isVerified.value) return;
    if (pointValue.value > 1) {
        pointValue.value--;
    }
};

const submitVerification = () => {
    if (isVerified.value) return;
    isSubmitting.value = true;
    const selectedIds = staffList.value.filter(s => s.selected).map(s => s.id);
    
    if (actionType.value !== 'NETRAL' && selectedIds.length === 0 && staffList.value.length > 0) {
        alert('Mohon pilih setidaknya 1 staf yang bertugas saat kejadian untuk mengaitkan poin KPI.');
        isSubmitting.value = false;
        return;
    }

    router.post(route('kasi.verify.process', { id: report.value.id }), {
        selected_staff_ids: selectedIds,
        action_type: actionType.value,
        points: actionType.value === 'NETRAL' ? 0 : pointValue.value,
        supervisor_notes: supervisorNotes.value
    }, {
        onSuccess: () => {
            isSubmitting.value = false;
            showSuccessModal.value = true;
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
};

const finishVerification = () => {
    router.get(route('kasi.dashboard'));
};
</script>

<template>
    <Head :title="report ? `Verifikasi Laporan ${report.id}` : 'Verifikasi Laporan'" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            
            <!-- Empty State: When No Report Exists in Database -->
            <div v-if="!report" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-12 text-center shadow-sm space-y-4">
                <div class="h-14 w-14 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                    <UserCheck class="h-7 w-7" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Tidak Ada Laporan yang Dipilih</h3>
                    <p class="text-xs text-slate-400 max-w-md mx-auto">
                        Silakan pilih laporan dari feed aduan unit untuk melakukan verifikasi aduan dan distribusi poin KPI staf.
                    </p>
                </div>
                <div>
                    <Link
                        :href="route('kasi.dashboard')"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-sm"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Kembali ke Feed Aduan</span>
                    </Link>
                </div>
            </div>

            <!-- When Report Exists -->
            <template v-else>
                <!-- Top Header Panel with Ticket Code & Status -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex">
                            <UserCheck class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Verifikasi Laporan {{ report.id }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                Verifikasi staf bertugas, evaluasi analisis AI, dan kelola saldo poin KPI staf unit.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold',
                            report.status === 'VERIFIED'
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                                : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800'
                        ]">
                            {{ report.status === 'VERIFIED' ? '✓ Telah Diverifikasi' : 'Menunggu Verifikasi Kasi' }}
                        </span>
                    </div>
                </div>

                <!-- Stacked Containers Layout (Tumpukan Atas ke Bawah) -->
                <div class="space-y-4">
                    
                    <!-- Top Row: Dua Kontainer Mandiri Berdampingan (Tanpa Container di dalam Container) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                        
                        <!-- 1. Container: Data & Uraian Aduan Pasien (8 Kolom) -->
                        <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            <!-- Header Kontainer -->
                            <div class="p-4 sm:p-5 bg-slate-50/60 dark:bg-slate-950/60 flex items-center justify-between gap-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Data & Uraian Aduan
                                </h3>
                                <span :class="[
                                    'px-2.5 py-1 rounded-lg text-[10px] font-medium',
                                    report.is_anonymous ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                ]">
                                    {{ report.is_anonymous ? 'Mode Anonim' : 'Identitas Terverifikasi' }}
                                </span>
                            </div>

                            <!-- Section 1: Identitas & Kontak Pelapor -->
                            <div class="p-4 sm:p-5 space-y-2">
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Identitas & Kontak Pelapor</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Nama Pasien</span>
                                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                            <div class="h-5 w-5 rounded-full bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center text-[10px] font-medium">
                                                {{ report.reporter_name.charAt(0) }}
                                            </div>
                                            <span>{{ report.reporter_name }}</span>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">No. WhatsApp / Telepon</span>
                                        <div v-if="report.reporter_phone" class="text-xs font-semibold text-slate-800 dark:text-slate-100">
                                            {{ report.reporter_phone }}
                                        </div>
                                        <span v-else class="text-xs text-slate-400 italic">Tidak dicantumkan</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Rincian Teks Aduan Pasien -->
                            <div class="p-4 sm:p-5 space-y-2">
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Rincian Teks Aduan Pasien</span>
                                <div class="bg-slate-50/70 dark:bg-slate-950/50 p-3.5 sm:p-4 rounded-xl border border-slate-200/70 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 text-xs sm:text-sm leading-relaxed font-normal">
                                    "{{ report.isi_laporan }}"
                                </div>
                            </div>

                            <!-- Section 3: Parameter & Lokasi Aduan -->
                            <div class="p-4 sm:p-5 space-y-2">
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Parameter & Lokasi Aduan</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <!-- Ruangan / Instalasi -->
                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Ruangan / Instalasi</span>
                                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">
                                            {{ report.unit }}
                                        </div>
                                    </div>

                                    <!-- Sasaran Aduan / Petugas / Loket -->
                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Sasaran / Petugas</span>
                                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">
                                            {{ report.target_object || 'Pelayanan Umum / Semua Staf' }}
                                        </div>
                                    </div>

                                    <!-- Waktu & Tanggal Laporan -->
                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Waktu Aduan</span>
                                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-100">
                                            {{ report.timestamp }}
                                        </div>
                                    </div>

                                    <!-- Tingkat Prioritas -->
                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Tingkat Prioritas</span>
                                        <div>
                                            <span :class="[
                                                'px-2 py-0.5 rounded text-[11px] font-semibold',
                                                report.priority === 'HIGH' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                            ]">
                                                {{ report.priority === 'HIGH' ? 'Prioritas Tinggi' : 'Prioritas Standar' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 4: Lampiran Foto / Bukti -->
                            <div class="p-4 sm:p-5 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500">Lampiran Foto & Bukti</span>
                                    <span class="text-[10px] text-slate-400">
                                        {{ report.attachments.length }} Berkas
                                    </span>
                                </div>

                                <div v-if="report.attachments.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                    <div
                                        v-for="att in report.attachments"
                                        :key="att.id"
                                        class="group relative rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-950 aspect-video flex items-center justify-center cursor-pointer"
                                        @click="selectedImagePreview = att.url"
                                    >
                                        <img
                                            :src="att.url"
                                            :alt="att.file_name"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-200"
                                        />
                                        <div class="absolute inset-0 bg-slate-950/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white gap-1.5 text-xs font-medium">
                                            <Maximize2 class="h-3.5 w-3.5" />
                                            <span>Perbesar</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="py-2.5 text-center text-slate-400 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 text-xs">
                                    <span>Pelapor tidak menyertakan foto lampiran.</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Container: Hasil Analisis AI Pintar (4 Kolom - Lebih Ramping & Ditumpuk) -->
                        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-emerald-300/80 dark:border-emerald-800/80 rounded-2xl shadow-sm overflow-hidden divide-y divide-emerald-100 dark:divide-slate-800 relative">
                            <!-- Header Hijau Solid yang Keren & Jelas -->
                            <div class="p-4 sm:p-5 bg-emerald-600 dark:bg-emerald-700 text-white flex items-center justify-between gap-3 shadow-xs">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-7 w-7 rounded-lg bg-white/20 text-white flex items-center justify-center shrink-0">
                                        <Sparkles class="h-3.5 w-3.5" />
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-white">
                                            Hasil Analisis AI
                                        </h3>
                                        <p class="text-[10px] text-emerald-100 font-normal">
                                            Klasifikasi real-time & deteksi urgensi
                                        </p>
                                    </div>
                                </div>
                                <span class="relative flex h-2 w-2 shrink-0">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                                </span>
                            </div>

                            <!-- Body Analisis AI -->
                            <div class="p-4 sm:p-5 space-y-4">
                                <!-- 4 Kartu Metrik AI Ditumpuk Vertikal (1 Kolom) -->
                                <div class="grid grid-cols-1 gap-2.5">
                                    <!-- Metrik 1: Sentimen AI -->
                                    <div class="bg-slate-50/70 dark:bg-slate-950/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 transition space-y-1">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 block font-normal">Sentimen Pasien</span>
                                        <div class="flex items-center justify-between">
                                            <span :class="[
                                                'text-xs font-semibold flex items-center gap-1',
                                                report.ai_sentiment === 'POSITIF' ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                                            ]">
                                                <TrendingUp v-if="report.ai_sentiment === 'POSITIF'" class="h-3 w-3 shrink-0" />
                                                <TrendingDown v-else class="h-3 w-3 shrink-0" />
                                                <span>{{ report.ai_sentiment }}</span>
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-normal">({{ report.ai_confidence }})</span>
                                        </div>
                                    </div>

                                    <!-- Metrik 2: Kategori Masalah -->
                                    <div class="bg-slate-50/70 dark:bg-slate-950/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 transition space-y-1">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 block font-normal">Kategori Masalah</span>
                                        <div class="text-xs font-semibold text-slate-800 dark:text-white truncate" :title="report.ai_category">
                                            {{ report.ai_category }}
                                        </div>
                                    </div>

                                    <!-- Metrik 3: Urgensi Tindakan -->
                                    <div class="bg-slate-50/70 dark:bg-slate-950/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 transition space-y-1">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 block font-normal">Urgensi Tindakan</span>
                                        <div>
                                            <span :class="[
                                                'px-2 py-0.5 rounded text-[10px] font-semibold inline-block',
                                                report.ai_urgency === 'TINGGI' || report.ai_urgency === 'KRITIS'
                                                    ? 'bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-500/20 dark:text-rose-300 dark:border-rose-500/30'
                                                    : 'bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30'
                                            ]">
                                                {{ report.ai_urgency }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Metrik 4: Rekomendasi Poin KPI -->
                                    <div class="bg-slate-50/70 dark:bg-slate-950/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 transition space-y-1">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 block font-normal">Arahan Aksi KPI</span>
                                        <div class="text-xs font-semibold">
                                            <span v-if="report.ai_sentiment === 'POSITIF'" class="text-emerald-700 dark:text-emerald-400">
                                                Reward (+ Poin)
                                            </span>
                                            <span v-else-if="isFacilityComplaint && report.ai_sentiment !== 'POSITIF'" class="text-blue-700 dark:text-blue-300">
                                                Netral (0 Poin)
                                            </span>
                                            <span v-else-if="report.ai_sentiment === 'NEGATIF'" class="text-rose-600 dark:text-rose-400">
                                                Evaluasi (- Poin)
                                            </span>
                                            <span v-else class="text-blue-700 dark:text-blue-300">
                                                Netral (0 Poin)
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Box Rekomendasi Solusi & Arahan AI -->
                                <div v-if="report.ai_recommendation" class="bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-500/30 rounded-xl p-4 space-y-2">
                                    <div class="flex items-center gap-1.5 text-emerald-800 dark:text-emerald-400 font-semibold text-[11px]">
                                        <Sparkles class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                        <span>Rekomendasi Solusi & Evaluasi:</span>
                                    </div>
                                    <p class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed font-normal">
                                        {{ report.ai_recommendation }}
                                    </p>
                                    <div v-if="report.ai_summary" class="pt-2 border-t border-emerald-200/80 dark:border-emerald-500/20 text-[11px] text-slate-500 dark:text-slate-400 font-normal">
                                        <span class="font-medium text-emerald-900/80 dark:text-emerald-300">Ringkasan AI:</span> {{ report.ai_summary }}
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Bar / Footer Analisis AI -->
                            <div class="px-4 py-2.5 bg-slate-50/80 dark:bg-slate-950/60 flex items-center gap-2">
                                <ShieldCheck class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                <p class="text-[10.5px] text-slate-500 dark:text-slate-400 italic leading-snug">
                                    Diproses otomatis oleh <span class="font-semibold not-italic text-slate-700 dark:text-slate-300">{{ report.ai_provider || 'Groq AI (Llama-3)' }}</span> sebagai validasi bukti telaah aduan.
                                </p>
                            </div>
                        </div>

                    </div>

                    <!-- 2. Container: Shift Staff Matching Card -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            
                            <!-- Staff Matching Container Header -->
                            <div class="p-4 sm:p-5 bg-slate-50/60 dark:bg-slate-950/60 flex flex-wrap items-center justify-between gap-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Pencocokan Staf Bertugas
                                </h3>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                    <Clock class="h-3 w-3 text-slate-400" />
                                    <span>{{ report.created_at_time || report.timestamp }}</span>
                                </span>
                            </div>

                            <!-- Staff Matching Container Body -->
                            <div class="p-4 sm:p-5 space-y-3">
                                <p v-if="isVerified" class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-normal">
                                    Daftar staf unit yang telah ditautkan dan dievaluasi pada verifikasi laporan ini:
                                </p>
                                <p v-else class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-normal">
                                    Centang staf yang bertugas saat aduan terjadi untuk verifikasi & evaluasi poin KPI (otomatis ditandai dari data Presensi Masuk):
                                </p>

                                <!-- Staff List Checkbox Grid -->
                                <div v-if="staffList.length > 0" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                                    <label
                                        v-for="staff in staffList"
                                        :key="staff.id"
                                        :class="[
                                            'flex items-center justify-between p-3 rounded-xl border select-none transition',
                                            isVerified ? 'cursor-default' : 'cursor-pointer',
                                            staff.selected 
                                                ? 'bg-emerald-50/80 border-emerald-500 dark:bg-emerald-950/40 dark:border-emerald-700' 
                                                : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-slate-300'
                                        ]"
                                    >
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="checkbox"
                                                v-model="staff.selected"
                                                :disabled="isVerified"
                                                class="rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4 accent-emerald-600 disabled:cursor-not-allowed"
                                            />
                                            <div>
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ staff.name }}</span>
                                                    <span
                                                        v-if="staff.attendance_type === 'ACTIVE_AT_REPORT'"
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800"
                                                        title="Tercatat berdinas saat jam aduan diterima"
                                                    >
                                                        ✓ On-Duty saat Kejadian
                                                    </span>
                                                    <span
                                                        v-else-if="staff.attendance_type === 'TODAY'"
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
                                                    >
                                                        Hadir Hari Ini
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-normal mt-0.5">
                                                    {{ staff.role }} • NIP: {{ staff.nip }}
                                                    <span v-if="staff.clock_in_time" class="ml-1 text-slate-400">
                                                        (Presensi: {{ staff.clock_in_time }})
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 block">
                                                {{ staff.total_points }} Poin
                                            </span>
                                            <span v-if="staff.selected" class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                                Terpilih
                                            </span>
                                        </div>
                                    </label>
                                </div>

                                <!-- If No Staff Registered in this Unit -->
                                <div v-else class="text-center py-6 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl space-y-2">
                                    <div class="h-9 w-9 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                                        <Users class="h-4 w-4" />
                                    </div>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 font-normal">
                                        Belum ada staf terdaftar di unit {{ report.unit }}.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Container: KPI Point Execution & Supervisor Notes Form -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            
                            <!-- KPI Form Container Header -->
                            <div class="p-4 sm:p-5 bg-slate-50/60 dark:bg-slate-950/60 flex items-center justify-between gap-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Eksekusi Poin KPI & Berita Acara
                                </h3>
                                <span v-if="isVerified" class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 flex items-center gap-1 shrink-0">
                                    <Check class="h-3 w-3" />
                                    <span>Telah Dieksekusi</span>
                                </span>
                                <span v-else class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white shrink-0">
                                    Poin KPI
                                </span>
                            </div>

                            <!-- KPI Form Container Body -->
                            <div class="p-4 sm:p-5 space-y-4">
                                <!-- Action Type Selector -->
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Jenis Tindakan KPI:</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                         <button
                                            type="button"
                                            :disabled="isVerified"
                                            @click="if (!isVerified) { actionType = 'PEMOTONGAN'; if (pointValue === 0) pointValue = 5; }"
                                            :class="[
                                                'py-2.5 sm:py-2 px-3 sm:px-2 rounded-xl border text-xs font-semibold flex items-center justify-center gap-2 sm:gap-1.5 transition outline-none focus:outline-none focus:ring-0 select-none',
                                                isVerified ? 'cursor-not-allowed' : 'cursor-pointer active:scale-[0.98]',
                                                actionType === 'PEMOTONGAN' 
                                                    ? 'bg-rose-600 border-rose-600 text-white shadow-xs' 
                                                    : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900'
                                            ]"
                                        >
                                            <TrendingDown :class="['h-4 w-4 sm:h-3.5 sm:w-3.5 shrink-0', actionType === 'PEMOTONGAN' ? 'text-white' : 'text-rose-500']" />
                                            <span>Potong Poin (-)</span>
                                        </button>

                                        <button
                                            type="button"
                                            :disabled="isVerified"
                                            @click="if (!isVerified) { actionType = 'PENAMBAHAN'; if (pointValue === 0) pointValue = 5; }"
                                            :class="[
                                                'py-2.5 sm:py-2 px-3 sm:px-2 rounded-xl border text-xs font-semibold flex items-center justify-center gap-2 sm:gap-1.5 transition outline-none focus:outline-none focus:ring-0 select-none',
                                                isVerified ? 'cursor-not-allowed' : 'cursor-pointer active:scale-[0.98]',
                                                actionType === 'PENAMBAHAN' 
                                                    ? 'bg-emerald-600 border-emerald-600 text-white shadow-xs' 
                                                    : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900'
                                            ]"
                                        >
                                            <TrendingUp :class="['h-4 w-4 sm:h-3.5 sm:w-3.5 shrink-0', actionType === 'PENAMBAHAN' ? 'text-white' : 'text-emerald-500']" />
                                            <span>Tambah Poin (+)</span>
                                        </button>

                                        <button
                                            type="button"
                                            :disabled="isVerified"
                                            @click="if (!isVerified) { actionType = 'NETRAL'; pointValue = 0; }"
                                            :class="[
                                                'py-2.5 sm:py-2 px-3 sm:px-2 rounded-xl border text-xs font-semibold flex items-center justify-center gap-2 sm:gap-1.5 transition outline-none focus:outline-none focus:ring-0 select-none',
                                                isVerified ? 'cursor-not-allowed' : 'cursor-pointer active:scale-[0.98]',
                                                actionType === 'NETRAL' 
                                                    ? 'bg-blue-600 border-blue-600 text-white shadow-xs' 
                                                    : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900'
                                            ]"
                                        >
                                            <ShieldCheck :class="['h-4 w-4 sm:h-3.5 sm:w-3.5 shrink-0', actionType === 'NETRAL' ? 'text-white' : 'text-blue-500']" />
                                            <span>Netral (0 Poin)</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Point Value Input: Clean Stepper with Direct Typed Sign -->
                                <div v-if="actionType !== 'NETRAL'" class="space-y-1.5">
                                    <label class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Besaran Poin Per Staf Terpilih:</label>
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <button
                                            type="button"
                                            :disabled="isVerified"
                                            @click="decrementPoint"
                                            :class="[
                                                'h-11 w-11 sm:h-10 sm:w-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center transition shrink-0 font-bold',
                                                isVerified ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-95 cursor-pointer'
                                            ]"
                                        >
                                            <Minus class="h-4 w-4" />
                                        </button>
                                        
                                        <div class="relative flex-1">
                                            <input
                                                :value="actionType === 'PEMOTONGAN' ? `-${pointValue}` : `+${pointValue}`"
                                                :disabled="isVerified"
                                                inputmode="numeric"
                                                @input="e => {
                                                    if (isVerified) return;
                                                    const cleaned = e.target.value.replace(/[^0-9]/g, '');
                                                    pointValue = cleaned ? parseInt(cleaned) : 1;
                                                }"
                                                type="text"
                                                :class="[
                                                    'w-full h-11 sm:h-10 text-center font-bold text-base sm:text-sm rounded-xl border bg-slate-50 dark:bg-slate-950 focus:outline-none transition px-12',
                                                    isVerified ? 'cursor-not-allowed opacity-80' : '',
                                                    actionType === 'PEMOTONGAN' 
                                                        ? 'text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-900/50 focus:border-rose-500' 
                                                        : 'text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/50 focus:border-emerald-500'
                                                ]"
                                            />
                                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400 pointer-events-none">
                                                Poin
                                            </span>
                                        </div>

                                        <button
                                            type="button"
                                            :disabled="isVerified"
                                            @click="incrementPoint"
                                            :class="[
                                                'h-11 w-11 sm:h-10 sm:w-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center transition shrink-0 font-bold',
                                                isVerified ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-95 cursor-pointer'
                                            ]"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Info Callout for Netral Action (0 Poin) -->
                                <div v-else class="p-3.5 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-900/50 text-blue-800 dark:text-blue-300 text-xs leading-relaxed space-y-1">
                                    <div class="font-semibold flex items-center gap-1.5">
                                        <CheckCircle2 class="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                        <span>Status Tindakan Netral (0 Poin KPI)</span>
                                    </div>
                                    <p class="text-[11px] text-blue-700/90 dark:text-blue-300/90 font-normal">
                                        Laporan ini dikategorikan sebagai masukan fasilitas/operasional umum. <strong>Tidak ada saldo poin KPI staf yang dipotong maupun ditambah</strong>.
                                    </p>
                                </div>

                                <!-- Supervisor Notes -->
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Catatan Berita Acara / Tindak Lanjut:</label>
                                    <textarea
                                        v-model="supervisorNotes"
                                        :disabled="isVerified"
                                        :readonly="isVerified"
                                        rows="3"
                                        placeholder="Tuliskan klarifikasi kejadian, evaluasi tindakan, atau catatan apresiasi untuk staf..."
                                        :class="[
                                            'w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs sm:text-sm text-slate-800 dark:text-slate-200 leading-relaxed font-normal focus:outline-none transition',
                                            isVerified ? 'cursor-not-allowed opacity-80' : 'focus:border-emerald-500'
                                        ]"
                                    ></textarea>
                                </div>

                                <!-- Verified State Banner or Submit Button -->
                                <div v-if="isVerified" class="p-3.5 sm:p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-center space-y-1.5 select-none shadow-xs">
                                    <div class="flex items-center justify-center gap-2 text-emerald-800 dark:text-emerald-300 font-bold text-xs sm:text-sm">
                                        <CheckCircle2 class="h-4 w-4 sm:h-4.5 sm:w-4.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                        <span>Laporan Ini Telah Selesai Diverifikasi</span>
                                    </div>
                                    <p class="text-[11px] sm:text-xs text-emerald-700/90 dark:text-emerald-300/80 font-normal leading-relaxed">
                                        Verifikasi dieksekusi pada <strong>{{ report.verified_at || '-' }}</strong><template v-if="report.verified_by"> oleh <strong>{{ report.verified_by }}</strong></template>. Saldo poin staf unit telah tercatat di logbook dan tidak dapat diubah kembali.
                                    </p>
                                </div>

                                <button
                                    v-else
                                    @click="submitVerification"
                                    :disabled="isSubmitting"
                                    class="w-full py-3.5 sm:py-3 bg-emerald-600 hover:bg-emerald-500 active:scale-[0.99] text-white rounded-xl text-xs sm:text-sm font-semibold flex items-center justify-center gap-2 shadow-sm transition disabled:opacity-50 cursor-pointer"
                                >
                                    <CheckCircle2 class="h-4 w-4" />
                                    <span>{{ isSubmitting ? 'Memproses Verifikasi...' : 'Simpan Verifikasi & Catat Logbook' }}</span>
                                </button>
                            </div>
                        </div>

                </div>
            </template>

        </div>

        <!-- Lightbox Image Preview Modal -->
        <div v-if="selectedImagePreview" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md">
            <div class="relative max-w-4xl w-full max-h-[90vh] flex flex-col items-center justify-center">
                <button
                    @click="selectedImagePreview = null"
                    class="absolute -top-10 right-0 text-white hover:text-slate-300 p-2 rounded-full cursor-pointer"
                >
                    <X class="h-6 w-6" />
                </button>
                <img
                    :src="selectedImagePreview"
                    alt="Lampiran Bukti Penuh"
                    class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl border border-white/10"
                />
            </div>
        </div>

        <!-- Success Verification Modal -->
        <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl text-center space-y-4">
                <div class="h-16 w-16 bg-emerald-100 text-emerald-600 dark:bg-white/10 dark:text-white rounded-full flex items-center justify-center mx-auto">
                    <CheckCircle2 class="h-8 w-8" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Verifikasi Berhasil Disimpan!</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Status laporan telah diperbarui menjadi <strong>TERVERIFIKASI</strong> dan poin KPI staf telah dicatat dalam Digital Logbook Unit.
                    </p>
                </div>
                <div class="pt-2">
                    <button
                        @click="finishVerification"
                        class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold cursor-pointer"
                    >
                        Kembali ke Feed Aduan Kasi
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
