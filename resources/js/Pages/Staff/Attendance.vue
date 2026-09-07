<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    LogIn,
    LogOut,
    Calendar,
    ChevronRight,
    MapPin,
    Clock
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
    recentAttendances: {
        type: Array,
        default: () => [],
    }
});

// 1. Live Digital Clock State
const now = ref(new Date());
let timerInterval = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

// Format jam digital: HH.mm (mengikuti format referensi: e.g. 14.17)
const formattedLiveTime = computed(() => {
    const hours = String(now.value.getHours()).padStart(2, '0');
    const minutes = String(now.value.getMinutes()).padStart(2, '0');
    return `${hours}.${minutes}`;
});

// Format tanggal: e.g. "Senin, 07 Sep 2026"
const formattedLiveDate = computed(() => {
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    
    const dayName = days[now.value.getDay()];
    const dateNum = String(now.value.getDate()).padStart(2, '0');
    const monthName = months[now.value.getMonth()];
    const year = now.value.getFullYear();
    
    return `${dayName}, ${dateNum} ${monthName} ${year}`;
});

// 2. Presensi Form & Modal State
const checkInForm = useForm({
    shift_name: 'PRESENSI',
    notes: '',
});

const showCheckInModal = ref(false);
const showCheckOutModal = ref(false);

const submitCheckIn = () => {
    checkInForm.post(route('attendance.check-in'), {
        preserveScroll: true,
        onSuccess: () => {
            showCheckInModal.value = false;
        },
        onError: () => {
            showCheckInModal.value = false;
        }
    });
};

const submitCheckOut = () => {
    router.post(route('attendance.check-out'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showCheckOutModal.value = false;
        },
        onError: () => {
            showCheckOutModal.value = false;
        }
    });
};

// Modal Scroll Lock & History/Back Handling
const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
        if (showCheckInModal.value) showCheckInModal.value = false;
        if (showCheckOutModal.value) showCheckOutModal.value = false;
    }
};

const handlePopState = () => {
    if (showCheckInModal.value) showCheckInModal.value = false;
    if (showCheckOutModal.value) showCheckOutModal.value = false;
};

watch([showCheckInModal, showCheckOutModal], ([inOpen, outOpen], [oldInOpen, oldOutOpen]) => {
    if (typeof document !== 'undefined') {
        const isAnyOpen = inOpen || outOpen;
        const wasAnyOpen = oldInOpen || oldOutOpen;

        if (isAnyOpen) {
            document.body.style.overflow = 'hidden';
            if (!window.history.state?.attendanceModalOpen) {
                window.history.pushState({ attendanceModalOpen: true }, '');
            }
        } else {
            document.body.style.overflow = '';
            if (wasAnyOpen && window.history.state?.attendanceModalOpen) {
                window.history.back();
            }
        }
    }
});

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('popstate', handlePopState);
});

onUnmounted(() => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('popstate', handlePopState);
});
</script>

<template>
    <Head title="Presensi" />

    <AuthenticatedLayout>
        <!-- Container Standar SIPUAS: Lebar desktop & styling shadow seragam dengan halaman lain -->
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">

            <!-- 1. Header Banner Gradien Hijau Emerald -->
            <div class="relative overflow-hidden bg-gradient-to-b from-emerald-600 via-emerald-600 to-teal-600 text-white border border-transparent dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm">
                <!-- Navigasi Bar Atas: Judul Presensi -->
                <div class="text-center relative z-10">
                    <h1 class="text-base sm:text-lg font-bold tracking-wide text-white">
                        Presensi
                    </h1>
                </div>

                <!-- Jam Digital Live Ticking (Besar, Bold, Center) -->
                <div class="text-center my-3 sm:my-4 relative z-10">
                    <div class="text-5xl sm:text-6xl md:text-7xl font-extrabold tracking-tight text-white select-none drop-shadow-xs">
                        {{ formattedLiveTime }}
                    </div>
                    <div class="text-xs sm:text-sm font-medium text-emerald-100/90 mt-1">
                        {{ formattedLiveDate }}
                    </div>
                    <div class="text-[11px] sm:text-xs text-emerald-200/90 mt-0.5 font-medium flex items-center justify-center gap-1.5">
                        <MapPin class="h-3.5 w-3.5" />
                        <span>{{ staff.unit_name }}</span>
                    </div>
                </div>

                <!-- Dekorasi Lingkaran Halus Latar Belakang -->
                <div class="absolute -top-12 -right-12 w-44 h-44 rounded-full bg-white/5 pointer-events-none"></div>
                <div class="absolute -bottom-16 -left-12 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>
            </div>

            <!-- 2. Kartu Presensi Utama -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-5">
                
                <!-- Dua Kolom: Start Time & End Time -->
                <div class="grid grid-cols-2 divide-x divide-slate-100 dark:divide-slate-800 text-center py-1">
                    <!-- Start Time (Jam Masuk) -->
                    <div class="px-2 sm:px-4 space-y-1">
                        <span class="text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400 block">
                            Start Time
                        </span>
                        <div class="text-xl sm:text-3xl font-black text-slate-900 dark:text-white">
                            {{ attendanceToday?.clock_in || '-' }}
                        </div>
                    </div>

                    <!-- End Time (Jam Pulang) -->
                    <div class="px-2 sm:px-4 space-y-1">
                        <span class="text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400 block">
                            End Time
                        </span>
                        <div class="text-xl sm:text-3xl font-black text-slate-900 dark:text-white">
                            {{ attendanceToday?.clock_out || '-' }}
                        </div>
                    </div>
                </div>

                <!-- Garis Pemisah Tipis -->
                <div class="border-t border-slate-100 dark:border-slate-800"></div>

                <!-- KONDISI A: BELUM CHECK-IN (Tombol Clock In dengan Modal Konfirmasi & shadow-sm) -->
                <div v-if="!staff.is_on_duty" class="max-w-md mx-auto w-full">
                    <button
                        type="button"
                        @click="showCheckInModal = true"
                        :disabled="checkInForm.processing"
                        class="w-full h-11 sm:h-12 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-sm transition cursor-pointer disabled:opacity-50"
                    >
                        <LogIn class="h-4.5 w-4.5" />
                        <span>{{ checkInForm.processing ? 'Memproses...' : 'Clock In' }}</span>
                    </button>
                </div>

                <!-- KONDISI B: SEDANG AKTIF (Status Aktif & Tombol Clock Out dengan Modal Konfirmasi & shadow-sm) -->
                <div v-else class="max-w-md mx-auto w-full space-y-3">
                    <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between text-xs sm:text-sm">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                            <span class="font-bold text-emerald-800 dark:text-emerald-300">
                                Sedang Bertugas (Masuk: {{ attendanceToday?.clock_in || '-' }})
                            </span>
                        </div>
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold text-[11px] sm:text-xs">
                            Aktif
                        </span>
                    </div>

                    <button
                        type="button"
                        @click="showCheckOutModal = true"
                        class="w-full h-11 sm:h-12 rounded-xl bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-sm transition cursor-pointer"
                    >
                        <LogOut class="h-4.5 w-4.5" />
                        <span>Clock Out</span>
                    </button>
                </div>

            </div>

            <!-- 3. Bagian Riwayat Presensi Terbaru -->
            <div class="space-y-2 pt-2">
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-800 dark:text-slate-100">
                        Riwayat Presensi
                    </h2>
                    <Link 
                        :href="route('staff.dashboard')"
                        class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-0.5"
                    >
                        <span>Lihat Logbook</span>
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <!-- Kartu Daftar Riwayat Presensi (border-transparent rounded-2xl shadow-sm) -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    
                    <!-- Kondisi Bila Belum Ada Riwayat -->
                    <div v-if="recentAttendances.length === 0" class="text-center py-8 text-slate-400 text-xs">
                        <Calendar class="h-8 w-8 mx-auto mb-2 opacity-40 text-slate-400" />
                        <p class="font-medium">Belum ada riwayat presensi yang tercatat.</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Tekan tombol Clock In di atas untuk memulai presensi hari ini.</p>
                    </div>

                    <!-- List Riwayat Mengikuti Tampilan Mockup -->
                    <div v-else class="space-y-4 divide-y divide-slate-100 dark:divide-slate-800">
                        <div 
                            v-for="(item, idx) in recentAttendances" 
                            :key="item.id"
                            :class="idx > 0 ? 'pt-4' : ''"
                            class="space-y-1.5 sm:space-y-0 sm:flex sm:items-center sm:justify-between sm:py-1"
                        >
                            <!-- Judul Tanggal (Hijau Emerald Sesuai Tema) -->
                            <div class="text-xs sm:text-sm font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                <Calendar class="h-3.5 w-3.5 opacity-70" />
                                <span>{{ item.formatted_date }}</span>
                            </div>

                            <!-- Baris Clock In & Out -->
                            <div class="space-y-1 sm:space-y-0 sm:flex sm:items-center sm:gap-8">
                                <div class="flex items-center justify-between sm:justify-start sm:gap-2 text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                                    <span class="font-normal text-slate-500 dark:text-slate-400">Clock In</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ item.clock_in }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between sm:justify-start sm:gap-2 text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                                    <span class="font-normal text-slate-500 dark:text-slate-400">Clock Out</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ item.clock_out }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- 1. Modal Konfirmasi Clock In -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showCheckInModal" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center min-h-screen">
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity" @click="showCheckInModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-sm p-6 text-center transform transition-all space-y-4 z-10">
                    <div class="h-12 w-12 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
                        <LogIn class="h-6 w-6" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Mulai Presensi?</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Apakah Anda yakin ingin melakukan Clock In sekarang? Waktu kehadiran Anda (<strong class="text-slate-700 dark:text-slate-200">{{ formattedLiveTime }} WITA</strong>) akan tercatat di sistem.
                        </p>
                    </div>
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-center gap-2 pt-2 w-full">
                        <button
                            type="button"
                            @click="showCheckInModal = false"
                            class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitCheckIn"
                            :disabled="checkInForm.processing"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white text-xs font-semibold transition cursor-pointer shadow-sm disabled:opacity-50"
                        >
                            {{ checkInForm.processing ? 'Memproses...' : 'Ya, Clock In' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- 2. Modal Konfirmasi Clock Out -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showCheckOutModal" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center min-h-screen">
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity" @click="showCheckOutModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-sm p-6 text-center transform transition-all space-y-4 z-10">
                    <div class="h-12 w-12 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
                        <LogOut class="h-6 w-6" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Selesai Presensi?</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Apakah Anda yakin ingin melakukan Clock Out hari ini? Waktu kepulangan Anda (<strong class="text-slate-700 dark:text-slate-200">{{ formattedLiveTime }} WITA</strong>) akan tercatat di sistem.
                        </p>
                    </div>
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-center gap-2 pt-2 w-full">
                        <button
                            type="button"
                            @click="showCheckOutModal = false"
                            class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitCheckOut"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white text-xs font-semibold transition cursor-pointer shadow-sm"
                        >
                            Ya, Clock Out
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
