<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
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
    Sparkles,
    ChevronLeft,
    ChevronRight
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

// Pagination State
const currentPage = ref(1);
const perPage = ref(10);

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
    role: '',
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

// Reset pagination when filters change
watch([searchQuery, selectedRole, selectedUnit, selectedStatus], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => {
    return Math.ceil(filteredUsers.value.length / perPage.value) || 1;
});

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredUsers.value.slice(start, start + perPage.value);
});

const startItemIndex = computed(() => {
    if (filteredUsers.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const endItemIndex = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredUsers.value.length);
});

const visiblePages = computed(() => {
    const pages = [];
    const total = totalPages.value;
    const current = currentPage.value;

    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
    } else {
        pages.push(1);
        if (current > 3) {
            pages.push('...');
        }
        const start = Math.max(2, current - 1);
        const end = Math.min(total - 1, current + 1);
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        if (current < total - 2) {
            pages.push('...');
        }
        pages.push(total);
    }
    return pages;
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

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
        case 'ADMINISTRATOR':
        case 'SUPERADMIN':
            return 'Administrator';
        case 'KABID':
            return 'Kepala Bidang';
        case 'KASI':
            return 'Kepala Seksi';
        case 'STAFF':
            return 'Staf Pelaksana';
        default:
            return role;
    }
};

// Modal Scroll Lock & Keyboard / History Navigation Management
const isAnyModalOpen = computed(() => {
    return showCreateModal.value || showDeleteConfirmModal.value;
});

const closeAllModals = () => {
    showCreateModal.value = false;
    showDeleteConfirmModal.value = false;
};

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && isAnyModalOpen.value) {
        closeAllModals();
    }
};

const handlePopState = () => {
    if (isAnyModalOpen.value) {
        closeAllModals();
    }
};

watch(isAnyModalOpen, (isOpen, oldVal) => {
    if (typeof document !== 'undefined') {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
            if (!window.history.state?.modalOpen) {
                window.history.pushState({ modalOpen: true }, '');
            }
        } else {
            document.body.style.overflow = '';
            if (oldVal && window.history.state?.modalOpen) {
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
    <Head title="Manajemen Pengguna" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Users class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Master Pengguna Sistem
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Kelola akun, penugasan unit, dan hak akses staf rumah sakit yang berwenang di SIPUAS.
                        </p>
                    </div>
                </div>

                <div class="w-full sm:w-auto flex items-center">
                    <button
                        @click="showCreateModal = true"
                        class="w-full sm:w-auto h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
                    >
                        <UserPlus class="h-4 w-4" />
                        <span>Tambah Pengguna Baru</span>
                    </button>
                </div>
            </div>

            <!-- Summary KPI Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Pengguna</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.total }}</div>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block">{{ stats.active }} Akun Aktif</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <Users class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Kepala Seksi (Kasi)</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.kasi }}</div>
                        <span class="text-[11px] text-slate-400 block">Penanggung Jawab Unit</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                        <Building2 class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Kabid Pelayanan</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.kabid }}</div>
                        <span class="text-[11px] text-slate-400 block">Manajemen Eksekutif</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-blue-50 dark:bg-blue-950/40">
                        <Activity class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Administrator</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.administrator ?? stats.superadmin }}</div>
                        <span class="text-[11px] text-slate-400 block">Akses Penuh Sistem</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-purple-50 dark:bg-purple-950/40">
                        <ShieldCheck class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </div>

            <!-- Unified Table & Filter Container -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                <!-- Filter & Search Toolbar -->
                <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    <div class="relative flex-1 max-w-full lg:max-w-xs xl:max-w-sm">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari nama, NIP, email, username..."
                            class="w-full h-10 pl-10 pr-4 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 lg:flex lg:items-center gap-2.5">
                        <!-- Filter Role -->
                        <select
                            v-model="selectedRole"
                            class="h-10 pl-3.5 pr-9 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition"
                        >
                            <option value="ALL">Semua Peran</option>
                            <option value="ADMINISTRATOR">Administrator</option>
                            <option value="KABID">Kepala Bidang</option>
                            <option value="KASI">Kepala Seksi</option>
                            <option value="STAFF">Staf Pelaksana</option>
                        </select>

                        <!-- Filter Unit -->
                        <select
                            v-model="selectedUnit"
                            class="h-10 pl-3.5 pr-9 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition max-w-full sm:max-w-[200px] truncate"
                        >
                            <option value="ALL">Semua Unit</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.name }}
                            </option>
                        </select>

                        <!-- Filter Status -->
                        <select
                            v-model="selectedStatus"
                            class="h-10 pl-3.5 pr-9 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition"
                        >
                            <option value="ALL">Semua Status</option>
                            <option value="ACTIVE">Aktif</option>
                            <option value="INACTIVE">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <tr>
                                <th class="px-6 py-4">Pengguna & Akun</th>
                                <th class="px-6 py-4">NIP</th>
                                <th class="px-6 py-4">Nomor HP</th>
                                <th class="px-6 py-4 text-center">Peran & Akses</th>
                                <th class="px-6 py-4">Unit Penugasan</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-200">
                            <tr 
                                v-for="user in paginatedUsers" 
                                :key="user.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors duration-150"
                            >
                                <!-- Name & Account -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div v-if="user.profile_photo_path" class="h-9 w-9 rounded-full overflow-hidden border border-emerald-200 dark:border-emerald-800 shrink-0 bg-slate-100 dark:bg-slate-800">
                                            <img :src="user.profile_photo_path" :alt="user.name" class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else class="h-9 w-9 rounded-full bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center font-bold text-xs shrink-0 border border-emerald-100 dark:border-white/20">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-900 dark:text-white">{{ user.name }}</div>
                                            <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- NIP (Connected without spaces) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">
                                    {{ user.nip ? String(user.nip).replace(/\s+/g, '') : '-' }}
                                </td>

                                <!-- Phone Number (No Icon) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">
                                    {{ user.phone_number || '-' }}
                                </td>

                                <!-- Role (No Badge, Bold Text) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs font-bold text-slate-600 dark:text-slate-400">
                                    {{ getRoleLabel(user.role) }}
                                </td>

                                <!-- Unit Name (No Icon) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                                    {{ user.unit_name || 'Semua Unit (Global)' }}
                                </td>

                                <!-- Status Badge -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button
                                        @click="toggleUserStatus(user)"
                                        :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition', user.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 hover:bg-rose-100']"
                                        :title="user.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                                    >
                                        <component :is="user.is_active ? CheckCircle2 : XCircle" class="h-3 w-3" />
                                        <span>{{ user.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </button>
                                </td>

                                <!-- Actions (Badge Styled Buttons) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <Link
                                            :href="route('users.show', { user: user.id })"
                                            class="p-2 rounded-md bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 border border-slate-200/60 dark:border-slate-800 transition duration-150"
                                            title="Lihat Detail Profil"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                        </Link>

                                        <Link
                                            :href="route('users.edit', { user: user.id })"
                                            class="p-2 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150"
                                            title="Edit Data & Hak Akses"
                                        >
                                            <Edit class="h-3.5 w-3.5" />
                                        </Link>

                                        <button
                                            type="button"
                                            @click="openDeleteConfirmModal(user)"
                                            class="p-2 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150 cursor-pointer"
                                            title="Hapus Pengguna"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <Users class="h-8 w-8 mx-auto mb-2 opacity-40" />
                                    <p class="font-medium text-xs">Tidak ada data pengguna yang sesuai dengan filter.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer / Interactive Pagination -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <!-- Left: Per-Page Selector -->
                    <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                        <span class="text-[11px] font-medium">Tampilkan</span>
                        <select
                            v-model="perPage"
                            @change="currentPage = 1"
                            class="h-7 py-0 pl-2 pr-6 text-[11px] font-normal rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 cursor-pointer transition"
                        >
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                        <span class="text-[11px] font-medium">data</span>
                    </div>

                    <!-- Right: Compact Range & Navigation Buttons -->
                    <div class="flex items-center gap-3">
                        <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                            {{ startItemIndex }}–{{ endItemIndex }} dari {{ filteredUsers.length }}
                        </span>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="h-7 w-7 rounded-lg flex items-center justify-center border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150 cursor-pointer"
                                aria-label="Halaman sebelumnya"
                            >
                                <ChevronLeft class="h-3.5 w-3.5" />
                            </button>
                            <button
                                type="button"
                                @click="goToPage(currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="h-7 w-7 rounded-lg flex items-center justify-center border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150 cursor-pointer"
                                aria-label="Halaman berikutnya"
                            >
                                <ChevronRight class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Modal (Pesu Peluh Style with Green Header, Fullscreen on Mobile & Safe Area) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto sm:px-0 flex sm:items-center sm:justify-center min-h-screen">
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity" @click="showCreateModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 w-full min-h-screen sm:min-h-0 sm:max-w-xl sm:rounded-2xl rounded-none border-0 shadow-2xl overflow-hidden transform transition-all flex flex-col z-10 sm:max-h-[90vh]">
                    <!-- Green Header with Icon, No X button -->
                    <div class="px-6 py-4 bg-emerald-600 dark:bg-emerald-700 text-white flex items-center gap-3 shrink-0">
                        <div class="p-2 rounded-xl bg-white/20 text-white shrink-0 flex items-center justify-center">
                            <UserPlus class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white leading-tight">Tambah Pengguna Baru</h3>
                            <p class="text-xs text-emerald-100 mt-0.5">Lengkapi data akun pengguna baru</p>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitCreateUser" class="flex flex-col flex-1 sm:flex-initial overflow-hidden">
                        <div class="p-6 space-y-4 overflow-y-auto flex-1 sm:flex-initial sm:max-h-[calc(90vh-140px)]">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Lengkap & Gelar *</label>
                                <input
                                    type="text"
                                    v-model="createForm.name"
                                    required
                                    placeholder="Contoh: dr. H. Rahmat, Sp.B"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">NIP (Nomor Induk Pegawai)</label>
                                    <input
                                        type="text"
                                        v-model="createForm.nip"
                                        placeholder="198207102008011003"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Username Login</label>
                                    <input
                                        type="text"
                                        v-model="createForm.username"
                                        placeholder="rahmat_igd"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email Resmi *</label>
                                    <input
                                        type="email"
                                        v-model="createForm.email"
                                        required
                                        placeholder="rahmat@rs.local"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nomor HP / WhatsApp</label>
                                    <input
                                        type="text"
                                        v-model="createForm.phone_number"
                                        placeholder="081234567891"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Peran / Hak Akses *</label>
                                    <select
                                        v-model="createForm.role"
                                        required
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition cursor-pointer"
                                    >
                                        <option value="" disabled>-- Pilih Peran / Hak Akses --</option>
                                        <option value="ADMINISTRATOR">Administrator</option>
                                        <option value="KABID">Kepala Bidang</option>
                                        <option value="KASI">Kepala Seksi</option>
                                        <option value="STAFF">Staf Pelaksana</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Penugasan Unit Instalasi</label>
                                    <select
                                        v-model="createForm.unit_id"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition cursor-pointer"
                                    >
                                        <option value="">-- Pilih Unit Kerja --</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                            {{ unit.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Kata Sandi Awal *</label>
                                <input
                                    type="password"
                                    v-model="createForm.password"
                                    required
                                    minlength="6"
                                    placeholder="Minimal 6 karakter"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                />
                            </div>
                        </div>

                        <!-- Footer (Stacked on mobile, side-by-side on desktop, pb-10 safe area for smartphone nav) -->
                        <div class="px-6 pt-4 pb-10 sm:pb-4 bg-slate-50/50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 shrink-0 mt-auto sm:mt-0">
                            <button
                                type="button"
                                @click="showCreateModal = false"
                                class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-center justify-center"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer text-center justify-center"
                            >
                                {{ createForm.processing ? 'Menyimpan...' : 'Simpan Pengguna' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Delete Confirmation Modal (Pesu Peluh Style with Smooth Transition & Safe Padding) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showDeleteConfirmModal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-0 flex items-center justify-center min-h-screen">
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity" @click="showDeleteConfirmModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 rounded-2xl border-0 shadow-2xl w-full max-w-md p-6 pb-8 sm:pb-6 text-center transform transition-all space-y-4 z-10">
                    <div class="h-12 w-12 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
                        <AlertTriangle class="h-6 w-6" />
                    </div>

                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Hapus Pengguna?</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Apakah Anda yakin ingin menghapus akun <strong class="text-slate-700 dark:text-slate-200">{{ selectedUserForAction?.name }}</strong>? Data akan dinonaktifkan dari sistem.
                        </p>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row items-center justify-center gap-2.5 pt-2 w-full">
                        <button
                            type="button"
                            @click="showDeleteConfirmModal = false"
                            class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-center justify-center"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitDeleteUser"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white text-xs font-semibold transition cursor-pointer text-center justify-center"
                        >
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
