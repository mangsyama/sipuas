<script setup>
import { ref, computed } from 'vue';
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
    Briefcase
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

const openCreateModal = () => {
    isEditing.value = false;
    editingStaffId.value = null;
    form.reset();
    form.clearErrors();
    form.unit_id = props.units.length > 0 ? props.units[0].id : '';
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
</script>

<template>
    <Head title="Master Staf & Pegawai RS" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Users class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Master Staf & Pegawai RS
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20">
                                MASTER DATA
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Direktori profil staf pelayanan unit kerja rumah sakit, nomor induk pegawai (NIP), dan akumulasi saldo poin KPI.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="openCreateModal"
                        class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-2 shadow-sm transition cursor-pointer"
                    >
                        <UserPlus class="h-4 w-4" />
                        <span>Tambah Staf Baru</span>
                    </button>
                </div>
            </div>

            <!-- Top Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase text-slate-400">Total Staf RS</span>
                        <div class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center">
                            <Users class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ stats.total }}</div>
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold block">{{ stats.active }} Staf Aktif Bertugas</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase text-slate-400">Total Apresiasi Pujian</span>
                        <div class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <TrendingUp class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">+{{ stats.praises }}</div>
                    <span class="text-[10px] text-slate-400 font-medium block">Pujian Pasien Terverifikasi</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase text-slate-400">Total Komplain</span>
                        <div class="h-8 w-8 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                            <TrendingDown class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-rose-600 dark:text-rose-400">-{{ stats.complaints }}</div>
                    <span class="text-[10px] text-slate-400 font-medium block">Evaluasi & Pembinaan</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase text-slate-400">Skor Tertinggi RS</span>
                        <div class="h-8 w-8 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <Award class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ stats.top_score }} Poin</div>
                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold block">Leaderboard #1</span>
                </div>
            </div>

            <!-- Filters & Search Bar -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
                <div class="relative w-full md:w-80">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, NIP, atau jabatan staf..."
                        class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <!-- Unit Filter -->
                    <select
                        v-model="selectedUnit"
                        class="px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:border-emerald-500"
                    >
                        <option value="ALL">Semua Unit RS</option>
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>

                    <!-- Status Filter -->
                    <select
                        v-model="selectedStatus"
                        class="px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:border-emerald-500"
                    >
                        <option value="ALL">Semua Status</option>
                        <option value="ACTIVE">Aktif</option>
                        <option value="INACTIVE">Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- Staff Table Card -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 uppercase tracking-wider font-extrabold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-3.5 px-4">Nama Staf & NIP</th>
                                <th class="py-3.5 px-4">Unit Kerja</th>
                                <th class="py-3.5 px-4">Jabatan / Role</th>
                                <th class="py-3.5 px-4 text-center">Saldo Poin KPI</th>
                                <th class="py-3.5 px-4 text-center">Rekam Jejak</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                            <tr v-if="filteredStaff.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <Users class="h-8 w-8 mx-auto mb-2 opacity-40" />
                                    <p class="font-bold">Belum ada staf yang terdaftar sesuai filter.</p>
                                </td>
                            </tr>
                            <tr
                                v-for="staff in filteredStaff"
                                :key="staff.id"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-950/40 transition"
                            >
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center font-bold text-xs border border-emerald-100 dark:border-white/20 shrink-0">
                                            {{ staff.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white text-sm">{{ staff.name }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono">NIP: {{ staff.nip || '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                        <Building2 class="h-3.5 w-3.5 text-slate-400" />
                                        {{ staff.unit ? staff.unit.name : 'Unit Umum' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-slate-600 dark:text-slate-300 font-medium">
                                        {{ staff.role }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 font-extrabold text-xs border border-emerald-200 dark:border-emerald-800">
                                        {{ staff.total_points }} Poin
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-2 text-[11px] font-bold">
                                        <span class="text-emerald-600 dark:text-emerald-400">+{{ staff.praise_count }}</span>
                                        <span class="text-slate-300 dark:text-slate-700">/</span>
                                        <span class="text-rose-600 dark:text-rose-400">-{{ staff.complaint_count }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        type="button"
                                        @click="toggleStaffStatus(staff)"
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold transition cursor-pointer',
                                            staff.is_active
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-white/10 dark:text-white hover:opacity-80'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:opacity-80'
                                        ]"
                                    >
                                        {{ staff.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(staff)"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-slate-800 transition cursor-pointer"
                                            title="Edit Staf"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(staff)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition cursor-pointer"
                                            title="Hapus Staf"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create / Edit Staff Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <Users class="h-5 w-5 text-emerald-600 dark:text-white" />
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">
                            {{ isEditing ? 'Edit Data Staf RS' : 'Tambah Staf Pelayanan Baru' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Unit Kerja Rumah Sakit *</label>
                        <select
                            v-model="form.unit_id"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold"
                            required
                        >
                            <option value="" disabled>Pilih Unit Kerja</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <div v-if="form.errors.unit_id" class="text-rose-500 text-[10px] mt-1">{{ form.errors.unit_id }}</div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap & Gelar *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Misal: Sinta Dewi, A.Md.Farm"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs"
                            required
                        />
                        <div v-if="form.errors.name" class="text-rose-500 text-[10px] mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nomor Induk Pegawai (NIP)</label>
                            <input
                                v-model="form.nip"
                                type="text"
                                placeholder="19920412..."
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-mono text-xs"
                            />
                            <div v-if="form.errors.nip" class="text-rose-500 text-[10px] mt-1">{{ form.errors.nip }}</div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Jabatan / Role Pelayanan *</label>
                            <input
                                v-model="form.role"
                                type="text"
                                placeholder="Misal: Apoteker Pelaksana"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs"
                                required
                            />
                            <div v-if="form.errors.role" class="text-rose-500 text-[10px] mt-1">{{ form.errors.role }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Saldo Awal Poin KPI</label>
                        <input
                            v-model="form.total_points"
                            type="number"
                            placeholder="100"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-mono text-xs"
                        />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="staff_is_active"
                            v-model="form.is_active"
                            class="rounded accent-emerald-600 cursor-pointer"
                        />
                        <label for="staff_is_active" class="font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            Staf Aktif Bertugas (Dapat dikaitkan pada verifikasi laporan shift)
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-50 cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Staf') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl text-center space-y-4">
                <div class="h-14 w-14 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400 flex items-center justify-center mx-auto">
                    <AlertTriangle class="h-7 w-7" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Hapus Data Staf?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Apakah Anda yakin ingin menghapus data staf <strong class="text-slate-800 dark:text-slate-200">{{ selectedStaffForDelete?.name }}</strong>?
                    </p>
                </div>
                <div class="flex items-center gap-2 pt-2">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-50 cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold transition cursor-pointer"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
