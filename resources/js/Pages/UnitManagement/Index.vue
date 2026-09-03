<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
    Building2, 
    Plus, 
    Search, 
    Filter, 
    Shield, 
    ShieldAlert, 
    Activity, 
    CheckCircle2, 
    XCircle, 
    Edit, 
    Trash2, 
    Phone, 
    User, 
    X,
    AlertTriangle,
    Sparkles,
    Stethoscope,
    ChevronLeft,
    ChevronRight
} from '@lucide/vue';

const props = defineProps({
    units: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            medik: 0,
            non_medik: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            category: '',
            status: ''
        })
    }
});

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || 'ALL');
const selectedStatus = ref(props.filters.status || 'ALL');

// Pagination State
const currentPage = ref(1);
const perPage = ref(10);

// Modal States
const showModal = ref(false);
const isEditing = ref(false);
const editingUnitId = ref(null);
const showDeleteModal = ref(false);
const selectedUnitForDelete = ref(null);

const form = useForm({
    code: '',
    name: '',
    category: '',
    is_active: true
});

const filteredUnits = computed(() => {
    return props.units.filter(u => {
        const matchesSearch = !searchQuery.value || 
            u.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            u.code.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesCategory = selectedCategory.value === 'ALL' || u.category === selectedCategory.value;
        const matchesStatus = selectedStatus.value === 'ALL' || 
            (selectedStatus.value === 'ACTIVE' && u.is_active) ||
            (selectedStatus.value === 'INACTIVE' && !u.is_active);

        return matchesSearch && matchesCategory && matchesStatus;
    });
});

// Reset pagination when filters change
watch([searchQuery, selectedCategory, selectedStatus], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => {
    return Math.ceil(filteredUnits.value.length / perPage.value) || 1;
});

const paginatedUnits = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredUnits.value.slice(start, start + perPage.value);
});

const startItemIndex = computed(() => {
    if (filteredUnits.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const endItemIndex = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredUnits.value.length);
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
    editingUnitId.value = null;
    form.reset();
    form.clearErrors();
    form.category = '';
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (unit) => {
    isEditing.value = true;
    editingUnitId.value = unit.id;
    form.clearErrors();
    form.code = unit.code;
    form.name = unit.name;
    form.category = unit.category;
    form.is_active = unit.is_active;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('units.update', { unit: editingUnitId.value }), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('units.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    }
};

const toggleUnitStatus = (unit) => {
    router.patch(route('units.toggle-status', { unit: unit.id }), {}, {
        preserveScroll: true
    });
};

const confirmDelete = (unit) => {
    selectedUnitForDelete.value = unit;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!selectedUnitForDelete.value) return;
    router.delete(route('units.destroy', { unit: selectedUnitForDelete.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedUnitForDelete.value = null;
        }
    });
};

const getCategoryLabel = (category) => {
    if (!category) return '-';
    const upper = String(category).toUpperCase().trim();
    if (upper === 'MEDIK') return 'Medik';
    if (upper === 'NON_MEDIK' || upper === 'NON-MEDIK' || upper === 'NONMEDIK') return 'Non Medik';
    return String(category).replace(/_/g, ' ');
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
    <Head title="Master Unit & Ruangan RS" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Building2 class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Master Unit & Ruangan RS
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Direktori seluruh instalasi pelayanan medik & non-medik rumah sakit.
                        </p>
                    </div>
                </div>

                <div class="w-full sm:w-auto flex items-center">
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Tambah Unit Baru</span>
                    </button>
                </div>
            </div>

            <!-- Top Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Unit RS</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.total }}</div>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium block">Seluruh Instalasi RS</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <Building2 class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Unit Aktif</span>
                        <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-tight">{{ stats.active }}</div>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block">Tersedia untuk Laporan</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                        <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Pelayanan Medik</span>
                        <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 leading-tight">{{ stats.medik }}</div>
                        <span class="text-[11px] text-slate-400 block">Klinis & Perawatan</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-blue-50 dark:bg-blue-950/40">
                        <Stethoscope class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Non-Medik & Sarpras</span>
                        <div class="text-3xl font-extrabold text-purple-600 dark:text-purple-400 leading-tight">{{ stats.non_medik }}</div>
                        <span class="text-[11px] text-slate-400 block">Administrasi & Penunjang</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-purple-50 dark:bg-purple-950/40">
                        <Activity class="h-6 w-6 text-purple-600 dark:text-purple-400" />
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
                            placeholder="Cari kode atau nama unit..."
                            class="w-full h-10 pl-10 pr-4 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:items-center gap-2.5">
                        <!-- Category Filter -->
                        <select
                            v-model="selectedCategory"
                            class="h-10 pl-3.5 pr-9 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition"
                        >
                            <option value="ALL">Semua Kategori</option>
                            <option value="MEDIK">Medik</option>
                            <option value="NON_MEDIK">Non-Medik</option>
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

                <!-- Units Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <tr>
                                <th class="px-6 py-4">Kode Unit</th>
                                <th class="px-6 py-4">Nama Unit Kerja</th>
                                <th class="px-6 py-4">Kategori Pelayanan</th>
                                <th class="px-6 py-4 text-center">Jumlah Staf</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-200">
                            <tr v-if="filteredUnits.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <Building2 class="h-8 w-8 mx-auto mb-2 opacity-40" />
                                    <p class="font-medium text-xs">Tidak ada unit kerja yang sesuai filter.</p>
                                </td>
                            </tr>
                            <tr
                                v-for="unit in paginatedUnits"
                                :key="unit.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors duration-150"
                            >
                                <!-- Unit Code (Separate Column, No Badge) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    {{ unit.code }}
                                </td>

                                <!-- Unit Name (Separate Column) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-slate-900 dark:text-white">
                                    {{ unit.name }}
                                </td>

                                <!-- Category (No Badge, Clean Text) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-medium text-slate-700 dark:text-slate-300">
                                    {{ getCategoryLabel(unit.category) }}
                                </td>

                                <!-- Staff Count (No Badge, Plain Text) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs font-bold text-slate-700 dark:text-slate-300">
                                    {{ unit.staff_count ?? 0 }}
                                </td>

                                <!-- Status Button -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button
                                        type="button"
                                        @click="toggleUnitStatus(unit)"
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition',
                                            unit.is_active
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100'
                                                : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 hover:bg-rose-100'
                                        ]"
                                        :title="unit.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                                    >
                                        <component :is="unit.is_active ? CheckCircle2 : XCircle" class="h-3 w-3" />
                                        <span>{{ unit.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </button>
                                </td>

                                <!-- Actions (Badge Styled Buttons) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(unit)"
                                            class="p-2 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150 cursor-pointer"
                                            title="Edit Unit"
                                        >
                                            <Edit class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(unit)"
                                            class="p-2 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150 cursor-pointer"
                                            title="Hapus Unit"
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
                        <span>Menampilkan</span>
                        <select 
                            v-model="perPage" 
                            class="h-8 px-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 cursor-pointer font-medium"
                            @change="currentPage = 1"
                        >
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                        <span>dari <strong class="text-slate-700 dark:text-slate-200 font-semibold">{{ filteredUnits.length }}</strong> data</span>
                    </div>

                    <!-- Center / Info: Showing items X to Y -->
                    <div class="text-slate-500 dark:text-slate-400 text-center text-xs">
                        <span v-if="filteredUnits.length > 0">
                            Data ke <strong class="text-slate-700 dark:text-slate-200 font-semibold">{{ startItemIndex }}</strong> - <strong class="text-slate-700 dark:text-slate-200 font-semibold">{{ endItemIndex }}</strong>
                        </span>
                        <span v-else>Tidak ada data</span>
                    </div>

                    <!-- Right: Navigation Buttons -->
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="h-8 px-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700/50 transition cursor-pointer flex items-center justify-center gap-1"
                            title="Halaman Sebelumnya"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            <span class="hidden sm:inline">Prev</span>
                        </button>

                        <div class="flex items-center gap-1 mx-1">
                            <template v-for="(p, idx) in visiblePages" :key="idx">
                                <button
                                    v-if="p !== '...'"
                                    type="button"
                                    @click="goToPage(p)"
                                    :class="[
                                        'h-8 w-8 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center justify-center',
                                        currentPage === p
                                            ? 'bg-emerald-600 text-white shadow-sm'
                                            : 'border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'
                                    ]"
                                >
                                    {{ p }}
                                </button>
                                <span v-else class="px-1 text-slate-400 dark:text-slate-500 select-none">...</span>
                            </template>
                        </div>

                        <button
                            type="button"
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="h-8 px-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700/50 transition cursor-pointer flex items-center justify-center gap-1"
                            title="Halaman Berikutnya"
                        >
                            <span class="hidden sm:inline">Next</span>
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Unit Modal (Pesu Peluh Style with Green Header, Fullscreen on Mobile & Safe Area) -->
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
                            <Building2 class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white leading-tight">
                                {{ isEditing ? 'Edit Data Unit Kerja' : 'Tambah Unit Kerja Baru' }}
                            </h3>
                            <p class="text-xs text-emerald-100 mt-0.5">
                                {{ isEditing ? 'Perbarui data unit kerja' : 'Lengkapi data unit kerja baru' }}
                            </p>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitForm" class="flex flex-col flex-1 sm:flex-initial overflow-hidden">
                        <div class="p-6 space-y-4 overflow-y-auto flex-1 sm:flex-initial sm:max-h-[calc(90vh-140px)]">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Kode Unit *</label>
                                    <input
                                        v-model="form.code"
                                        type="text"
                                        placeholder="Misal: FARMASI"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition uppercase"
                                        required
                                    />
                                    <div v-if="form.errors.code" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.code }}</div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Kategori Pelayanan *</label>
                                    <select
                                        v-model="form.category"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition cursor-pointer"
                                        required
                                    >
                                        <option value="" disabled>-- Pilih Kategori Pelayanan --</option>
                                        <option value="MEDIK">Medik</option>
                                        <option value="NON_MEDIK">Non Medik</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Lengkap Unit Kerja *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Misal: Instalasi Farmasi & Depo Obat"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.name }}</div>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    v-model="form.is_active"
                                    class="rounded accent-emerald-600 cursor-pointer h-4 w-4"
                                />
                                <label for="is_active" class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                    Unit Kerja Aktif (Muncul pada Formulir Laporan Publik)
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
                                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Unit') }}
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
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Hapus Unit Kerja?</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Apakah Anda yakin ingin menghapus unit <strong class="text-slate-700 dark:text-slate-200">{{ selectedUnitForDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
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
