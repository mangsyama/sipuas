<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { 
    Users, 
    UserPlus, 
    Search, 
    Filter, 
    Shield, 
    ShieldCheck, 
    Activity, 
    Building2, 
    CheckCircle2, 
    XCircle, 
    MoreVertical, 
    Eye, 
    Edit, 
    Trash2, 
    KeyRound, 
    Lock, 
    Mail, 
    Phone, 
    User, 
    X,
    Check,
    AlertTriangle,
    Sparkles
} from '@lucide/vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    units: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            superadmin: 0,
            kabid: 0,
            kasi: 0,
            staff: 0,
            active: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            role: '',
            unit: '',
            status: ''
        })
    }
});

const searchQuery = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || 'ALL');
const selectedUnit = ref(props.filters.unit || 'ALL');
const selectedStatus = ref(props.filters.status || 'ALL');

// Modal States
const showCreateModal = ref(false);
const showResetPasswordModal = ref(false);
const showDeleteConfirmModal = ref(false);
const selectedUserForAction = ref(null);

const createForm = useForm({
    name: '',
    nip: '',
    username: '',
    email: '',
    phone_number: '',
    password: '',
    role: 'KASI',
    unit_id: ''
});

const resetPasswordForm = useForm({
    new_password: ''
});

const filteredUsers = computed(() => {
    return props.users.filter(u => {
        const matchesSearch = !searchQuery.value || 
            u.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (u.nip && u.nip.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            u.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (u.username && u.username.toLowerCase().includes(searchQuery.value.toLowerCase()));

        const matchesRole = selectedRole.value === 'ALL' || u.role === selectedRole.value;
        const matchesUnit = selectedUnit.value === 'ALL' || (u.unit_id && String(u.unit_id) === String(selectedUnit.value));
        const matchesStatus = selectedStatus.value === 'ALL' || 
            (selectedStatus.value === 'ACTIVE' && u.is_active) ||
            (selectedStatus.value === 'INACTIVE' && !u.is_active);

        return matchesSearch && matchesRole && matchesUnit && matchesStatus;
    });
});

const submitCreateUser = () => {
    createForm.post(route('users.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

const toggleUserStatus = (user) => {
    router.patch(route('users.toggle-status', { user: user.id }), {}, {
        preserveScroll: true
    });
};

const openResetPasswordModal = (user) => {
    selectedUserForAction.value = user;
    resetPasswordForm.reset();
    showResetPasswordModal.value = true;
};

const submitResetPassword = () => {
    if (!selectedUserForAction.value) return;
    resetPasswordForm.post(route('users.reset-password', { user: selectedUserForAction.value.id }), {
        onSuccess: () => {
            showResetPasswordModal.value = false;
            resetPasswordForm.reset();
            selectedUserForAction.value = null;
        }
    });
};

const openDeleteConfirmModal = (user) => {
    selectedUserForAction.value = user;
    showDeleteConfirmModal.value = true;
};

const submitDeleteUser = () => {
    if (!selectedUserForAction.value) return;
    router.delete(route('users.destroy', { user: selectedUserForAction.value.id }), {
        onSuccess: () => {
            showDeleteConfirmModal.value = false;
            selectedUserForAction.value = null;
        }
    });
};

const getRoleBadgeClass = (role) => {
    switch (role) {
        case 'SUPERADMIN':
            return 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800';
        case 'KABID':
            return 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800';
        case 'KASI':
            return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
        default:
            return 'bg-slate-50 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
};

const getRoleLabel = (role) => {
    switch (role) {
        case 'SUPERADMIN': return 'Superadmin IT';
        case 'KABID': return 'Kabid Pelayanan';
        case 'KASI': return 'Kepala Seksi / Ruangan';
        case 'STAFF': return 'Staf Pelaksana';
        default: return role;
    }
};
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Users class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Master Pengguna Sistem
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20">
                                MASTER DATA
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Kelola akun, penugasan unit, dan hak akses staf rumah sakit yang berwenang di SIPUAS.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="showCreateModal = true"
                        class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-2 shadow-sm transition cursor-pointer"
                    >
                        <UserPlus class="h-4 w-4" />
                        <span>Tambah Pengguna Baru</span>
                    </button>
                </div>
            </div>

            <!-- Summary KPI Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Pengguna</span>
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Users class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.total }}</div>
                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5 block">{{ stats.active }} Akun Aktif</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kepala Seksi (Kasi)</span>
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
                            <Building2 class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.kasi }}</div>
                    <span class="text-[11px] text-slate-400 font-semibold mt-0.5 block">Penanggung Jawab Unit</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kabid Pelayanan</span>
                        <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                            <Activity class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.kabid }}</div>
                    <span class="text-[11px] text-slate-400 font-semibold mt-0.5 block">Manajemen Eksekutif</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Superadmin IT</span>
                        <div class="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400">
                            <ShieldCheck class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.superadmin }}</div>
                    <span class="text-[11px] text-slate-400 font-semibold mt-0.5 block">Administrator Utama</span>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-4 rounded-2xl shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
                <div class="relative w-full md:w-80">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Cari nama, NIP, email, username..."
                        class="w-full pl-10 pr-4 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <!-- Filter Role -->
                    <select
                        v-model="selectedRole"
                        class="px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer"
                    >
                        <option value="ALL">Semua Peran</option>
                        <option value="SUPERADMIN">Superadmin</option>
                        <option value="KABID">Kabid Pelayanan</option>
                        <option value="KASI">Kepala Seksi (Kasi)</option>
                        <option value="STAFF">Staf Pelaksana</option>
                    </select>

                    <!-- Filter Unit -->
                    <select
                        v-model="selectedUnit"
                        class="px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer"
                    >
                        <option value="ALL">Semua Unit</option>
                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                            {{ unit.name }}
                        </option>
                    </select>

                    <!-- Filter Status -->
                    <select
                        v-model="selectedStatus"
                        class="px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer"
                    >
                        <option value="ALL">Semua Status</option>
                        <option value="ACTIVE">Aktif</option>
                        <option value="INACTIVE">Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4 sm:px-6">Pengguna & Akun</th>
                                <th class="py-3.5 px-4 sm:px-6">NIP & Kontak</th>
                                <th class="py-3.5 px-4 sm:px-6">Peran & Akses</th>
                                <th class="py-3.5 px-4 sm:px-6">Unit Instalasi</th>
                                <th class="py-3.5 px-4 sm:px-6 text-center">Status</th>
                                <th class="py-3.5 px-4 sm:px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-200">
                            <tr 
                                v-for="user in filteredUsers" 
                                :key="user.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
                            >
                                <!-- Name & Account -->
                                <td class="py-4 px-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center font-bold text-sm shrink-0 border border-emerald-100 dark:border-white/20">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 dark:text-white truncate">{{ user.name }}</div>
                                            <div class="text-[11px] text-slate-400 truncate flex items-center gap-1.5 mt-0.5">
                                                <span>@{{ user.username }}</span>
                                                <span>•</span>
                                                <span>{{ user.email }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- NIP & Contact -->
                                <td class="py-4 px-4 sm:px-6">
                                    <div class="space-y-0.5">
                                        <div class="font-medium text-slate-800 dark:text-slate-200">{{ user.nip }}</div>
                                        <div class="text-[11px] text-slate-400">{{ user.phone_number }}</div>
                                    </div>
                                </td>

                                <!-- Role Badge -->
                                <td class="py-4 px-4 sm:px-6">
                                    <span 
                                        :class="['inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border', getRoleBadgeClass(user.role)]"
                                    >
                                        {{ getRoleLabel(user.role) }}
                                    </span>
                                </td>

                                <!-- Unit Name -->
                                <td class="py-4 px-4 sm:px-6">
                                    <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-medium">
                                        <Building2 class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                        <span>{{ user.unit_name }}</span>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-4 sm:px-6 text-center">
                                    <button
                                        @click="toggleUserStatus(user)"
                                        :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition', user.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 hover:bg-rose-100']"
                                        :title="user.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                                    >
                                        <component :is="user.is_active ? CheckCircle2 : XCircle" class="h-3 w-3" />
                                        <span>{{ user.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-4 sm:px-6 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <Link
                                            :href="route('users.show', { user: user.id })"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="Lihat Detail"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Link>

                                        <Link
                                            :href="route('users.edit', { user: user.id })"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="Edit Pengguna"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Link>

                                        <button
                                            type="button"
                                            @click="openResetPasswordModal(user)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                                            title="Reset Kata Sandi"
                                        >
                                            <KeyRound class="h-4 w-4" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteConfirmModal(user)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                                            title="Hapus Pengguna"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <Users class="h-8 w-8 mx-auto mb-2 opacity-40" />
                                    <p class="font-medium text-xs">Tidak ada data pengguna yang sesuai dengan filter.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Tambah Pengguna Baru -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-spa-fade-in">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <UserPlus class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Tambah Pengguna Baru</h3>
                            <p class="text-xs text-slate-400">Buat akun untuk staf atau pejabat rumah sakit.</p>
                        </div>
                    </div>
                    <button @click="showCreateModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreateUser" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap & Gelar *</label>
                        <input
                            type="text"
                            v-model="createForm.name"
                            required
                            placeholder="Contoh: dr. H. Rahmat, Sp.B"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">NIP (Nomor Induk Pegawai)</label>
                            <input
                                type="text"
                                v-model="createForm.nip"
                                placeholder="19820710 200801 1 003"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Username Login</label>
                            <input
                                type="text"
                                v-model="createForm.username"
                                placeholder="rahmat_igd"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Email Resmi *</label>
                            <input
                                type="email"
                                v-model="createForm.email"
                                required
                                placeholder="rahmat@rs.local"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">No. Telepon / WhatsApp</label>
                            <input
                                type="text"
                                v-model="createForm.phone_number"
                                placeholder="081234567891"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Peran / Hak Akses *</label>
                            <select
                                v-model="createForm.role"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option value="KASI">Kepala Seksi / Ruangan (Kasi)</option>
                                <option value="KABID">Kabid Pelayanan</option>
                                <option value="SUPERADMIN">Superadmin IT</option>
                                <option value="STAFF">Staf Pelaksana</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Penugasan Unit Instalasi</label>
                            <select
                                v-model="createForm.unit_id"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option value="">Semua Unit (Global / Kabid / Admin)</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                    {{ unit.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Kata Sandi Awal *</label>
                        <input
                            type="password"
                            v-model="createForm.password"
                            required
                            minlength="6"
                            placeholder="Minimal 6 karakter"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold transition disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan Pengguna' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Reset Password -->
        <div v-if="showResetPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden animate-spa-fade-in">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">
                            <KeyRound class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Reset Kata Sandi</h3>
                            <p class="text-xs text-slate-400">{{ selectedUserForAction?.name }}</p>
                        </div>
                    </div>
                    <button @click="showResetPasswordModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitResetPassword" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Kata Sandi Baru *</label>
                        <input
                            type="password"
                            v-model="resetPasswordForm.new_password"
                            required
                            minlength="6"
                            placeholder="Ketik kata sandi baru (min 6 karakter)"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showResetPasswordModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="resetPasswordForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold transition disabled:opacity-50"
                        >
                            {{ resetPasswordForm.processing ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <div v-if="showDeleteConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-sm rounded-2xl shadow-2xl p-6 text-center animate-spa-fade-in space-y-4">
                <div class="h-12 w-12 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
                    <AlertTriangle class="h-6 w-6" />
                </div>

                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Hapus Pengguna?</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Apakah Anda yakin ingin menghapus akun <strong class="text-slate-700 dark:text-slate-200">{{ selectedUserForAction?.name }}</strong>? Data akan dinonaktifkan dari sistem.
                    </p>
                </div>

                <div class="flex items-center justify-center gap-2 pt-2">
                    <button
                        type="button"
                        @click="showDeleteConfirmModal = false"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-50 dark:hover:bg-slate-800 text-xs transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitDeleteUser"
                        class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs transition"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
