<script setup>
import { ref, computed } from 'vue';
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
    Stethoscope
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
            medik: 0,
            non_medik: 0,
            high_risk: 0,
            active: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            category: '',
            risk_status: '',
            status: ''
        })
    }
});

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || 'ALL');
const selectedRisk = ref(props.filters.risk_status || 'ALL');
const selectedStatus = ref(props.filters.status || 'ALL');

// Modal States
const showModal = ref(false);
const isEditing = ref(false);
const editingUnitId = ref(null);
const showDeleteModal = ref(false);
const selectedUnitForDelete = ref(null);

const form = useForm({
    code: '',
    name: '',
    category: 'MEDIK',
    risk_status: 'LOW_RISK',
    pic_name: '',
    phone_contact: '',
    is_active: true
});

const filteredUnits = computed(() => {
    return props.units.filter(u => {
        const matchesSearch = !searchQuery.value || 
            u.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            u.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (u.pic_name && u.pic_name.toLowerCase().includes(searchQuery.value.toLowerCase()));

        const matchesCategory = selectedCategory.value === 'ALL' || u.category === selectedCategory.value;
        const matchesRisk = selectedRisk.value === 'ALL' || u.risk_status === selectedRisk.value;
        const matchesStatus = selectedStatus.value === 'ALL' || 
            (selectedStatus.value === 'ACTIVE' && u.is_active) ||
            (selectedStatus.value === 'INACTIVE' && !u.is_active);

        return matchesSearch && matchesCategory && matchesRisk && matchesStatus;
    });
});

const openCreateModal = () => {
    isEditing.value = false;
    editingUnitId.value = null;
    form.reset();
    form.clearErrors();
    form.category = 'MEDIK';
    form.risk_status = 'LOW_RISK';
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
    form.risk_status = unit.risk_status;
    form.pic_name = unit.pic_name || '';
    form.phone_contact = unit.phone_contact || '';
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
</script>

<template>
    <Head title="Master Unit & Ruangan RS" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Building2 class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Master Unit & Ruangan RS
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20">
                                MASTER DATA
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Direktori seluruh instalasi pelayanan medik & non-medik, pemetaan zona risiko, dan penanggung jawab unit.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="openCreateModal"
                        class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-2 shadow-sm transition cursor-pointer"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Tambah Unit Baru</span>
                    </button>
                </div>
            </div>

            <!-- Top Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase text-slate-400">Total Unit RS</span>
                        <div class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center">
                            <Building2 class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ stats.total }}</div>
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold block">{{ stats.active }} Unit Aktif</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase text-slate-400">Pelayanan Medik</span>
                        <div class="h-8 w-8 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <Stethoscope class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ stats.medik }}</div>
                    <span class="text-[10px] text-slate-400 font-medium block">Klinis & Perawatan Pasien</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase text-slate-400">Non-Medik & Sarpras</span>
                        <div class="h-8 w-8 rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <Activity class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-purple-600 dark:text-purple-400">{{ stats.non_medik }}</div>
                    <span class="text-[10px] text-slate-400 font-medium block">Administrasi & Penunjang</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase text-slate-400">Unit High Risk</span>
                        <div class="h-8 w-8 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                            <ShieldAlert class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-rose-600 dark:text-rose-400">{{ stats.high_risk }}</div>
                    <span class="text-[10px] text-rose-600 dark:text-rose-400 font-semibold block">Prioritas Pengawasan</span>
                </div>
            </div>

            <!-- Filters & Search Bar -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
                <div class="relative w-full md:w-80">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari kode atau nama unit..."
                        class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <!-- Category Filter -->
                    <select
                        v-model="selectedCategory"
                        class="px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:border-emerald-500"
                    >
                        <option value="ALL">Semua Kategori</option>
                        <option value="MEDIK">Medik</option>
                        <option value="NON_MEDIK">Non-Medik</option>
                    </select>

                    <!-- Risk Filter -->
                    <select
                        v-model="selectedRisk"
                        class="px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:border-emerald-500"
                    >
                        <option value="ALL">Semua Tingkat Risiko</option>
                        <option value="HIGH_RISK">Tinggi (High Risk)</option>
                        <option value="MEDIUM_RISK">Sedang (Medium Risk)</option>
                        <option value="LOW_RISK">Rendah (Low Risk)</option>
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

            <!-- Units Table Card -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 uppercase tracking-wider font-extrabold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-3.5 px-4">Kode & Nama Unit</th>
                                <th class="py-3.5 px-4">Kategori Pelayanan</th>
                                <th class="py-3.5 px-4 text-center">Tingkat Risiko</th>
                                <th class="py-3.5 px-4">Penanggung Jawab (PIC)</th>
                                <th class="py-3.5 px-4 text-center">Staf</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                            <tr v-if="filteredUnits.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <Building2 class="h-8 w-8 mx-auto mb-2 opacity-40" />
                                    <p class="font-bold">Tidak ada unit kerja yang sesuai filter.</p>
                                </td>
                            </tr>
                            <tr
                                v-for="unit in filteredUnits"
                                :key="unit.id"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-950/40 transition"
                            >
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                            {{ unit.code }}
                                        </span>
                                        <span class="font-bold text-slate-900 dark:text-white text-sm">
                                            {{ unit.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase border',
                                            unit.category === 'MEDIK'
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800'
                                                : 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800'
                                        ]"
                                    >
                                        {{ unit.category }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase',
                                            unit.risk_status === 'HIGH_RISK'
                                                ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                                : unit.risk_status === 'MEDIUM_RISK'
                                                ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                                : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                        ]"
                                    >
                                        {{ unit.risk_status === 'HIGH_RISK' ? 'Tinggi' : unit.risk_status === 'MEDIUM_RISK' ? 'Sedang' : 'Rendah' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div v-if="unit.pic_name" class="space-y-0.5">
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ unit.pic_name }}</div>
                                        <div v-if="unit.phone_contact" class="text-[11px] text-slate-400 font-mono flex items-center gap-1">
                                            <Phone class="h-3 w-3" /> {{ unit.phone_contact }}
                                        </div>
                                    </div>
                                    <span v-else class="text-slate-400 italic text-[11px]">Belum ditentukan</span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                                        {{ unit.staff_count ?? 0 }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        type="button"
                                        @click="toggleUnitStatus(unit)"
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold transition cursor-pointer',
                                            unit.is_active
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-white/10 dark:text-white hover:opacity-80'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:opacity-80'
                                        ]"
                                    >
                                        {{ unit.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(unit)"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-slate-800 transition cursor-pointer"
                                            title="Edit Unit"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(unit)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition cursor-pointer"
                                            title="Hapus Unit"
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

        <!-- Create / Edit Unit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <Building2 class="h-5 w-5 text-emerald-600 dark:text-white" />
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">
                            {{ isEditing ? 'Edit Data Unit Kerja' : 'Tambah Unit Kerja Baru' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-3.5 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Kode Unit *</label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="Misal: FARMASI"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-mono text-xs uppercase"
                                required
                            />
                            <div v-if="form.errors.code" class="text-rose-500 text-[10px] mt-1">{{ form.errors.code }}</div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Kategori Pelayanan *</label>
                            <select
                                v-model="form.category"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold"
                            >
                                <option value="MEDIK">MEDIK</option>
                                <option value="NON_MEDIK">NON_MEDIK</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Unit Kerja *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Misal: Instalasi Farmasi & Depo Obat"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs"
                            required
                        />
                        <div v-if="form.errors.name" class="text-rose-500 text-[10px] mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Tingkat Risiko Pelayanan *</label>
                        <select
                            v-model="form.risk_status"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold"
                        >
                            <option value="LOW_RISK">Rendah (Low Risk)</option>
                            <option value="MEDIUM_RISK">Sedang (Medium Risk)</option>
                            <option value="HIGH_RISK">Tinggi (High Risk)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nama PIC / Penanggung Jawab</label>
                            <input
                                v-model="form.pic_name"
                                type="text"
                                placeholder="Nama kepala ruangan..."
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">No. Telepon / Kontak</label>
                            <input
                                v-model="form.phone_contact"
                                type="text"
                                placeholder="08..."
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-mono text-xs"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="is_active"
                            v-model="form.is_active"
                            class="rounded accent-emerald-600 cursor-pointer"
                        />
                        <label for="is_active" class="font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            Unit Kerja Aktif (Muncul pada Formulir Laporan Publik)
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
                            {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Unit') }}
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
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Hapus Unit Kerja?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Apakah Anda yakin ingin menghapus unit <strong class="text-slate-800 dark:text-slate-200">{{ selectedUnitForDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
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
