<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Search, 
    CheckCircle2, 
    Clock, 
    AlertCircle, 
    ArrowRight, 
    Copy, 
    ShieldCheck, 
    MessageSquare,
    RefreshCw,
    XCircle,
    ArrowLeft
} from '@lucide/vue';

const props = defineProps({
    initialTicket: {
        type: String,
        default: ''
    },
    report: {
        type: Object,
        default: null
    },
    searched: {
        type: Boolean,
        default: false
    }
});

const searchInput = ref(props.initialTicket || '');
const isSearching = ref(false);
const copied = ref(false);
const isKeyboardOpen = ref(false);
const isClearing = ref(false);
const searchInputRef = ref(null);

const clearSearch = () => {
    searchInput.value = '';
    if (searchInputRef.value) {
        searchInputRef.value.focus();
    }
    if (props.searched || props.report) {
        isClearing.value = true;
        router.get(route('report.track'), {}, {
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isClearing.value = false;
            }
        });
    }
};

const checkKeyboard = () => {
    if (window.visualViewport) {
        isKeyboardOpen.value = window.visualViewport.height < window.innerHeight - 150;
    }
};

onMounted(() => {
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', checkKeyboard);
    }
});

onUnmounted(() => {
    if (window.visualViewport) {
        window.visualViewport.removeEventListener('resize', checkKeyboard);
    }
});

const handleSearch = () => {
    const trimmed = searchInput.value.trim().toUpperCase();
    if (!trimmed) return;
    searchInput.value = trimmed;
    
    isSearching.value = true;
    router.get(route('report.track'), { ticket: trimmed }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isSearching.value = false;
        }
    });
};

const copyTicket = () => {
    if (!props.report?.ticket_number) return;
    navigator.clipboard.writeText(props.report.ticket_number);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

// Map status to progress step (1 to 4)
const currentStepNumber = computed(() => {
    if (!props.report) return 1;
    const s = props.report.status?.toUpperCase() || 'PENDING';
    if (s === 'REJECTED') return -1;
    if (s === 'RESOLVED') return 4;
    if (s === 'IN_PROGRESS') return 3;
    if (s === 'VERIFIED') return 2;
    return 1; // PENDING
});

const statusBadge = computed(() => {
    if (!props.report) return { text: 'Menunggu', class: 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' };
    const s = props.report.status?.toUpperCase() || 'PENDING';
    switch (s) {
        case 'RESOLVED':
            return { text: 'Selesai Ditangani', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700' };
        case 'IN_PROGRESS':
            return { text: 'Dalam Penanganan', class: 'bg-sky-100 text-sky-800 dark:bg-sky-950/80 dark:text-sky-300 border-sky-300 dark:border-sky-700' };
        case 'VERIFIED':
            return { text: 'Diverifikasi Kepala Ruangan', class: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/80 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700' };
        case 'REJECTED':
            return { text: 'Tidak Dapat Diproses', class: 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border-rose-300 dark:border-rose-700' };
        default:
            return { text: 'Menunggu Verifikasi', class: 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-300 dark:border-amber-700' };
    }
});
</script>

<template>
    <Head title="Lacak Progres Laporan - SIPUAS" />

    <div class="min-h-screen flex flex-col justify-between sm:justify-center items-center relative overflow-x-hidden font-sans bg-white dark:bg-slate-900 sm:bg-transparent p-0 sm:p-6 text-slate-900 dark:text-slate-100">
        <!-- Background Image with Blur & Dark Overlay (Desktop/Tablet Fixed) -->
        <div class="hidden sm:block fixed inset-0 z-0 bg-cover bg-center bg-no-repeat filter blur-xs sm:blur-sm scale-105 pointer-events-none" style="background-image: url('/images/hospital-hero.jpg');"></div>
        <div class="hidden sm:block fixed inset-0 z-0 bg-slate-900/55 dark:bg-slate-950/80 transition-colors duration-200 pointer-events-none"></div>

        <!-- Ambient Glow Elements matching SIPUAS emerald theme -->
        <div class="pointer-events-none hidden sm:block fixed top-[-15%] left-[-10%] h-[70vw] w-[70vw] max-w-[800px] max-h-[800px] rounded-full bg-emerald-600/[0.08] blur-[130px] dark:bg-emerald-600/20"></div>
        <div class="pointer-events-none hidden sm:block fixed right-[-10%] bottom-[-10%] h-[60vw] w-[60vw] max-w-[700px] max-h-[700px] rounded-full bg-teal-600/[0.08] blur-[120px] dark:bg-teal-900/30"></div>

        <!-- Main Container Card -->
        <main class="w-full sm:max-w-xl mx-auto z-20 my-auto flex flex-col flex-1 sm:flex-initial py-0 sm:py-6">
            <div class="w-full flex-1 sm:flex-initial flex flex-col sm:rounded-2xl sm:border border-slate-200 bg-white sm:shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <!-- Header Section with Logo & Title -->
                <div class="border-b border-slate-200 bg-slate-50 p-5 sm:p-7 text-center dark:border-slate-800 dark:bg-slate-950 sm:rounded-t-2xl">
                    <Link
                        :href="route('report.create')"
                        class="inline-flex items-center justify-center transition-transform hover:scale-105 cursor-pointer"
                        title="SIPUAS"
                    >
                        <img
                            src="/images/logo-sidebar.png"
                            alt="SIPUAS Logo"
                            class="h-8 sm:h-10 w-auto object-contain mx-auto dark:brightness-0 dark:invert"
                        />
                    </Link>
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Lacak Progres & Tindak Lanjut Penanganan Suara Masyarakat
                    </p>
                </div>

                <!-- Card Body -->
                <div class="p-5 sm:p-8 space-y-6 pb-32 sm:pb-8">
                    <!-- Search Box Form -->
                    <form @submit.prevent="handleSearch" class="space-y-2">
                        <label for="ticket_search" class="block text-xs font-extrabold uppercase tracking-wide text-slate-700 dark:text-slate-300">
                            Masukkan Nomor Registrasi Laporan
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input
                                    ref="searchInputRef"
                                    id="ticket_search"
                                    v-model="searchInput"
                                    type="text"
                                    placeholder="Contoh: LP-2026-09-0001"
                                    class="h-12 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 pl-4 pr-10 text-xs sm:text-sm uppercase font-bold tracking-wider focus:border-emerald-500 dark:focus:border-emerald-400 focus:bg-white dark:focus:bg-slate-950 focus:outline-none focus:ring-0 transition"
                                />
                                <span v-if="searchInput" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer p-1" title="Hapus Pencarian">
                                    <XCircle class="h-4 w-4" />
                                </span>
                            </div>
                            <button
                                type="submit"
                                :disabled="!searchInput.trim() || isSearching"
                                class="h-12 flex items-center justify-center gap-2 px-5 sm:px-7 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs sm:text-sm transition cursor-pointer active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
                            >
                                <RefreshCw v-if="isSearching" class="h-4 w-4 animate-spin" />
                                <Search v-else class="h-4 w-4" />
                                <span>Lacak</span>
                            </button>
                        </div>
                    </form>

                    <!-- STATE 1: Hasil Ditemukan (Detail Laporan & Timeline Cek Resi) -->
                    <div v-if="!isClearing && report" class="space-y-5 animate-spa-fade-in">
                        <!-- Top Summary Ticket Card -->
                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800/80 pb-3">
                                <div>
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 block">Nomor Registrasi</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-base sm:text-lg font-extrabold text-emerald-600 dark:text-emerald-400 tracking-wider">
                                            {{ report.ticket_number }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="copyTicket"
                                            class="p-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 transition cursor-pointer"
                                            title="Salin Nomor Tiket"
                                        >
                                            <Copy class="h-3.5 w-3.5" />
                                        </button>
                                        <span v-if="copied" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">Disalin!</span>
                                    </div>
                                </div>
                                <span :class="['px-3 py-1 rounded-full text-[11px] font-extrabold border', statusBadge.class]">
                                    {{ statusBadge.text }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                <div>
                                    <span class="text-slate-400 font-medium">Unit Pelayanan:</span>
                                    <p class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                                        <span class="truncate">{{ report.unit_name }}</span>
                                    </p>
                                </div>
                                <div v-if="report.target_object">
                                    <span class="text-slate-400 font-medium">Sasaran / Fasilitas:</span>
                                    <p class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                                        {{ report.target_object }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-medium">Waktu Laporan:</span>
                                    <p class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5">
                                        {{ report.created_at || '-' }}
                                    </p>
                                </div>
                                <div v-if="report.has_attachment">
                                    <span class="text-slate-400 font-medium">Bukti Terlampir:</span>
                                    <p class="font-semibold text-emerald-600 dark:text-emerald-400 mt-0.5 flex items-center gap-1">
                                        <CheckCircle2 class="h-3.5 w-3.5" />
                                        <span>{{ report.attachments_count }} Foto / Video</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Stepper / Resi Timeline -->
                        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-5">
                                Alur Progres Penanganan
                            </h3>

                            <div class="space-y-0.5">
                                <!-- Step 1: Laporan Masuk -->
                                <div class="flex gap-3.5">
                                    <div class="flex flex-col items-center shrink-0">
                                        <div class="h-6 w-6 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                                            <CheckCircle2 class="h-3.5 w-3.5" />
                                        </div>
                                        <div class="w-0.5 flex-1 min-h-[34px] bg-slate-200 dark:bg-slate-800 my-1"></div>
                                    </div>
                                    <div class="pb-6 pt-0.5 min-w-0 flex-1">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white">Laporan Masuk & Diterima Sistem</p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                            Laporan berhasil dicatat di sistem SIPUAS dan notifikasi telah dikirim ke unit pelayanan.
                                        </p>
                                        <span class="text-[10px] text-slate-400 mt-1 block">{{ report.created_at }}</span>
                                    </div>
                                </div>

                                <!-- Step 2: Verifikasi Kepala Ruangan / KASI -->
                                <div class="flex gap-3.5">
                                    <div class="flex flex-col items-center shrink-0">
                                        <div 
                                            :class="[
                                                'h-6 w-6 rounded-full flex items-center justify-center shrink-0 transition-colors shadow-sm',
                                                currentStepNumber >= 2 
                                                    ? 'bg-emerald-600 text-white' 
                                                    : (currentStepNumber === 1 ? 'bg-amber-500 text-white animate-pulse' : 'bg-slate-200 dark:bg-slate-800 text-slate-400')
                                            ]"
                                        >
                                            <CheckCircle2 v-if="currentStepNumber >= 2" class="h-3.5 w-3.5" />
                                            <Clock v-else class="h-3.5 w-3.5" />
                                        </div>
                                        <div class="w-0.5 flex-1 min-h-[34px] bg-slate-200 dark:bg-slate-800 my-1"></div>
                                    </div>
                                    <div class="pb-6 pt-0.5 min-w-0 flex-1">
                                        <p class="text-xs font-bold" :class="currentStepNumber >= 2 ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'">
                                            Verifikasi Kepala Ruangan / KASI
                                        </p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                            {{ currentStepNumber >= 2 
                                                ? 'Laporan telah ditinjau dan divalidasi oleh pimpinan unit kerja terkait.' 
                                                : 'Sedang menunggu telaah dan verifikasi langsung dari Kepala Ruangan unit kerja.' }}
                                        </p>
                                        <span v-if="report.verified_at" class="text-[10px] text-slate-400 mt-1 block">
                                            {{ report.verified_at }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Step 3: Tindak Lanjut & Pembinaan -->
                                <div class="flex gap-3.5">
                                    <div class="flex flex-col items-center shrink-0">
                                        <div 
                                            :class="[
                                                'h-6 w-6 rounded-full flex items-center justify-center shrink-0 transition-colors shadow-sm',
                                                currentStepNumber >= 3 
                                                    ? 'bg-emerald-600 text-white' 
                                                    : (currentStepNumber === 2 ? 'bg-indigo-500 text-white animate-pulse' : 'bg-slate-200 dark:bg-slate-800 text-slate-400')
                                            ]"
                                        >
                                            <CheckCircle2 v-if="currentStepNumber >= 3" class="h-3.5 w-3.5" />
                                            <Clock v-else class="h-3.5 w-3.5" />
                                        </div>
                                        <div class="w-0.5 flex-1 min-h-[34px] bg-slate-200 dark:bg-slate-800 my-1"></div>
                                    </div>
                                    <div class="pb-6 pt-0.5 min-w-0 flex-1">
                                        <p class="text-xs font-bold" :class="currentStepNumber >= 3 ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'">
                                            Tindak Lanjut & Evaluasi Layanan
                                        </p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                            {{ currentStepNumber >= 3 
                                                ? 'Langkah perbaikan SOP, fasilitas, atau evaluasi kinerja staf sedang/telah dijalankan.' 
                                                : 'Koordinasi internal tindak lanjut penyelesaian kendala.' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Step 4: Selesai Ditangani -->
                                <div class="flex gap-3.5">
                                    <div class="flex flex-col items-center shrink-0">
                                        <div 
                                            :class="[
                                                'h-6 w-6 rounded-full flex items-center justify-center shrink-0 transition-colors shadow-sm',
                                                report.status === 'RESOLVED' 
                                                    ? 'bg-emerald-600 text-white' 
                                                    : (report.status === 'REJECTED' ? 'bg-rose-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-400')
                                            ]"
                                        >
                                            <CheckCircle2 v-if="report.status === 'RESOLVED'" class="h-3.5 w-3.5" />
                                            <XCircle v-else-if="report.status === 'REJECTED'" class="h-3.5 w-3.5" />
                                            <Clock v-else class="h-3.5 w-3.5" />
                                        </div>
                                    </div>
                                    <div class="pt-0.5 min-w-0 flex-1">
                                        <p class="text-xs font-bold" :class="report.status === 'RESOLVED' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'">
                                            {{ report.status === 'REJECTED' ? 'Laporan Tidak Dapat Diproses' : 'Selesai & Ditutup' }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                            {{ report.status === 'RESOLVED' 
                                                ? 'Seluruh proses penanganan laporan telah selesai dilakukan.' 
                                                : (report.status === 'REJECTED' ? 'Laporan tidak memenuhi kriteria tindak lanjut atau informasi kurang memadai.' : 'Menunggu penyelesaian akhir dari unit terkait.') }}
                                        </p>
                                        <span v-if="report.resolved_at" class="text-[10px] text-slate-400 mt-1 block">
                                            {{ report.resolved_at }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Resmi Tindak Lanjut dari RS (Jika sudah diverifikasi / ada catatan) -->
                        <div 
                            v-if="report.supervisor_notes || report.resolution_notes" 
                            class="p-4 sm:p-5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-500/30 dark:border-emerald-500/30 space-y-1.5"
                        >
                            <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300 font-bold text-xs">
                                <MessageSquare class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                <span>Tanggapan Resmi Pihak Rumah Sakit</span>
                            </div>
                            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed pl-6">
                                "{{ report.resolution_notes || report.supervisor_notes }}"
                            </p>
                        </div>

                        <!-- Ringkasan Isi Laporan Pasien -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Kutipan Laporan Anda:</span>
                            <p class="text-slate-600 dark:text-slate-400 italic leading-relaxed">
                                "{{ report.isi_laporan }}"
                            </p>
                        </div>
                    </div>

                    <!-- STATE 2: Laporan Tidak Ditemukan -->
                    <div v-else-if="!isClearing && searched" class="text-center py-8 space-y-3 animate-spa-fade-in">
                        <div class="h-14 w-14 rounded-full bg-rose-50 dark:bg-rose-950/50 text-rose-500 flex items-center justify-center mx-auto border border-rose-200 dark:border-rose-900/50">
                            <AlertCircle class="h-7 w-7" />
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Nomor Tiket Tidak Ditemukan</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto leading-relaxed">
                            Kami tidak menemukan laporan dengan nomor registrasi <strong class="text-slate-800 dark:text-slate-200 font-bold">{{ initialTicket }}</strong>. Mohon periksa kembali nomor tiket yang tercetak di tanda terima Anda.
                        </p>
                    </div>

                    <!-- STATE 3: Panduan Awal (Belum Mencari) -->
                    <div v-else class="text-center py-8 space-y-3">
                        <div class="h-14 w-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto border border-emerald-500/20">
                            <Search class="h-7 w-7" />
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Pantau Status Laporan Anda</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                            Ketikkan Nomor Registrasi yang Anda peroleh (misal: <strong class="text-slate-700 dark:text-slate-300">LP-2026-09-0001</strong>) pada kolom di atas untuk melihat perkembangan penanganan secara real-time.
                        </p>
                    </div>

                    <!-- Desktop Action (Inside Card - No Top Border) -->
                    <div class="hidden sm:flex items-center pt-4">
                        <Link
                            :href="route('report.create')"
                            class="w-full flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99]"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali ke Menu Utama</span>
                        </Link>
                    </div>
                </div>

                <!-- Floating Bottom Navigation Bar (ONLY MOBILE, Auto-hides when keyboard opens) -->
                <div
                    v-show="!isKeyboardOpen"
                    class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-4 pt-3.5 pb-8 shadow-2xl transition-all duration-200"
                >
                    <div class="w-full">
                        <Link
                            :href="route('report.create')"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99]"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali ke Menu Utama</span>
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
