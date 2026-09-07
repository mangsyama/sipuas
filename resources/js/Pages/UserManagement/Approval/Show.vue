<script setup>
import { ref, computed, onMounted, onUnmounted, watch, getCurrentInstance } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { 
    User, 
    UserCheck, 
    UserX, 
    Building2, 
    Mail, 
    Phone, 
    Shield, 
    Clock, 
    Check, 
    Trash2, 
    AlertTriangle,
    KeyRound,
    RotateCcw,
    X,
    ChevronDown,
    ChevronUp
} from '@lucide/vue';

const props = defineProps({
    targetUser: {
        type: Object,
        required: true
    },
    units: {
        type: Array,
        default: () => []
    },
    roles: {
        type: Array,
        default: () => []
    },
    allPermissionKeys: {
        type: Array,
        default: () => []
    }
});

const { proxy } = getCurrentInstance() || {};

const showRejectModal = ref(false);
const showPhotoModal = ref(false);
const showCustomPermissions = ref(false);

const approveForm = useForm({
    role_id: props.targetUser.role_id || 5,
    unit_id: props.targetUser.unit_id || '',
    page_permissions: null,
    use_role_default: true,
});

const selectedPermissions = ref([]);

const roleOptions = computed(() => {
    return (props.roles || []).map(r => ({
        id: r.id,
        name: r.name + (r.code ? ` (${r.code})` : '')
    }));
});

const unitOptions = computed(() => [
    { id: '', name: 'Semua Ruangan (Global / Tanpa Ruangan Spesifik)' },
    ...(props.rooms || props.units || [])
]);

const getRoleDefaultPermissions = (roleId) => {
    const role = (props.roles || []).find(r => Number(r.id) === Number(roleId));
    let perms = [];
    if (role && role.page_permissions) {
        perms = Array.isArray(role.page_permissions)
            ? [...role.page_permissions]
            : (typeof role.page_permissions === 'string' ? JSON.parse(role.page_permissions) : []);
    }
    return perms;
};

const activeRoleDefaultPermissions = computed(() => {
    return getRoleDefaultPermissions(approveForm.role_id);
});

const isPermissionChecked = (key) => {
    if (approveForm.use_role_default) {
        return activeRoleDefaultPermissions.value.includes(key);
    }
    return selectedPermissions.value.includes(key);
};

const togglePermission = (key) => {
    if (approveForm.use_role_default) {
        selectedPermissions.value = [...activeRoleDefaultPermissions.value];
        approveForm.use_role_default = false;
    }
    const index = selectedPermissions.value.indexOf(key);
    if (index > -1) {
        selectedPermissions.value.splice(index, 1);
    } else {
        selectedPermissions.value.push(key);
    }
};

const resetToRoleDefault = () => {
    approveForm.use_role_default = true;
    selectedPermissions.value = [];
};

const submitApprove = () => {
    approveForm.page_permissions = approveForm.use_role_default ? null : selectedPermissions.value;
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
                <!-- Header Panel -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-amber-50 dark:bg-white/10 text-amber-600 dark:text-white">
                            <UserCheck class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                    Verifikasi Pendaftar Baru
                                </h2>
                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase text-center leading-none bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300 border border-amber-200/50 dark:border-amber-500/20">
                                    Menunggu Verifikasi
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Tinjau data pendaftar, pasfoto formal, dan tetapkan peran serta penempatan unit staf sebelum disetujui.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Card / Container -->
                <form @submit.prevent="submitApprove" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-4">
                    <div class="p-6 space-y-8">

                        <!-- SEKSI 1: PROFIL & INFORMASI PENDAFTAR -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Data Profil & Identitas Pendaftar
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Informasi identitas pribadi calon staf yang diajukan.</p>
                            </div>

                            <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row items-stretch gap-6">
                                    <!-- Avatar Card Box -->
                                    <div class="flex flex-col items-center justify-center shrink-0 w-full sm:w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center space-y-3">
                                        <div 
                                            v-if="targetUser.profile_photo_path" 
                                            @click="showPhotoModal = true"
                                            class="h-20 w-20 rounded-full overflow-hidden border-2 border-amber-500 bg-slate-100 dark:bg-slate-800 cursor-pointer hover:opacity-90 hover:scale-105 transition transform group relative"
                                            title="Klik untuk melihat foto penuh"
                                        >
                                            <img :src="targetUser.profile_photo_path" :alt="targetUser.name" class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else class="h-20 w-20 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30 flex items-center justify-center font-black text-2xl">
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

                                    <!-- Grid Data Detail -->
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

                                        <!-- Nomor Telepon / WA -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Nomor Telepon / WhatsApp
                                            </label>
                                            <div class="w-full h-10 px-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center">
                                                {{ targetUser.phone_number || '-' }}
                                            </div>
                                        </div>

                                        <!-- Unit Saat Registrasi -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Unit Diajukan Saat Registrasi
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
                                                {{ targetUser.created_at }} ({{ targetUser.created_at_human }})
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
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Tentukan peran resmi dan unit penugasan staf sebelum disetujui.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Peran Jabatan -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Peran Jabatan Resmi <span class="text-rose-500">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="approveForm.role_id"
                                        :options="roleOptions"
                                        :searchable="true"
                                        :absolute="false"
                                        value-key="id"
                                        label-key="name"
                                        placeholder="Pilih Peran Jabatan"
                                        search-placeholder="Cari peran..."
                                    />
                                    <div v-if="approveForm.errors.role_id" class="text-rose-500 text-[11px] font-medium">{{ approveForm.errors.role_id }}</div>
                                </div>

                                <!-- Penugasan Ruangan Pelayanan -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Penugasan Ruangan Pelayanan Resmi
                                    </label>
                                    <SearchableSelect
                                        v-model="approveForm.unit_id"
                                        :options="unitOptions"
                                        :searchable="true"
                                        :absolute="false"
                                        value-key="id"
                                        label-key="name"
                                        subtitle-key="code"
                                        placeholder="Semua Ruangan (Global)"
                                        search-placeholder="Cari ruangan atau lokasi gedung..."
                                    />
                                    <div v-if="approveForm.errors.unit_id" class="text-rose-500 text-[11px] font-medium">{{ approveForm.errors.unit_id }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- SEKSI 3: KUSTOMISASI HAK AKSES HALAMAN (OPSIONAL) -->
                        <div class="space-y-3 pt-2">
                            <button
                                type="button"
                                @click="showCustomPermissions = !showCustomPermissions"
                                class="inline-flex items-center gap-2 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer"
                            >
                                <KeyRound class="h-4 w-4" />
                                <span>{{ showCustomPermissions ? 'Sembunyikan Kustomisasi Hak Akses Halaman' : 'Kustomisasi Hak Akses Halaman Khusus (Opsional)' }}</span>
                                <ChevronUp v-if="showCustomPermissions" class="h-3.5 w-3.5" />
                                <ChevronDown v-else class="h-3.5 w-3.5" />
                            </button>

                            <div v-if="showCustomPermissions" class="p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-950/30 border border-slate-200 dark:border-slate-800 space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        Secara default pendaftar akan mewarisi seluruh hak akses halaman dari peran yang dipilih di atas.
                                    </span>
                                    <button
                                        type="button"
                                        @click="resetToRoleDefault"
                                        :disabled="approveForm.use_role_default"
                                        class="text-xs font-semibold px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 disabled:opacity-40"
                                    >
                                        Gunakan Default Peran
                                    </button>
                                </div>

                                <div class="space-y-5 pt-2">
                                    <div v-for="group in allPermissionKeys" :key="group.group" class="space-y-2">
                                        <h5 class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide">
                                            {{ group.group }}
                                        </h5>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                            <div
                                                v-for="perm in group.permissions"
                                                :key="perm.key"
                                                @click="togglePermission(perm.key)"
                                                :class="[
                                                    'p-2.5 rounded-xl border text-xs font-medium flex items-center justify-between gap-2 cursor-pointer transition select-none',
                                                    isPermissionChecked(perm.key)
                                                        ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/40'
                                                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800'
                                                ]"
                                            >
                                                <span class="truncate">{{ perm.label }}</span>
                                                <div :class="['h-3.5 w-3.5 rounded flex items-center justify-center shrink-0 border transition-all', isPermissionChecked(perm.key) ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 dark:border-slate-700']">
                                                    <Check v-if="isPermissionChecked(perm.key)" class="h-2.5 w-2.5 stroke-[3]" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Actions Card -->
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
                                <span>{{ approveForm.processing ? 'Memproses...' : 'Setujui & Aktifkan Akun' }}</span>
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
                        Data calon staf <strong class="text-slate-800 dark:text-slate-200">{{ targetUser.name }}</strong> akan dihapus permanen.
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
