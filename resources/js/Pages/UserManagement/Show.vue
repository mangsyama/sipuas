<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    User,
    UserCheck,
    UserX,
    Edit,
    Building2,
    Mail,
    Phone,
    KeyRound,
    Calendar,
    Shield,
    X,
    Award,
    ThumbsUp,
    MessageSquareWarning,
    CheckCircle2,
    Check
} from '@lucide/vue';

const props = defineProps({
    targetUser: {
        type: Object,
        required: true
    },
    allPermissionKeys: {
        type: Array,
        default: () => []
    }
});

const showPhotoModal = ref(false);

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && showPhotoModal.value) {
        showPhotoModal.value = false;
    }
};

watch(showPhotoModal, (isOpen) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = isOpen ? 'hidden' : '';
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

const getRoleLabel = (roleId, roleStr) => {
    const map = {
        1: 'Administrator',
        2: 'Direktur',
        3: 'Wakil Direktur',
        4: 'Kepala Bidang',
        5: 'Kepala Seksi',
        6: 'Kepala Instalasi',
        7: 'Kepala Ruangan',
        8: 'Tim Penunjang Medis',
        9: 'Tim Sarana Prasarana',
        10: 'Tim Keperawatan',
        11: 'Staf / Pelapor',
    };
    if (map[roleId]) return map[roleId];
    if (roleStr === 'ADMINISTRATOR') return 'Administrator';
    if (roleStr === 'KABID') return 'Kepala Bidang';
    if (roleStr === 'KASI') return 'Kepala Seksi';
    return 'Staf Pelayanan';
};

const effectivePermissions = computed(() => {
    return Array.isArray(props.targetUser.effective_permissions) ? props.targetUser.effective_permissions : [];
});

const isPermissionActive = (key) => {
    return effectivePermissions.value.includes(key);
};
</script>

<template>
    <Head :title="`Detail Pengguna - ${targetUser.name}`" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <div class="w-full">
                <!-- Header Panel -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <User class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Detail Data Pengguna
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Ringkasan profil, peran akses, kinerja poin, dan hak akses halaman pengguna.
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2.5">
                        <Link
                            :href="route('users.edit', targetUser.id)"
                            class="w-full sm:w-auto h-10 inline-flex items-center justify-center gap-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition duration-150 whitespace-nowrap shadow-sm border-0 cursor-pointer"
                        >
                            <Edit class="h-4 w-4" />
                            <span>Edit Data Pengguna</span>
                        </Link>
                    </div>
                </div>

                <!-- KPI Mini Stats Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 shadow-sm flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                            <Award class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-lg font-black text-slate-900 dark:text-white leading-tight">{{ targetUser.total_points ?? 0 }}</div>
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Total Skor KPI</div>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 shadow-sm flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                            <ThumbsUp class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-lg font-black text-slate-900 dark:text-white leading-tight">{{ targetUser.praise_count ?? 0 }}</div>
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Apresiasi Masuk</div>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 shadow-sm flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <MessageSquareWarning class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-lg font-black text-slate-900 dark:text-white leading-tight">{{ targetUser.complaint_count ?? 0 }}</div>
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Keluhan Masuk</div>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 shadow-sm flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-lg font-black text-slate-900 dark:text-white leading-tight">{{ targetUser.verified_reports_count ?? 0 }}</div>
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Laporan Diverifikasi</div>
                        </div>
                    </div>
                </div>

                <!-- CONTAINER 1: DATA PROFIL, AKUN, PERAN & STATUS PENGGUNA -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-4">
                    <div class="p-6 space-y-8">

                        <!-- SEKSI 1: PROFIL & INFORMASI AKUN -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Data Profil & Akun Pengguna
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Informasi identitas pribadi dan kredensial login pengguna.</p>
                            </div>

                            <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row items-stretch gap-6">
                                    <!-- Avatar Card Box -->
                                    <div class="flex flex-col items-center justify-center shrink-0 w-full sm:w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center space-y-3">
                                        <div 
                                            v-if="targetUser.profile_photo_path" 
                                            @click="showPhotoModal = true"
                                            class="h-20 w-20 rounded-full overflow-hidden border-2 border-emerald-500 bg-slate-100 dark:bg-slate-800 cursor-pointer hover:opacity-90 hover:scale-105 transition transform group relative"
                                            title="Klik untuk melihat foto penuh"
                                        >
                                            <img :src="targetUser.profile_photo_path" :alt="targetUser.name" class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else class="h-20 w-20 rounded-full bg-emerald-600 text-white flex items-center justify-center font-black text-2xl">
                                            {{ targetUser.name ? targetUser.name.charAt(0).toUpperCase() : 'U' }}
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-900 dark:text-white truncate max-w-[150px] mx-auto">
                                                {{ targetUser.name }}
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-medium mt-0.5">
                                                @{{ targetUser.username || '-' }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Grid Data Detail -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
                                        <!-- Nama Lengkap -->
                                        <div class="space-y-1.5">
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
                                                Username Login
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
                                            <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                                {{ targetUser.email || '-' }}
                                            </div>
                                        </div>

                                        <!-- Nomor Telepon -->
                                        <div class="space-y-1.5 sm:col-span-2">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Nomor Telepon / WhatsApp
                                            </label>
                                            <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                                {{ targetUser.phone_number || '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEKSI 2: PERAN AKSES & PENEMPATAN UNIT -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Peran Akses Sistem & Penempatan Unit
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Peran hak akses serta penempatan unit pelayanan pengguna.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Peran Akses -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Peran Akses Sistem
                                    </label>
                                    <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-bold flex items-center gap-2">
                                        <Shield class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                        <span>{{ getRoleLabel(targetUser.role_id, targetUser.role) }}</span>
                                    </div>
                                </div>

                                <!-- Penugasan Unit -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Penugasan Unit Pelayanan
                                    </label>
                                    <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-semibold flex items-center gap-2">
                                        <Building2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                        <span>{{ targetUser.unit_name || 'Semua Unit (Global / RS)' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEKSI 3: STATUS KEAKTIFAN AKUN -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Status Keaktifan Akun
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Status kelayakan akses login pengguna ke dalam sistem SIPUAS.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div
                                    :class="[
                                        'p-4 rounded-xl border flex items-start gap-3 transition',
                                        targetUser.is_active
                                            ? 'border-emerald-600 bg-emerald-500/10 text-emerald-900 dark:text-emerald-300 font-extrabold'
                                            : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 text-slate-500 dark:text-slate-400 opacity-60'
                                    ]"
                                >
                                    <UserCheck class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" />
                                    <div class="space-y-0.5">
                                        <div class="text-xs font-bold uppercase tracking-wide">Aktif (Verified)</div>
                                        <div class="text-[11px] font-normal leading-relaxed opacity-80">Pengguna dapat login dan mengakses fitur sistem sesuai perannya.</div>
                                    </div>
                                </div>

                                <div
                                    :class="[
                                        'p-4 rounded-xl border flex items-start gap-3 transition',
                                        !targetUser.is_active
                                            ? 'border-amber-600 bg-amber-500/10 text-amber-900 dark:text-amber-300 font-extrabold'
                                            : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 text-slate-500 dark:text-slate-400 opacity-60'
                                    ]"
                                >
                                    <UserX class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                                    <div class="space-y-0.5">
                                        <div class="text-xs font-bold uppercase tracking-wide">Nonaktif / Suspended</div>
                                        <div class="text-[11px] font-normal leading-relaxed opacity-80">Akses login ditutup sementara oleh Administrator.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- CONTAINER 2: HAK AKSES HALAMAN AKTIF -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-4">
                    <div class="p-6 space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Hak Akses Halaman Efektif
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                    Daftar modul yang dapat dibuka pengguna berdasarkan perannya atau hak kustom.
                                </p>
                            </div>
                            <span :class="[
                                'px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                targetUser.has_custom_permissions
                                    ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20'
                                    : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20'
                            ]">
                                {{ targetUser.has_custom_permissions ? 'Kustom Khusus' : 'Bawaan Default Peran' }}
                            </span>
                        </div>

                        <div class="space-y-6">
                            <div v-for="group in allPermissionKeys" :key="group.group" class="space-y-2.5">
                                <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide flex items-center gap-1.5">
                                    <KeyRound class="h-3.5 w-3.5 text-emerald-600 dark:text-white" />
                                    <span>{{ group.group }}</span>
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5">
                                    <div
                                        v-for="perm in group.permissions"
                                        :key="perm.key"
                                        :class="[
                                            'p-3 rounded-xl border text-xs font-semibold flex items-center justify-between gap-2',
                                            isPermissionActive(perm.key)
                                                ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/40'
                                                : 'bg-slate-50/40 dark:bg-slate-950/10 text-slate-400 dark:text-slate-600 border-slate-200 dark:border-slate-800 opacity-60'
                                        ]"
                                    >
                                        <span class="truncate">{{ perm.label }}</span>
                                        <div :class="['h-4 w-4 rounded flex items-center justify-center shrink-0 border', isPermissionActive(perm.key) ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-200 dark:border-slate-800 bg-transparent']">
                                            <Check v-if="isPermissionActive(perm.key)" class="h-3 w-3 stroke-[3]" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                        <User class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white truncate">Pasfoto - {{ targetUser.name }}</h3>
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
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes spa-fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-spa-fade-in {
  animation: spa-fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
</style>
