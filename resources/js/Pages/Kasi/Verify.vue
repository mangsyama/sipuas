<script setup>
import { ref } from 'vue';
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
    Send
} from '@lucide/vue';

const props = defineProps({
    id: {
        type: String,
        default: 'LP-2026-08-001'
    }
});

const isSubmitting = ref(false);
const showSuccessModal = ref(false);

const reportDetail = ref({
    id: props.id,
    timestamp: '2026-08-06 14:30',
    unit: 'Instalasi Farmasi',
    isi_laporan: 'Ambil obat lama sekali, sudah antre 2 jam petugasnya malah asyik ngobrol dan lambat melayani.',
    ai_sentiment: 'NEGATIF',
    ai_category: 'Waktu Tunggu & Pelayanan',
    ai_confidence: '96%',
    shift_info: 'Shift Pagi / Siang (08:00 - 15:00 Wita)'
});

const staffMembers = ref([
    { id: 1, name: 'Sinta Dewi, A.Md.Farm', role: 'Apoteker Pelaksana', selected: true },
    { id: 2, name: 'Budi Santoso, S.Farm', role: 'Petugas Penyerahan Obat', selected: true },
    { id: 3, name: 'Citra Lestari, A.Md.AK', role: 'Petugas Gudang Farmasi', selected: false },
    { id: 4, name: 'Doni Pratama', role: 'Staf Administrasi Resep', selected: false }
]);

const actionType = ref('PEMOTONGAN');
const pointValue = ref(5);
const supervisorNotes = ref('Hasil verifikasi shift: Terjadi kepadatan antrean resep obat dan kecepatan pelayanan staf perlu ditingkatkan saat jam sibuk.');

const submitVerification = () => {
    isSubmitting.value = true;
    setTimeout(() => {
        isSubmitting.value = false;
        showSuccessModal.value = true;
    }, 1000);
};

const finishVerification = () => {
    router.get(route('kasi.dashboard'));
};
</script>

<template>
    <Head title="Verifikasi Detail Laporan & Shift" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel (WA Gateway Style - Navbar handles Back button) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <UserCheck class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Verifikasi Laporan {{ reportDetail.id }}
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20">
                                {{ reportDetail.unit }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Verifikasi shift kerja dan kelola poin KPI staf unit.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                        AI Sentimen: {{ reportDetail.ai_sentiment }} ({{ reportDetail.ai_confidence }})
                    </span>
                </div>
            </div>

            <!-- Main Content Container Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Left Column: Report Text & Shift Selection (7 cols) -->
                <div class="lg:col-span-7 space-y-4">
                    <!-- Detail Teks Aduan Card -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Rincian Teks Aduan Pasien</h3>
                            <span class="text-xs text-slate-400 flex items-center gap-1">
                                <Clock class="h-3.5 w-3.5" /> {{ reportDetail.timestamp }}
                            </span>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-950 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 text-slate-800 dark:text-slate-100 text-sm leading-relaxed italic font-medium">
                            "{{ reportDetail.isi_laporan }}"
                        </div>
                    </div>

                    <!-- Shift Staff Matching Card -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2">
                                <Users class="h-5 w-5 text-emerald-500" />
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">Pencocokan Staf Bertugas</h3>
                            </div>
                            <span class="text-xs text-slate-400 font-medium">{{ reportDetail.shift_info }}</span>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Centang staf yang bertugas pada shift tersebut untuk mengaitkan poin KPI secara langsung:
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <label
                                v-for="staff in staffMembers"
                                :key="staff.id"
                                :class="[
                                    'flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer select-none transition',
                                    staff.selected ? 'bg-emerald-50/70 border-emerald-500/80 dark:bg-white/10 dark:border-white/30' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800'
                                ]"
                            >
                                <input
                                    type="checkbox"
                                    v-model="staff.selected"
                                    class="mt-1 rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4"
                                />
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">{{ staff.name }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ staff.role }}</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Right Column: KPI Point Execution Form (5 cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <ShieldCheck class="h-5 w-5 text-emerald-500" />
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">Eksekusi Poin KPI</h3>
                        </div>

                        <!-- Action Type Toggle -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Jenis Tindakan KPI:</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="actionType = 'PEMOTONGAN'"
                                    :class="[
                                        'py-2.5 px-3 rounded-xl border text-xs font-extrabold flex items-center justify-center gap-1.5 transition',
                                        actionType === 'PEMOTONGAN' ? 'bg-rose-50 border-rose-500 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-500'
                                    ]"
                                >
                                    <TrendingDown class="h-4 w-4" />
                                    <span>Potong Poin</span>
                                </button>

                                <button
                                    type="button"
                                    @click="actionType = 'PENAMBAHAN'"
                                    :class="[
                                        'py-2.5 px-3 rounded-xl border text-xs font-extrabold flex items-center justify-center gap-1.5 transition',
                                        actionType === 'PENAMBAHAN' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 dark:bg-white/10 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-500'
                                    ]"
                                >
                                    <TrendingUp class="h-4 w-4" />
                                    <span>Tambah Poin</span>
                                </button>
                            </div>
                        </div>

                        <!-- Point Value -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Besaran Poin KPI Per Staf:</label>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model.number="pointValue"
                                    type="number"
                                    min="1"
                                    max="20"
                                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-xs font-bold text-slate-900 dark:text-white"
                                />
                                <span class="text-xs text-slate-400 shrink-0 font-medium">(Default: 5 Poin)</span>
                            </div>
                        </div>

                        <!-- Notes textarea -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Catatan Supervisor:</label>
                            <textarea
                                v-model="supervisorNotes"
                                rows="3"
                                class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs text-slate-800 dark:text-slate-200 leading-relaxed focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            ></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button
                                @click="submitVerification"
                                :disabled="isSubmitting"
                                class="w-full h-10 bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 rounded-xl text-xs font-extrabold flex items-center justify-center gap-2 shadow-sm transition disabled:opacity-50"
                            >
                                <svg v-if="isSubmitting" class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Validasi & Update Logbook Staf</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Modal Simulation -->
        <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl text-center space-y-4">
                <div class="h-16 w-16 bg-emerald-100 text-emerald-600 dark:bg-white/10 dark:text-white rounded-full flex items-center justify-center mx-auto">
                    <CheckCircle2 class="h-8 w-8" />
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Verifikasi Berhasil Disimpan!</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Poin KPI staf terpilih telah diperbarui dan dicatat dalam Digital Logbook Unit.
                </p>
                <div class="pt-2">
                    <button
                        @click="finishVerification"
                        class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 rounded-xl text-xs font-bold"
                    >
                        Kembali ke Feed Aduan Kasi
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
