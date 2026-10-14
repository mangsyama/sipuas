<script setup>
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReportDetailModal from '@/Components/ReportDetailModal.vue';
import { Head } from '@inertiajs/vue3';
import { 
    FileText, 
    TrendingUp, 
    TrendingDown, 
    Users, 
    Clock, 
    Search, 
    History, 
    X, 
    ArrowUpDown, 
    Building2, 
    RotateCcw 
} from '@lucide/vue';

const props = defineProps({
    staffLogbooks: {
        type: Array,
        default: () => []
    },
    rooms: {
        type: Array,
        default: () => []
    }
});

const staffLogbooks = ref(props.staffLogbooks || []);
const searchQuery = ref('');
const selectedStatus = ref('RECENT'); // Default: 'RECENT' (Penilaian Terbaru), atau 'ALL', 'PRAISE', 'COMPLAINT', 'HAS_HISTORY'
const selectedRoom = ref('ALL');
const sortBy = ref('RECENT');
const selectedLogForModal = ref(null);
const showDetailModal = ref(false);

// Watch props jika terjadi Inertia partial reload
watch(() => props.staffLogbooks, (val) => {
    staffLogbooks.value = val || [];
});

// Hitung total untuk indikator filter
const recentStaffCount = computed(() => {
    return (staffLogbooks.value || []).filter(s => s.history && s.history.length > 0).length;
});
const allStaffCount = computed(() => (staffLogbooks.value || []).length);

// Daftar seluruh ruangan aktif (diambil dari props.rooms yang dikirim backend)
const activeRooms = computed(() => {
    if (props.rooms && props.rooms.length > 0) {
        return props.rooms;
    }
    // Fallback dinamis jika props.rooms kosong
    const map = new Map();
    (staffLogbooks.value || []).forEach(s => {
        if (s.room_name) {
            s.room_name.split(',').forEach(r => {
                const trimmed = r.trim();
                if (trimmed && !map.has(trimmed)) {
                    map.set(trimmed, { id: trimmed, name: trimmed });
                }
            });
        }
    });
    return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name));
});

// Nama ruangan terpilih untuk label indikator
const selectedRoomName = computed(() => {
    if (selectedRoom.value === 'ALL') return 'Semua Ruangan';
    const found = activeRooms.value.find(r => String(r.id) === String(selectedRoom.value) || r.name === selectedRoom.value);
    return found ? found.name : selectedRoom.value;
});

// Algoritma Levenshtein distance untuk toleransi typo / kemiripan nama
const getLevenshteinDistance = (a, b) => {
    if (a === b) return 0;
    const al = a.length, bl = b.length;
    if (al === 0) return bl;
    if (bl === 0) return al;
    const row = new Array(bl + 1);
    for (let j = 0; j <= bl; j++) row[j] = j;
    for (let i = 1; i <= al; i++) {
        let prev = i - 1;
        row[0] = i;
        for (let j = 1; j <= bl; j++) {
            const temp = row[j];
            if (a.charAt(i - 1) === b.charAt(j - 1)) {
                row[j] = prev;
            } else {
                row[j] = Math.min(prev + 1, row[j] + 1, row[j - 1] + 1);
            }
            prev = temp;
        }
    }
    return row[bl];
};

// Normalisasi fonetik & variasi ejaan umum nama Indonesia (dr., s.kep, y/i, v/f, ch/c, dll)
const normalizeIndoName = (str) => {
    return String(str || '')
        .toLowerCase()
        .replace(/^(dr|drg|ns|ners|apt|bidan|perawat)\.?\s+/gi, '')
        .replace(/,\s*(s\.?kep|m\.?kep|a\.?md\.?kep|s\.?tr\.?keb|a\.?md|s\.?ked|sp\.?[a-z]+|s\.?si|s\.?kom|s\.?e|m\.?m).*$/gi, '')
        .replace(/[^a-z0-9\s]/g, ' ')
        .replace(/\boe\b/g, 'u')
        .replace(/dh/g, 'd')
        .replace(/th/g, 't')
        .replace(/ch/g, 'c')
        .replace(/kh/g, 'k')
        .replace(/ph/g, 'f')
        .replace(/v/g, 'f')
        .replace(/y/g, 'i')
        .replace(/sh/g, 's')
        .replace(/sy/g, 's')
        .replace(/\s+/g, ' ')
        .trim();
};

// Cek kecocokan nama dengan toleransi kemiripan kata / typo / ejaan
const isTolerantNameMatch = (candidateName, rawQuery) => {
    if (!rawQuery || !rawQuery.trim()) return true;
    const cleanQuery = rawQuery.toLowerCase().trim();
    const cleanName = String(candidateName || '').toLowerCase().trim();

    if (cleanName.includes(cleanQuery)) return true;

    const normQuery = normalizeIndoName(cleanQuery);
    const normName = normalizeIndoName(cleanName);
    if (normName.includes(normQuery)) return true;

    const queryTokens = cleanQuery.split(/\s+/).filter(Boolean);
    const nameTokens = cleanName.split(/[\s,.-]+/).filter(Boolean);
    const normQueryTokens = normQuery.split(/\s+/).filter(Boolean);
    const normNameTokens = normName.split(/\s+/).filter(Boolean);

    return queryTokens.every(qToken => {
        const qNorm = normalizeIndoName(qToken);
        const qLen = qToken.length;

        for (const nToken of nameTokens) {
            if (nToken.includes(qToken) || qToken.includes(nToken)) return true;
            if (qLen >= 3) {
                const maxDist = qLen <= 4 ? 1 : 2;
                if (Math.abs(nToken.length - qLen) <= maxDist) {
                    if (getLevenshteinDistance(qToken, nToken) <= maxDist) return true;
                }
                if (nToken.length > qLen) {
                    for (let i = 0; i <= nToken.length - qLen; i++) {
                        const slice = nToken.substring(i, i + qLen);
                        if (getLevenshteinDistance(qToken, slice) <= 1) return true;
                    }
                }
            }
        }

        for (const nNorm of normNameTokens) {
            if (nNorm.includes(qNorm) || qNorm.includes(nNorm)) return true;
            if (qNorm.length >= 3) {
                const maxDist = qNorm.length <= 4 ? 1 : 2;
                if (Math.abs(nNorm.length - qNorm.length) <= maxDist) {
                    if (getLevenshteinDistance(qNorm, nNorm) <= maxDist) return true;
                }
            }
        }

        return false;
    });
};

// Filter Kombinasi Dinamis (Compound Multi-filter: Status AND Ruangan AND Search, diurutkan via SortBy)
const filteredStaffLogbooks = computed(() => {
    let list = [...(staffLogbooks.value || [])];

    // 1. Filter Dropdown Status / Jenis Penilaian
    if (selectedStatus.value === 'RECENT') {
        // Default: Hanya staf yang memiliki riwayat mutasi / penilaian aktif
        list = list.filter(s => s.history && s.history.length > 0);
    } else if (selectedStatus.value === 'PRAISE') {
        // Hanya staf dengan apresiasi pujian
        list = list.filter(s => Number(s.praise_count || 0) > 0);
    } else if (selectedStatus.value === 'COMPLAINT') {
        // Hanya staf dengan pemotongan poin / komplain
        list = list.filter(s => Number(s.complaint_count || 0) > 0);
    } else if (selectedStatus.value === 'HAS_HISTORY') {
        // Semua staf yang pernah ada riwayat KPI
        list = list.filter(s => s.history && s.history.length > 0);
    }
    // Jika selectedStatus.value === 'ALL', tampilkan seluruh staf tanpa membatasi riwayat penilaian

    // 2. Filter Dropdown Ruangan / Unit Aktif
    if (selectedRoom.value !== 'ALL') {
        const selRoom = String(selectedRoom.value);
        const roomObj = activeRooms.value.find(r => String(r.id) === selRoom || r.name.toLowerCase() === selRoom.toLowerCase());
        const targetRoomName = roomObj ? roomObj.name.toLowerCase() : selRoom.toLowerCase();

        list = list.filter(s => {
            // Cek kesesuaian room_ids jika array
            if (s.room_ids && Array.isArray(s.room_ids)) {
                if (s.room_ids.some(id => String(id) === selRoom)) return true;
            }
            // Cek kesesuaian nama ruangan pada string room_name
            if (s.room_name) {
                const rName = String(s.room_name).toLowerCase();
                return rName.includes(targetRoomName) || rName.includes(selRoom.toLowerCase());
            }
            return false;
        });
    }

    // 3. Filter Pencarian (Search Query)
    if (searchQuery.value && searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(s => {
            const name = String(s.name || '').toLowerCase();
            const nip = String(s.nip || '').toLowerCase();
            const role = String(s.role || '').toLowerCase();
            const room = String(s.room_name || '').toLowerCase();
            
            if (name.includes(q) || nip.includes(q) || role.includes(q) || room.includes(q)) return true;
            if (isTolerantNameMatch(s.name, q)) return true;

            if (s.history && s.history.length > 0) {
                return s.history.some(h => {
                    const ticket = String(h.ticket_number || '').toLowerCase();
                    const note = String(h.note || '').toLowerCase();
                    const cat = String(h.kpi_category || '').toLowerCase();
                    const verifier = String(h.verifier_name || '').toLowerCase();
                    return ticket.includes(q) || note.includes(q) || cat.includes(q) || verifier.includes(q);
                });
            }
            return false;
        });
    }

    // 4. Pengurutan (Sorting)
    return list.sort((a, b) => {
        if (sortBy.value === 'POINTS_DESC') {
            return (b.total_points || 0) - (a.total_points || 0);
        }
        if (sortBy.value === 'POINTS_ASC') {
            return (a.total_points || 0) - (b.total_points || 0);
        }
        if (sortBy.value === 'NAME_ASC') {
            return String(a.name || '').localeCompare(String(b.name || ''));
        }
        if (sortBy.value === 'NAME_DESC') {
            return String(b.name || '').localeCompare(String(a.name || ''));
        }
        if (sortBy.value === 'PRAISE_DESC') {
            return (b.praise_count || 0) - (a.praise_count || 0);
        }
        if (sortBy.value === 'COMPLAINT_DESC') {
            return (b.complaint_count || 0) - (a.complaint_count || 0);
        }
        
        // Default 'RECENT': Staf dengan riwayat penilaian paling mutakhir di posisi paling atas
        const aHas = a.history && a.history.length > 0;
        const bHas = b.history && b.history.length > 0;
        if (aHas && !bHas) return -1;
        if (!aHas && bHas) return 1;

        const aDate = a.last_update && a.last_update !== '-' ? a.last_update : '';
        const bDate = b.last_update && b.last_update !== '-' ? b.last_update : '';
        if (aDate && bDate) {
            return bDate.localeCompare(aDate);
        }
        return (b.total_points || 0) - (a.total_points || 0);
    });
});

const isFilterActive = computed(() => {
    return searchQuery.value.trim() !== '' 
        || selectedRoom.value !== 'ALL' 
        || selectedStatus.value !== 'RECENT' 
        || sortBy.value !== 'RECENT';
});

const resetFilters = () => {
    searchQuery.value = '';
    selectedRoom.value = 'ALL';
    selectedStatus.value = 'RECENT';
    sortBy.value = 'RECENT';
};

const openReportDetail = (log) => {
    selectedLogForModal.value = log;
    showDetailModal.value = true;
};

const getCategoryLabel = (log) => {
    if (!log) return 'Penilaian Kinerja';
    const raw = log.kpi_category;
    if (raw) {
        const upper = String(raw).toUpperCase();
        const map = {
            'KERAMAHAN': 'Keramahan & Komunikasi',
            'KEDISIPLINAN': 'Kedisiplinan & Waktu Tanggap',
            'SOP_PELAYANAN': 'Kepatuhan SOP & Profesionalisme',
            'INTEGRITAS': 'Integritas & Etika Profesi',
        };
        return map[upper] || raw;
    }
    if (log.points > 0) return 'Apresiasi Pelayanan';
    if (log.points < 0) return 'Evaluasi Pelayanan';
    return 'Penilaian Kinerja';
};
</script>

<template>
    <Head title="Digital Logbook Kinerja Staf" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white border border-emerald-100 dark:border-white/20">
                        <History class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Digital Logbook Staf Unit
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                            Akumulasi kinerja, riwayat mutasi poin, apresiasi pujian, dan pemotongan poin KPI staf unit secara transparan.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filter Controls Container (Deretan Dropdown + Search yang Saling Berkombinasi Dinamis) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 rounded-2xl shadow-xs space-y-3.5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                    <!-- 1. Search Input Multi-Kriteria (Nama, NIP, Peran, Ruangan, No. Tiket) -->
                    <div class="sm:col-span-2 lg:col-span-4 relative">
                        <Search class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama staf, NIP, peran, ruangan, atau no tiket..."
                            class="w-full h-10 pl-10 pr-9 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:focus:border-emerald-400 transition"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="searchQuery = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                            title="Bersihkan Pencarian"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- 2. Dropdown Filter Status / Kategori Penilaian (Menggantikan tab horizontal) -->
                    <div class="lg:col-span-3">
                        <div class="relative">
                            <Clock class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <select
                                v-model="selectedStatus"
                                class="w-full h-10 pl-9 pr-8 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:focus:border-emerald-400 transition cursor-pointer appearance-none font-medium"
                            >
                                <option value="RECENT">Penilaian Terbaru</option>
                                <option value="ALL">Semua Daftar Staf</option>
                                <option value="PRAISE">Apresiasi Pujian (+)</option>
                                <option value="COMPLAINT">Potong Poin (-)</option>
                                <option value="HAS_HISTORY">Semua yang Ada Riwayat KPI</option>
                            </select>
                        </div>
                    </div>

                    <!-- 3. Dropdown Filter Ruangan / Unit (Menampilkan Seluruh Ruangan Aktif RS) -->
                    <div class="lg:col-span-3">
                        <div class="relative">
                            <Building2 class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <select
                                v-model="selectedRoom"
                                class="w-full h-10 pl-9 pr-8 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:focus:border-emerald-400 transition cursor-pointer appearance-none font-medium"
                            >
                                <option value="ALL">Semua Ruangan</option>
                                <option v-for="rm in activeRooms" :key="rm.id" :value="rm.id">
                                    {{ rm.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- 4. Dropdown Pengurutan (Sort) -->
                    <div class="sm:col-span-2 lg:col-span-2">
                        <div class="relative">
                            <ArrowUpDown class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <select
                                v-model="sortBy"
                                class="w-full h-10 pl-9 pr-8 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:focus:border-emerald-400 transition cursor-pointer appearance-none font-medium"
                            >
                                <option value="RECENT">Urutkan: Penilaian Terbaru</option>
                                <option value="POINTS_DESC">Urutkan: Poin Tertinggi</option>
                                <option value="POINTS_ASC">Urutkan: Poin Terendah</option>
                                <option value="NAME_ASC">Urutkan: Nama Staf (A - Z)</option>
                                <option value="NAME_DESC">Urutkan: Nama Staf (Z - A)</option>
                                <option value="PRAISE_DESC">Urutkan: Pujian Terbanyak</option>
                                <option value="COMPLAINT_DESC">Urutkan: Komplain Terbanyak</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Info Keterangan Filter Kombinasi Aktif & Tombol Reset -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs px-1 text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-800/80 gap-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span>Menampilkan <strong>{{ filteredStaffLogbooks.length }}</strong> dari total {{ allStaffCount }} staf</span>
                        
                        <!-- Badges Filter Aktif -->
                        <span
                            v-if="selectedStatus !== 'RECENT'"
                            :class="[
                                'px-2 py-0.5 rounded-md text-[11px] font-semibold border',
                                selectedStatus === 'COMPLAINT'
                                    ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 border-rose-200 dark:border-rose-800'
                                    : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'
                            ]"
                        >
                            Status: {{ selectedStatus === 'ALL' ? 'Semua Staf' : (selectedStatus === 'PRAISE' ? 'Pujian (+)' : (selectedStatus === 'COMPLAINT' ? 'Potong Poin (-)' : 'Ada Riwayat')) }}
                        </span>
                        
                        <span v-if="selectedRoom !== 'ALL'" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            Ruangan: {{ selectedRoomName }}
                        </span>

                        <span v-if="searchQuery" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            Cari: "{{ searchQuery }}"
                        </span>
                    </div>

                    <div class="flex items-center gap-3 self-end sm:self-auto">
                        <span v-if="selectedStatus === 'RECENT' && recentStaffCount < allStaffCount" class="text-slate-400 hidden md:inline">
                            Ingin lihat semua staf? 
                            <button type="button" @click="selectedStatus = 'ALL'" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline cursor-pointer">
                                Klik Semua Staf
                            </button>
                        </span>

                        <!-- Reset Filter Button -->
                        <button
                            v-if="isFilterActive"
                            type="button"
                            @click="resetFilters"
                            class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:text-rose-700 flex items-center gap-1 transition cursor-pointer"
                        >
                            <RotateCcw class="h-3.5 w-3.5" />
                            <span>Reset Semua Filter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State / Belum Ada Data Sesuai Kombinasi Filter -->
            <div v-if="filteredStaffLogbooks.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-10 sm:p-12 text-center shadow-xs space-y-3">
                <!-- Case 1: Search Query Aktif -->
                <template v-if="searchQuery">
                    <div class="h-12 w-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                        <Search class="h-6 w-6" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                            Tidak Ada Staf yang Cocok
                        </h3>
                        <p class="text-xs text-slate-400 max-w-md mx-auto">
                            Tidak ditemukan data staf atau riwayat KPI yang cocok dengan kata kunci "{{ searchQuery }}" pada filter aktif saat ini.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white hover:bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition cursor-pointer shadow-xs"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span>Reset Semua Filter</span>
                    </button>
                </template>

                <!-- Case 2: Status Penilaian Terbaru Kosong (Belum ada riwayat mutasi) -->
                <template v-else-if="selectedStatus === 'RECENT'">
                    <div class="h-12 w-12 rounded-2xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center mx-auto border border-emerald-100 dark:border-white/20">
                        <Clock class="h-6 w-6" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                            Belum Ada Staf dengan Penilaian Terbaru
                        </h3>
                        <p class="text-xs text-slate-400 max-w-md mx-auto">
                            <span v-if="selectedRoom !== 'ALL'">Belum ada riwayat mutasi poin pada staf di <strong>{{ selectedRoomName }}</strong>.</span>
                            <span v-else>Belum ada mutasi poin atau penilaian KPI yang tercatat pada staf unit Anda.</span>
                            Anda dapat memilih status "Semua Daftar Staf" untuk memeriksa seluruh saldo awal staf.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="selectedStatus = 'ALL'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition cursor-pointer shadow-xs"
                    >
                        <Users class="h-4 w-4" />
                        <span>Lihat Semua Daftar Staf</span>
                    </button>
                </template>

                <!-- Case 3: Filter Lain Kosong -->
                <template v-else>
                    <div class="h-12 w-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                        <Users class="h-6 w-6" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                            Tidak Ada Data Staf pada Filter Ini
                        </h3>
                        <p class="text-xs text-slate-400 max-w-md mx-auto">
                            Tidak ada staf yang sesuai dengan kombinasi filter ruangan, status penilaian, atau urutan yang Anda pilih.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white hover:bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition cursor-pointer shadow-xs"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span>Reset Semua Filter</span>
                    </button>
                </template>
            </div>

            <!-- Staf Logbook Cards -->
            <div v-else class="space-y-4">
                <div
                    v-for="staff in filteredStaffLogbooks"
                    :key="staff.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4 hover:border-emerald-500/40 dark:hover:border-emerald-500/30 transition"
                >
                    <!-- Header Card Staf -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="h-12 w-12 rounded-full bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center font-bold text-lg border border-emerald-100 dark:border-white/20 shrink-0">
                                {{ staff.name.charAt(0) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white truncate">
                                        {{ staff.name }}
                                    </h3>
                                    <span v-if="staff.history && staff.history.length > 0" class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        ✓ Ada Riwayat KPI
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 truncate mt-0.5">
                                    NIP: {{ staff.nip }} • {{ staff.role }}
                                    <span v-if="staff.room_name" class="font-medium text-emerald-600 dark:text-emerald-400"> • {{ staff.room_name }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- KPI Score Badge -->
                        <div class="flex items-center gap-4 shrink-0">
                            <div class="text-left sm:text-right">
                                <span class="text-[11px] text-slate-400 font-medium block">Total Poin KPI</span>
                                <span :class="[
                                    'text-xl sm:text-2xl font-black block',
                                    staff.total_points >= 100 
                                        ? 'text-emerald-600 dark:text-emerald-400' 
                                        : 'text-amber-600 dark:text-amber-400'
                                ]">
                                    {{ staff.total_points }} Poin
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Summary -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 sm:gap-3 text-xs">
                        <div class="bg-emerald-50/60 dark:bg-emerald-950/20 p-3 rounded-xl border border-emerald-100 dark:border-emerald-900/30">
                            <span class="text-slate-500 dark:text-slate-400 block font-medium text-[11px]">Total Apresiasi Pujian:</span>
                            <span class="font-extrabold text-emerald-700 dark:text-emerald-300 text-sm mt-0.5 block flex items-center gap-1">
                                <TrendingUp class="h-3.5 w-3.5" />
                                <span>{{ staff.praise_count }} Laporan</span>
                            </span>
                        </div>
                        <div class="bg-rose-50/60 dark:bg-rose-950/20 p-3 rounded-xl border border-rose-100 dark:border-rose-900/30">
                            <span class="text-slate-500 dark:text-slate-400 block font-medium text-[11px]">Total Keluhan / Komplain:</span>
                            <span class="font-extrabold text-rose-700 dark:text-rose-400 text-sm mt-0.5 block flex items-center gap-1">
                                <TrendingDown class="h-3.5 w-3.5" />
                                <span>{{ staff.complaint_count }} Laporan</span>
                            </span>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-950/60 p-3 rounded-xl border border-slate-200/80 dark:border-slate-800 col-span-2 sm:col-span-1">
                            <span class="text-slate-500 dark:text-slate-400 block font-medium text-[11px]">Update Terakhir:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-200 mt-0.5 block truncate">
                                {{ staff.last_update }}
                            </span>
                        </div>
                    </div>

                    <!-- History Log Table -->
                    <div>
                        <div v-if="staff.history && staff.history.length > 0">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2.5">
                                Riwayat Transaksi Poin Terakhir:
                            </span>
                            <div class="space-y-2.5">
                                <div
                                    v-for="(log, idx) in staff.history"
                                    :key="(log.id || 'log') + '-' + idx"
                                    class="p-3 sm:p-4 rounded-xl bg-slate-50/80 dark:bg-slate-950/60 border border-slate-200/70 dark:border-slate-800/80 hover:border-emerald-500/40 dark:hover:border-emerald-500/30 transition flex items-center justify-between gap-2.5 sm:gap-4 min-w-0"
                                >
                                    <!-- Sisi Kiri: Badge Poin + Informasi Transaksi -->
                                    <div class="flex items-center gap-2.5 sm:gap-3.5 min-w-0 flex-1">
                                        <!-- Badge Poin Kotak Rounded -->
                                        <div :class="[
                                            'h-10 w-10 sm:h-11 sm:w-11 rounded-xl flex items-center justify-center font-black text-xs sm:text-sm shrink-0',
                                            log.points > 0 
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' 
                                                : 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800'
                                        ]">
                                            {{ log.points > 0 ? '+' + log.points : log.points }}
                                        </div>

                                        <!-- Informasi & Detail Catatan -->
                                        <div class="min-w-0 flex-1 space-y-0.5">
                                            <h4 class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate" :title="getCategoryLabel(log)">
                                                {{ getCategoryLabel(log) }}
                                            </h4>

                                            <p class="text-[10.5px] sm:text-[11px] text-slate-400 truncate">
                                                <span>Diverifikasi: <strong class="text-slate-600 dark:text-slate-300 font-medium">{{ log.verifier_name || 'Supervisor Kasi' }}</strong></span>
                                                <span class="mx-1.5">•</span>
                                                <span class="text-slate-400 font-medium">{{ log.date }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Sisi Kanan: Tombol Tetap Berada di Kanan -->
                                    <div class="flex items-center shrink-0">
                                        <button
                                            v-if="log.report_detail"
                                            type="button"
                                            @click="openReportDetail(log)"
                                            class="inline-flex items-center justify-center gap-1.5 p-2 sm:px-3.5 sm:py-2 rounded-xl text-xs font-bold bg-white dark:bg-slate-900 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-300 dark:hover:border-emerald-700 transition cursor-pointer select-none whitespace-nowrap"
                                            title="Buka detail bukti laporan & verifikasi"
                                        >
                                            <FileText class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                            <span class="hidden sm:inline">Bukti Laporan</span>
                                        </button>
                                        <span
                                            v-else
                                            class="text-[10px] sm:text-[11px] font-semibold text-slate-400 bg-slate-100 dark:bg-slate-800/80 px-2 py-1.5 sm:px-3 rounded-xl border border-slate-200/50 dark:border-slate-700/50 whitespace-nowrap"
                                            title="Penilaian Manual"
                                        >
                                            <span class="sm:hidden">Manual</span>
                                            <span class="hidden sm:inline">Penilaian Manual</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kondisi jika staf belum memiliki mutasi KPI -->
                        <div v-else class="p-4 rounded-xl bg-slate-50/60 dark:bg-slate-950/40 border border-dashed border-slate-200 dark:border-slate-800 text-center space-y-0.5">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                Belum ada catatan mutasi poin KPI untuk staf ini.
                            </p>
                            <p class="text-[10.5px] text-slate-400">
                                Saldo staf saat ini berada pada nilai standar awal ({{ staff.total_points }} Poin).
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Detail Bukti Laporan & Mutasi KPI -->
            <ReportDetailModal
                :show="showDetailModal"
                :report="selectedLogForModal?.report_detail"
                :kpi-info="selectedLogForModal"
                @close="showDetailModal = false"
            />
        </div>
    </AuthenticatedLayout>
</template>
