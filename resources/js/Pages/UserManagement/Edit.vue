<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    User,
    UserCheck,
    UserX,
    Save,
    KeyRound,
    Lock,
    Shield,
    Building2,
    Mail,
    Phone,
    X
} from '@lucide/vue';

const props = defineProps({
    targetUser: {
        type: Object,
        required: true
    },
    units: {
        type: Array,
        default: () => []
    }
});

const showPhotoModal = ref(false);

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && showPhotoModal.value) {
        showPhotoModal.value = false;
    }
};

watch(showPhotoModal, (isOpen) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }
});

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
    window.removeEventListener('keydown', handleKeyDown);
});

const initialProfileState = {
    name: props.targetUser.name || '',
    nip: props.targetUser.nip || '',
    username: props.targetUser.username || '',
    email: props.targetUser.email || '',
    phone_number: props.targetUser.phone_number || '',
    role: props.targetUser.role || 'STAFF',
    unit_id: props.targetUser.unit_id || '',
    is_active: Boolean(props.targetUser.is_active),
    password: '',
    current_password: ''
};

const form = useForm({
    name: props.targetUser.name || '',
    nip: props.targetUser.nip || '',
    username: props.targetUser.username || '',
    email: props.targetUser.email || '',
    phone_number: props.targetUser.phone_number || '',
    role: props.targetUser.role || 'STAFF',
    unit_id: props.targetUser.unit_id || '',
    is_active: Boolean(props.targetUser.is_active),
    password: '',
    current_password: ''
});

const isProfileDirty = computed(() => {
    return (
        form.name !== initialProfileState.name ||
        form.nip !== initialProfileState.nip ||
        form.username !== initialProfileState.username ||
        form.email !== initialProfileState.email ||
        form.phone_number !== initialProfileState.phone_number ||
        form.role !== initialProfileState.role ||
        String(form.unit_id || '') !== String(initialProfileState.unit_id || '') ||
        form.is_active !== initialProfileState.is_active ||
        (form.password && form.password.trim().length > 0) ||
        (form.current_password && form.current_password.trim().length > 0)
    );
});

const submitUpdate = () => {
    form.put(route('users.update', { user: props.targetUser.id }), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head :title="`Ubah Data Pengguna - ${targetUser.name}`" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <div class="w-full">
                <!-- Header Panel (SAME LAYOUT AS INDEX & PESU PELUH) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <User class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Ubah Data Pengguna
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Perbarui informasi profil, peran akses, penugasan unit, dan kata sandi akun.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Card / Container -->
                <form @submit.prevent="submitUpdate" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-4">
                    <div class="p-6 space-y-8">

                        <!-- SEKSI 1: PROFIL & INFORMASI AKUN -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Data Profil & Akun Pengguna
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Informasi identitas pribadi dan kredensial login pengguna.</p>
                            </div>

                            <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row items-stretch gap-6">
                                    <!-- Avatar Card Box (No Shadow, Clean Flat Style) -->
                                    <div class="flex flex-col items-center justify-center shrink-0 w-full sm:w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center space-y-3">
                                        <div 
                                            v-if="targetUser.profile_photo_path" 
                                            @click="showPhotoModal = true"
                                            class="h-20 w-20 rounded-full overflow-hidden border-2 border-emerald-500 bg-slate-100 dark:bg-slate-800 cursor-pointer hover:opacity-90 hover:scale-105 transition transform group relative"
                                            title="Klik untuk melihat foto penuh"
                                        >
                                            <img :src="targetUser.profile_photo_path" :alt="form.name" class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else class="h-20 w-20 rounded-full bg-emerald-600 text-white flex items-center justify-center font-black text-2xl">
                                            {{ form.name ? form.name.charAt(0).toUpperCase() : 'U' }}
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-900 dark:text-white truncate max-w-[150px] mx-auto">
                                                {{ form.name || 'Pengguna' }}
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-medium mt-0.5">
                                                @{{ form.username || '-' }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Grid Input Data -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
                                        <!-- Nama Lengkap -->
                                        <div class="space-y-1.5 sm:col-span-2">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                Nama Lengkap & Gelar *
                                            </label>
                                            <input
                                                type="text"
                                                v-model="form.name"
                                                required
                                                placeholder="Contoh: dr. H. Rahmat, Sp.B"
                                                class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                            />
                                            <div v-if="form.errors.name" class="text-rose-500 text-[11px] font-medium">{{ form.errors.name }}</div>
                                        </div>

                                        <!-- NIP -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                NIP (Nomor Induk Pegawai)
                                            </label>
                                            <input
                                                type="text"
                                                v-model="form.nip"
                                                placeholder="198207102008011003"
                                                class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                            />
                                            <div v-if="form.errors.nip" class="text-rose-500 text-[11px] font-medium">{{ form.errors.nip }}</div>
                                        </div>

                                        <!-- Username -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                Username Login
                                            </label>
                                            <input
                                                type="text"
                                                v-model="form.username"
                                                placeholder="rahmat_igd"
                                                class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                            />
                                            <div v-if="form.errors.username" class="text-rose-500 text-[11px] font-medium">{{ form.errors.username }}</div>
                                        </div>

                                        <!-- Email -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                Email Resmi *
                                            </label>
                                            <input
                                                type="email"
                                                v-model="form.email"
                                                required
                                                placeholder="rahmat@rs.local"
                                                class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                            />
                                            <div v-if="form.errors.email" class="text-rose-500 text-[11px] font-medium">{{ form.errors.email }}</div>
                                        </div>

                                        <!-- Nomor Telepon -->
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                Nomor Telepon / WhatsApp
                                            </label>
                                            <input
                                                type="text"
                                                v-model="form.phone_number"
                                                placeholder="081234567891"
                                                class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                            />
                                            <div v-if="form.errors.phone_number" class="text-rose-500 text-[11px] font-medium">{{ form.errors.phone_number }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEKSI 2: PERAN AKSES & PENEMPATAN UNIT -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Peran Akses Sistem & Penempatan Unit
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Peran hak akses serta penempatan unit kerja pengguna.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Peran Akses -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Peran / Hak Akses *
                                    </label>
                                    <select
                                        v-model="form.role"
                                        required
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition cursor-pointer"
                                    >
                                        <option value="ADMINISTRATOR">Administrator</option>
                                        <option value="KABID">Kepala Bidang</option>
                                        <option value="KASI">Kepala Seksi</option>
                                        <option value="STAFF">Staf Pelaksana</option>
                                    </select>
                                    <div v-if="form.errors.role" class="text-rose-500 text-[11px] font-medium">{{ form.errors.role }}</div>
                                </div>

                                <!-- Penugasan Unit -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Penugasan Unit Kerja
                                    </label>
                                    <select
                                        v-model="form.unit_id"
                                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition cursor-pointer"
                                    >
                                        <option value="">-- Pilih Unit Kerja --</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                            {{ unit.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.unit_id" class="text-rose-500 text-[11px] font-medium">{{ form.errors.unit_id }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- SEKSI 3: STATUS KEAKTIFAN AKUN -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Status Keaktifan Akun
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Pilih status kelayakan akses login pengguna.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Option Aktif -->
                                <div
                                    @click="form.is_active = true"
                                    :class="[
                                        'p-4 rounded-xl border flex items-start gap-3 cursor-pointer transition select-none',
                                        form.is_active
                                            ? 'border-emerald-600 bg-emerald-500/10 text-emerald-900 dark:text-emerald-300 font-extrabold'
                                            : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 text-slate-500 dark:text-slate-400 hover:border-slate-300'
                                    ]"
                                >
                                    <UserCheck class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" />
                                    <div class="space-y-0.5">
                                        <div class="text-xs font-bold uppercase tracking-wide">Aktif (Verified)</div>
                                        <div class="text-[11px] font-normal leading-relaxed opacity-80">Pengguna dapat login dan mengakses fitur sistem sesuai perannya.</div>
                                    </div>
                                </div>

                                <!-- Option Nonaktif -->
                                <div
                                    @click="form.is_active = false"
                                    :class="[
                                        'p-4 rounded-xl border flex items-start gap-3 cursor-pointer transition select-none',
                                        !form.is_active
                                            ? 'border-amber-600 bg-amber-500/10 text-amber-900 dark:text-amber-300 font-extrabold'
                                            : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 text-slate-500 dark:text-slate-400 hover:border-slate-300'
                                    ]"
                                >
                                    <UserX class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                                    <div class="space-y-0.5">
                                        <div class="text-xs font-bold uppercase tracking-wide">Nonaktif / Suspended</div>
                                        <div class="text-[11px] font-normal leading-relaxed opacity-80">Akses login ditutup sementara oleh Administrator.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEKSI 4: KEAMANAN & KATA SANDI -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Keamanan Akun & Kata Sandi
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                    Kosongkan kedua kolom jika tidak ingin mengubah kata sandi akun pengguna ini.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Kolom 1: Kata Sandi Diri Sendiri (Pengaman) -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Kata Sandi Anda Saat Ini
                                    </label>
                                    <div class="relative">
                                        <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                                        <input
                                            type="password"
                                            v-model="form.current_password"
                                            placeholder="Masukkan kata sandi login Anda..."
                                            class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                        />
                                    </div>
                                    <div v-if="form.errors.current_password" class="text-rose-500 text-[11px] font-medium">{{ form.errors.current_password }}</div>
                                </div>

                                <!-- Kolom 2: Kata Sandi Baru Pengguna -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Kata Sandi Baru Pengguna
                                    </label>
                                    <div class="relative">
                                        <KeyRound class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                                        <input
                                            type="password"
                                            v-model="form.password"
                                            minlength="6"
                                            placeholder="Masukkan kata sandi baru (minimal 6 karakter)..."
                                            class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                        />
                                    </div>
                                    <div v-if="form.errors.password" class="text-rose-500 text-[11px] font-medium">{{ form.errors.password }}</div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Actions Card (Stacked on mobile, side-by-side on desktop) -->
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <span class="text-xs font-medium text-slate-400 dark:text-slate-500 text-center sm:text-left">
                            {{ isProfileDirty ? 'Ada perubahan data yang belum disimpan.' : 'Semua data pengguna telah tersinkronisasi.' }}
                        </span>
                        <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2.5 w-full sm:w-auto">
                            <Link
                                :href="route('users.index')"
                                class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center justify-center flex items-center"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                :disabled="!isProfileDirty || form.processing"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-1.5 text-center"
                            >
                                <Save class="h-4 w-4" />
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Full Photo Modal / Lightbox -->
        <div 
            v-if="showPhotoModal && targetUser.profile_photo_path" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-xs animate-fade-in" 
            @click="showPhotoModal = false"
        >
            <div class="relative max-w-lg w-full bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800" @click.stop>
                <div class="flex items-center justify-between p-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <User class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white truncate">Pasfoto - {{ form.name || targetUser.name }}</h3>
                    </div>
                    <button @click="showPhotoModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer p-1 rounded-lg">
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <div class="p-4 flex items-center justify-center bg-slate-50 dark:bg-slate-950">
                    <img :src="targetUser.profile_photo_path" :alt="form.name || targetUser.name" class="max-h-[70vh] w-auto max-w-full rounded-xl object-contain shadow-md" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
