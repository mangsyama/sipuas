<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { 
    Users, 
    UserCheck, 
    UserX, 
    Search, 
    Building2, 
    CheckCircle2, 
    Eye, 
    Trash2, 
    User, 
    X,
    Check,
    AlertTriangle,
    Clock,
    ChevronLeft,
    ChevronRight,
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
            pending: 0,
            approved_today: 0,
            total_active: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            unit: ''
        })
    }
});

const searchQuery = ref(props.filters.search || '');
const selectedUnit = ref(props.filters.unit || 'ALL');

// Pagination State
const currentPage = ref(1);
const perPage = ref(10);

// Modal States
const showQuickApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedUserForAction = ref(null);

const approveForm = useForm({
    role: 'STAFF',
    unit_id: ''
});

const filteredUsers = computed(() => {
    return props.users.filter(u => {
        const matchesSearch = !searchQuery.value || 
            u.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (u.nip && u.nip.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            u.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (u.username && u.username.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (u.phone_number && u.phone_number.includes(searchQuery.value));

        const matchesUnit = selectedUnit.value === 'ALL' || (u.unit_id && String(u.unit_id) === String(selectedUnit.value));

        return matchesSearch && matchesUnit;
    });
});

// Reset pagination when filters change
watch([searchQuery, selectedUnit], () => {
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

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const openQuickApprove = (user) => {
    selectedUserForAction.value = user;
    approveForm.role = user.role || 'STAFF';
    approveForm.unit_id = user.unit_id || '';
    showQuickApproveModal.value = true;
};

const submitQuickApprove = () => {
    if (!selectedUserForAction.value) return;
    approveForm.post(route('users.approvals.approve', { user: selectedUserForAction.value.id }), {
        onSuccess: () => {
            showQuickApproveModal.value = false;
            selectedUserForAction.value = null;
        }
    });
};

const openRejectModal = (user) => {
    selectedUserForAction.value = user;
    showRejectModal.value = true;
};

const submitReject = () => {
    if (!selectedUserForAction.value) return;
    router.delete(route('users.approvals.reject', { user: selectedUserForAction.value.id }), {
        onSuccess: () => {
            showRejectModal.value = false;
            selectedUserForAction.value = null;
        }
    });
};

// Modal Scroll Lock
const isAnyModalOpen = computed(() => {
    return showQuickApproveModal.value || showRejectModal.value;
});

const closeAllModals = () => {
    showQuickApproveModal.value = false;
    showRejectModal.value = false;
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
    <Head title="Persetujuan Pendaftaran Pengguna" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel (SAME LAYOUT AS MASTER PENGGUNA) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-amber-50 dark:bg-white/10 text-amber-600 dark:text-white">
                        <UserCheck class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Persetujuan Pendaftaran Pengguna
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Verifikasi berkas pendaftaran, pasfoto formal, dan tetapkan hak akses akun staf baru di SIPUAS.
                        </p>
                    </div>
                </div>

                <div class="w-full sm:w-auto flex items-center">
                    <Link
                        :href="route('users.index')"
                        class="w-full sm:w-auto h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
                    >
                        <Users class="h-4 w-4" />
                        <span>Master Pengguna Aktif</span>
                    </Link>
                </div>
            </div>

            <!-- Summary KPI Stats Cards (SAME LAYOUT AS MASTER PENGGUNA) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Menunggu Verifikasi</span>
                        <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 leading-tight">{{ stats.pending }}</div>
                        <span class="text-[11px] text-slate-400 block">Pendaftar baru belum disetujui</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-950/40">
                        <Clock class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Disetujui Hari Ini</span>
                        <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-tight">{{ stats.approved_today }}</div>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block">Akun diaktifkan hari ini</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                        <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Pengguna Aktif</span>
                        <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 leading-tight">{{ stats.total_active }}</div>
                        <span class="text-[11px] text-slate-400 block">Akun aktif dalam sistem</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-blue-50 dark:bg-blue-950/40">
                        <Users class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            <!-- Unified Table & Filter Container (SAME LAYOUT AS MASTER PENGGUNA) -->
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:items-center gap-2.5">
                        <!-- Filter Unit -->
                        <select
                            v-model="selectedUnit"
                            class="h-10 pl-3.5 pr-9 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition max-w-full sm:max-w-[220px] truncate"
                        >
                            <option value="ALL">Semua Unit Kerja</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.name }}
                            </option>
                        </select>

                        <!-- Reset Filter -->
                        <button
                            v-if="searchQuery || selectedUnit !== 'ALL'"
                            @click="searchQuery = ''; selectedUnit = 'ALL';"
                            class="h-10 px-3.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <tr>
                                <th class="px-6 py-4">Calon Pengguna</th>
                                <th class="px-6 py-4">NIP</th>
                                <th class="px-6 py-4">Nomor HP</th>
                                <th class="px-6 py-4">Pilihan Unit Kerja</th>
                                <th class="px-6 py-4">Waktu Pendaftaran</th>
                                <th class="px-6 py-4 text-center">Aksi Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-200">
                            <!-- Empty State -->
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                            <CheckCircle2 class="h-6 w-6 text-emerald-500" />
                                        </div>
                                        <div class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                            Tidak Ada Antrean Pendaftaran
                                        </div>
                                        <div class="text-xs text-slate-400 max-w-sm">
                                            Semua akun pendaftar baru telah diverifikasi atau tidak ada data yang cocok dengan pencarian.
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Data Rows -->
                            <tr 
                                v-for="user in paginatedUsers" 
                                :key="user.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors duration-150"
                            >
                                <!-- Name & Account with Avatar Thumbnail -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div v-if="user.profile_photo_path" class="h-9 w-9 rounded-full overflow-hidden border border-amber-300 dark:border-amber-700 shrink-0 bg-slate-100 dark:bg-slate-800">
                                            <img :src="user.profile_photo_path" :alt="user.name" class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else class="h-9 w-9 rounded-full bg-amber-50 dark:bg-white/10 text-amber-600 dark:text-white flex items-center justify-center font-bold text-xs shrink-0 border border-amber-100 dark:border-white/20">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-900 dark:text-white">{{ user.name }}</div>
                                            <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- NIP -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">
                                    {{ user.nip ? String(user.nip).replace(/\s+/g, '') : '-' }}
                                </td>

                                <!-- Phone Number -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">
                                    {{ user.phone_number || '-' }}
                                </td>

                                <!-- Unit Penugasan -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">
                                    {{ user.unit_name }}
                                </td>

                                <!-- Waktu Pendaftaran (Clean single date, no relative text) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">
                                    {{ user.created_at }}
                                </td>

                                <!-- Aksi (Badge Styled Buttons) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Detail Link -->
                                        <Link
                                            :href="route('users.approvals.show', { user: user.id })"
                                            class="p-2 rounded-md bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 border border-slate-200/60 dark:border-slate-800 transition duration-150"
                                            title="Tinjau & Verifikasi Lengkap"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                        </Link>

                                        <!-- Quick Approve Button -->
                                        <button
                                            type="button"
                                            @click="openQuickApprove(user)"
                                            class="p-2 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150 cursor-pointer"
                                            title="Setujui dan Aktifkan Akun"
                                        >
                                            <Check class="h-3.5 w-3.5" />
                                        </button>

                                        <!-- Reject Button -->
                                        <button
                                            type="button"
                                            @click="openRejectModal(user)"
                                            class="p-2 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150 cursor-pointer"
                                            title="Tolak Pendaftaran"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer / Interactive Pagination (EXACT MATCH AS MASTER PENGGUNA) -->
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

        <!-- Quick Approve Modal -->
        <div v-if="showQuickApproveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fade-in">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-5 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <UserCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                Setujui Pendaftaran Akun
                            </h3>
                            <p class="text-xs text-slate-400">Tetapkan peran & unit sebelum akun diaktifkan</p>
                        </div>
                    </div>
                    <button @click="showQuickApproveModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div v-if="selectedUserForAction" class="bg-slate-50 dark:bg-slate-950 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-3">
                    <div v-if="selectedUserForAction.profile_photo_path" class="h-10 w-10 rounded-full overflow-hidden border border-emerald-500 shrink-0">
                        <img :src="selectedUserForAction.profile_photo_path" :alt="selectedUserForAction.name" class="h-full w-full object-cover" />
                    </div>
                    <div v-else class="h-10 w-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shrink-0">
                        {{ selectedUserForAction.name.charAt(0) }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900 dark:text-white text-xs truncate">
                            {{ selectedUserForAction.name }}
                        </div>
                        <div class="text-[11px] text-slate-400">
                            NIP: {{ selectedUserForAction.nip || '-' }} • @{{ selectedUserForAction.username }}
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitQuickApprove" class="space-y-4">
                    <!-- Role Assignment -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Peran / Role Akses *
                        </label>
                        <select
                            v-model="approveForm.role"
                            required
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition"
                        >
                            <option value="STAFF">Staf Pelaksana</option>
                            <option value="KASI">Kepala Seksi (Kasi)</option>
                            <option value="KABID">Kepala Bidang (Kabid)</option>
                            <option value="ADMINISTRATOR">Administrator</option>
                            <option value="SUPERADMIN">Super Administrator</option>
                        </select>
                    </div>

                    <!-- Unit Assignment -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Unit Penugasan
                        </label>
                        <select
                            v-model="approveForm.unit_id"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition"
                        >
                            <option value="">-- Pilih Unit Kerja --</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="showQuickApproveModal = false"
                            class="h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="approveForm.processing"
                            class="h-9 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1.5 transition cursor-pointer disabled:opacity-50"
                        >
                            <Check class="h-4 w-4" />
                            <span>{{ approveForm.processing ? 'Menyimpan...' : 'Setujui & Aktifkan' }}</span>
                        </button>
                    </div>
                </form>
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
                        Data calon pengguna <strong class="text-slate-800 dark:text-slate-200">{{ selectedUserForAction?.name }}</strong> akan dihapus permanen dari antrean pendaftaran.
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
