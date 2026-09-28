<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { 
    Users, 
    UserPlus, 
    Search, 
    Filter, 
    Shield, 
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
    ChevronRight,
    UserCheck
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
            active: 0,
            pending: 0
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

const allowOnlyNumbers = (e) => {
    // Allow control/navigation keys
    if (
        ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key) ||
        e.ctrlKey || e.metaKey || e.altKey
    ) {
        return;
    }
    // Block any non-digit character
    if (!/^[0-9]$/.test(e.key)) {
        e.preventDefault();
    }
};

const blockNonNumericInput = (e) => {
    if (e.data && !/^[0-9]+$/.test(e.data)) {
        e.preventDefault();
    }
};

const handleCreateNipInput = (e) => {
    createForm.clearErrors('nip');
    const cleaned = (e.target.value || '').replace(/\D/g, '').slice(0, 18);
    createForm.nip = cleaned;
    e.target.value = cleaned;
};

const handleCreatePhoneInput = (e) => {
    createForm.clearErrors('phone_number');
    const cleaned = (e.target.value || '').replace(/\D/g, '').slice(0, 15);
    createForm.phone_number = cleaned;
    e.target.value = cleaned;
};

const handleNipPaste = (e) => {
    e.preventDefault();
    const paste = (e.clipboardData || window.clipboardData)?.getData('text') || '';
    const cleaned = paste.replace(/\D/g, '');
    const target = e.target;
    const start = target.selectionStart || 0;
    const end = target.selectionEnd || 0;
    const currentVal = target.value || '';
    const combined = (currentVal.slice(0, start) + cleaned + currentVal.slice(end)).slice(0, 18);
    createForm.nip = combined;
    target.value = combined;
    createForm.clearErrors('nip');
};

const handlePhonePaste = (e) => {
    e.preventDefault();
    const paste = (e.clipboardData || window.clipboardData)?.getData('text') || '';
    const cleaned = paste.replace(/\D/g, '');
    const target = e.target;
    const start = target.selectionStart || 0;
    const end = target.selectionEnd || 0;
    const currentVal = target.value || '';
    const combined = (currentVal.slice(0, start) + cleaned + currentVal.slice(end)).slice(0, 15);
    createForm.phone_number = combined;
    target.value = combined;
    createForm.clearErrors('phone_number');
};

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreateUser = () => {
    createForm.clearErrors();

    // 1. Nama Lengkap (Wajib)
    if (!createForm.name || !createForm.name.trim()) {
        createForm.setError('name', 'Nama lengkap & gelar wajib diisi.');
        return;
    }

    // 2. NIP (Wajib, tepat 18 karakter angka)
    if (!createForm.nip) {
        createForm.setError('nip', 'NIP wajib diisi.');
        return;
    }
    if (createForm.nip.length !== 18) {
        createForm.setError('nip', 'NIP harus terdiri dari 18 digit angka.');
        return;
    }

    // 3. Username Login (Wajib)
    if (!createForm.username || !createForm.username.trim()) {
        createForm.setError('username', 'Username login wajib diisi.');
        return;
    }

    // 4. Email Resmi (Wajib)
    if (!createForm.email || !createForm.email.trim()) {
        createForm.setError('email', 'Email resmi wajib diisi.');
        return;
    }

    // 5. Nomor HP / WhatsApp (Wajib, 10 - 15 digit angka)
    if (!createForm.phone_number) {
        createForm.setError('phone_number', 'Nomor HP / WhatsApp wajib diisi.');
        return;
    }
    if (createForm.phone_number.length < 10) {
        createForm.setError('phone_number', 'Nomor HP / WhatsApp minimal 10 digit angka.');
        return;
    }

    // 6. Peran / Hak Akses (Wajib)
    if (!createForm.role) {
        createForm.setError('role', 'Peran / Hak Akses wajib dipilih.');
        return;
    }

    // 7. Penugasan Unit Instalasi (Wajib)
    if (!createForm.unit_id) {
        createForm.setError('unit_id', 'Penugasan Unit Kerja wajib dipilih.');
        return;
    }

    // 8. Kata Sandi Awal (Wajib, minimal 6 karakter)
    if (!createForm.password) {
        createForm.setError('password', 'Kata sandi awal wajib diisi.');
        return;
    }
    if (createForm.password.length < 6) {
        createForm.setError('password', 'Kata sandi minimal 6 karakter.');
        return;
    }

    createForm.post(route('users.store'), {
        onSuccess: () => {
            closeCreateModal();
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
        case 'ADMINISTRATOR':
            return 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800';
        case 'DIREKTUR':
            return 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800';
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
        case 'DIREKTUR':
            return 'Direktur';
        case 'KABID':
            return 'Kepala Bidang';
        case 'KASI':
            return 'Kepala Seksi';
        case 'STAFF':
            return 'Staf Pelayanan';
        default:
            return role;
    }
};

// Modal Scroll Lock & Keyboard / History Navigation Management
const isAnyModalOpen = computed(() => {
    return showCreateModal.value || showDeleteConfirmModal.value || showResetPasswordModal.value;
});

const closeAllModals = () => {
    closeCreateModal();
    showDeleteConfirmModal.value = false;
    showResetPasswordModal.value = false;
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
    <Head title="Daftar Pengguna" />

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
                            Daftar Pengguna
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Kelola akun, penugasan ruangan, dan hak akses staf rumah sakit yang berwenang di SIPUAS.
                        </p>
                    </div>
                </div>

                <div class="w-full sm:w-auto flex flex-wrap items-center gap-2.5">
                    <Link
                        v-if="stats.pending > 0"
                        :href="route('users.approvals')"
                        class="w-full sm:w-auto h-10 px-4 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60 text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
                    >
                        <UserCheck class="h-4 w-4" />
                        <span>Persetujuan Pendaftar</span>
                        <span class="min-w-[20px] h-5 px-1.5 rounded-full bg-amber-500 text-white text-[10px] font-extrabold flex items-center justify-center shrink-0 leading-none">{{ stats.pending }}</span>
                    </Link>

                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer shadow-sm"
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
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Staf Pelayanan</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.staff }}</div>
                        <span class="text-[11px] text-slate-400 block">Pelaksana Pelayanan RS</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-purple-50 dark:bg-purple-950/40">
                        <User class="h-6 w-6 text-purple-600 dark:text-purple-400" />
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
                            <option value="DIREKTUR">Direktur</option>
                            <option value="KABID">Kepala Bidang</option>
                            <option value="KASI">Kepala Seksi</option>
                            <option value="STAFF">Staf Pelayanan</option>
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
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity select-none"></div>

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
                                <InputError :message="createForm.errors.name" class="mt-1.5" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">NIP (Nomor Induk Pegawai) *</label>
                                    <input
                                        type="text"
                                        required
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="18"
                                        :value="createForm.nip"
                                        @keydown="allowOnlyNumbers"
                                        @beforeinput="blockNonNumericInput"
                                        @input="handleCreateNipInput"
                                        @paste="handleNipPaste"
                                        placeholder="198207102008011003"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                    <InputError :message="createForm.errors.nip" class="mt-1.5" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Username Login *</label>
                                    <input
                                        type="text"
                                        v-model="createForm.username"
                                        required
                                        placeholder="rahmat_igd"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                    <InputError :message="createForm.errors.username" class="mt-1.5" />
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
                                    <InputError :message="createForm.errors.email" class="mt-1.5" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nomor HP / WhatsApp *</label>
                                    <input
                                        type="tel"
                                        required
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="15"
                                        :value="createForm.phone_number"
                                        @keydown="allowOnlyNumbers"
                                        @beforeinput="blockNonNumericInput"
                                        @input="handleCreatePhoneInput"
                                        @paste="handlePhonePaste"
                                        placeholder="081234567891"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                    <InputError :message="createForm.errors.phone_number" class="mt-1.5" />
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
                                        <option value="DIREKTUR">Direktur</option>
                                        <option value="KABID">Kepala Bidang</option>
                                        <option value="KASI">Kepala Seksi</option>
                                        <option value="STAFF">Staf Pelayanan</option>
                                    </select>
                                    <InputError :message="createForm.errors.role" class="mt-1.5" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Penugasan Unit Kerja *</label>
                                    <select
                                        v-model="createForm.unit_id"
                                        required
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition cursor-pointer"
                                    >
                                        <option value="" disabled>-- Pilih Unit Kerja --</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                            {{ unit.name }}
                                        </option>
                                    </select>
                                    <InputError :message="createForm.errors.unit_id" class="mt-1.5" />
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
                                <InputError :message="createForm.errors.password" class="mt-1.5" />
                            </div>
                        </div>

                        <!-- Footer (Stacked on mobile, side-by-side on desktop, pb-10 safe area for smartphone nav) -->
                        <div class="px-6 pt-4 pb-10 sm:pb-4 bg-slate-50/50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 shrink-0 mt-auto sm:mt-0">
                            <button
                                type="button"
                                @click="closeCreateModal"
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
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity select-none"></div>

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
