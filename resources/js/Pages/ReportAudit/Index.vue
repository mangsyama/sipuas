<script setup>
import { ref, computed, watch, onMounted, onUnmounted, getCurrentInstance } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ShieldCheck,
    Search,
    CheckCircle2,
    Clock,
    AlertCircle,
    User,
    SlidersHorizontal,
    X,
    Check,
    TrendingUp,
    TrendingDown,
    Building2,
    Calendar,
    MessageSquare,
    AlertTriangle,
    Info,
    RotateCcw,
    Users,
    UserPlus,
    XCircle,
    FileText
} from '@lucide/vue';

const { proxy } = getCurrentInstance() || {};

const triggerStandardAlert = (options) => {
    if (proxy?.$swal) {
        proxy.$swal(options);
    } else if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('trigger-custom-alert', {
            detail: {
                options: {
                    title: options.title || '',
                    text: options.text || '',
                    icon: options.icon || 'warning',
                    confirmText: options.confirmButtonText || 'Mengerti',
                    cancelText: options.cancelButtonText || ''
                }
            }
        }));
    }
};

const props = defineProps({
    reports: {
        type: Object,
        default: () => ({ data: [], links: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 })
    },
    rooms: {
        type: Array,
        default: () => []
    },
    staffList: {
        type: Array,
        default: () => []
    },
    kpiCategories: {
        type: Object,
        default: () => ({})
    },
    stats: {
        type: Object,
        default: () => ({
            total_reports: 0,
            verified_reports: 0,
            praise_actions: 0,
            complaint_actions: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            room_id: 'ALL',
            status: 'ALL',
            action_type: 'ALL',
            per_page: 10
        })
    }
});

// Filter States
const searchQuery = ref(props.filters.search || '');
const selectedRoom = ref(props.filters.room_id || 'ALL');
const selectedStatus = ref(props.filters.status || 'ALL');
const selectedActionType = ref(props.filters.action_type || 'ALL');

let searchDebounceTimer = null;

const applyFilters = () => {
    router.get(
        route('admin.report-audit.index'),
        {
            search: searchQuery.value || undefined,
            room_id: selectedRoom.value !== 'ALL' ? selectedRoom.value : undefined,
            status: selectedStatus.value !== 'ALL' ? selectedStatus.value : undefined,
            action_type: selectedActionType.value !== 'ALL' ? selectedActionType.value : undefined,
            per_page: props.filters.per_page || 10
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true
        }
    );
};

const handleSearchInput = () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        applyFilters();
    }, 400);
};

const handleDropdownFilterChange = () => {
    applyFilters();
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedRoom.value = 'ALL';
    selectedStatus.value = 'ALL';
    selectedActionType.value = 'ALL';
    applyFilters();
};

// Modal Koreksi Audit State
const showAuditModal = ref(false);
const selectedReport = ref(null);
const initialReportStaffList = ref([]);
const previousActionType = ref('NETRAL');
const previousPoints = ref(0);
const previousKpiCategory = ref('');
const previousSupervisorNotes = ref('');

// Form Koreksi Audit (Mendukung Multi-Staf, Ringkas & Simpel)
const auditForm = useForm({
    action_type: 'PENAMBAHAN',
    points: 10,
    kpi_category: 'KERAMAHAN',
    staff_ids: []
});

// Dropdown pilihan poin standar
const standardPointOptions = [
    { label: '5 Poin (Ringan / Dasar)', value: 5 },
    { label: '10 Poin (Standar SOP)', value: 10 },
    { label: '15 Poin (Signifikan)', value: 15 },
    { label: '20 Poin (Sangat Berpengaruh)', value: 20 },
    { label: '25 Poin (Luar Biasa / Berat)', value: 25 },
    { label: '50 Poin (Maksimal Eksekutif)', value: 50 },
    { label: 'Custom (Ketik Bebas)', value: 'CUSTOM' }
];

const pointPreset = ref(10);
const customPointInput = ref(10);
const staffSelectDropdown = ref('');

const handlePointPresetChange = () => {
    if (pointPreset.value === 'CUSTOM') {
        auditForm.points = Number(customPointInput.value) || 0;
    } else {
        auditForm.points = Number(pointPreset.value);
        customPointInput.value = Number(pointPreset.value);
    }
};

const handleCustomPointInput = (e) => {
    const val = parseInt(e.target.value.replace(/\D/g, ''), 10) || 0;
    customPointInput.value = Math.min(100, Math.max(0, val));
    auditForm.points = customPointInput.value;
};

// Pengelolaan Multi Staf Terpilih
const selectedStaffObjects = computed(() => {
    if (!auditForm.staff_ids || auditForm.staff_ids.length === 0) return [];
    const idSet = new Set(auditForm.staff_ids.map(Number));
    return props.staffList.filter(s => idSet.has(Number(s.id)));
});

const availableStaffToAdd = computed(() => {
    const selectedSet = new Set((auditForm.staff_ids || []).map(Number));
    return props.staffList.filter(s => !selectedSet.has(Number(s.id)));
});

const addStaffToSelection = (staffId) => {
    if (!staffId) return;
    const numId = Number(staffId);
    if (!auditForm.staff_ids.includes(numId)) {
        auditForm.staff_ids.push(numId);
    }
    staffSelectDropdown.value = '';
};

const removeStaffFromSelection = (staffId) => {
    const numId = Number(staffId);
    auditForm.staff_ids = auditForm.staff_ids.filter(id => Number(id) !== numId);
};

const selectAllInitialStaff = () => {
    const ids = initialReportStaffList.value.map(s => Number(s.id)).filter(Boolean);
    auditForm.staff_ids = Array.from(new Set([...auditForm.staff_ids, ...ids]));
};

const clearStaffSelection = () => {
    auditForm.staff_ids = [];
};

// Kalkulasi Preview Penyesuaian Saldo Poin Staf
const previewCalculation = computed(() => {
    if (!selectedReport.value) return null;

    const rawNewPoints = Math.abs(Number(auditForm.points) || 0);
    const newSigned = auditForm.action_type === 'PENAMBAHAN' 
        ? rawNewPoints 
        : (auditForm.action_type === 'PEMOTONGAN' ? -rawNewPoints : 0);

    const oldSigned = previousPoints.value;
    const delta = newSigned - oldSigned;
    const staffCount = auditForm.staff_ids.length;

    return {
        oldSigned,
        newSigned,
        delta,
        staffCount,
        isPositive: delta > 0,
        isNegative: delta < 0,
        isNeutral: delta === 0
    };
});

// Buka Modal Audit untuk Laporan Terpilih
const openAuditModal = (report) => {
    selectedReport.value = report;
    auditForm.clearErrors();

    // 1. Ekstrak data verifikasi awal Kasi
    const reportStaffArray = report.report_staff || [];
    const firstRs = reportStaffArray.length > 0 ? reportStaffArray[0] : null;

    const oldType = firstRs?.action_type || (report.status === 'VERIFIED' ? 'PENAMBAHAN' : 'NETRAL');
    const oldPts = firstRs ? Number(firstRs.points) : 0;
    const oldCat = firstRs?.kpi_category || report.verified_kpi_category || 'KERAMAHAN';

    previousActionType.value = oldType;
    previousPoints.value = oldPts;
    previousKpiCategory.value = oldCat;
    previousSupervisorNotes.value = report.supervisor_notes || '';

    // 2. Kumpulkan staf yang sudah terkait pada laporan ini
    const existingStaffList = [];
    const existingStaffIds = [];

    reportStaffArray.forEach(rs => {
        const staffObj = rs.hospital_staff || (rs.staff_id ? props.staffList.find(s => Number(s.id) === Number(rs.staff_id)) : null);
        if (staffObj) {
            existingStaffList.push(staffObj);
            existingStaffIds.push(Number(staffObj.id));
        } else if (rs.user) {
            const matchedByNip = rs.user.nip ? props.staffList.find(s => s.nip === rs.user.nip) : null;
            if (matchedByNip) {
                existingStaffList.push(matchedByNip);
                existingStaffIds.push(Number(matchedByNip.id));
            }
        }
    });

    initialReportStaffList.value = existingStaffList;

    // 3. Set nilai form
    auditForm.action_type = oldType;
    const absPts = Math.abs(oldPts);
    auditForm.points = absPts > 0 ? absPts : 10;
    auditForm.kpi_category = oldCat;
    auditForm.staff_ids = existingStaffIds.length > 0 ? [...existingStaffIds] : [];

    // 4. Sinkronkan preset dropdown poin
    const matchedPreset = standardPointOptions.find(opt => opt.value === auditForm.points);
    if (matchedPreset) {
        pointPreset.value = auditForm.points;
        customPointInput.value = auditForm.points;
    } else {
        pointPreset.value = 'CUSTOM';
        customPointInput.value = auditForm.points;
    }

    staffSelectDropdown.value = '';
    showAuditModal.value = true;
};

const closeAuditModal = () => {
    showAuditModal.value = false;
    selectedReport.value = null;
    initialReportStaffList.value = [];
    auditForm.reset();
};

const submitAuditForm = () => {
    if (!selectedReport.value) return;

    if ((auditForm.action_type === 'PENAMBAHAN' || auditForm.action_type === 'PEMOTONGAN') && auditForm.staff_ids.length === 0) {
        triggerStandardAlert({
            icon: 'warning',
            title: 'Pilih Staf Terkait',
            text: 'Tindakan penambahan atau pemotongan poin mewajibkan setidaknya 1 staf dipilih.',
            confirmButtonText: 'Mengerti'
        });
        return;
    }

    auditForm.put(route('admin.report-audit.update', selectedReport.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeAuditModal();
        },
        onError: () => {
            triggerStandardAlert({
                icon: 'error',
                title: 'Gagal Menyimpan Koreksi',
                text: 'Silakan periksa kembali isian formulir koreksi audit Anda.',
                confirmButtonText: 'Tutup'
            });
        }
    });
};

// Keyboard modal ESC
const handleKeyDown = (e) => {
    if (e.key === 'Escape' && showAuditModal.value) {
        closeAuditModal();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});

// Helper Format Tanggal
const formatDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const d = new Date(dateString);
        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch {
        return dateString;
    }
};

// Helper Sentimen Badge
const getSentimentBadge = (sentiment) => {
    const s = (sentiment || '').toUpperCase();
    if (s === 'POSITIVE' || s === 'POSITIF') {
        return { label: 'Positif', class: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' };
    }
    if (s === 'NEGATIVE' || s === 'NEGATIF') {
        return { label: 'Negatif', class: 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border-rose-200 dark:border-rose-800' };
    }
    return { label: 'Netral', class: 'bg-slate-50 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700' };
};
</script>

<template>
    <Head title="Audit Laporan Masyarakat" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel Serasi Master Data SIPUAS -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <ShieldCheck class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Audit Laporan Masyarakat
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Audit hasil verifikasi aduan masyarakat, evaluasi kesesuaian tindakan, dan koreksi penyesuaian poin KPI staf.
                        </p>
                    </div>
                </div>

                <div class="w-full sm:w-auto flex items-center gap-2">
                    <button
                        type="button"
                        @click="resetFilters"
                        class="w-full sm:w-auto h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/60 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
                        title="Reset Semua Filter"
                    >
                        <RotateCcw class="h-4 w-4 text-slate-500" />
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            <!-- KPI Summary Cards (Grid 4 Kolom Serasi) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Laporan</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.total_reports }}</div>
                        <span class="text-[11px] text-slate-400 block">Aspirasi & Aduan Terdata</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                        <MessageSquare class="h-6 w-6 text-emerald-600 dark:text-white" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Diverifikasi</span>
                        <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-tight">{{ stats.verified_reports }}</div>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block">Telah Ditinjau Kasi/Unit</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                        <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Poin Apresiasi (+)</span>
                        <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 leading-tight">{{ stats.praise_actions }}</div>
                        <span class="text-[11px] text-slate-400 block">Tindakan Penambahan Poin</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-blue-50 dark:bg-blue-950/40">
                        <TrendingUp class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Poin Evaluasi (-)</span>
                        <div class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 leading-tight">{{ stats.complaint_actions }}</div>
                        <span class="text-[11px] text-slate-400 block">Tindakan Pemotongan Poin</span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-rose-50 dark:bg-rose-950/40">
                        <TrendingDown class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                    </div>
                </div>
            </div>

            <!-- Unified Table & Filter Container -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                <!-- Filter & Search Toolbar (Dropdown Standar, Simpel & Clean) -->
                <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1 max-w-full lg:max-w-xs xl:max-w-sm">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            @input="handleSearchInput"
                            placeholder="Cari tiket, pelapor, isi aduan, atau staf..."
                            class="w-full h-10 pl-10 pr-4 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition"
                        />
                    </div>

                    <!-- Dropdown Filters Standar -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 lg:flex lg:items-center gap-2.5">
                        <!-- Dropdown Standar Ruangan -->
                        <select
                            v-model="selectedRoom"
                            @change="handleDropdownFilterChange"
                            class="h-10 pl-3.5 pr-8 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition max-w-full sm:max-w-[190px] truncate"
                        >
                            <option value="ALL">Semua Ruangan</option>
                            <option value="GLOBAL">Pelayanan Umum (Global)</option>
                            <option v-for="r in rooms" :key="r.id" :value="r.id">
                                {{ r.name }}
                            </option>
                        </select>

                        <!-- Dropdown Standar Status Verifikasi -->
                        <select
                            v-model="selectedStatus"
                            @change="handleDropdownFilterChange"
                            class="h-10 pl-3.5 pr-8 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition max-w-full sm:max-w-[170px]"
                        >
                            <option value="ALL">Semua Status</option>
                            <option value="VERIFIED">Sudah Diverifikasi</option>
                            <option value="PENDING">Menunggu Verifikasi</option>
                            <option value="REJECTED">Ditolak</option>
                        </select>

                        <!-- Dropdown Standar Tindakan -->
                        <select
                            v-model="selectedActionType"
                            @change="handleDropdownFilterChange"
                            class="h-10 pl-3.5 pr-8 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition max-w-full sm:max-w-[180px]"
                        >
                            <option value="ALL">Semua Tindakan</option>
                            <option value="PENAMBAHAN">Apresiasi (+ Poin)</option>
                            <option value="PEMOTONGAN">Evaluasi (- Poin)</option>
                            <option value="NETRAL">Netral (0 Poin)</option>
                            <option value="DIBATALKAN">Dibatalkan</option>
                        </select>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-slate-800/30">
                                <th class="py-3.5 px-4 sm:px-6">Tiket & Tanggal</th>
                                <th class="py-3.5 px-4">Ruangan & Pelapor</th>
                                <th class="py-3.5 px-4">Isi Aduan & AI</th>
                                <th class="py-3.5 px-4">Verifikator Kasi & Catatan</th>
                                <th class="py-3.5 px-4">Staf & Penilaian Poin</th>
                                <th class="py-3.5 px-4 sm:px-6 text-right whitespace-nowrap w-px">Aksi Audit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <tr
                                v-for="report in reports.data"
                                :key="report.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition group"
                            >
                                <!-- Tiket & Tanggal (Non-Monospace, Sans Standar Bersih) -->
                                <td class="py-4 px-4 sm:px-6 align-top">
                                    <div class="space-y-1">
                                        <div class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                            #{{ report.ticket_number }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1 font-medium">
                                            <Calendar class="h-3 w-3" />
                                            <span>{{ formatDate(report.created_at) }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Ruangan & Pelapor -->
                                <td class="py-4 px-4 align-top">
                                    <div class="space-y-1">
                                        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                            <Building2 class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
                                            <span>{{ report.room ? report.room.name : 'Pelayanan Umum (Global)' }}</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                            <User class="h-3 w-3 text-slate-400" />
                                            <span>{{ report.reporter_name || 'Anonim / Warga' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Isi Aduan & AI -->
                                <td class="py-4 px-4 align-top max-w-xs sm:max-w-sm">
                                    <div class="space-y-1.5">
                                        <p class="text-slate-700 dark:text-slate-300 line-clamp-2 leading-relaxed">
                                            {{ report.isi_laporan }}
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <span
                                                :class="[
                                                    'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold border',
                                                    getSentimentBadge(report.ai_sentiment).class
                                                ]"
                                            >
                                                Sentimen {{ getSentimentBadge(report.ai_sentiment).label }}
                                            </span>
                                            <span v-if="report.supervisor_notes && report.supervisor_notes.includes('[AUDIT OLEH')" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                                Telah Diaudit
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Verifikator Kasi, Tingkat Keparahan, & Catatan -->
                                <td class="py-4 px-4 align-top max-w-xs">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2">
                                            <div v-if="report.status === 'VERIFIED'" class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold text-[11px]">
                                                <CheckCircle2 class="h-3.5 w-3.5" />
                                                <span>Diverifikasi</span>
                                            </div>
                                            <div v-else-if="report.status === 'REJECTED'" class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400 font-semibold text-[11px]">
                                                <AlertCircle class="h-3.5 w-3.5" />
                                                <span>Ditolak</span>
                                            </div>
                                            <div v-else class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400 font-semibold text-[11px]">
                                                <Clock class="h-3.5 w-3.5" />
                                                <span>Menunggu</span>
                                            </div>
                                        </div>

                                        <div class="text-[11px] text-slate-700 dark:text-slate-300 font-medium">
                                            {{ report.verifier ? report.verifier.name : 'Kasi Pelayanan' }}
                                            <span class="text-[10px] text-slate-400 block">{{ report.verified_at ? formatDate(report.verified_at) : 'Belum ditinjau' }}</span>
                                        </div>

                                        <!-- Cuplikan Catatan Kasi Awal -->
                                        <div v-if="report.supervisor_notes" class="text-[11px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/60 p-2 rounded-lg border border-slate-100 dark:border-slate-800 line-clamp-2 italic">
                                            "{{ report.supervisor_notes.split('\n\n[AUDIT OLEH')[0] }}"
                                        </div>
                                    </div>
                                </td>

                                <!-- Staf Terkait (Mendukung Multi-Staf) & Penilaian Poin -->
                                <td class="py-4 px-4 align-top">
                                    <div v-if="report.report_staff && report.report_staff.length > 0" class="space-y-2">
                                        <div v-for="rs in report.report_staff" :key="rs.id" class="space-y-1 pb-1 border-b border-slate-100 dark:border-slate-800 last:border-0 last:pb-0">
                                            <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                                <User class="h-3 w-3 text-slate-400" />
                                                <span>{{ rs.hospital_staff ? rs.hospital_staff.name : (rs.user ? rs.user.name : 'Staf Ditugaskan') }}</span>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <!-- Badge Poin -->
                                                <span
                                                    v-if="rs.action_type === 'PENAMBAHAN'"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
                                                >
                                                    <TrendingUp class="h-3 w-3" />
                                                    +{{ Math.abs(rs.points) }} Poin
                                                </span>
                                                <span
                                                    v-else-if="rs.action_type === 'PEMOTONGAN'"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300"
                                                >
                                                    <TrendingDown class="h-3 w-3" />
                                                    -{{ Math.abs(rs.points) }} Poin
                                                </span>
                                                <span
                                                    v-else-if="rs.action_type === 'DIBATALKAN'"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                                                >
                                                    Dibatalkan
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                                                >
                                                    Netral (0 Poin)
                                                </span>

                                                <span v-if="rs.kpi_category" class="text-[10px] text-slate-400 truncate max-w-[120px]">
                                                    {{ kpiCategories[rs.kpi_category] || rs.kpi_category }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-slate-400 text-[11px] italic">
                                        Belum ada staf yang dinilai
                                    </div>
                                </td>

                                <!-- Aksi Audit (Tidak Boleh Wrap) -->
                                <td class="py-4 px-4 sm:px-6 align-top text-right whitespace-nowrap w-px">
                                    <button
                                        type="button"
                                        @click="openAuditModal(report)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 transition cursor-pointer whitespace-nowrap shrink-0"
                                    >
                                        <SlidersHorizontal class="h-3.5 w-3.5 shrink-0" />
                                        <span class="whitespace-nowrap">Audit Nilai</span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!reports.data || reports.data.length === 0">
                                <td colspan="6" class="py-12 px-6 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <ShieldCheck class="h-8 w-8 text-slate-300 dark:text-slate-600" />
                                        <p class="text-sm font-semibold">Tidak ada laporan yang sesuai dengan filter.</p>
                                        <button
                                            type="button"
                                            @click="resetFilters"
                                            class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-bold"
                                        >
                                            Reset Filter Pencarian
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Bar -->
                <div
                    v-if="reports.links && reports.links.length > 3"
                    class="p-4 sm:p-5 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400"
                >
                    <div>
                        Menampilkan <span class="font-bold text-slate-800 dark:text-slate-200">{{ reports.from || 0 }}</span> - 
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ reports.to || 0 }}</span> dari 
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ reports.total || 0 }}</span> laporan
                    </div>

                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in reports.links" :key="i">
                            <span
                                v-if="!link.url"
                                class="px-3 py-1.5 rounded-lg text-slate-300 dark:text-slate-600 select-none"
                                v-html="link.label"
                            />
                            <button
                                v-else
                                type="button"
                                @click="router.visit(link.url, { preserveScroll: true, preserveState: true })"
                                :class="[
                                    'px-3 py-1.5 rounded-lg font-bold transition cursor-pointer',
                                    link.active
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Audit & Koreksi Penilaian (Mendukung Multi-Staf, Lebar Maksimal 4XL, Dropdown Standar Bebas Overlap) -->
        <div
            v-if="showAuditModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            @click.self="closeAuditModal"
        >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-4xl w-full shadow-2xl overflow-hidden animate-spa-fade-in my-8">
                <!-- Header Modal -->
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight">
                                Audit & Koreksi Penilaian Verifikasi
                            </h3>
                            <p class="text-[11px] text-slate-400">
                                Koreksi nilai poin staf, sesuaikan multi-staf, dan perbarui saldo KPI secara otomatis.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeAuditModal"
                        class="h-8 w-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Body Modal -->
                <form @submit.prevent="submitAuditForm" class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
                    <!-- Ringkasan Lengkap: Tiket, Aduan, Catatan Verifikasi Kasi Awal & Tingkat Keparahan -->
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/80 space-y-3 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-slate-900 dark:text-white">
                                Tiket #{{ selectedReport?.ticket_number }}
                            </span>
                            <span class="text-slate-500 font-medium text-[11px] flex items-center gap-1">
                                <Building2 class="h-3.5 w-3.5 text-emerald-600" />
                                {{ selectedReport?.room ? selectedReport.room.name : 'Pelayanan Umum' }}
                            </span>
                        </div>

                        <!-- Isi Laporan Aduan Masyarakat -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Isi Laporan / Aduan Pasien:</span>
                            <p class="text-slate-700 dark:text-slate-300 italic leading-relaxed bg-white dark:bg-slate-900/60 p-2.5 rounded-lg border border-slate-200/60 dark:border-slate-700/60">
                                "{{ selectedReport?.isi_laporan }}"
                            </p>
                        </div>

                        <!-- Catatan Verifikasi Kasi Sebelumnya (Tampil Lengkap) -->
                        <div class="space-y-1">
                            <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                                <MessageSquare class="h-3.5 w-3.5" />
                                <span>Catatan Verifikasi Kasi Awal:</span>
                            </span>
                            <div class="p-2.5 rounded-lg bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 text-amber-950 dark:text-amber-200 leading-relaxed whitespace-pre-line text-[11px]">
                                {{ previousSupervisorNotes || 'Tidak ada catatan verifikasi khusus dari Kasi.' }}
                            </div>
                        </div>

                        <!-- Ringkasan Nilai Awal -->
                        <div class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between text-[11px]">
                            <div>
                                <span class="text-slate-500">Nilai Saat Ini:</span>
                                <span class="font-bold ml-1" :class="previousPoints > 0 ? 'text-emerald-600' : (previousPoints < 0 ? 'text-rose-600' : 'text-slate-500')">
                                    {{ previousPoints > 0 ? `+${previousPoints}` : previousPoints }} Poin ({{ previousActionType }})
                                </span>
                            </div>
                            <div v-if="previousKpiCategory">
                                <span class="text-slate-500">Kategori Saat Ini:</span>
                                <span class="font-bold ml-1 text-slate-700 dark:text-slate-300">
                                    {{ kpiCategories[previousKpiCategory] || previousKpiCategory }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Pemilihan Multi Nama Staf (Dinamis, Fleksibel, Dropdown Standar) -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Staf yang Dinilai / Terkait ({{ auditForm.staff_ids.length }} Staf Dipilih) <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="initialReportStaffList.length > 0"
                                    type="button"
                                    @click="selectAllInitialStaff"
                                    class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer"
                                >
                                    Pilih Semua Staf Laporan Ini
                                </button>
                                <span v-if="initialReportStaffList.length > 0 && auditForm.staff_ids.length > 0" class="text-slate-300">|</span>
                                <button
                                    v-if="auditForm.staff_ids.length > 0"
                                    type="button"
                                    @click="clearStaffSelection"
                                    class="text-[11px] font-semibold text-rose-500 hover:underline cursor-pointer"
                                >
                                    Hapus Semua
                                </button>
                            </div>
                        </div>

                        <!-- Tag/Pill Daftar Staf Terpilih -->
                        <div class="min-h-[46px] p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex flex-wrap gap-2 items-center">
                            <div
                                v-for="staff in selectedStaffObjects"
                                :key="staff.id"
                                class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 rounded-lg text-xs font-semibold bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-800 dark:text-slate-100 shadow-2xs group"
                            >
                                <User class="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
                                <span>{{ staff.name }}</span>
                                <span class="text-[10px] text-slate-400">({{ staff.position || 'Staf' }})</span>
                                <button
                                    type="button"
                                    @click="removeStaffFromSelection(staff.id)"
                                    class="h-4 w-4 rounded-full hover:bg-slate-200 dark:hover:bg-slate-600 flex items-center justify-center text-slate-400 hover:text-rose-500 transition cursor-pointer"
                                    title="Hapus staf dari penilaian ini"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>

                            <div v-if="auditForm.staff_ids.length === 0" class="text-slate-400 text-xs italic py-1">
                                Belum ada staf yang dipilih. Silakan pilih satu atau beberapa staf melalui dropdown di bawah.
                            </div>
                        </div>

                        <!-- Dropdown Standar Tambah Staf Baru ke Laporan -->
                        <div class="flex items-center gap-2">
                            <select
                                v-model="staffSelectDropdown"
                                @change="addStaffToSelection(staffSelectDropdown)"
                                class="flex-1 h-10 px-3.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer"
                            >
                                <option value="">+ Tambah / Pilih Nama Staf Pelayanan Lain...</option>
                                <option v-for="s in availableStaffToAdd" :key="s.id" :value="s.id">
                                    {{ s.name }} - {{ s.position || 'Staf RS' }} (Saldo: {{ s.total_points }} Poin)
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Form Grid: Pengaturan Nilai & Kategori Audit (3 Kolom Simpel & Clean) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                        <!-- Dropdown Standar Jenis Tindakan -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Tindakan Hasil Audit <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="auditForm.action_type"
                                class="w-full h-11 pl-3.5 pr-10 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition truncate"
                            >
                                <option value="PENAMBAHAN">Apresiasi (+ Tambah Poin KPI)</option>
                                <option value="PEMOTONGAN">Evaluasi (- Potong Poin KPI)</option>
                                <option value="NETRAL">Netral (0 Poin / Penanganan Biasa)</option>
                                <option value="DIBATALKAN">Dibatalkan (Laporan Tidak Valid)</option>
                            </select>
                        </div>

                        <!-- Dropdown Standar Nilai Poin -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Nilai Poin Audit <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-if="auditForm.action_type === 'PENAMBAHAN' || auditForm.action_type === 'PEMOTONGAN'"
                                v-model="pointPreset"
                                @change="handlePointPresetChange"
                                class="w-full h-11 pl-3.5 pr-10 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition truncate"
                            >
                                <option v-for="opt in standardPointOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <input
                                v-else
                                type="text"
                                disabled
                                value="0 Poin (Otomatis)"
                                class="w-full h-11 px-3.5 text-xs rounded-xl bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-slate-400 font-medium cursor-not-allowed"
                            />
                        </div>

                        <!-- Dropdown Standar Kategori KPI -->
                        <div class="space-y-1.5 sm:col-span-2 lg:col-span-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Kategori Penilaian KPI
                            </label>
                            <select
                                v-model="auditForm.kpi_category"
                                :disabled="auditForm.action_type === 'NETRAL' || auditForm.action_type === 'DIBATALKAN'"
                                class="w-full h-11 pl-3.5 pr-10 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 disabled:bg-slate-100 dark:disabled:bg-slate-800/40 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium cursor-pointer transition truncate"
                            >
                                <option v-for="(label, key) in kpiCategories" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Input Custom Poin (Jika Dipilih Custom) -->
                    <div v-if="pointPreset === 'CUSTOM' && (auditForm.action_type === 'PENAMBAHAN' || auditForm.action_type === 'PEMOTONGAN')" class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                            Ketik Nilai Poin Khusus (0 - 100)
                        </label>
                        <input
                            type="text"
                            :value="customPointInput"
                            @input="handleCustomPointInput"
                            placeholder="Ketik angka poin (misal: 10)"
                            class="w-full h-11 px-3.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500 font-medium"
                        />
                    </div>

                    <!-- Footer Modal Action -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="closeAuditModal"
                            class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="auditForm.processing"
                            class="h-10 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer shadow-sm"
                        >
                            <Check class="h-4 w-4" />
                            <span>{{ auditForm.processing ? 'Menyimpan...' : 'Simpan Koreksi Audit' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
