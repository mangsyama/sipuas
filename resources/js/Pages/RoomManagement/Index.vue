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
    CheckCircle2, 
    XCircle, 
    Edit, 
    Trash2, 
    AlertTriangle,
    Layers,
    MapPin,
    Users,
    ChevronLeft,
    ChevronRight
} from '@lucide/vue';

const props = defineProps({
    rooms: {
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
            buildings: 0
        })
    },
    buildings: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            building: '',
            status: ''
        })
    }
});

const allRooms = computed(() => {
    return props.rooms && props.rooms.length > 0 ? props.rooms : props.units;
});

const searchQuery = ref(props.filters.search || '');
const selectedBuilding = ref(props.filters.building || props.filters.category || 'ALL');
const selectedStatus = ref(props.filters.status || 'ALL');

// Pagination State
const currentPage = ref(1);
const perPage = ref(10);

// Modal States
const showModal = ref(false);
const isEditing = ref(false);
const editingRoomId = ref(null);
const showDeleteModal = ref(false);
const selectedRoomForDelete = ref(null);

const form = useForm({
    name: '',
    building_name: '',
    location_floor: '',
    is_active: true
});

const availableBuildings = computed(() => {
    if (props.buildings && props.buildings.length > 0) {
        return props.buildings;
    }
    const bSet = new Set();
    allRooms.value.forEach(r => {
        if (r.building_name) bSet.add(r.building_name);
    });
    return Array.from(bSet).sort();
});

const filteredRooms = computed(() => {
    return allRooms.value.filter(r => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !query || 
            (r.name && r.name.toLowerCase().includes(query)) ||
            (r.building_name && r.building_name.toLowerCase().includes(query)) ||
            (r.location_floor && r.location_floor.toLowerCase().includes(query));

        const matchesBuilding = selectedBuilding.value === 'ALL' || r.building_name === selectedBuilding.value;
        const matchesStatus = selectedStatus.value === 'ALL' || 
            (selectedStatus.value === 'ACTIVE' && r.is_active) ||
            (selectedStatus.value === 'INACTIVE' && !r.is_active);

        return matchesSearch && matchesBuilding && matchesStatus;
    });
});

// Reset pagination when filters change
watch([searchQuery, selectedBuilding, selectedStatus], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => {
    return Math.ceil(filteredRooms.value.length / perPage.value) || 1;
});

const paginatedRooms = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredRooms.value.slice(start, start + perPage.value);
});

const startItemIndex = computed(() => {
    if (filteredRooms.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const endItemIndex = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredRooms.value.length);
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
    editingRoomId.value = null;
    form.reset();
    form.clearErrors();
    form.name = '';
    form.building_name = '';
    form.location_floor = '';
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (room) => {
    isEditing.value = true;
    editingRoomId.value = room.id;
    form.clearErrors();
    form.name = room.name;
    form.building_name = room.building_name || '';
    form.location_floor = room.location_floor || '';
    form.is_active = Boolean(room.is_active);
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('units.update', { unit: editingRoomId.value }), {
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

const toggleRoomStatus = (room) => {
    router.patch(route('units.toggle-status', { unit: room.id }), {}, {
        preserveScroll: true
    });
};

const confirmDelete = (room) => {
    selectedRoomForDelete.value = room;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!selectedRoomForDelete.value) return;
    router.delete(route('units.destroy', { unit: selectedRoomForDelete.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedRoomForDelete.value = null;
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
    <Head title="Daftar Ruangan" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4 font-['Poppins',sans-serif]">
            <!-- Header Panel -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <MapPin class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Daftar Ruangan
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Daftar seluruh ruangan, gedung, dan lantai pelayanan rumah sakit (sinkron dengan Pesu Peluh).
                        </p>
                    </div>
                </div>

                <div class="w-full sm:w-auto flex items-center">
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Tambah Ruangan Baru</span>
                    </button>
                </div>
            </div>

            <!-- Top Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Ruangan RS</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.total ?? allRooms.length }}</div>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium block">Seluruh Ruangan Pelayanan</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <Building2 class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Ruangan Aktif</span>
                        <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-tight">{{ stats.active }}</div>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block">Tersedia untuk Laporan & Staf</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                        <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Gedung Pelayanan</span>
                        <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 leading-tight">{{ stats.buildings ?? availableBuildings.length }}</div>
                        <span class="text-[11px] text-slate-400 block">Area & Kompleks Gedung RS</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-blue-50 dark:bg-blue-950/40">
                        <Layers class="h-6 w-6 text-blue-600 dark:text-blue-400" />
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
                            placeholder="Cari nama ruangan, gedung, atau lokasi..."
                            class="w-full h-10 pl-10 pr-4 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:items-center gap-2.5">
                        <!-- Building Filter -->
                        <select
                            v-model="selectedBuilding"
                            class="h-10 pl-3.5 pr-9 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition"
                        >
                            <option value="ALL">Semua Gedung</option>
                            <option v-for="b in availableBuildings" :key="b" :value="b">{{ b }}</option>
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

                <!-- Rooms Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <tr>
                                <th class="px-6 py-4">Nama Ruangan</th>
                                <th class="px-6 py-4">Gedung</th>
                                <th class="px-6 py-4">Lantai / Lokasi</th>
                                <th class="px-6 py-4 text-center">Jumlah Staf</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-200">
                            <tr v-if="filteredRooms.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <Building2 class="h-8 w-8 mx-auto mb-2 opacity-40" />
                                    <p class="font-medium text-xs">Tidak ada ruangan yang sesuai filter.</p>
                                </td>
                            </tr>
                            <tr
                                v-for="room in paginatedRooms"
                                :key="room.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors duration-150"
                            >
                                <!-- Room Name -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-slate-900 dark:text-white">
                                    {{ room.name }}
                                </td>

                                <!-- Building Name (Plain Text, Not Bold, No Badge/Icon) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-normal text-slate-700 dark:text-slate-300">
                                    {{ room.building_name || '-' }}
                                </td>

                                <!-- Floor / Location (Plain Text, Not Bold, No Badge/Icon) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-normal text-slate-700 dark:text-slate-300">
                                    {{ room.location_floor || '-' }}
                                </td>

                                <!-- Staff Count -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs font-bold text-slate-700 dark:text-slate-300">
                                    {{ room.staff_count ?? (room.users_count ?? 0) }}
                                </td>

                                <!-- Status Button -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button
                                        type="button"
                                        @click="toggleRoomStatus(room)"
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition',
                                            room.is_active
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100'
                                                : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 hover:bg-rose-100'
                                        ]"
                                        :title="room.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                                    >
                                        <component :is="room.is_active ? CheckCircle2 : XCircle" class="h-3 w-3" />
                                        <span>{{ room.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(room)"
                                            class="p-2 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150 cursor-pointer"
                                            title="Edit Ruangan"
                                        >
                                            <Edit class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(room)"
                                            class="p-2 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150 cursor-pointer"
                                            title="Hapus Ruangan"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer / Interactive Pagination (Same layout as Daftar Pengguna) -->
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
                            {{ startItemIndex }}–{{ endItemIndex }} dari {{ filteredRooms.length }}
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

        <!-- Create / Edit Room Modal (Pesu Peluh Style with Green Header, Fullscreen on Mobile & Safe Area) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto sm:px-0 flex sm:items-center sm:justify-center min-h-screen font-['Poppins',sans-serif]">
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity" @click="showModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 w-full min-h-screen sm:min-h-0 sm:max-w-xl sm:rounded-2xl rounded-none border-0 shadow-2xl overflow-hidden transform transition-all flex flex-col z-10 sm:max-h-[90vh]">
                    <!-- Green Header with Icon, No X button -->
                    <div class="px-6 py-4 bg-emerald-600 dark:bg-emerald-700 text-white flex items-center gap-3 shrink-0">
                        <div class="p-2 rounded-xl bg-white/20 text-white shrink-0 flex items-center justify-center">
                            <MapPin class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white leading-tight">
                                {{ isEditing ? 'Edit Data Ruangan' : 'Tambah Ruangan Baru' }}
                            </h3>
                            <p class="text-xs text-emerald-100 mt-0.5">
                                {{ isEditing ? 'Perbarui data ruangan rumah sakit' : 'Lengkapi data ruangan rumah sakit' }}
                            </p>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitForm" class="flex flex-col flex-1 sm:flex-initial overflow-hidden">
                        <div class="p-6 space-y-4 overflow-y-auto flex-1 sm:flex-initial sm:max-h-[calc(90vh-140px)]">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Ruangan *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Misal: Ruang Rawat Inap Melati / Poliklinik Gigi"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.name }}</div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Gedung</label>
                                    <input
                                        v-model="form.building_name"
                                        type="text"
                                        list="building-suggestions"
                                        placeholder="Misal: Gedung A / Gedung B"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                    <datalist id="building-suggestions">
                                        <option v-for="b in availableBuildings" :key="b" :value="b" />
                                    </datalist>
                                    <div v-if="form.errors.building_name" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.building_name }}</div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Lantai / Lokasi</label>
                                    <input
                                        v-model="form.location_floor"
                                        type="text"
                                        placeholder="Misal: Lantai 1 / Lantai 2 / -"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                    <div v-if="form.errors.location_floor" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.location_floor }}</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    v-model="form.is_active"
                                    class="rounded accent-emerald-600 cursor-pointer h-4 w-4"
                                />
                                <label for="is_active" class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                    Ruangan Aktif (Muncul pada Formulir Laporan Publik & Registrasi Staf)
                                </label>
                            </div>
                        </div>

                        <!-- Footer (pb-10 safe area for smartphone nav) -->
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
                                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Ruangan') }}
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
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-0 flex items-center justify-center min-h-screen font-['Poppins',sans-serif]">
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity" @click="showDeleteModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 rounded-2xl border-0 shadow-2xl w-full max-w-md p-6 pb-8 sm:pb-6 text-center transform transition-all space-y-4 z-10">
                    <div class="h-12 w-12 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
                        <AlertTriangle class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Hapus Ruangan RS?</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Apakah Anda yakin ingin menghapus ruangan <strong class="text-slate-700 dark:text-slate-200">{{ selectedRoomForDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan jika ruangan telah memiliki riwayat laporan.
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
