<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import {
    Building2,
    Phone,
    CheckCircle2,
    Clock,
    ArrowRight,
    ArrowLeft,
    LogOut,
    Edit3,
    Link2,
} from '@lucide/vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    units: {
        type: Array,
        default: () => [],
    },
    status: {
        type: String,
        default: null,
    },
});

const isEditing = ref(!props.user.has_requested);
const showSuccessModal = ref(false);
const statusMessage = ref('');

const scrollToTop = () => {
    window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
};

const closeModal = () => {
    showSuccessModal.value = false;
    scrollToTop();
};

onMounted(() => {
    if (props.status) {
        statusMessage.value = props.status;
        showSuccessModal.value = true;
        scrollToTop();
    }
});

watch(() => props.status, (newVal) => {
    if (newVal) {
        statusMessage.value = newVal;
        showSuccessModal.value = true;
        scrollToTop();
    }
});

const form = useForm({
    unit_id: props.user.unit_id || '',
    phone_number: props.user.phone_number || '',
});

const submit = () => {
    form.post(route('activation.request'), {
        preserveScroll: false,
        onSuccess: () => {
            isEditing.value = false;
            statusMessage.value = props.status || 'Permohonan aktivasi akun berhasil diajukan! Administrator akan memverifikasi dan mengaktifkan akun Anda.';
            showSuccessModal.value = true;
            scrollToTop();
        },
    });
};
</script>

<template>
    <Head title="Aktivasi Akun Pegawai - SIPUAS" />

    <div
        class="min-h-screen flex flex-col justify-between sm:justify-center items-center relative overflow-x-hidden font-['Poppins',sans-serif] bg-white dark:bg-slate-900 sm:bg-transparent p-0 sm:p-6 text-slate-900 dark:text-slate-100"
    >
        <!-- Background Image with Blur & Dark Overlay (Desktop/Tablet Fixed) -->
        <div
            class="hidden sm:block fixed inset-0 z-0 bg-cover bg-center bg-no-repeat filter blur-xs sm:blur-sm scale-105 pointer-events-none"
            style="background-image: url('/images/hospital-hero.jpg');"
        ></div>
        <div
            class="hidden sm:block fixed inset-0 z-0 bg-slate-900/55 dark:bg-slate-950/80 transition-colors duration-200 pointer-events-none"
        ></div>

        <!-- Ambient Glow Elements matching SIPUAS emerald theme (Desktop/Tablet) -->
        <div
            class="pointer-events-none hidden sm:block fixed top-[-15%] left-[-10%] h-[70vw] w-[70vw] max-w-[800px] max-h-[800px] rounded-full bg-emerald-600/[0.08] blur-[130px] dark:bg-emerald-600/20"
        ></div>
        <div
            class="pointer-events-none hidden sm:block fixed right-[-10%] bottom-[-10%] h-[60vw] w-[60vw] max-w-[700px] max-h-[700px] rounded-full bg-teal-600/[0.08] blur-[120px] dark:bg-teal-900/30"
        ></div>

        <!-- Main Container (Full-screen edge-to-edge on Mobile, Card Modal on Desktop) -->
        <main class="w-full sm:max-w-xl mx-auto z-20 my-auto flex flex-col flex-1 sm:flex-initial py-0 sm:py-4">
            <!-- Solid Container Card with Integrated Header -->
            <div
                class="w-full flex-1 sm:flex-initial flex flex-col sm:rounded-2xl sm:border border-slate-200 bg-white sm:shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900"
            >
                <!-- Card Header Section for Logo & Subtitle (Flat Solid) -->
                <div
                    class="border-b border-slate-200 bg-slate-50 p-5 sm:p-7 text-center dark:border-slate-800 dark:bg-slate-950 sm:rounded-t-2xl relative"
                >
                    <!-- Centered Logo (Static, no hover/scale) -->
                    <div class="inline-flex items-center justify-center">
                        <img
                            src="/images/logo-sidebar.png"
                            alt="SIPUAS Logo"
                            class="h-9 sm:h-10 w-auto object-contain mx-auto dark:brightness-0 dark:invert"
                        />
                    </div>

                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 font-medium font-['Poppins',sans-serif]">
                        Sistem Informasi Pelayanan Unit & Akuntabilitas Staf
                    </p>
                </div>

                <!-- Card Body (Extra bottom padding on mobile so content never hidden behind raised bottom bar) -->
                <div class="flex-1 p-5 sm:p-7 bg-white dark:bg-slate-900 pb-56 sm:pb-7 sm:rounded-b-2xl space-y-6">
                    <!-- 3 Step Progress Bar Indicator (Matching Report/Create.vue Stepper Style) -->
                    <div class="px-1 sm:px-2">
                        <div class="grid grid-cols-3 relative">
                            <!-- Connecting Progress Line -->
                            <div class="absolute top-[18px] left-[16.66%] right-[16.66%] -translate-y-1/2 h-1 bg-slate-100 dark:bg-slate-800 z-0">
                                <div
                                    class="h-full bg-emerald-500 transition-all duration-300 rounded-full"
                                    :style="{ width: user.has_requested ? '100%' : '50%' }"
                                ></div>
                            </div>

                            <!-- Step 1: Akun Pegawai (Completed) -->
                            <div class="flex flex-col items-center text-center z-10 relative">
                                <div
                                    class="h-9 w-9 rounded-full flex items-center justify-center font-semibold text-xs transition-all duration-300 border-2 bg-emerald-600 text-white border-emerald-600 shadow-md ring-4 ring-emerald-50 dark:ring-emerald-950"
                                >
                                    <CheckCircle2 class="h-5 w-5" />
                                </div>
                                <span class="text-[10px] sm:text-[11px] font-semibold mt-2 uppercase tracking-wide text-slate-700 dark:text-slate-200">
                                    Akun
                                </span>
                                <span class="text-[9px] text-emerald-600 dark:text-emerald-400 font-normal -mt-0.5">
                                    Terdaftar
                                </span>
                            </div>

                            <!-- Step 2: Pilih Unit Pelayanan -->
                            <div class="flex flex-col items-center text-center z-10 relative">
                                <div
                                    :class="[
                                        'h-9 w-9 rounded-full flex items-center justify-center font-semibold text-xs transition-all duration-300 border-2',
                                        user.has_requested
                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-md ring-4 ring-emerald-50 dark:ring-emerald-950'
                                            : 'bg-emerald-600 text-white border-emerald-600 shadow-md ring-4 ring-emerald-50 dark:ring-emerald-950'
                                    ]"
                                >
                                    <CheckCircle2 v-if="user.has_requested" class="h-5 w-5" />
                                    <span v-else>2</span>
                                </div>
                                <span class="text-[10px] sm:text-[11px] font-semibold mt-2 uppercase tracking-wide text-slate-700 dark:text-slate-200">
                                    Pilih Unit
                                </span>
                                <span class="text-[9px] font-normal -mt-0.5" :class="user.has_requested ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'">
                                    {{ user.has_requested ? 'Diajukan' : 'Isi Form' }}
                                </span>
                            </div>

                            <!-- Step 3: Verifikasi Admin -->
                            <div class="flex flex-col items-center text-center z-10 relative">
                                <div
                                    :class="[
                                        'h-9 w-9 rounded-full flex items-center justify-center font-semibold text-xs transition-all duration-300 border-2',
                                        user.has_requested
                                            ? 'bg-amber-500 text-white border-amber-500 shadow-md ring-4 ring-amber-50 dark:ring-amber-950'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    <Clock v-if="user.has_requested" class="h-5 w-5" />
                                    <span v-else>3</span>
                                </div>
                                <span class="text-[10px] sm:text-[11px] font-semibold mt-2 uppercase tracking-wide text-slate-700 dark:text-slate-200">
                                    Aktivasi
                                </span>
                                <span class="text-[9px] font-normal -mt-0.5" :class="user.has_requested ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400'">
                                    {{ user.has_requested ? 'Menunggu' : 'Pending' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Welcome Header Banner (Matching Report/Create.vue Step Header) -->
                    <div class="bg-gradient-to-b from-slate-50 to-slate-100/50 dark:from-slate-950 dark:to-slate-900 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 text-center space-y-1.5">
                        <div class="flex items-center justify-center gap-2 mb-1">
                            <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                <Link2 class="h-3 w-3 shrink-0 text-emerald-500" />
                                Integrasi Akun Baru
                            </span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            Permohonan Aktivasi Akun
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                            Data akun Anda terhubung dari sistem Pesu Peluh. Silakan lengkapi unit pelayanan bertugas dan konfirmasi nomor WhatsApp untuk mengaktifkan akun Anda di SIPUAS.
                        </p>
                    </div>



                    <!-- User Identity Card from Pesu Peluh (Stacked vertically, no icons, not divided in columns) -->
                    <div class="p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                Data Akun Pesu Peluh
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-500/20">
                                <CheckCircle2 class="h-3 w-3 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                <span>Terverifikasi</span>
                            </span>
                        </div>

                        <!-- Stacked Rows (No columns, 100% Poppins, font-medium matching dropdown) -->
                        <div class="space-y-2 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80">
                                <span class="block text-[11px] font-normal text-slate-500 dark:text-slate-400">Nama Lengkap</span>
                                <span class="font-medium text-slate-800 dark:text-slate-100 block mt-0.5 text-sm font-['Poppins',sans-serif]">{{ user.name }}</span>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80">
                                <span class="block text-[11px] font-normal text-slate-500 dark:text-slate-400">NIP / Identitas</span>
                                <span class="font-medium text-slate-800 dark:text-slate-100 block mt-0.5 text-sm font-['Poppins',sans-serif]">{{ user.nip || '-' }}</span>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80">
                                <span class="block text-[11px] font-normal text-slate-500 dark:text-slate-400">Username</span>
                                <span class="font-medium text-slate-800 dark:text-slate-100 block mt-0.5 text-sm font-['Poppins',sans-serif]">{{ user.username }}</span>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80">
                                <span class="block text-[11px] font-normal text-slate-500 dark:text-slate-400">Email</span>
                                <span class="font-medium text-slate-800 dark:text-slate-100 block mt-0.5 text-sm font-['Poppins',sans-serif]">{{ user.email || '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- STATE A: Request Already Submitted & Waiting Review -->
                    <div
                        v-if="user.has_requested && !isEditing"
                        class="space-y-4 animate-spa-fade-in"
                    >
                        <!-- Status Alert Card -->
                        <div class="p-5 rounded-2xl bg-amber-50/90 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/70 space-y-4">
                            <div class="flex flex-col items-center text-center space-y-2.5 pt-1">
                                <div class="h-12 w-12 rounded-2xl bg-amber-500/15 dark:bg-amber-500/25 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex items-center justify-center">
                                    <Clock class="h-6 w-6" />
                                </div>
                                <div class="space-y-1 max-w-md mx-auto">
                                    <h3 class="text-base font-bold text-amber-950 dark:text-amber-200">
                                        Pengajuan Sedang Ditinjau
                                    </h3>
                                    <p class="text-xs text-amber-900/85 dark:text-amber-300/90 leading-relaxed">
                                        Permohonan aktivasi Anda telah tersimpan dan menunggu persetujuan (approval). Setelah aktif, notifikasi otomatis dikirimkan ke WhatsApp Anda.
                                    </p>
                                </div>
                            </div>

                            <!-- Details Summary Strip (Stacked vertically, no 2-column split) -->
                            <div class="space-y-2 text-xs text-left">
                                <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-200/80 dark:border-amber-900/50">
                                    <span class="flex items-center gap-1.5 text-[11px] font-normal text-slate-500 dark:text-slate-400">
                                        <Building2 class="h-3.5 w-3.5 text-slate-400" />
                                        Ruangan Pelayanan Dipilih
                                    </span>
                                    <span class="font-medium text-slate-800 dark:text-slate-100 block mt-0.5 text-sm leading-snug">
                                        {{ user.unit_name }}
                                    </span>
                                </div>

                                <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/70 border border-amber-200/80 dark:border-amber-900/50">
                                    <span class="flex items-center gap-1.5 text-[11px] font-normal text-slate-500 dark:text-slate-400">
                                        <Phone class="h-3.5 w-3.5 text-slate-400" />
                                        WhatsApp Notifikasi
                                    </span>
                                    <span class="font-medium font-['Poppins',sans-serif] text-slate-800 dark:text-slate-100 block mt-0.5 text-sm leading-snug">
                                        {{ user.phone_number }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons for Review State (Desktop) -->
                        <div class="hidden sm:flex flex-col gap-2.5 pt-2">
                            <button
                                type="button"
                                @click="isEditing = true"
                                class="flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700"
                            >
                                <Edit3 class="h-4 w-4" />
                                <span>Ubah Ruangan / No. WhatsApp</span>
                            </button>

                            <!-- Tombol Keluar Diletakkan di Bawah Tombol Aksi -->
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 text-sm font-semibold text-slate-500 hover:text-red-600 hover:bg-red-50 dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-950/30 transition-colors"
                            >
                                <LogOut class="h-4 w-4" />
                                <span>Keluar Sistem</span>
                            </Link>
                        </div>
                    </div>

                    <!-- STATE B: Form to Choose Unit & Phone (Not Submitted Yet OR Editing) -->
                    <form
                        v-else
                        @submit.prevent="submit"
                        class="space-y-4 animate-spa-fade-in"
                    >
                        <!-- Unit Pelayanan using SearchableSelect Component -->
                        <div class="space-y-1.5">
                            <InputLabel for="unit_id" value="Pilih Ruangan Pelayanan Bertugas *" />
                            <div>
                                <SearchableSelect
                                    v-model="form.unit_id"
                                    :options="units"
                                    valueKey="id"
                                    labelKey="name"
                                    subtitleKey="code"
                                    :absolute="false"
                                    placeholder="-- Pilih Ruangan Pelayanan Rumah Sakit --"
                                    searchPlaceholder="Cari nama ruangan atau lokasi gedung..."
                                />
                            </div>
                            <InputError :message="form.errors.unit_id" />
                        </div>

                        <!-- WhatsApp / Phone Field -->
                        <div class="space-y-1.5">
                            <InputLabel for="phone_number" value="Nomor WhatsApp / Handphone Aktif *" />
                            <div class="relative">
                                <Phone
                                    class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                                />
                                <TextInput
                                    id="phone_number"
                                    type="text"
                                    v-model="form.phone_number"
                                    required
                                    placeholder="Contoh: 081234567890"
                                    class="block w-full h-11 pl-10 font-medium font-['Poppins',sans-serif] text-sm text-slate-800 dark:text-slate-100"
                                />
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">
                                Pastikan nomor WhatsApp aktif untuk menerima notifikasi saat akun Anda disetujui.
                            </p>
                            <InputError :message="form.errors.phone_number" />
                        </div>



                        <!-- Desktop Submit Action (Inside Card) -->
                        <div class="hidden sm:flex flex-col gap-2.5 pt-3">
                            <div class="flex items-center gap-3">
                                <button
                                    v-if="user.has_requested"
                                    type="button"
                                    @click="isEditing = false"
                                    class="flex h-11 px-4 cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 shrink-0"
                                >
                                    <ArrowLeft class="h-4 w-4" />
                                    <span>Batal</span>
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing || !form.unit_id || !form.phone_number"
                                    class="flex-1 flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span
                                        v-if="form.processing"
                                        class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                                    ></span>
                                    <template v-else>
                                        <span>{{ user.has_requested ? 'Simpan Perubahan' : 'Ajukan Pengaktifan Akun' }}</span>
                                        <ArrowRight class="h-4 w-4" />
                                    </template>
                                </button>
                            </div>

                            <!-- Tombol Keluar Diletakkan di Bawah Tombol Ajukan -->
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 text-sm font-semibold text-slate-500 hover:text-red-600 hover:bg-red-50 dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-950/30 transition-colors"
                            >
                                <LogOut class="h-4 w-4" />
                                <span>Keluar Sistem</span>
                            </Link>
                        </div>
                    </form>
                </div>

                <!-- Floating Bottom Navigation Bar (ONLY MOBILE, Matching Report/Create.vue Safe Distance) -->
                <div
                    class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-4 pt-3.5 pb-10 shadow-2xl transition-all duration-200"
                    style="padding-bottom: max(2.5rem, calc(env(safe-area-inset-bottom, 1.5rem) + 1.25rem));"
                >
                    <!-- Review State Mobile Buttons -->
                    <div v-if="user.has_requested && !isEditing" class="flex flex-col gap-2 w-full">
                        <button
                            type="button"
                            @click="isEditing = true"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700"
                        >
                            <Edit3 class="h-4 w-4" />
                            <span>Ubah Ruangan / No. WA</span>
                        </button>

                        <!-- Tombol Keluar Diletakkan di Bawah Tombol Aksi -->
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 text-sm font-semibold text-slate-500 hover:text-red-600 hover:bg-red-50 dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-950/30 transition-colors"
                        >
                            <LogOut class="h-4 w-4" />
                            <span>Keluar Sistem</span>
                        </Link>
                    </div>

                    <!-- Form State Mobile Buttons -->
                    <div v-else class="flex flex-col gap-2 w-full">
                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing || !form.unit_id || !form.phone_number"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                            ></span>
                            <template v-else>
                                <span>{{ user.has_requested ? 'Simpan Perubahan' : 'Ajukan Pengaktifan Akun' }}</span>
                                <ArrowRight class="h-4 w-4" />
                            </template>
                        </button>

                        <button
                            v-if="user.has_requested"
                            type="button"
                            @click="isEditing = false"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Batal Ubah</span>
                        </button>

                        <!-- Tombol Keluar Diletakkan di Bawah Tombol Ajukan -->
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 text-sm font-semibold text-slate-500 hover:text-red-600 hover:bg-red-50 dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-950/30 transition-colors"
                        >
                            <LogOut class="h-4 w-4" />
                            <span>Keluar Sistem</span>
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Standard SIPUAS Swal-Style Alert Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 font-['Poppins',sans-serif]">
                    <!-- Backdrop overlay -->
                    <div @click="closeModal" class="fixed inset-0 bg-black/40 backdrop-blur-xs"></div>

                    <!-- Modal Card matching AuthenticatedLayout Swal design -->
                    <div class="relative bg-white/95 dark:bg-slate-900/95 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden p-6 sm:p-7 flex flex-col items-center text-center transform transition-all duration-200 backdrop-blur-md">
                        <!-- Status Icon -->
                        <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-full flex items-center justify-center mb-4 sm:mb-5 flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500">
                            <CheckCircle2 class="h-8 w-8 sm:h-10 sm:w-10" />
                        </div>

                        <!-- Info Content -->
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white leading-tight px-2">
                            Pengajuan Berhasil!
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2.5 leading-relaxed px-1">
                            {{ statusMessage }}
                        </p>

                        <!-- Action Button -->
                        <div class="w-full mt-6">
                            <button
                                type="button"
                                @click="closeModal"
                                class="w-full h-11 text-sm font-semibold rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 transition duration-150 focus:outline-none cursor-pointer active:scale-[0.99]"
                            >
                                OK, Mengerti
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
