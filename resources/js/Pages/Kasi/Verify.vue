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
const staffList = ref(props.staffMembers ? props.staffMembers.map(s => ({ ...s, selected: s.selected || false })) : []);

const actionType = ref(report.value?.ai_sentiment === 'POSITIF' ? 'PENAMBAHAN' : (report.value?.ai_sentiment === 'NEGATIF' ? 'PEMOTONGAN' : 'NETRAL'));
const pointValue = ref(5);
const supervisorNotes = ref(report.value?.supervisor_notes || '');

const incrementPoint = () => {
    if (pointValue.value < 100) {
        pointValue.value++;
    }
};

const decrementPoint = () => {
    if (pointValue.value > 1) {
        pointValue.value--;
    }
};

const submitVerification = () => {
    isSubmitting.value = true;
    const selectedIds = staffList.value.filter(s => s.selected).map(s => s.id);
    
    if (actionType.value !== 'NETRAL' && selectedIds.length === 0 && staffList.value.length > 0) {
        alert('Mohon pilih setidaknya 1 staf yang bertugas pada shift tersebut untuk mengaitkan poin KPI.');
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
                        Silakan pilih laporan dari feed aduan unit untuk melakukan verifikasi shift dan distribusi poin KPI staf.
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
                                Verifikasi shift kerja, evaluasi analisis AI, dan kelola saldo poin KPI staf unit.
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

                <!-- Main Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    
                    <!-- Left Column: Report Details & Separate AI Insights (7 cols) -->
                    <div class="lg:col-span-7 space-y-4">
                        
                        <!-- 1. Container Detail Laporan & Lampiran Pasien -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            
                            <!-- Container Header: Unit & Tanggal -->
                            <div class="p-5 bg-slate-50/60 dark:bg-slate-950/60 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20">
                                        {{ report.unit }}
                                    </span>
                                </div>

                                <div class="text-[11px] text-slate-400 flex items-center gap-1.5 font-medium">
                                    <Calendar class="h-3.5 w-3.5" />
                                    <span>{{ report.timestamp }}</span>
                                </div>
                            </div>

                            <!-- Section 1: Rincian Teks Aduan Pasien -->
                            <div class="p-5 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Rincian Teks Aduan Pasien</span>
                                    <span v-if="report.target_object" class="text-[10px] text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded font-bold">
                                        Petugas / Loket: {{ report.target_object }}
                                    </span>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-950 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 text-slate-800 dark:text-slate-100 text-sm leading-relaxed italic font-medium">
                                    "{{ report.isi_laporan }}"
                                </div>
                            </div>

                            <!-- Section 2: Identitas & Kontak Pelapor -->
                            <div class="p-5 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Identitas & Kontak Pelapor</span>
                                    <span :class="[
                                        'px-2 py-0.5 rounded-full text-[9px] font-bold uppercase',
                                        report.is_anonymous ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                    ]">
                                        {{ report.is_anonymous ? 'Mode Anonim' : 'Identitas Terverifikasi' }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200/80 dark:border-slate-800 space-y-0.5">
                                        <span class="text-[9px] font-bold uppercase text-slate-400 block">Nama Pasien</span>
                                        <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                            <div class="h-5 w-5 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-[10px]">
                                                {{ report.reporter_name.charAt(0) }}
                                            </div>
                                            <span>{{ report.reporter_name }}</span>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200/80 dark:border-slate-800 space-y-0.5">
                                        <span class="text-[9px] font-bold uppercase text-slate-400 block">No. WhatsApp / Telepon</span>
                                        <div v-if="report.reporter_phone" class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ report.reporter_phone }}</span>
                                            <a
                                                :href="`https://wa.me/${report.reporter_phone.replace(/[^0-9]/g, '')}`"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold transition"
                                            >
                                                <Phone class="h-3 w-3" />
                                                <span>Chat WA</span>
                                            </a>
                                        </div>
                                        <span v-else class="text-xs text-slate-400 italic">Tidak dicantumkan</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 3: Lampiran Foto / Bukti -->
                            <div class="p-5 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Lampiran Foto & Bukti</span>
                                    <span class="text-[10px] text-slate-400 font-bold">
                                        {{ report.attachments.length }} Berkas
                                    </span>
                                </div>

                                <div v-if="report.attachments.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <div
                                        v-for="att in report.attachments"
                                        :key="att.id"
                                        class="group relative rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-950 aspect-video flex items-center justify-center cursor-pointer shadow-sm"
                                        @click="selectedImagePreview = att.url"
                                    >
                                        <img
                                            :src="att.url"
                                            :alt="att.file_name"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-200"
                                        />
                                        <div class="absolute inset-0 bg-slate-950/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white gap-1 text-xs font-bold">
                                            <Maximize2 class="h-4 w-4" />
                                            <span>Perbesar</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="py-3 text-center text-slate-400 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200/80 dark:border-slate-800 text-xs">
                                    <span>Pelapor tidak menyertakan foto lampiran.</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Container Terpisah: Hasil Analisis AI Pintar (Groq AI Insights) -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            
                            <!-- AI Container Header -->
                            <div class="p-5 bg-slate-50/60 dark:bg-slate-950/60 flex flex-wrap items-center justify-between gap-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Hasil Analisis AI Pintar
                                </h3>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                    {{ report.ai_provider }}
                                </span>
                            </div>

                            <!-- AI Container Body -->
                            <div class="p-5 space-y-3.5">
                                <div class="grid grid-cols-3 gap-2.5">
                                    <div class="bg-slate-50 dark:bg-slate-950 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-800">
                                        <span class="text-[9px] font-bold uppercase text-slate-400 block">Sentimen AI</span>
                                        <span :class="[
                                            'text-xs font-extrabold',
                                            report.ai_sentiment === 'POSITIF' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                                        ]">
                                            {{ report.ai_sentiment }} ({{ report.ai_confidence }})
                                        </span>
                                    </div>

                                    <div class="bg-slate-50 dark:bg-slate-950 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-800">
                                        <span class="text-[9px] font-bold uppercase text-slate-400 block">Kategori Masalah</span>
                                        <span class="text-xs font-bold text-slate-900 dark:text-white truncate block">{{ report.ai_category }}</span>
                                    </div>

                                    <div class="bg-slate-50 dark:bg-slate-950 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-800">
                                        <span class="text-[9px] font-bold uppercase text-slate-400 block">Urgensi Tindakan</span>
                                        <span :class="[
                                            'text-xs font-extrabold uppercase',
                                            report.ai_urgency === 'TINGGI' || report.ai_urgency === 'KRITIS' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'
                                        ]">
                                            {{ report.ai_urgency }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="report.ai_recommendation" class="bg-emerald-50/80 dark:bg-emerald-950/40 p-3.5 rounded-xl border border-emerald-200/80 dark:border-emerald-900/60 space-y-1">
                                    <span class="text-[9px] font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-300 block">
                                        Rekomendasi Solusi AI:
                                    </span>
                                    <p class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed font-medium">
                                        {{ report.ai_recommendation }}
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Shift Staff Matching & KPI Point Action Form (5 cols) -->
                    <div class="lg:col-span-5 space-y-4">
                        
                        <!-- Shift Staff Matching Card -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            
                            <!-- Staff Matching Container Header -->
                            <div class="p-5 bg-slate-50/60 dark:bg-slate-950/60 flex flex-wrap items-center justify-between gap-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Pencocokan Staf Bertugas
                                </h3>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    {{ report.shift_info }}
                                </span>
                            </div>

                            <!-- Staff Matching Container Body -->
                            <div class="p-5 space-y-3">
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Centang staf yang bertugas pada shift ini untuk mengaitkan catatan verifikasi & poin KPI:
                                </p>

                                <!-- Staff List Checkbox Grid -->
                                <div v-if="staffList.length > 0" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                                    <label
                                        v-for="staff in staffList"
                                        :key="staff.id"
                                        :class="[
                                            'flex items-center justify-between p-3 rounded-xl border cursor-pointer select-none transition',
                                            staff.selected 
                                                ? 'bg-emerald-50/80 border-emerald-500 dark:bg-emerald-950/40 dark:border-emerald-700' 
                                                : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-slate-300'
                                        ]"
                                    >
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="checkbox"
                                                v-model="staff.selected"
                                                class="rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4 accent-emerald-600"
                                            />
                                            <div>
                                                <div class="text-xs font-bold text-slate-900 dark:text-white">{{ staff.name }}</div>
                                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ staff.role }} • NIP: {{ staff.nip }}</div>
                                            </div>
                                        </div>
                                        <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                            {{ staff.total_points }} Poin
                                        </span>
                                    </label>
                                </div>

                                <!-- If No Staff Registered in this Unit -->
                                <div v-else class="py-6 text-center text-slate-400 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200/80 dark:border-slate-800 space-y-2">
                                    <Users class="h-6 w-6 mx-auto opacity-40" />
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Belum ada staf terdaftar di unit {{ report.unit }}.</p>
                                    <Link
                                        :href="route('users.index')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-[11px] font-bold hover:bg-emerald-500"
                                    >
                                        <span>Buka Daftar Pengguna</span>
                                        <ExternalLink class="h-3 w-3" />
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- KPI Point Execution & Supervisor Notes Form -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            
                            <!-- KPI Form Container Header -->
                            <div class="p-5 bg-slate-50/60 dark:bg-slate-950/60 flex flex-wrap items-center justify-between gap-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Eksekusi Poin KPI & Berita Acara
                                </h3>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white">
                                    Poin KPI
                                </span>
                            </div>

                            <!-- KPI Form Container Body -->
                            <div class="p-5 space-y-4">
                                <!-- Action Type Selector -->
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Jenis Tindakan KPI:</label>
                                    <div class="grid grid-cols-3 gap-2">
                                         <button
                                            type="button"
                                            @click="actionType = 'PEMOTONGAN'; if (pointValue === 0) pointValue = 5"
                                            :class="[
                                                'py-2.5 px-2 rounded-xl border text-[11px] font-extrabold flex items-center justify-center gap-1.5 transition cursor-pointer',
                                                actionType === 'PEMOTONGAN' ? 'bg-rose-50 border-rose-500 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-500'
                                            ]"
                                        >
                                            <TrendingDown class="h-3.5 w-3.5" />
                                            <span>Potong (-)</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="actionType = 'PENAMBAHAN'; if (pointValue === 0) pointValue = 5"
                                            :class="[
                                                'py-2.5 px-2 rounded-xl border text-[11px] font-extrabold flex items-center justify-center gap-1.5 transition cursor-pointer',
                                                actionType === 'PENAMBAHAN' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-500'
                                            ]"
                                        >
                                            <TrendingUp class="h-3.5 w-3.5" />
                                            <span>Tambah (+)</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="actionType = 'NETRAL'; pointValue = 0"
                                            :class="[
                                                'py-2.5 px-2 rounded-xl border text-[11px] font-extrabold flex items-center justify-center gap-1.5 transition cursor-pointer',
                                                actionType === 'NETRAL' ? 'bg-blue-50 border-blue-500 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-500'
                                            ]"
                                        >
                                            <span>Netral (0)</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Point Value Input: Clean Stepper with Direct Typed Sign -->
                                <div v-if="actionType !== 'NETRAL'" class="space-y-1.5">
                                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Besaran Poin Per Staf Terpilih:</label>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="decrementPoint"
                                            class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center transition cursor-pointer shrink-0 font-bold"
                                        >
                                            <Minus class="h-4 w-4" />
                                        </button>
                                        
                                        <div class="relative flex-1">
                                            <input
                                                :value="actionType === 'PEMOTONGAN' ? `-${pointValue}` : `+${pointValue}`"
                                                @input="e => {
                                                    const cleaned = e.target.value.replace(/[^0-9]/g, '');
                                                    pointValue = cleaned ? parseInt(cleaned) : 1;
                                                }"
                                                type="text"
                                                :class="[
                                                    'w-full h-10 text-center font-extrabold text-sm rounded-xl border bg-slate-50 dark:bg-slate-950 focus:outline-none transition',
                                                    actionType === 'PEMOTONGAN' 
                                                        ? 'text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-900/50 focus:border-rose-500' 
                                                        : 'text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/50 focus:border-emerald-500'
                                                ]"
                                            />
                                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">
                                                Poin
                                            </span>
                                        </div>

                                        <button
                                            type="button"
                                            @click="incrementPoint"
                                            class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center transition cursor-pointer shrink-0 font-bold"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Info Callout for Netral Action (0 Poin) -->
                                <div v-else class="p-3.5 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-900/50 text-blue-800 dark:text-blue-300 text-xs leading-relaxed space-y-1">
                                    <div class="font-extrabold flex items-center gap-1.5">
                                        <CheckCircle2 class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                        <span>Status Tindakan Netral (0 Poin KPI)</span>
                                    </div>
                                    <p class="text-[11px] text-blue-700/90 dark:text-blue-300/90">
                                        Laporan ini dikategorikan sebagai masukan fasilitas/operasional umum. <strong>Tidak ada saldo poin KPI staf yang dipotong maupun ditambah</strong>.
                                    </p>
                                </div>

                                <!-- Supervisor Notes -->
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Catatan Berita Acara / Tindak Lanjut:</label>
                                    <textarea
                                        v-model="supervisorNotes"
                                        rows="3"
                                        placeholder="Tuliskan klarifikasi shift, evaluasi, atau catatan apresiasi untuk staf..."
                                        class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs text-slate-800 dark:text-slate-200 leading-relaxed focus:outline-none focus:border-emerald-500"
                                    ></textarea>
                                </div>

                                <!-- Submit Button -->
                                <button
                                    @click="submitVerification"
                                    :disabled="isSubmitting"
                                    class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-sm transition disabled:opacity-50 cursor-pointer"
                                >
                                    <CheckCircle2 class="h-4 w-4" />
                                    <span>{{ isSubmitting ? 'Memproses Verifikasi...' : 'Simpan Verifikasi & Catat Logbook' }}</span>
                                </button>
                            </div>
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
