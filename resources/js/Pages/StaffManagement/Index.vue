<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { 
    Users, 
    UserPlus, 
    Search, 
    Filter, 
    Building2, 
    CheckCircle2, 
    XCircle, 
    Edit, 
    Trash2, 
    Phone, 
    User, 
    X,
    Check,
    AlertTriangle,
    ChevronLeft,
    ChevronRight,
    Contact,
    MapPin,
    Briefcase,
    Award,
    Upload,
    FileSpreadsheet,
    Download,
    Loader2
} from '@lucide/vue';

const props = defineProps({
    staff: {
        type: Array,
        default: () => []
    },
    rooms: {
        type: Array,
        default: () => []
    },
    positions: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            rooms_with_staff: 0,
            positions_count: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            room_id: '',
            position: '',
            status: ''
        })
    }
});

const searchQuery = ref(props.filters.search || '');
const selectedRoom = ref(props.filters.room_id || 'ALL');
const selectedPosition = ref(props.filters.position || 'ALL');
const selectedStatus = ref(props.filters.status || 'ALL');

// Pagination State
const currentPage = ref(1);
const perPage = ref(10);

// Modal States
const showModal = ref(false);
const isEditing = ref(false);
const editingStaffId = ref(null);
const showDeleteConfirmModal = ref(false);
const selectedStaffForAction = ref(null);
const roomSearchInsideModal = ref('');

// Form State
const form = useForm({
    name: '',
    position: '',
    nip: '',
    phone_number: '',
    is_active: true,
    room_ids: []
});

// Saved Position History via LocalStorage (Clean & Dynamic, No Hardcoding)
const savedPositions = ref([]);

const loadSavedPositions = () => {
    if (typeof window !== 'undefined') {
        try {
            const raw = localStorage.getItem('sipuas_staff_positions');
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) {
                    savedPositions.value = parsed.filter(p => typeof p === 'string' && p.trim().length > 0);
                    return;
                }
            }
        } catch (e) {
            console.error('Error loading saved positions:', e);
        }
        savedPositions.value = [];
    }
};

const savePositionHistory = (newPos) => {
    if (!newPos || !newPos.trim()) return;
    const cleanPos = newPos.trim();
    if (typeof window !== 'undefined') {
        try {
            const list = savedPositions.value.filter(p => p.toLowerCase() !== cleanPos.toLowerCase());
            list.unshift(cleanPos);
            const truncated = list.slice(0, 10);
            savedPositions.value = truncated;
            localStorage.setItem('sipuas_staff_positions', JSON.stringify(truncated));
        } catch (e) {
            console.error('Error saving position history:', e);
        }
    }
};

const filteredStaff = computed(() => {
    return props.staff.filter(s => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !query || 
            s.name.toLowerCase().includes(query) ||
            (s.nip && s.nip.toLowerCase().includes(query)) ||
            (s.position && s.position.toLowerCase().includes(query)) ||
            (s.phone_number && s.phone_number.toLowerCase().includes(query)) ||
            (s.rooms && s.rooms.some(r => r.name.toLowerCase().includes(query)));

        const matchesRoom = selectedRoom.value === 'ALL' || !selectedRoom.value || 
            (selectedRoom.value === 'GLOBAL' && (!s.rooms || s.rooms.length === 0)) ||
            (s.rooms && s.rooms.some(r => String(r.id) === String(selectedRoom.value)));

        const matchesPosition = selectedPosition.value === 'ALL' || !selectedPosition.value || 
            s.position === selectedPosition.value;

        const matchesStatus = selectedStatus.value === 'ALL' || selectedStatus.value === '' || 
            (selectedStatus.value === 'ACTIVE' && s.is_active) ||
            (selectedStatus.value === 'INACTIVE' && !s.is_active) ||
            (selectedStatus.value === '1' && s.is_active) ||
            (selectedStatus.value === '0' && !s.is_active);

        return matchesSearch && matchesRoom && matchesPosition && matchesStatus;
    });
});

// Reset pagination when filters change
watch([searchQuery, selectedRoom, selectedPosition, selectedStatus], () => {
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

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// Filtered Rooms for Modal Multi-Select
const filteredRoomsForModal = computed(() => {
    if (!roomSearchInsideModal.value.trim()) {
        return props.rooms;
    }
    const q = roomSearchInsideModal.value.toLowerCase().trim();
    return props.rooms.filter(r => 
        (r.name && r.name.toLowerCase().includes(q)) ||
        (r.building_name && r.building_name.toLowerCase().includes(q)) ||
        (r.location_floor && r.location_floor.toLowerCase().includes(q))
    );
});

const isRoomSelected = (roomId) => {
    return form.room_ids.includes(roomId);
};

const toggleRoomSelection = (roomId) => {
    const index = form.room_ids.indexOf(roomId);
    if (index > -1) {
        form.room_ids.splice(index, 1);
    } else {
        form.room_ids.push(roomId);
    }
};


// Modal Actions
const openAddModal = () => {
    isEditing.value = false;
    editingStaffId.value = null;
    roomSearchInsideModal.value = '';
    form.reset();
    form.clearErrors();
    form.is_active = true;
    form.room_ids = [];
    showModal.value = true;
};

const openEditModal = (staffMember) => {
    isEditing.value = true;
    editingStaffId.value = staffMember.id;
    roomSearchInsideModal.value = '';
    form.clearErrors();
    form.name = staffMember.name || '';
    form.position = staffMember.position || '';
    form.nip = staffMember.nip || '';
    form.phone_number = staffMember.phone_number || '';
    form.is_active = Boolean(staffMember.is_active);
    form.room_ids = staffMember.rooms ? staffMember.rooms.map(r => r.id) : [];
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

// Numeric Input Restrictors & Paste Sanitizers (Matching Daftar Pengguna)
const allowOnlyNumbers = (e) => {
    if (
        ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key) ||
        e.ctrlKey || e.metaKey || e.altKey
    ) {
        return;
    }
    if (!/^[0-9]$/.test(e.key)) {
        e.preventDefault();
    }
};

const blockNonNumericInput = (e) => {
    if (e.data && !/^[0-9]+$/.test(e.data)) {
        e.preventDefault();
    }
};

const handleNipInput = (e) => {
    form.clearErrors('nip');
    const cleaned = (e.target.value || '').replace(/\D/g, '').slice(0, 18);
    form.nip = cleaned;
    e.target.value = cleaned;
};

const handlePhoneInput = (e) => {
    form.clearErrors('phone_number');
    const cleaned = (e.target.value || '').replace(/\D/g, '').slice(0, 15);
    form.phone_number = cleaned;
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
    form.nip = combined;
    target.value = combined;
    form.clearErrors('nip');
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
    form.phone_number = combined;
    target.value = combined;
    form.clearErrors('phone_number');
};

const submitForm = () => {
    form.clearErrors();

    // 1. Nama Lengkap Staf (Wajib)
    if (!form.name || !form.name.trim()) {
        form.setError('name', 'Nama lengkap staf wajib diisi.');
        return;
    }

    // 2. Jabatan / Profesi (Wajib)
    if (!form.position || !form.position.trim()) {
        form.setError('position', 'Jabatan / Profesi staf wajib diisi.');
        return;
    }

    // 3. NIP (Wajib, tepat 18 karakter angka)
    if (!form.nip) {
        form.setError('nip', 'NIP wajib diisi.');
        return;
    }
    if (form.nip.length !== 18) {
        form.setError('nip', 'NIP harus terdiri dari 18 digit angka.');
        return;
    }

    // 4. Nomor HP (Opsional, jika diisi minimal 10 - 15 digit angka)
    if (form.phone_number && form.phone_number.trim()) {
        if (form.phone_number.trim().length < 10) {
            form.setError('phone_number', 'Nomor HP minimal 10 digit angka.');
            return;
        }
    }

    savePositionHistory(form.position);
    if (isEditing.value) {
        form.put(route('staff-management.update', editingStaffId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('staff-management.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

const toggleStaffStatus = (staffMember) => {
    router.patch(route('staff-management.toggle-status', staffMember.id), {}, {
        preserveScroll: true
    });
};

const openDeleteConfirmModal = (staffMember) => {
    selectedStaffForAction.value = staffMember;
    showDeleteConfirmModal.value = true;
};

const submitDeleteStaff = () => {
    if (!selectedStaffForAction.value) return;
    router.delete(route('staff-management.destroy', selectedStaffForAction.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmModal.value = false;
            selectedStaffForAction.value = null;
        }
    });
};

// Import Data Modal State
const showImportModal = ref(false);
const selectedFileName = ref('');
const selectedFileSize = ref('');
const fileInputRef = ref(null);
const isDragging = ref(false);
const importForm = useForm({
    file: null
});

const openImportModal = () => {
    importForm.reset();
    importForm.clearErrors();
    selectedFileName.value = '';
    selectedFileSize.value = '';
    showImportModal.value = true;
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
    importForm.clearErrors();
    selectedFileName.value = '';
    selectedFileSize.value = '';
};

const handleFileSelect = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        processSelectedFile(file);
    }
};

const handleFileDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) {
        processSelectedFile(file);
    }
};

const processSelectedFile = (file) => {
    importForm.clearErrors('file');
    const validExtensions = ['xlsx', 'xls', 'csv'];
    const ext = file.name.split('.').pop()?.toLowerCase();
    if (!validExtensions.includes(ext)) {
        importForm.setError('file', 'Format file tidak didukung. Harap pilih file .xlsx, .xls, atau .csv');
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        importForm.setError('file', 'Ukuran file melebihi batas maksimal 10 MB.');
        return;
    }
    importForm.file = file;
    selectedFileName.value = file.name;
    selectedFileSize.value = (file.size / 1024).toFixed(1) + ' KB';
};

const removeSelectedFile = () => {
    importForm.file = null;
    selectedFileName.value = '';
    selectedFileSize.value = '';
    importForm.clearErrors('file');
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const submitImport = () => {
    if (!importForm.file) {
        importForm.setError('file', 'Silakan pilih file Excel (.xlsx) atau CSV terlebih dahulu.');
        return;
    }

    importForm.post(route('staff-management.import'), {
        preserveScroll: true,
        onSuccess: () => {
            closeImportModal();
        }
    });
};

// Modal Scroll Lock & Keyboard Handling
const isAnyModalOpen = computed(() => {
    return showModal.value || showDeleteConfirmModal.value || showImportModal.value;
});

const closeAllModals = () => {
    closeModal();
    closeImportModal();
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
    loadSavedPositions();
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
    <Head title="Daftar Staf" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel (Exact Match with Daftar Pengguna) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Contact class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Daftar Staf
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Kelola direktori staf rumah sakit dan penempatan ruangan pelayanan.
                        </p>
                    </div>
                </div>

                <div class="w-full sm:w-auto flex flex-wrap items-center gap-2.5">
                    <button
                        type="button"
                        @click="openImportModal"
                        class="w-full sm:w-auto h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/60 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
                    >
                        <Upload class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        <span>Import Data</span>
                    </button>
                    <button
                        type="button"
                        @click="openAddModal"
                        class="w-full sm:w-auto h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer shadow-sm"
                    >
                        <UserPlus class="h-4 w-4" />
                        <span>Tambah Staf Baru</span>
                    </button>
                </div>
            </div>

            <!-- Summary KPI Stats Cards (Exact Match with Daftar Pengguna Grid) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Staf RS</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.total }}</div>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block">{{ stats.active }} Staf Aktif</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <Users class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Ruangan Terisi</span>
                        <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-tight">{{ stats.rooms_with_staff }}</div>
                        <span class="text-[11px] text-slate-400 block">Ruangan Berpenempatan</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                        <MapPin class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Variasi Jabatan</span>
                        <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 leading-tight">{{ stats.positions_count }}</div>
                        <span class="text-[11px] text-slate-400 block">Profesi & Posisi Staf</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-blue-50 dark:bg-blue-950/40">
                        <Briefcase class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Status Pelayanan</span>
                        <div class="text-3xl font-extrabold text-purple-600 dark:text-purple-400 leading-tight">{{ stats.active }}</div>
                        <span class="text-[11px] text-slate-400 block">Staf Siap Verifikasi</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-purple-50 dark:bg-purple-950/40">
                        <CheckCircle2 class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </div>

            <!-- Unified Table & Filter Container (Exact Match with Daftar Pengguna) -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                <!-- Filter & Search Toolbar -->
                <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    <div class="relative flex-1 max-w-full lg:max-w-xs xl:max-w-sm">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari nama staf, NIP, atau jabatan..."
                            class="w-full h-10 pl-10 pr-4 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 lg:flex lg:items-center gap-2.5">
                        <!-- Filter Ruangan -->
                        <select
                            v-model="selectedRoom"
                            class="h-10 pl-3.5 pr-9 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition max-w-full sm:max-w-[200px] truncate"
                        >
                            <option value="ALL">Semua Ruangan</option>
                            <option value="GLOBAL">Tanpa Ruangan</option>
                            <option v-for="r in rooms" :key="r.id" :value="r.id">
                                {{ r.name }} {{ r.building_name ? `(${r.building_name})` : '' }} {{ (r.is_active === false || r.is_active === 0) ? '[Nonaktif]' : '' }}
                            </option>
                        </select>

                        <!-- Filter Jabatan -->
                        <select
                            v-model="selectedPosition"
                            class="h-10 pl-3.5 pr-9 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition"
                        >
                            <option value="ALL">Semua Jabatan</option>
                            <option v-for="pos in positions" :key="pos" :value="pos">
                                {{ pos }}
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

                <!-- Table Content (Exact Table Structure from Daftar Pengguna) -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <tr>
                                <th class="px-6 py-4">Nama Staf</th>
                                <th class="px-6 py-4">NIP</th>
                                <th class="px-6 py-4">Nomor HP</th>
                                <th class="px-6 py-4 text-center">Jabatan / Profesi</th>
                                <th class="px-6 py-4">Penempatan Ruangan</th>
                                <th class="px-6 py-4 text-center">Poin KPI</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-200">
                            <tr 
                                v-for="staffMember in paginatedStaff" 
                                :key="staffMember.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors duration-150"
                            >
                                <!-- Name & Profile Icon -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center font-bold text-xs shrink-0 border border-emerald-100 dark:border-white/20">
                                            {{ (staffMember.name || 'S').charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-900 dark:text-white">{{ staffMember.name }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- NIP -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">
                                    {{ staffMember.nip ? String(staffMember.nip).replace(/\s+/g, '') : '-' }}
                                </td>

                                <!-- Phone Number -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">
                                    {{ (staffMember.phone_number && String(staffMember.phone_number).trim()) ? staffMember.phone_number : '-' }}
                                </td>

                                <!-- Jabatan / Role (Bold Text, matching Daftar Pengguna) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs font-bold text-slate-600 dark:text-slate-400">
                                    {{ staffMember.position }}
                                </td>

                                <!-- Penempatan Ruangan (Many-to-Many Badges) -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                                    <div v-if="staffMember.rooms && staffMember.rooms.length > 0" class="flex flex-wrap items-center gap-1.5 max-w-xs">
                                        <span 
                                            v-for="r in staffMember.rooms" 
                                            :key="r.id"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700/60"
                                        >
                                            <MapPin class="w-3 h-3 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                            {{ r.name }}
                                        </span>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 font-medium">
                                        -
                                    </span>
                                </td>

                                <!-- Poin KPI -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="[
                                            staffMember.total_points >= 100 
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                : staffMember.total_points >= 80
                                                ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
                                                : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                                        ]"
                                    >
                                        <Award class="w-3 h-3" />
                                        {{ staffMember.total_points }} Poin
                                    </span>
                                </td>

                                <!-- Status Badge Toggle Button (Identical to Daftar Pengguna) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button
                                        type="button"
                                        @click="toggleStaffStatus(staffMember)"
                                        :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition', staffMember.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 hover:bg-rose-100']"
                                        :title="staffMember.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                                    >
                                        <component :is="staffMember.is_active ? CheckCircle2 : XCircle" class="h-3 w-3" />
                                        <span>{{ staffMember.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </button>
                                </td>

                                <!-- Actions (Badge Styled Buttons, Identical to Daftar Pengguna) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(staffMember)"
                                            class="p-2 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150 cursor-pointer"
                                            title="Edit Data Staf & Penempatan"
                                        >
                                            <Edit class="h-3.5 w-3.5" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteConfirmModal(staffMember)"
                                            class="p-2 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150 cursor-pointer"
                                            title="Hapus Staf"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredStaff.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <Users class="h-8 w-8 mx-auto mb-2 opacity-40" />
                                    <p class="font-medium text-xs">Tidak ada data staf yang sesuai dengan filter.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer / Interactive Pagination (Exact Match with Daftar Pengguna) -->
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

        <!-- CREATE / EDIT MODAL (Pesu Peluh Style with Green Banner Header, Safe Area) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto sm:px-0 flex sm:items-center sm:justify-center min-h-screen">
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity select-none"></div>

                <div class="relative bg-white dark:bg-slate-900 w-full min-h-screen sm:min-h-0 sm:max-w-xl sm:rounded-2xl rounded-none border-0 shadow-2xl overflow-hidden transform transition-all flex flex-col z-10 sm:max-h-[90vh]">
                    <!-- Green Header with Icon -->
                    <div class="px-6 py-4 bg-emerald-600 dark:bg-emerald-700 text-white flex items-center gap-3 shrink-0">
                        <div class="p-2 rounded-xl bg-white/20 text-white shrink-0 flex items-center justify-center">
                            <Contact class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white leading-tight">
                                {{ isEditing ? 'Edit Data Staf RS' : 'Tambah Staf Baru' }}
                            </h3>
                            <p class="text-xs text-emerald-100 mt-0.5">
                                {{ isEditing ? 'Perbarui data staf dan penugasan ruangan rumah sakit' : 'Lengkapi data identitas staf dan tentukan ruangan penugasan' }}
                            </p>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitForm" class="flex flex-col flex-1 sm:flex-initial overflow-hidden">
                        <div class="p-6 space-y-4 overflow-y-auto flex-1 sm:flex-initial sm:max-h-[calc(90vh-140px)]">
                            <!-- Nama Lengkap Staf -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Nama Lengkap Staf *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Misal: Ns. Siti Rahmah, S.Kep atau Budi Santoso"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.name }}</div>
                            </div>

                            <!-- Jabatan / Profesi -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Jabatan / Profesi *
                                </label>
                                <input
                                    v-model="form.position"
                                    type="text"
                                    placeholder="Masukkan jabatan atau profesi staf"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    required
                                />
                                <div v-if="form.errors.position" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.position }}</div>

                                <!-- Saved Position History Pills from localStorage (Hanya muncul jika ada riwayat input) -->
                                <div v-if="savedPositions.length > 0" class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <span class="text-[10px] text-slate-400 mr-1">Tersimpan:</span>
                                    <button 
                                        type="button" 
                                        v-for="preset in savedPositions" 
                                        :key="preset" 
                                        @click="form.position = preset" 
                                        class="px-2 py-0.5 text-[11px] rounded-md bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 dark:bg-slate-800 dark:hover:bg-emerald-950/50 dark:hover:text-emerald-300 text-slate-600 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700 transition-colors"
                                    >
                                        {{ preset }}
                                    </button>
                                </div>
                            </div>

                            <!-- NIP & No HP -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                        NIP *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="18"
                                        :value="form.nip"
                                        @keydown="allowOnlyNumbers"
                                        @beforeinput="blockNonNumericInput"
                                        @input="handleNipInput"
                                        @paste="handleNipPaste"
                                        placeholder="198207102008011003"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                    <div v-if="form.errors.nip" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.nip }}</div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                        Nomor HP <span class="text-slate-400 font-normal text-[11px]">(opsional)</span>
                                    </label>
                                    <input
                                        type="tel"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="15"
                                        :value="form.phone_number"
                                        @keydown="allowOnlyNumbers"
                                        @beforeinput="blockNonNumericInput"
                                        @input="handlePhoneInput"
                                        @paste="handlePhonePaste"
                                        placeholder="081234567891"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                    />
                                    <div v-if="form.errors.phone_number" class="text-rose-500 text-[11px] mt-1 font-medium">{{ form.errors.phone_number }}</div>
                                </div>
                            </div>

                            <!-- Penempatan Ruangan (Many-to-Many Multi Select Grid) -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Penempatan Ruangan <span class="text-emerald-600 dark:text-emerald-400">({{ form.room_ids.length }} Terpilih)</span>
                                </label>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">
                                    Staf dapat ditugaskan di 1 atau beberapa ruangan.
                                </p>

                                <!-- Room Search inside modal -->
                                <div class="relative mb-2">
                                    <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                                    <input 
                                        v-model="roomSearchInsideModal"
                                        type="text"
                                        placeholder="Cari ruangan di bawah..."
                                        class="w-full pl-8 pr-3 py-1.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500"
                                    />
                                </div>

                                <!-- Checkbox box -->
                                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 max-h-48 overflow-y-auto bg-slate-50/50 dark:bg-slate-800/40 space-y-1">
                                    <div 
                                        v-for="r in filteredRoomsForModal" 
                                        :key="r.id"
                                        @click="toggleRoomSelection(r.id)"
                                        class="flex items-center justify-between p-2 rounded-lg cursor-pointer transition-colors text-xs"
                                        :class="isRoomSelected(r.id) 
                                            ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-800 font-medium' 
                                            : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                    >
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div 
                                                class="w-4 h-4 rounded border flex items-center justify-center transition-colors shrink-0"
                                                :class="isRoomSelected(r.id) 
                                                    ? 'bg-emerald-600 border-emerald-600 text-white' 
                                                    : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800'"
                                            >
                                                <Check v-if="isRoomSelected(r.id)" class="w-3 h-3 stroke-[3]" />
                                            </div>
                                            <span class="truncate">{{ r.name }}</span>
                                            <span v-if="r.is_active === false || r.is_active === 0" class="text-[10px] px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400 font-semibold shrink-0">
                                                Nonaktif
                                            </span>
                                        </div>
                                        <span v-if="r.building_name" class="text-[11px] text-slate-400 shrink-0 ml-2">
                                            {{ r.building_name }}
                                        </span>
                                    </div>

                                    <div v-if="filteredRoomsForModal.length === 0" class="py-4 text-center text-xs text-slate-400">
                                        Tidak ada ruangan yang cocok dengan "{{ roomSearchInsideModal }}".
                                    </div>
                                </div>
                            </div>

                            <!-- Status Aktif Switch -->
                            <div class="flex items-center gap-2 pt-1">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    v-model="form.is_active"
                                    class="rounded accent-emerald-600 cursor-pointer h-4 w-4"
                                />
                                <label for="is_active" class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                    Staf Aktif (Muncul dalam daftar pilihan verifikasi laporan Kasi)
                                </label>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 pt-4 pb-10 sm:pb-4 bg-slate-50/50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 shrink-0 mt-auto sm:mt-0">
                            <button
                                type="button"
                                @click="closeModal"
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

        <!-- DELETE CONFIRMATION MODAL (Matching UserManagement & RoomManagement) -->
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
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Hapus Data Staf?</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Apakah Anda yakin ingin menghapus staf <strong class="text-slate-700 dark:text-slate-200">{{ selectedStaffForAction?.name }}</strong>? Tindakan ini akan melepas penempatan ruangan staf.
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
                            @click="submitDeleteStaff"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white text-xs font-semibold transition cursor-pointer text-center justify-center"
                        >
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- IMPORT DATA STAF MODAL -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showImportModal" class="fixed inset-0 z-50 overflow-y-auto sm:px-0 flex sm:items-center sm:justify-center min-h-screen">
                <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 transition-opacity select-none"></div>

                <div class="relative bg-white dark:bg-slate-900 w-full min-h-screen sm:min-h-0 sm:max-w-xl sm:rounded-2xl rounded-none border-0 shadow-2xl overflow-hidden transform transition-all flex flex-col z-10 sm:max-h-[90vh]">
                    <!-- Header -->
                    <div class="px-6 py-4 bg-emerald-600 dark:bg-emerald-700 text-white flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl bg-white/20 text-white shrink-0 flex items-center justify-center">
                                <FileSpreadsheet class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white leading-tight">
                                    Import Data Staf
                                </h3>
                                <p class="text-xs text-emerald-100 mt-0.5">
                                    Unggah file Excel atau CSV direktori staf.
                                </p>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            @click="closeImportModal" 
                            class="p-1.5 rounded-lg text-emerald-100 hover:text-white hover:bg-white/10 transition cursor-pointer"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="p-6 space-y-5 overflow-y-auto flex-1">
                        <!-- Step 1: Download Template -->
                        <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 p-4 space-y-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-600 text-white text-[11px] font-bold">1</span>
                                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100">
                                        Unduh Template Format Data
                                    </h4>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 pl-7 leading-relaxed">
                                    Gunakan template resmi agar format kolom NIP (18 digit) tidak terpotong atau terformat ilmiah oleh spreadsheet.
                                </p>
                            </div>

                            <div class="pl-7 flex flex-wrap items-center gap-2 pt-1">
                                <a
                                    :href="route('staff-management.template', { format: 'xlsx' })"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-sm transition"
                                >
                                    <Download class="w-3.5 h-3.5" />
                                    <span>Download Template Excel (.xlsx)</span>
                                    <span class="ml-1 text-[10px] bg-emerald-800/60 text-emerald-100 px-1.5 py-0.5 rounded font-mono">Rekomendasi</span>
                                </a>

                                <a
                                    :href="route('staff-management.template', { format: 'csv' })"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 text-xs font-semibold transition"
                                >
                                    <Download class="w-3.5 h-3.5" />
                                    <span>Download Template CSV (.csv)</span>
                                </a>
                            </div>

                            <!-- Column Guide -->
                            <div class="pl-7 pt-2">
                                <div class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                                    Format Kolom Template:
                                </div>
                                <div class="space-y-1 text-[11px] text-slate-600 dark:text-slate-300">
                                    <div class="flex items-start gap-1.5">
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400 shrink-0">• Nama Staf:</span>
                                        <span>Wajib diisi</span>
                                    </div>
                                    <div class="flex items-start gap-1.5">
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400 shrink-0">• NIP:</span>
                                        <span>Wajib 18 digit angka</span>
                                    </div>
                                    <div class="flex items-start gap-1.5">
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400 shrink-0">• Jabatan:</span>
                                        <span>Wajib (misal: Perawat Pelaksana)</span>
                                    </div>
                                    <div class="flex items-start gap-1.5">
                                        <span class="font-bold text-slate-500 dark:text-slate-400 shrink-0">• Nomor HP:</span>
                                        <span>Opsional (10–15 digit)</span>
                                    </div>
                                    <div class="flex items-start gap-1.5">
                                        <span class="font-bold text-slate-500 dark:text-slate-400 shrink-0">• Ruangan:</span>
                                        <span>Opsional, pisahkan koma jika multi-ruangan (contoh: "IGD, ICU")</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Upload File -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-600 text-white text-[11px] font-bold">2</span>
                                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100">
                                    Unggah File yang Telah Diisi
                                </h4>
                            </div>

                            <!-- Drag and Drop Area -->
                            <div
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleFileDrop"
                                @click="fileInputRef?.click()"
                                :class="[
                                    'border-2 border-dashed rounded-2xl p-6 text-center transition cursor-pointer flex flex-col items-center justify-center',
                                    isDragging 
                                        ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/20' 
                                        : 'border-slate-300 dark:border-slate-700 hover:border-emerald-500/70 hover:bg-slate-50/50 dark:hover:bg-slate-800/40 bg-white dark:bg-slate-900'
                                ]"
                            >
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    class="hidden"
                                    @change="handleFileSelect"
                                />

                                <div v-if="!selectedFileName" class="space-y-2 flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                        <Upload class="w-6 h-6" />
                                    </div>
                                    <div class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                        Klik untuk memilih file <span class="font-normal text-slate-500 dark:text-slate-400">atau seret file ke sini</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">
                                        Format file: .xlsx, .xls, .csv (Maksimal 10 MB)
                                    </p>
                                </div>

                                <div v-else class="w-full flex items-center justify-between gap-3 bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 rounded-xl p-3" @click.stop>
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0">
                                            <FileSpreadsheet class="w-5 h-5" />
                                        </div>
                                        <div class="min-w-0 text-left">
                                            <div class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">
                                                {{ selectedFileName }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                                Ukuran: {{ selectedFileSize }} • Siap diimpor
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeSelectedFile"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-white dark:hover:bg-slate-800 transition shrink-0 cursor-pointer"
                                        title="Hapus file"
                                    >
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <div v-if="importForm.errors.file" class="text-rose-500 text-[11px] font-medium flex items-center gap-1.5">
                                <AlertTriangle class="w-3.5 h-3.5 shrink-0" />
                                <span>{{ importForm.errors.file }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 pt-4 pb-10 sm:pb-4 bg-slate-50/50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 shrink-0 mt-auto sm:mt-0">
                        <button
                            type="button"
                            @click="closeImportModal"
                            class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-center justify-center"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitImport"
                            :disabled="!importForm.file || importForm.processing"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white text-xs font-semibold flex items-center justify-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer shadow-sm"
                        >
                            <Loader2 v-if="importForm.processing" class="w-4 h-4 animate-spin" />
                            <Upload v-else class="w-4 h-4" />
                            <span>{{ importForm.processing ? 'Memproses Import...' : 'Mulai Import Data' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
