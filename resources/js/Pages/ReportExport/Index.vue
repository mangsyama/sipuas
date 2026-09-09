<script setup>
import { ref, computed, watch, getCurrentInstance, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { 
    FileText, Download, BarChart3, CheckCircle2, Clock, 
    FileBarChart2, Filter, RotateCcw, Building, MapPin, 
    UserCheck, Eye, ExternalLink, ClipboardList, ChevronDown, 
    ChevronLeft, ChevronRight, Check, Search, Calendar,
    ThumbsUp, AlertTriangle, MessageSquare, Layers, Sparkles
} from '@lucide/vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

const { proxy } = getCurrentInstance();

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ total: 0, positive: 0, negative: 0, verified: 0, pending: 0 }),
    },
    filters: {
        type: Object,
        default: () => ({
            start_date: '',
            end_date: '',
            room_id: '',
            sentiment: '',
            category: '',
            status: '',
            shift: '',
            search: '',
        }),
    },
    rooms: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    sentiments: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => [],
    },
    shifts: {
        type: Array,
        default: () => [],
    },
    reports: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
});

const formFilters = ref({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    room_id: props.filters.room_id || '',
    sentiment: props.filters.sentiment || '',
    category: props.filters.category || '',
    status: props.filters.status || '',
    shift: props.filters.shift || '',
    search: props.filters.search || '',
});

// Flatpickr calendar refs and instances
const dateRangeRef = ref(null);
let fpRange = null;

// Custom Dropdowns State
const isRoomDropdownOpen = ref(false);
const isSentimentDropdownOpen = ref(false);
const isCategoryDropdownOpen = ref(false);
const isStatusDropdownOpen = ref(false);

// Custom Dropdowns Search State
const roomSearchQuery = ref('');
const categorySearchQuery = ref('');

const formatRoomDetails = (room) => {
    if (!room) return '';
    const b = room.building_name ? (/^gedung/i.test(room.building_name.trim()) ? room.building_name.trim() : `Gedung ${room.building_name.trim()}`) : null;
    const f = room.location_floor ? (/^lantai/i.test(room.location_floor.trim()) || /^lt\./i.test(room.location_floor.trim()) ? room.location_floor.trim() : `Lantai ${room.location_floor.trim()}`) : null;
    return [b, f].filter(Boolean).join(' - ');
};

const selectedRoomLabel = computed(() => {
    if (!formFilters.value.room_id) return '-- Semua Ruangan RS --';
    const r = props.rooms.find(item => String(item.id) === String(formFilters.value.room_id));
    if (!r) return '-- Semua Ruangan RS --';
    const details = formatRoomDetails(r);
    return details ? `${r.name} (${details})` : r.name;
});

const selectedSentimentLabel = computed(() => {
    if (!formFilters.value.sentiment) return '-- Semua Sentimen --';
    const s = props.sentiments.find(item => item.id === formFilters.value.sentiment);
    return s ? s.name : '-- Semua Sentimen --';
});

const selectedCategoryLabel = computed(() => {
    if (!formFilters.value.category) return '-- Semua Kategori --';
    const c = props.categories.find(item => item.id === formFilters.value.category);
    return c ? c.name : '-- Semua Kategori --';
});

const selectedStatusLabel = computed(() => {
    if (!formFilters.value.status) return '-- Semua Status --';
    const st = props.statuses.find(item => item.id === formFilters.value.status);
    return st ? st.name : '-- Semua Status --';
});

const closeAllDropdowns = () => {
    isRoomDropdownOpen.value = false;
    isSentimentDropdownOpen.value = false;
    isCategoryDropdownOpen.value = false;
    isStatusDropdownOpen.value = false;
    
    roomSearchQuery.value = '';
    categorySearchQuery.value = '';
};

const toggleRoomDropdown = (e) => {
    e?.stopPropagation();
    isRoomDropdownOpen.value = !isRoomDropdownOpen.value;
    isSentimentDropdownOpen.value = false;
    isCategoryDropdownOpen.value = false;
    isStatusDropdownOpen.value = false;
    roomSearchQuery.value = '';
};

const selectRoom = (id) => {
    formFilters.value.room_id = id;
    closeAllDropdowns();
};

const toggleSentimentDropdown = (e) => {
    e?.stopPropagation();
    isSentimentDropdownOpen.value = !isSentimentDropdownOpen.value;
    isRoomDropdownOpen.value = false;
    isCategoryDropdownOpen.value = false;
    isStatusDropdownOpen.value = false;
};

const selectSentiment = (id) => {
    formFilters.value.sentiment = id;
    closeAllDropdowns();
};

const toggleCategoryDropdown = (e) => {
    e?.stopPropagation();
    isCategoryDropdownOpen.value = !isCategoryDropdownOpen.value;
    isRoomDropdownOpen.value = false;
    isSentimentDropdownOpen.value = false;
    isStatusDropdownOpen.value = false;
    categorySearchQuery.value = '';
};

const selectCategory = (id) => {
    formFilters.value.category = id;
    closeAllDropdowns();
};

const toggleStatusDropdown = (e) => {
    e?.stopPropagation();
    isStatusDropdownOpen.value = !isStatusDropdownOpen.value;
    isRoomDropdownOpen.value = false;
    isSentimentDropdownOpen.value = false;
    isCategoryDropdownOpen.value = false;
};

const selectStatus = (id) => {
    formFilters.value.status = id;
    closeAllDropdowns();
};

// Search filters
const filteredRooms = computed(() => {
    const q = roomSearchQuery.value.toLowerCase().trim();
    if (!q) return props.rooms;
    return props.rooms.filter(r => 
        (r.name && r.name.toLowerCase().includes(q)) || 
        (r.building_name && r.building_name.toLowerCase().includes(q)) ||
        (r.location_floor && r.location_floor.toLowerCase().includes(q))
    );
});

const filteredCategories = computed(() => {
    const q = categorySearchQuery.value.toLowerCase().trim();
    if (!q) return props.categories;
    return props.categories.filter(c => c.name.toLowerCase().includes(q));
});

const isFiltering = ref(false);
const isLoading = computed(() => isFiltering.value || !props.reports?.data);

watch(() => props.reports, () => {
    isFiltering.value = false;
});

const applyFilters = () => {
    isFiltering.value = true;
    router.visit(route('reports.index'), {
        data: formFilters.value,
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
            isFiltering.value = true;
        },
        onFinish: () => {
            isFiltering.value = false;
        }
    });
};

const resetFilters = () => {
    formFilters.value = {
        start_date: '',
        end_date: '',
        room_id: '',
        sentiment: '',
        category: '',
        status: '',
    };

    if (fpRange) fpRange.clear();

    applyFilters();
};

const isExporting = ref(false);
const exportType = ref('pdf');

const exportPdf = () => {
    exportType.value = 'pdf';
    isExporting.value = true;
    setTimeout(() => {
        window.location.href = route('reports.export.pdf', formFilters.value);
        setTimeout(() => {
            isExporting.value = false;
            if (proxy?.$toast) {
                proxy.$toast('Dokumen PDF berhasil diunduh', 'success');
            }
        }, 1500);
    }, 500);
};

const exportExcel = () => {
    exportType.value = 'excel';
    isExporting.value = true;
    setTimeout(() => {
        window.location.href = route('reports.export.excel', formFilters.value);
        setTimeout(() => {
            isExporting.value = false;
            if (proxy?.$toast) {
                proxy.$toast('File Excel (.xlsx) berhasil diunduh', 'success');
            }
        }, 1500);
    }, 500);
};

const totalCount = computed(() => props.reports?.total ?? props.reports?.meta?.total ?? 0);
const fromCount = computed(() => props.reports?.from ?? props.reports?.meta?.from ?? 0);
const toCount = computed(() => props.reports?.to ?? props.reports?.meta?.to ?? 0);
const lastPage = computed(() => props.reports?.last_page ?? props.reports?.meta?.last_page ?? 1);

const prevPageUrl = computed(() => {
    if (props.reports?.prev_page_url) return props.reports.prev_page_url;
    if (props.reports?.links?.prev) return props.reports.links.prev;
    if (Array.isArray(props.reports?.links) && props.reports.links.length > 0) {
        const prevLink = props.reports.links[0];
        return prevLink && prevLink.url ? prevLink.url : null;
    }
    return null;
});

const nextPageUrl = computed(() => {
    if (props.reports?.next_page_url) return props.reports.next_page_url;
    if (props.reports?.links?.next) return props.reports.links.next;
    if (Array.isArray(props.reports?.links) && props.reports.links.length > 0) {
        const nextLink = props.reports.links[props.reports.links.length - 1];
        return nextLink && nextLink.url ? nextLink.url : null;
    }
    return null;
});

const goToPage = (url) => {
    if (url) {
        router.visit(url, { preserveScroll: true, preserveState: true, data: formFilters.value });
    }
};

onMounted(() => {
    window.addEventListener('click', closeAllDropdowns);

    fpRange = flatpickr(dateRangeRef.value, {
        locale: {
            ...Indonesian,
            rangeSeparator: ' - '
        },
        mode: 'range',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd F Y',
        altInputClass: 'w-full h-10 px-4 text-center border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-0 focus:border-slate-200 dark:focus:border-slate-800 transition duration-150',
        defaultDate: (formFilters.value.start_date && formFilters.value.end_date) ? [formFilters.value.start_date, formFilters.value.end_date] : null,
        onChange: (selectedDates) => {
            if (selectedDates.length === 2) {
                const formatDate = (d) => {
                    const yyyy = d.getFullYear();
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const dd = String(d.getDate()).padStart(2, '0');
                    return `${yyyy}-${mm}-${dd}`;
                };
                formFilters.value.start_date = formatDate(selectedDates[0]);
                formFilters.value.end_date = formatDate(selectedDates[1]);
            }
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('click', closeAllDropdowns);
});

watch(() => props.filters, (newVal) => {
    if (newVal) {
        formFilters.value.start_date = newVal.start_date || '';
        formFilters.value.end_date = newVal.end_date || '';
        formFilters.value.room_id = newVal.room_id || '';
        formFilters.value.sentiment = newVal.sentiment || '';
        formFilters.value.category = newVal.category || '';
        formFilters.value.status = newVal.status || '';
    }
    if (fpRange) {
        if (newVal.start_date && newVal.end_date) {
            fpRange.setDate([newVal.start_date, newVal.end_date]);
        } else {
            fpRange.clear();
        }
    }
}, { deep: true });
</script>

<template>
    <Head title="Pusat Rekapitulasi & Ekspor Laporan" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                <!-- Premium Header Panel (Pesu Peluh Style) -->
                <div class="hidden sm:flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <FileBarChart2 class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-850 dark:text-white leading-tight">
                                Pusat Rekapitulasi & Ekspor Laporan
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Rekapitulasi dan ekspor data laporan suara pasien ke format PDF atau Excel.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Top 4 KPI Metrics Grid (Sesuai Desain & Ukuran Command Center) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
                    <!-- Metric 1: Total Laporan -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Laporan Terfilter</span>
                            <div v-if="isLoading" class="h-8 w-16 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mt-0.5"></div>
                            <div v-else class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                {{ stats?.total ?? 0 }}
                            </div>
                            <span class="text-[11px] text-slate-400 block">Seluruh data suara pasien terfilter</span>
                        </div>
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                            <FileText class="h-6 w-6 text-emerald-600 dark:text-white" />
                        </div>
                    </div>

                    <!-- Metric 2: Apresiasi (Positif) -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Apresiasi (Positif)</span>
                            <div v-if="isLoading" class="h-8 w-16 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mt-0.5"></div>
                            <div v-else class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-tight">
                                {{ stats?.positive ?? 0 }}
                            </div>
                            <span class="text-[11px] text-slate-400 block">Umpan balik pujian dari pasien</span>
                        </div>
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                            <ThumbsUp class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>

                    <!-- Metric 3: Keluhan (Negatif) -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Keluhan (Negatif)</span>
                            <div v-if="isLoading" class="h-8 w-16 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mt-0.5"></div>
                            <div v-else class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 leading-tight">
                                {{ stats?.negative ?? 0 }}
                            </div>
                            <span class="text-[11px] text-slate-400 block">Aduan pelayanan & sarpras</span>
                        </div>
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-rose-50 dark:bg-rose-950/40">
                            <AlertTriangle class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                        </div>
                    </div>

                    <!-- Metric 4: Terverifikasi / Selesai -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Terverifikasi / Selesai</span>
                            <div v-if="isLoading" class="h-8 w-16 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mt-0.5"></div>
                            <div v-else class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                {{ stats?.verified ?? 0 }}
                            </div>
                            <span class="text-[11px] text-slate-400 block">Telah ditindaklanjuti Kasi / Supervisor</span>
                        </div>
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-blue-50 dark:bg-blue-950/40">
                            <CheckCircle2 class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>

                <!-- Split Grid Layout (Row 3: Filters & Exports) -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 mb-4">
                    <!-- Left Column: Filters (8 cols) -->
                    <div class="xl:col-span-8">
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between h-full space-y-6">
                            <div class="space-y-4">
                                <div class="pb-3 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                        Filter Pencarian Data
                                    </h3>
                                    <span class="text-[11px] text-slate-400">Parameter multi-kriteria laporan</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <!-- Tanggal Mulai - Selesai -->
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center">
                                            Tanggal Mulai - Selesai
                                        </label>
                                        <input 
                                            ref="dateRangeRef"
                                            type="text" 
                                            placeholder="Pilih Rentang Tanggal"
                                            class="w-full h-10 px-4 text-center border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-0 focus:border-slate-200 dark:focus:border-slate-800 transition duration-150"
                                        />
                                    </div>

                                    <!-- Sentimen AI -->
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center">
                                            Sentimen AI
                                        </label>
                                        <div class="relative">
                                            <button
                                                type="button"
                                                @click.stop="toggleSentimentDropdown"
                                                class="w-full h-10 px-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs flex items-center justify-center focus:outline-none transition-all duration-150 text-center relative"
                                            >
                                                <span class="truncate font-medium text-slate-800 dark:text-slate-100 text-center">
                                                    {{ selectedSentimentLabel }}
                                                </span>
                                                <ChevronDown :class="['absolute right-4 h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0', isSentimentDropdownOpen ? 'rotate-180 text-emerald-500 dark:text-white' : '']" />
                                            </button>

                                            <div
                                                v-if="isSentimentDropdownOpen"
                                                class="absolute z-30 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-1 shadow-md"
                                            >
                                                <button
                                                    type="button"
                                                    @click.stop="selectSentiment('')"
                                                    class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                    :class="!formFilters.sentiment ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                >
                                                    <span class="truncate">-- Semua Sentimen --</span>
                                                    <Check v-if="!formFilters.sentiment" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                </button>
                                                <button
                                                    v-for="s in sentiments"
                                                    :key="s.id"
                                                    type="button"
                                                    @click.stop="selectSentiment(s.id)"
                                                    class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                    :class="formFilters.sentiment === s.id ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                >
                                                    <span class="truncate">{{ s.name }}</span>
                                                    <Check v-if="formFilters.sentiment === s.id" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kategori AI -->
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center">
                                            Kategori AI
                                        </label>
                                        <div class="relative">
                                            <button
                                                type="button"
                                                @click.stop="toggleCategoryDropdown"
                                                class="w-full h-10 px-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs flex items-center justify-center focus:outline-none transition-all duration-150 text-center relative"
                                            >
                                                <span class="truncate font-medium text-slate-800 dark:text-slate-100 text-center">
                                                    {{ selectedCategoryLabel }}
                                                </span>
                                                <ChevronDown :class="['absolute right-4 h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0', isCategoryDropdownOpen ? 'rotate-180 text-emerald-500 dark:text-white' : '']" />
                                            </button>

                                            <div
                                                v-if="isCategoryDropdownOpen"
                                                class="absolute z-30 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-2 shadow-md"
                                            >
                                                <!-- Search Input -->
                                                <div class="relative">
                                                    <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                                    <input 
                                                        v-model="categorySearchQuery"
                                                        type="text"
                                                        placeholder="Cari kategori..."
                                                        class="w-full h-8 pl-9 pr-3 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-0 focus:ring-offset-0"
                                                        @click.stop
                                                    />
                                                </div>
                                                <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                                    <button
                                                        type="button"
                                                        @click.stop="selectCategory('')"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="!formFilters.category ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">-- Semua Kategori --</span>
                                                        <Check v-if="!formFilters.category" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                    <button
                                                        v-for="cat in filteredCategories"
                                                        :key="cat.id"
                                                        type="button"
                                                        @click.stop="selectCategory(cat.id)"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="formFilters.category === cat.id ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">{{ cat.name }}</span>
                                                        <Check v-if="formFilters.category === cat.id" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ruangan / Unit RS -->
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center">
                                            Ruangan / Unit Pelayanan Rumah Sakit
                                        </label>
                                        <div class="relative">
                                            <button
                                                type="button"
                                                @click.stop="toggleRoomDropdown"
                                                class="w-full h-10 px-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs flex items-center justify-center focus:outline-none transition-all duration-150 text-center relative"
                                            >
                                                <span class="truncate font-medium text-slate-800 dark:text-slate-100 text-center">
                                                    {{ selectedRoomLabel }}
                                                </span>
                                                <ChevronDown :class="['absolute right-4 h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0', isRoomDropdownOpen ? 'rotate-180 text-emerald-500 dark:text-white' : '']" />
                                            </button>

                                            <div
                                                v-if="isRoomDropdownOpen"
                                                class="absolute z-30 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-2 shadow-md"
                                            >
                                                <!-- Search Input -->
                                                <div class="relative">
                                                    <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                                    <input 
                                                        v-model="roomSearchQuery"
                                                        type="text"
                                                        placeholder="Cari ruangan atau lantai..."
                                                        class="w-full h-8 pl-9 pr-3 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-0 focus:ring-offset-0"
                                                        @click.stop
                                                    />
                                                </div>
                                                <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                                    <div v-if="filteredRooms.length === 0" class="p-3 text-center text-xs text-slate-400 dark:text-slate-500">
                                                        Ruangan tidak ditemukan
                                                    </div>
                                                    <button
                                                        v-else
                                                        type="button"
                                                        @click.stop="selectRoom('')"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="!formFilters.room_id ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">-- Semua Ruangan RS --</span>
                                                        <Check v-if="!formFilters.room_id" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                    <button
                                                        v-for="room in filteredRooms"
                                                        :key="room.id"
                                                        type="button"
                                                        @click.stop="selectRoom(room.id)"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="String(formFilters.room_id) === String(room.id) ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">{{ room.name }} <span v-if="formatRoomDetails(room)" class="opacity-75">({{ formatRoomDetails(room) }})</span></span>
                                                        <Check v-if="String(formFilters.room_id) === String(room.id)" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Laporan -->
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center">
                                            Status Laporan
                                        </label>
                                        <div class="relative">
                                            <button
                                                type="button"
                                                @click.stop="toggleStatusDropdown"
                                                class="w-full h-10 px-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs flex items-center justify-center focus:outline-none transition-all duration-150 text-center relative"
                                            >
                                                <span class="truncate font-medium text-slate-800 dark:text-slate-100 text-center">
                                                    {{ selectedStatusLabel }}
                                                </span>
                                                <ChevronDown :class="['absolute right-4 h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0', isStatusDropdownOpen ? 'rotate-180 text-emerald-500 dark:text-white' : '']" />
                                            </button>

                                            <div
                                                v-if="isStatusDropdownOpen"
                                                class="absolute z-30 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-1 shadow-md"
                                            >
                                                <button
                                                    type="button"
                                                    @click.stop="selectStatus('')"
                                                    class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                    :class="!formFilters.status ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                >
                                                    <span class="truncate">-- Semua Status --</span>
                                                    <Check v-if="!formFilters.status" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                </button>
                                                <button
                                                    v-for="st in statuses"
                                                    :key="st.id"
                                                    type="button"
                                                    @click.stop="selectStatus(st.id)"
                                                    class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                    :class="formFilters.status === st.id ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                >
                                                    <span class="truncate">{{ st.name }}</span>
                                                    <Check v-if="formFilters.status === st.id" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5 sm:gap-3 pt-2">
                                <button 
                                    type="button"
                                    @click="resetFilters"
                                    class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition w-full sm:w-auto"
                                >
                                    <RotateCcw class="h-3.5 w-3.5" />
                                    <span>Reset Filter</span>
                                </button>
                                
                                <button 
                                    type="button"
                                    @click="applyFilters"
                                    class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 text-xs font-bold transition shadow-sm shadow-emerald-500/10 w-full sm:w-auto"
                                >
                                    <Filter class="h-3.5 w-3.5" />
                                    <span>Terapkan Filter</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Export Actions (4 cols) -->
                    <div class="xl:col-span-4">
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between h-full min-h-[250px] space-y-6">
                            <div class="space-y-3">
                                <div class="pb-3 border-b border-slate-100 dark:border-slate-800/80">
                                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                        Pusat Unduh Dokumen
                                    </h3>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Unduh data laporan yang disaring ke format PDF resmi (layout cetak A4) atau CSV mentah (dapat diolah di Excel/Sheets).
                                </p>
                            </div>

                            <div class="space-y-2.5">
                                <!-- PDF Button -->
                                <button
                                    @click="exportPdf"
                                    class="w-full inline-flex items-center justify-center px-4 py-3 bg-red-600 hover:bg-red-500 text-white font-extrabold text-xs rounded-xl transition duration-150 shadow-sm gap-2"
                                >
                                    <FileText class="h-4 w-4" />
                                    Unduh Dokumen PDF
                                </button>

                                <!-- Excel Button -->
                                <button
                                    @click="exportExcel"
                                    class="w-full inline-flex items-center justify-center px-4 py-3 bg-emerald-700 hover:bg-emerald-600 text-white font-extrabold text-xs rounded-xl transition duration-150 shadow-sm gap-2"
                                >
                                    <BarChart3 class="h-4 w-4" />
                                    Unduh Berkas Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 4: Data Preview Table -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-0">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Pratinjau Data Laporan
                            </h3>
                        </div>
                        <span class="text-xs text-slate-400 dark:text-slate-500">
                            Menampilkan {{ reports.data?.length ?? 0 }} baris dari total {{ reports.total ?? 0 }} data terfilter
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800/80 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="px-5 py-3.5 whitespace-nowrap">No. Tiket</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Tanggal & Jam</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Ruangan / Unit</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Pelapor</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap text-center">Sentimen AI</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Kategori AI</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Isi Ulasan Pasien</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                                <!-- Skeleton Loading Rows -->
                                <template v-if="isLoading">
                                    <tr v-for="n in 5" :key="'skel-r-' + n" class="align-middle">
                                        <td class="px-5 py-4"><div class="h-4 w-20 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-5 py-4"><div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-5 py-4"><div class="h-4 w-28 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-5 py-4"><div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-5 py-4 text-center"><div class="h-5 w-16 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse mx-auto"></div></td>
                                        <td class="px-5 py-4"><div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-5 py-4"><div class="h-4 w-48 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-5 py-4 text-center"><div class="h-5 w-16 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse mx-auto"></div></td>
                                    </tr>
                                </template>

                                <!-- Empty State -->
                                <tr v-else-if="!reports.data || reports.data.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500 font-medium italic">
                                        Tidak ada data laporan suara pasien yang sesuai dengan filter yang dipilih.
                                    </td>
                                </tr>

                                <!-- Real Data Rows -->
                                <tr 
                                    v-else
                                    v-for="rep in reports.data" 
                                    :key="rep.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-950/20 text-slate-700 dark:text-slate-300"
                                >
                                    <td class="px-5 py-4 font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                        {{ rep.ticket_number }}
                                        <div v-if="rep.shift_info" class="text-[10px] text-slate-400 font-normal">
                                            {{ rep.shift_info }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        {{ rep.created_at ? new Date(rep.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-' }}
                                    </td>
                                    <td class="px-5 py-4 whitespace-normal break-words max-w-[170px]">
                                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ rep.room?.name ?? 'Unit Umum' }}</span>
                                        <div v-if="rep.room?.location_floor" class="text-[10px] text-slate-400">
                                            Lantai {{ rep.room.location_floor }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-normal break-words max-w-[140px]">
                                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ rep.reporter_name || 'Anonim' }}</span>
                                        <div v-if="rep.reporter_phone" class="text-[10px] text-slate-400">
                                            {{ rep.reporter_phone }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-center">
                                        <span 
                                            :class="[
                                                'inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase border',
                                                rep.ai_sentiment === 'POSITIF' 
                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800' 
                                                    : rep.ai_sentiment === 'NEGATIF'
                                                        ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800'
                                                        : 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
                                            ]"
                                        >
                                            {{ rep.ai_sentiment || 'NETRAL' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 whitespace-normal break-words max-w-[140px] font-medium text-slate-800 dark:text-slate-200">
                                        {{ rep.ai_category || '-' }}
                                    </td>
                                    <td class="px-5 py-4 whitespace-normal break-words max-w-sm text-slate-600 dark:text-slate-300">
                                        {{ rep.isi_laporan }}
                                        <div v-if="rep.supervisor_notes" class="mt-1 text-[10px] text-emerald-600 dark:text-emerald-400 italic">
                                            Catatan: {{ rep.supervisor_notes }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-center">
                                        <span 
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase border',
                                                (rep.status === 'VERIFIED' || rep.status === 'RESOLVED')
                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800' 
                                                    : rep.status === 'PENDING'
                                                        ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-400 dark:border-amber-800'
                                                        : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:border-rose-800'
                                            ]"
                                        >
                                            {{ rep.status === 'VERIFIED' ? 'Verified' : (rep.status === 'RESOLVED' ? 'Selesai' : (rep.status === 'PENDING' ? 'Pending' : 'Ditolak')) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div 
                        v-if="lastPage > 1" 
                        class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                    >
                        <div class="flex items-center gap-2"></div>

                        <div class="flex items-center gap-3">
                            <span class="text-[10px] sm:text-xs font-medium text-slate-500 dark:text-slate-400">
                                {{ fromCount }}–{{ toCount }} dari {{ totalCount }}
                            </span>
                            
                            <div class="flex items-center gap-1">
                                <button
                                    @click="goToPage(prevPageUrl)"
                                    :disabled="!prevPageUrl"
                                    class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                                    aria-label="Halaman sebelumnya"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </button>
                                <button
                                    @click="goToPage(nextPageUrl)"
                                    :disabled="!nextPageUrl"
                                    class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                                    aria-label="Halaman berikutnya"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Fullscreen Export Loading Backdrop Overlay -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isExporting" class="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-slate-950/60 backdrop-blur-md text-white select-none">
                <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl flex flex-col items-center gap-4 max-w-xs text-center">
                    <div class="relative flex items-center justify-center">
                        <div class="w-12 h-12 rounded-full border-4 border-emerald-200 dark:border-emerald-900/60 border-t-emerald-600 dark:border-t-emerald-400 animate-spin"></div>
                        <FileText v-if="exportType === 'pdf'" class="h-5 w-5 text-emerald-600 dark:text-emerald-400 absolute" />
                        <BarChart3 v-else class="h-5 w-5 text-emerald-600 dark:text-emerald-400 absolute" />
                    </div>
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-900 dark:text-white">
                            {{ exportType === 'pdf' ? 'Menyiapkan Dokumen PDF...' : 'Menyiapkan File Excel...' }}
                        </h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Mohon tunggu sebentar, file rekapitulasi laporan sedang diunduh.
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
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

/* Flatpickr Custom Styling matching Pesu Peluh */
:deep(.flatpickr-calendar) {
    width: 100% !important;
    max-width: 280px !important;
    min-width: unset !important;
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05) !important;
    font-family: inherit !important;
    padding: 8px !important;
}
:deep(.dark .flatpickr-calendar) {
    background: #0f172a !important;
    border-color: #1e293b !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -4px rgba(0, 0, 0, 0.3) !important;
}
:deep(.flatpickr-months) {
    padding: 4px 0 !important;
}
:deep(.flatpickr-months .flatpickr-month) {
    background: transparent !important;
    color: inherit !important;
}
:deep(.flatpickr-current-month) {
    font-size: 110% !important;
    font-weight: 700 !important;
}
:deep(.flatpickr-current-month .flatpickr-monthDropdown-months) {
    background: transparent !important;
    color: inherit !important;
    font-weight: 700 !important;
}
:deep(.flatpickr-weekday) {
    font-size: 11px !important;
    font-weight: 700 !important;
    color: #94a3b8 !important;
}
:deep(.flatpickr-days) {
    width: 100% !important;
    margin-top: 6px !important;
}
:deep(.dayContainer) {
    width: 100% !important;
    min-width: unset !important;
    max-width: unset !important;
}
:deep(.flatpickr-day) {
    font-size: 12px !important;
    max-width: unset !important;
    flex-basis: 14.28% !important;
    height: 32px !important;
    line-height: 32px !important;
    border-radius: 10px !important;
    color: #475569 !important;
}
:deep(.dark .flatpickr-day) {
    color: #cbd5e1 !important;
}
:deep(.flatpickr-day.today) {
    border-color: #10b981 !important;
    color: #10b981 !important;
    font-weight: 800 !important;
}
:deep(.flatpickr-day.selected) {
    background: #059669 !important;
    border-color: #059669 !important;
    color: #ffffff !important;
}
:deep(.flatpickr-day:hover) {
    background: #f1f5f9 !important;
}
:deep(.dark .flatpickr-day:hover) {
    background: #1e293b !important;
}
:deep(.flatpickr-day.prevMonthDay),
:deep(.flatpickr-day.nextMonthDay) {
    color: #cbd5e1 !important;
    opacity: 0.4 !important;
}
:deep(.dark .flatpickr-day.prevMonthDay),
:deep(.dark .flatpickr-day.nextMonthDay) {
    color: #475569 !important;
}

/* Custom Scrollbar for dropdowns */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}

/* Force disable outline and box shadow rings on focus / active */
:deep(input:focus),
:deep(input[type="text"]:focus),
:deep(input.flatpickr-input:focus),
:deep(button:focus),
:deep(input:active),
:deep(button:active),
:deep(input:focus-within),
:deep(button:focus-within),
:deep(input:focus-visible),
:deep(button:focus-visible) {
    outline: none !important;
    outline-width: 0 !important;
    box-shadow: none !important;
    --tw-shadow: none !important;
    --tw-shadow-colored: none !important;
    --tw-ring-shadow: none !important;
    --tw-ring-color: transparent !important;
    -webkit-tap-highlight-color: transparent !important;
}
</style>
