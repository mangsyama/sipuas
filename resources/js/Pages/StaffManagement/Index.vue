<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
    Users, 
    UserPlus, 
    Search, 
    Filter, 
    Shield, 
    Activity, 
    CheckCircle2, 
    XCircle, 
    Edit, 
    Trash2, 
    User, 
    X,
    AlertTriangle,
    Award,
    TrendingUp,
    TrendingDown,
    Building2,
    Briefcase,
    ChevronLeft,
    ChevronRight
} from '@lucide/vue';

const props = defineProps({
    staffMembers: {
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
            active: 0,
            praises: 0,
            complaints: 0,
            top_score: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            unit_id: '',
            status: ''
        })
    }
});

const searchQuery = ref(props.filters.search || '');
const selectedUnit = ref(props.filters.unit_id || 'ALL');
const selectedStatus = ref(props.filters.status || 'ALL');

// Pagination State
const currentPage = ref(1);
const perPage = ref(10);

// Modal States
const showModal = ref(false);
const isEditing = ref(false);
const editingStaffId = ref(null);
const showDeleteModal = ref(false);
const selectedStaffForDelete = ref(null);

const form = useForm({
    unit_id: '',
    name: '',
    nip: '',
    role: '',
    total_points: 100,
    is_active: true
});

const filteredStaff = computed(() => {
    return props.staffMembers.filter(s => {
        const matchesSearch = !searchQuery.value || 
            s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (s.nip && s.nip.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            s.role.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesUnit = selectedUnit.value === 'ALL' || String(s.unit_id) === String(selectedUnit.value);
        const matchesStatus = selectedStatus.value === 'ALL' || 
            (selectedStatus.value === 'ACTIVE' && s.is_active) ||
            (selectedStatus.value === 'INACTIVE' && !s.is_active);

        return matchesSearch && matchesUnit && matchesStatus;
    });
});

// Reset pagination when filters change
watch([searchQuery, selectedUnit, selectedStatus], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => {
    return Math.ceil(filteredStaff.value.length / perPage.value) || 1;
});

const paginatedStaff = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredStaff.value.slice(start, start + perPage.value);
});

const startItemIndex = computed(() => {
    if (filteredStaff.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const endItemIndex = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredStaff.value.length);
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

const openCreateModal = () => {
    isEditing.value = false;
    editingStaffId.value = null;
    form.reset();
    form.clearErrors();
    form.unit_id = '';
    form.total_points = 100;
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (staff) => {
    isEditing.value = true;
    editingStaffId.value = staff.id;
    form.clearErrors();
    form.unit_id = staff.unit_id;
    form.name = staff.name;
    form.nip = staff.nip || '';
    form.role = staff.role;
    form.total_points = staff.total_points;
    form.is_active = staff.is_active;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('staff.update', { staff: editingStaffId.value }), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('staff.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    }
};

const toggleStaffStatus = (staff) => {
    router.patch(route('staff.toggle-status', { staff: staff.id }), {}, {
        preserveScroll: true
    });
};

const confirmDelete = (staff) => {
    selectedStaffForDelete.value = staff;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!selectedStaffForDelete.value) return;
    router.delete(route('staff.destroy', { staff: selectedStaffForDelete.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedStaffForDelete.value = null;
        }
    });
};

// Modal Scroll Lock & Keyboard / History Navigation Management
const isAnyModalOpen = computed(() => {
    return showModal.value || showDeleteModal.value;
});

const closeAllModals = () => {
    showModal.value = false;
    showDeleteModal.value = false;
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
    <Head title="Master Staf & Pegawai RS" />

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
                            Master Staf & Pegawai RS
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Direktori profil staf pelayanan unit kerja rumah sakit, nomor induk pegawai (NIP), dan akumulasi saldo poin KPI.
                        </p>
                    </div>
                </div>

                <div class="w-full sm:w-auto flex items-center">
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
                    >
                        <UserPlus class="h-4 w-4" />
                        <span>Tambah Staf Baru</span>
                    </button>
                </div>
            </div>

            <!-- Top Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Staf RS</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.total }}</div>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block">{{ stats.active }} Staf Aktif Bertugas</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <Users class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Apresiasi Pujian</span>
                        <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-tight">+{{ stats.praises }}</div>
                        <span class="text-[11px] text-slate-400 block">Pujian Pasien Terverifikasi</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                        <TrendingUp class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Komplain</span>
                        <div class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 leading-tight">-{{ stats.complaints }}</div>
                        <span class="text-[11px] text-slate-400 block">Evaluasi & Pembinaan</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-rose-50 dark:bg-rose-950/40">
                        <TrendingDown class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Skor Tertinggi RS</span>
                        <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 leading-tight">{{ stats.top_score }} Poin</div>
                        <span class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold block">Leaderboard #1 Teratas</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-950/40">
                        <Award class="h-6 w-6 text-amber-600 dark:text-amber-400" />
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
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama, NIP, atau jabatan staf..."
                            class="w-full h-10 pl-10 pr-4 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:items-center gap-2.5">
                        <!-- Unit Filter -->
                        <select
                            v-model="selectedUnit"
                            class="h-10 pl-3.5 pr-9 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition max-w-full sm:max-w-[200px] truncate"
                        >
                            <option value="ALL">Semua Unit RS</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>

                        <!-- Status Filter -->
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

                <!-- Staff Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <tr>
                                <th class="px-6 py-4">Nama Staf</th>
                                <th class="px-6 py-4">NIP</th>
                                <th class="px-6 py-4">Unit Kerja</th>
                                <th class="px-6 py-4">Jabatan / Peran</th>
                                <th class="px-6 py-4 text-center">Saldo Poin KPI</th>
                                <th class="px-6 py-4 text-center">Rekam Jejak</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-200">
                            <tr v-if="filteredStaff.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <Users class="h-8 w-8 mx-auto mb-2 opacity-40" />
                                    <p class="font-medium text-xs">Belum ada staf yang terdaftar sesuai filter.</p>
                                </td>
                            </tr>
                            <tr
                                v-for="staff in paginatedStaff"
                                :key="staff.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors duration-150"
                            >
                                <!-- Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center font-bold text-xs border border-emerald-100 dark:border-white/20 shrink-0">
                                            {{ staff.name.charAt(0) }}
                                        </div>
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ staff.name }}</div>
                                    </div>
                                </td>

                                <!-- NIP (Separate Column, connected without spaces) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">
                                    {{ staff.nip ? String(staff.nip).replace(/\s+/g, '') : '-' }}
                                </td>

                                <!-- Unit Kerja (No Icon) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                                    {{ staff.unit ? staff.unit.name : 'Unit Umum' }}
                                </td>

                                <!-- Role / Jabatan -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300 font-medium">
                                    {{ staff.role }}
                                </td>

                                <!-- KPI Points -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 font-extrabold text-xs border border-emerald-200 dark:border-emerald-800">
                                        {{ staff.total_points }} Poin
                                    </span>
                                </td>

                                <!-- Praise / Complaint Track Record -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="inline-flex items-center gap-1.5 text-[11px] font-bold">
                                        <span class="text-emerald-600 dark:text-emerald-400">+{{ staff.praise_count }}</span>
                                        <span class="text-slate-300 dark:text-slate-700">/</span>
                                        <span class="text-rose-600 dark:text-rose-400">-{{ staff.complaint_count }}</span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button
                                        type="button"
                                        @click="toggleStaffStatus(staff)"
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition',
                                            staff.is_active
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100'
                                                : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 hover:bg-rose-100'
                                        ]"
                                        :title="staff.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                                    >
                                        <component :is="staff.is_active ? CheckCircle2 : XCircle" class="h-3 w-3" />
                                        <span>{{ staff.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </button>
                                </td>

                                <!-- Actions (Badge Styled Buttons) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(staff)"
                                            class="p-2 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150 cursor-pointer"
                                            title="Edit Staf"
                                        >
                                            <Edit class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(staff)"
                                            class="p-2 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150 cursor-pointer"
                                            title="Hapus Staf"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
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
                            {{ startItemIndex }}–{{ endItemIndex }} dari {{ filteredStaff.length }}
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

        <!-- Create / Edit Staff Modal (Pesu Peluh Style with Green Header, Fullscreen on Mobile & Safe Area) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto sm:px-0 flex sm:items-center sm:justify-center min-h-screen">
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity" @click="showModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 w-full min-h-screen sm:min-h-0 sm:max-w-xl sm:rounded-2xl rounded-none border-0 shadow-2xl overflow-hidden transform transition-all flex flex-col z-10 sm:max-h-[90vh]">
                    <!-- Green Header with Icon, No X button -->
                    <div class="px-6 py-4 bg-emerald-600 dark:bg-emerald-700 text-white flex items-center gap-3 shrink-0">
                        <div class="p-2 rounded-xl bg-white/20 text-white shrink-0 flex items-center justify-center">
                            <Users class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white leading-tight">
                                {{ isEditing ? 'Edit Data Staf RS' : 'Tambah Staf Pelayanan Baru' }}
                            </h3>
                            <p class="text-xs text-emerald-100 mt-0.5">
                                {{ isEditing ? 'Perbarui data dan profil staf' : 'Lengkapi data staf baru' }}
                            </p>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitForm" class="flex flex-col flex-1 sm:flex-initial overflow-hidden">
                        <div class="p-6 space-y-4 overflow-y-auto flex-1 sm:flex-initial sm:max-h-[calc(90vh-140px)]">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Unit Kerja Rumah Sakit *</label>
                                <select
                                    v-model="form.unit_id"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition cursor-pointer"
                                    required
                                >
                                    <option value="" disabled>-- Pilih Unit Kerja --</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                                <div v-if="form.errors.unit_id" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.unit_id }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Lengkap & Gelar *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Misal: Sinta Dewi, A.Md.Farm"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.name }}</div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nomor Induk Pegawai (NIP)</label>
                                    <input
                                        v-model="form.nip"
                                        type="text"
                                        placeholder="19920412..."
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                    <div v-if="form.errors.nip" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.nip }}</div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Jabatan / Role Pelayanan *</label>
                                    <input
                                        v-model="form.role"
                                        type="text"
                                        placeholder="Misal: Apoteker Pelaksana"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                        required
                                    />
                                    <div v-if="form.errors.role" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.role }}</div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Saldo Awal Poin KPI</label>
                                <input
                                    v-model="form.total_points"
                                    type="number"
                                    placeholder="100"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                />
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input
                                    type="checkbox"
                                    id="staff_is_active"
                                    v-model="form.is_active"
                                    class="rounded accent-emerald-600 cursor-pointer h-4 w-4"
                                />
                                <label for="staff_is_active" class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                    Staf Aktif Bertugas (Dapat dikaitkan pada verifikasi laporan shift)
                                </label>
                            </div>
                        </div>

                        <!-- Footer (Stacked on mobile, side-by-side on desktop, pb-10 safe area for smartphone nav) -->
                        <div class="px-6 pt-4 pb-10 sm:pb-4 bg-slate-50/50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 shrink-0 mt-auto sm:mt-0">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-center justify-center"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer text-center justify-center"
                            >
                                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Staf') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Delete Confirmation Modal (Pesu Peluh Style with Smooth Transition & Safe Area) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-0 flex items-center justify-center min-h-screen">
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity" @click="showDeleteModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 rounded-2xl border-0 shadow-2xl w-full max-w-md p-6 pb-8 sm:pb-6 text-center transform transition-all space-y-4 z-10">
                    <div class="h-12 w-12 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
                        <AlertTriangle class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Hapus Data Staf?</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Apakah Anda yakin ingin menghapus data staf <strong class="text-slate-700 dark:text-slate-200">{{ selectedStaffForDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-center gap-2.5 pt-2 w-full">
                        <button
                            type="button"
                            @click="showDeleteModal = false"
                            class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-center justify-center"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="executeDelete"
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
