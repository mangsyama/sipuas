<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { 
    User, 
    UserCheck, 
    Building2, 
    Mail, 
    Phone, 
    Shield, 
    Clock, 
    Check, 
    Trash2, 
    AlertTriangle,
    X
} from '@lucide/vue';

const props = defineProps({
    targetUser: {
        type: Object,
        required: true
    },
    units: {
        type: Array,
        default: () => []
    }
});

const showRejectModal = ref(false);
const showPhotoModal = ref(false);

const approveForm = useForm({
    role: props.targetUser.role || 'STAFF',
    unit_id: props.targetUser.unit_id || ''
});

const submitApprove = () => {
    approveForm.post(route('users.approvals.approve', { user: props.targetUser.id }));
};

const submitReject = () => {
    router.delete(route('users.approvals.reject', { user: props.targetUser.id }), {
        onSuccess: () => {
            showRejectModal.value = false;
        }
    });
};

// Modal Scroll Lock & Keyboard Handling
const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
        if (showRejectModal.value) showRejectModal.value = false;
        if (showPhotoModal.value) showPhotoModal.value = false;
    }
};

watch([showRejectModal, showPhotoModal], ([isRejectOpen, isPhotoOpen]) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = (isRejectOpen || isPhotoOpen) ? 'hidden' : '';
    }
});

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <Head :title="`Verifikasi Pendaftar - ${targetUser.name}`" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <div class="w-full">
                <!-- Header Panel (SAME AS MASTER PENGGUNA SHOW) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-amber-50 dark:bg-white/10 text-amber-600 dark:text-white">
                            <UserCheck class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Verifikasi Pendaftar Baru
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Tinjau berkas pendaftaran, pasfoto formal, dan tetapkan hak akses akun staf baru di SIPUAS.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Card / Container (SAME STRUCTURE AS SHOW.VUE & EDIT.VUE) -->
                <form @submit.prevent="submitApprove" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-4">
                    <div class="p-6 space-y-8">

                        <!-- SEKSI 1: PROFIL & INFORMASI PENDAFTAR -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Data Profil & Identitas Pendaftar
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Informasi identitas pribadi dan pasfoto formal calon pengguna.</p>
                            </div>

                            <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row items-stretch gap-6">
                                    <!-- Avatar Card Box (No Shadow, Clean Flat Style) -->
                                    <div class="flex flex-col items-center justify-center shrink-0 w-full sm:w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center space-y-3">
                                        <div 
                                            v-if="targetUser.profile_photo_path" 
                                            @click="showPhotoModal = true"
                                            class="h-20 w-20 rounded-full overflow-hidden border-2 border-amber-500 bg-slate-100 dark:bg-slate-800 cursor-pointer hover:opacity-90 hover:scale-105 transition transform group relative"
                                            title="Klik untuk melihat foto penuh"
                                        >
                                            <img :src="targetUser.profile_photo_path" :alt="targetUser.name" class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else class="h-20 w-20 rounded-full bg-amber-600 text-white flex items-center justify-center font-black text-2xl">
                                            {{ targetUser.name ? targetUser.name.charAt(0).toUpperCase() : 'U' }}
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-900 dark:text-white truncate max-w-[150px] mx-auto">
                                                {{ targetUser.name || 'Pengguna' }}
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-medium mt-0.5">
                                                @{{ targetUser.username || '-' }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Grid Data Detail (SAME AS MASTER PENGGUNA SHOW.VUE) -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
                                        <!-- Nama Lengkap -->
                                        <div class="space-y-1.5 sm:col-span-2">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Nama Lengkap & Gelar
                                            </label>
                                            <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-semibold flex items-center">
                                                {{ targetUser.name }}
                                            </div>
                                        </div>

                                        <!-- NIP -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                NIP (Nomor Induk Pegawai)
                                            </label>
                                            <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                                {{ targetUser.nip || '-' }}
                                            </div>
                                        </div>

                                        <!-- Username -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Username Akun
                                            </label>
                                            <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                                {{ targetUser.username || '-' }}
                                            </div>
                                        </div>

                                        <!-- Email -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Email Resmi
                                            </label>
                                            <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center truncate">
                                                {{ targetUser.email }}
                                            </div>
                                        </div>

                                        <!-- Nomor Telepon (Clean, no WA button) -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Nomor Telepon / WhatsApp
                                            </label>
                                            <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                                {{ targetUser.phone_number || '-' }}
                                            </div>
                                        </div>

                                        <!-- Pilihan Unit Saat Registrasi -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Pilihan Unit Saat Registrasi
                                            </label>
                                            <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                                {{ targetUser.unit_name }}
                                            </div>
                                        </div>

                                        <!-- Waktu Mendaftar -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Waktu Mendaftar
                                            </label>
                                            <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                                {{ targetUser.created_at }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEKSI 2: PENETAPAN PERAN AKSES & UNIT RESMI -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Penetapan Hak Akses & Penempatan Unit
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Tentukan peran resmi dan unit penugasan pengguna sebelum disetujui.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Peran Akses -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Peran / Hak Akses Resmi *
                                    </label>
                                    <select
                                        v-model="approveForm.role"
                                        required
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition cursor-pointer font-medium"
                                    >
                                        <option value="STAFF">Staf Pelaksana</option>
                                        <option value="KASI">Kepala Seksi (Kasi)</option>
                                        <option value="KABID">Kepala Bidang (Kabid)</option>
                                        <option value="ADMINISTRATOR">Administrator</option>
                                        <option value="SUPERADMIN">Super Administrator</option>
                                    </select>
                                </div>

                                <!-- Penugasan Unit -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Penugasan Unit Kerja Resmi
                                    </label>
                                    <select
                                        v-model="approveForm.unit_id"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition cursor-pointer font-medium"
                                    >
                                        <option value="">-- Pilih Unit Kerja (Global jika kosong) --</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                            {{ unit.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Actions Card (Stacked on mobile, side-by-side on desktop) -->
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <button
                                type="button"
                                @click="showRejectModal = true"
                                class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 text-xs font-semibold flex items-center justify-center gap-1.5 transition cursor-pointer"
                            >
                                <Trash2 class="h-4 w-4" />
                                <span>Tolak Pendaftaran</span>
                            </button>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2.5 w-full sm:w-auto">
                            <Link
                                :href="route('users.approvals')"
                                class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center justify-center flex items-center"
                            >
                                Kembali
                            </Link>
                            <button
                                type="submit"
                                :disabled="approveForm.processing"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-1.5 text-center"
                            >
                                <Check class="h-4 w-4" />
                                <span>{{ approveForm.processing ? 'Menyimpan...' : 'Setujui & Aktifkan Akun' }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Full Photo Modal / Lightbox -->
        <div 
            v-if="showPhotoModal && targetUser.profile_photo_path" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-xs animate-fade-in" 
            @click="showPhotoModal = false"
        >
            <div class="relative max-w-lg w-full bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800" @click.stop>
                <div class="flex items-center justify-between p-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <User class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white truncate">Pasfoto Pendaftar - {{ targetUser.name }}</h3>
                    </div>
                    <button @click="showPhotoModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer p-1 rounded-lg">
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <div class="p-4 flex items-center justify-center bg-slate-50 dark:bg-slate-950">
                    <img :src="targetUser.profile_photo_path" :alt="targetUser.name" class="max-h-[70vh] w-auto max-w-full rounded-xl object-contain shadow-md" />
                </div>
            </div>
        </div>

        <!-- Reject Confirmation Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fade-in">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-sm w-full p-6 space-y-4 shadow-2xl text-center">
                <div class="h-12 w-12 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-600 mx-auto flex items-center justify-center">
                    <AlertTriangle class="h-6 w-6" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Tolak Pendaftaran Pengguna?
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Data calon pengguna <strong class="text-slate-800 dark:text-slate-200">{{ targetUser.name }}</strong> akan dihapus permanen.
                    </p>
                </div>

                <div class="flex items-center justify-center gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="showRejectModal = false"
                        class="h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitReject"
                        class="h-9 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold flex items-center gap-1.5 transition cursor-pointer"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span>Ya, Tolak & Hapus</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
