<script setup>
import { ref, computed, watch, onMounted, onUnmounted, getCurrentInstance } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import {
    User,
    UserCheck,
    UserX,
    Save,
    KeyRound,
    Lock,
    Shield,
    Building2,
    Mail,
    Phone,
    X,
    RotateCcw,
    Check,
    Edit2
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

// ===== PROFILE FORM & DIRTY TRACKING =====
const initialProfileState = {
    name: props.targetUser.name || '',
    nip: props.targetUser.nip || '',
    username: props.targetUser.username || '',
    email: props.targetUser.email || '',
    phone_number: props.targetUser.phone_number || '',
    role_id: props.targetUser.role_id || 5,
    unit_id: props.targetUser.unit_id || '',
    is_active: Boolean(props.targetUser.is_active),
    password: '',
    current_password: ''
};

const form = useForm({
    name: props.targetUser.name || '',
    nip: props.targetUser.nip || '',
    username: props.targetUser.username || '',
    email: props.targetUser.email || '',
    phone_number: props.targetUser.phone_number || '',
    role_id: props.targetUser.role_id || 5,
    unit_id: props.targetUser.unit_id || '',
    is_active: Boolean(props.targetUser.is_active),
    password: '',
    current_password: ''
});

const isProfileDirty = computed(() => {
    return (
        form.name !== initialProfileState.name ||
        form.nip !== initialProfileState.nip ||
        form.username !== initialProfileState.username ||
        form.email !== initialProfileState.email ||
        form.phone_number !== initialProfileState.phone_number ||
        Number(form.role_id) !== Number(initialProfileState.role_id) ||
        String(form.unit_id || '') !== String(initialProfileState.unit_id || '') ||
        form.is_active !== initialProfileState.is_active ||
        (form.password && form.password.trim().length > 0) ||
        (form.current_password && form.current_password.trim().length > 0)
    );
});

// ===== PERMISSIONS FORM & DIRTY TRACKING =====
const initialUseRoleDefault = props.targetUser.page_permissions === null || props.targetUser.page_permissions === undefined;
const initialPermissions = Array.isArray(props.targetUser.page_permissions) ? [...props.targetUser.page_permissions] : [];

const useRoleDefault = ref(initialUseRoleDefault);
const selectedPermissions = ref([...initialPermissions]);

const permissionForm = useForm({
    page_permissions: [...initialPermissions],
    use_role_default: initialUseRoleDefault,
});

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
    return getRoleDefaultPermissions(form.role_id);
});

const isPermissionChecked = (key) => {
    if (useRoleDefault.value) {
        return activeRoleDefaultPermissions.value.includes(key);
    }
    return selectedPermissions.value.includes(key);
};

const isPermissionDirty = computed(() => {
    if (useRoleDefault.value !== initialUseRoleDefault) return true;
    if (useRoleDefault.value && initialUseRoleDefault) return false;

    const currentSorted = [...selectedPermissions.value].sort();
    const initSorted = [...initialPermissions].sort();

    if (currentSorted.length !== initSorted.length) return true;
    return currentSorted.some((val, idx) => val !== initSorted[idx]);
});

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

const togglePermission = (key) => {
    if (useRoleDefault.value) {
        selectedPermissions.value = [...activeRoleDefaultPermissions.value];
        useRoleDefault.value = false;
    }
    const index = selectedPermissions.value.indexOf(key);
    if (index > -1) {
        selectedPermissions.value.splice(index, 1);
    } else {
        selectedPermissions.value.push(key);
    }
};

const resetToRoleDefault = () => {
    useRoleDefault.value = true;
    selectedPermissions.value = [];
};

const submitUpdate = () => {
    form.put(route('users.update', { user: props.targetUser.id }), {
        preserveScroll: true
    });
};

const submitPermissionUpdate = () => {
    permissionForm.use_role_default = useRoleDefault.value;
    permissionForm.page_permissions = selectedPermissions.value;

    permissionForm.patch(route('users.permissions.update', props.targetUser.id), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head :title="`Ubah Data Pengguna - ${targetUser.name}`" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <div class="w-full">
                <!-- Header Panel (Standardized across SIPUAS & Pesupeluh) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Edit2 class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Ubah Data Pengguna
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Kelola profil, peran jabatan, penugasan unit, dan hak akses halaman pengguna.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- CONTAINER 1: DATA PROFIL, AKUN & PERAN -->
                <form @submit.prevent="submitUpdate" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-4">
                    <div class="p-6 space-y-8">

                        <!-- SEKSI 1.1: PROFIL & INFORMASI AKUN -->
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
                                            <img :src="targetUser.profile_photo_path" :alt="form.name" class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else class="h-20 w-20 rounded-full bg-emerald-50 dark:bg-white/5 border-2 border-slate-200 dark:border-slate-700 flex items-center justify-center text-emerald-600 dark:text-white">
                                            <User class="h-10 w-10" />
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[150px]">{{ form.name || 'Pengguna' }}</div>
                                            <div class="text-[10px] text-slate-400">{{ form.nip || 'Belum ada NIP' }}</div>
                                        </div>
                                    </div>

                                    <!-- Form Fields Profil -->
                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <!-- Nama Lengkap -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                Nama Lengkap <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                v-model="form.name"
                                                required
                                                placeholder="Contoh: dr. Ahmad Dahlan, Sp.PD"
                                                class="w-full px-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                            />
                                            <div v-if="form.errors.name" class="text-rose-500 text-[11px] font-medium">{{ form.errors.name }}</div>
                                        </div>

                                        <!-- NIP / Pegawai ID -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                NIP / ID Pegawai
                                            </label>
                                            <input
                                                type="text"
                                                v-model="form.nip"
                                                placeholder="Contoh: 198501012010011001"
                                                class="w-full px-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                            />
                                            <div v-if="form.errors.nip" class="text-rose-500 text-[11px] font-medium">{{ form.errors.nip }}</div>
                                        </div>

                                        <!-- Username -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                Username
                                            </label>
                                            <input
                                                type="text"
                                                v-model="form.username"
                                                placeholder="Username unik sistem..."
                                                class="w-full px-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                            />
                                            <div v-if="form.errors.username" class="text-rose-500 text-[11px] font-medium">{{ form.errors.username }}</div>
                                        </div>

                                        <!-- Email -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                Email <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                type="email"
                                                v-model="form.email"
                                                required
                                                placeholder="email@rsud.go.id"
                                                class="w-full px-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                            />
                                            <div v-if="form.errors.email" class="text-rose-500 text-[11px] font-medium">{{ form.errors.email }}</div>
                                        </div>

                                        <!-- Nomor Telepon / WA -->
                                        <div class="space-y-1.5 sm:col-span-2">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                Nomor WhatsApp / HP
                                            </label>
                                            <input
                                                type="text"
                                                v-model="form.phone_number"
                                                placeholder="08xxxxxxxxxx"
                                                class="w-full px-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                            />
                                            <div v-if="form.errors.phone_number" class="text-rose-500 text-[11px] font-medium">{{ form.errors.phone_number }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEKSI 1.2: PERAN & PENUGASAN UNIT -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Peran & Penugasan Unit
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Tentukan peran otorisasi serta unit kerja penempatan pengguna.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Peran Jabatan -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Peran Jabatan <span class="text-rose-500">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="form.role_id"
                                        :options="roleOptions"
                                        :searchable="true"
                                        :absolute="false"
                                        value-key="id"
                                        label-key="name"
                                        placeholder="Pilih Peran Jabatan"
                                        search-placeholder="Cari peran..."
                                    />
                                    <div v-if="form.errors.role_id" class="text-rose-500 text-[11px] font-medium">{{ form.errors.role_id }}</div>
                                </div>

                                <!-- Penugasan Ruangan Pelayanan -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Penugasan Ruangan Pelayanan
                                    </label>
                                    <SearchableSelect
                                        v-model="form.unit_id"
                                        :options="unitOptions"
                                        :searchable="true"
                                        :absolute="false"
                                        value-key="id"
                                        label-key="name"
                                        subtitle-key="code"
                                        placeholder="Semua Ruangan (Global)"
                                        search-placeholder="Cari nama ruangan atau lokasi..."
                                    />
                                    <div v-if="form.errors.unit_id" class="text-rose-500 text-[11px] font-medium">{{ form.errors.unit_id }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- SEKSI 1.3: STATUS KEAKTIFAN AKUN -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Status Keaktifan Akun
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Atur kelayakan akses login pengguna ke dalam sistem.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Option Aktif -->
                                <div
                                    @click="form.is_active = true"
                                    :class="[
                                        'p-4 rounded-xl border flex items-start gap-3 cursor-pointer transition select-none',
                                        form.is_active
                                            ? 'border-emerald-600 bg-emerald-500/10 text-emerald-900 dark:text-emerald-300 font-extrabold'
                                            : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 text-slate-500 dark:text-slate-400 hover:border-slate-300'
                                    ]"
                                >
                                    <UserCheck class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" />
                                    <div class="space-y-0.5">
                                        <div class="text-xs font-bold uppercase tracking-wide">Aktif (Verified)</div>
                                        <div class="text-[11px] font-normal leading-relaxed opacity-80">Pengguna dapat login dan mengakses fitur sistem sesuai perannya.</div>
                                    </div>
                                </div>

                                <!-- Option Nonaktif -->
                                <div
                                    @click="form.is_active = false"
                                    :class="[
                                        'p-4 rounded-xl border flex items-start gap-3 cursor-pointer transition select-none',
                                        !form.is_active
                                            ? 'border-amber-600 bg-amber-500/10 text-amber-900 dark:text-amber-300 font-extrabold'
                                            : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 text-slate-500 dark:text-slate-400 hover:border-slate-300'
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

                        <!-- SEKSI 1.4: KEAMANAN & KATA SANDI -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Keamanan Akun & Kata Sandi
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                    Kosongkan kedua kolom jika tidak ingin mengubah kata sandi akun pengguna ini.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Kata Sandi Anda Saat Ini (Verifikasi Pengaman)
                                    </label>
                                    <div class="relative">
                                        <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                                        <input
                                            type="password"
                                            v-model="form.current_password"
                                            placeholder="Masukkan kata sandi Anda..."
                                            class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                        />
                                    </div>
                                    <div v-if="form.errors.current_password" class="text-rose-500 text-[11px] font-medium">{{ form.errors.current_password }}</div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Kata Sandi Baru Pengguna
                                    </label>
                                    <div class="relative">
                                        <KeyRound class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                                        <input
                                            type="password"
                                            v-model="form.password"
                                            minlength="6"
                                            placeholder="Minimal 6 karakter..."
                                            class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                        />
                                    </div>
                                    <div v-if="form.errors.password" class="text-rose-500 text-[11px] font-medium">{{ form.errors.password }}</div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Actions Card 1 -->
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <span class="text-xs font-medium text-slate-400 dark:text-slate-500 text-center sm:text-left">
                            {{ isProfileDirty ? 'Ada perubahan data profil yang belum disimpan.' : 'Tidak ada perubahan pada data profil.' }}
                        </span>
                        <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2.5 w-full sm:w-auto">
                            <Link
                                :href="route('users.index')"
                                class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center justify-center flex items-center"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                :disabled="!isProfileDirty || form.processing"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-1.5 text-center"
                            >
                                <Save class="h-4 w-4" />
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Profil & Akun' }}</span>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- CONTAINER 2: HAK AKSES HALAMAN SISTEM (PERMISSIONS OVERRIDE) -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-4">
                    <div class="p-6 space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Hak Akses Halaman Sistem
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Sesuaikan modul & menu halaman yang dapat dibuka secara spesifik oleh pengguna ini.</p>
                            </div>

                            <button
                                type="button"
                                @click="resetToRoleDefault"
                                :class="[
                                    'w-full sm:w-auto h-10 px-3.5 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer border shrink-0',
                                    useRoleDefault
                                        ? 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-transparent cursor-default'
                                        : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-white/10 dark:text-white border-emerald-200/50'
                                ]"
                            >
                                <RotateCcw class="h-3.5 w-3.5" />
                                <span>{{ useRoleDefault ? 'Menggunakan Default Peran' : 'Reset ke Default Peran' }}</span>
                            </button>
                        </div>

                        <!-- Mode Alert Badge -->
                        <div v-if="useRoleDefault" class="p-4 rounded-xl bg-slate-100/70 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 text-xs flex items-center gap-2.5">
                            <Shield class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <span>Saat ini pengguna mengikuti hak akses bawaan dari perannya. Centang atau ubah opsi di bawah jika ingin mengkustomisasi secara khusus.</span>
                        </div>

                        <!-- Grouped Permission Checkboxes -->
                        <div class="space-y-6 pt-2">
                            <div v-for="group in allPermissionKeys" :key="group.group" class="space-y-2.5">
                                <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide flex items-center gap-1.5">
                                    <KeyRound class="h-3.5 w-3.5 text-emerald-600 dark:text-white" />
                                    <span>{{ group.group }}</span>
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5">
                                    <div
                                        v-for="perm in group.permissions"
                                        :key="perm.key"
                                        @click="togglePermission(perm.key)"
                                        :class="[
                                            'p-3 rounded-xl border transition-all text-xs font-semibold flex items-center justify-between gap-2 cursor-pointer select-none',
                                            isPermissionChecked(perm.key)
                                                ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/40'
                                                : 'bg-slate-50/60 dark:bg-slate-950/20 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:border-slate-300'
                                        ]"
                                    >
                                        <span class="truncate">{{ perm.label }}</span>
                                        <div :class="['h-4 w-4 rounded flex items-center justify-center shrink-0 border transition-all', isPermissionChecked(perm.key) ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900']">
                                            <Check v-if="isPermissionChecked(perm.key)" class="h-3 w-3 stroke-[3]" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions Card 2 -->
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <span class="text-xs font-medium text-slate-400 dark:text-slate-500 text-center sm:text-left">
                            {{ isPermissionDirty ? 'Ada perubahan hak akses yang belum disimpan.' : 'Tidak ada perubahan pada hak akses.' }}
                        </span>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full sm:w-auto">
                            <button 
                                type="button" 
                                @click="resetToRoleDefault"
                                :disabled="useRoleDefault"
                                class="w-full sm:w-auto h-11 sm:h-10 px-4 inline-flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-xs rounded-xl transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
                            >
                                Reset
                            </button>
                            <button 
                                type="button" 
                                @click="submitPermissionUpdate" 
                                :disabled="!isPermissionDirty || permissionForm.processing"
                                :class="[
                                    'w-full sm:w-auto h-11 sm:h-10 px-6 inline-flex items-center justify-center gap-2 font-bold text-xs rounded-xl transition duration-150 shadow-sm border-0 cursor-pointer shrink-0',
                                    isPermissionDirty && !permissionForm.processing
                                        ? 'bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900'
                                        : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 opacity-50 cursor-not-allowed'
                                ]"
                            >
                                <KeyRound class="h-4 w-4 shrink-0" />
                                <span>{{ permissionForm.processing ? 'Menyimpan...' : 'Simpan Hak Akses' }}</span>
                            </button>
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
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white truncate">Pasfoto - {{ form.name || targetUser.name }}</h3>
                    </div>
                    <button @click="showPhotoModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer p-1 rounded-lg">
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <div class="p-4 flex items-center justify-center bg-slate-50 dark:bg-slate-950">
                    <img :src="targetUser.profile_photo_path" :alt="form.name || targetUser.name" class="max-h-[70vh] w-auto max-w-full rounded-xl object-contain shadow-md" />
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
