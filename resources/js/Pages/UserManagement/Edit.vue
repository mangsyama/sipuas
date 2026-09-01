<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    User, 
    ArrowLeft, 
    Building2, 
    Shield, 
    Mail, 
    Phone, 
    Save, 
    KeyRound 
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

const form = useForm({
    name: props.targetUser.name,
    username: props.targetUser.username,
    nip: props.targetUser.nip,
    email: props.targetUser.email,
    phone_number: props.targetUser.phone_number,
    role: props.targetUser.role,
    unit_id: props.targetUser.unit_id || '',
    password: ''
});

const submitUpdate = () => {
    form.put(route('users.update', { user: props.targetUser.id }));
};
</script>

<template>
    <Head :title="`Ubah Data Pengguna - ${targetUser.name}`" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4 max-w-4xl mx-auto">
            <!-- Header Panel -->
            <div class="flex items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('users.index')"
                        class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                    >
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Ubah Data Pengguna
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Perbarui profil, unit penugasan, dan kata sandi akun.</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <form @submit.prevent="submitUpdate" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap & Gelar *</label>
                        <input
                            type="text"
                            v-model="form.name"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">NIP (Nomor Induk Pegawai)</label>
                            <input
                                type="text"
                                v-model="form.nip"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Username</label>
                            <input
                                type="text"
                                v-model="form.username"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Email Resmi *</label>
                            <input
                                type="email"
                                v-model="form.email"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">No. Telepon / WhatsApp</label>
                            <input
                                type="text"
                                v-model="form.phone_number"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Peran / Hak Akses *</label>
                            <select
                                v-model="form.role"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option value="KASI">Kepala Seksi / Ruangan (Kasi)</option>
                                <option value="KABID">Kabid Pelayanan</option>
                                <option value="SUPERADMIN">Superadmin IT</option>
                                <option value="STAFF">Staf Pelaksana</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Penugasan Unit Instalasi</label>
                            <select
                                v-model="form.unit_id"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option value="">Semua Unit (Global / Kabid / Admin)</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                    {{ unit.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 dark:border-slate-800 pt-4">
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Kata Sandi Baru (Kosongkan jika tidak ingin mengubah)
                        </label>
                        <input
                            type="password"
                            v-model="form.password"
                            minlength="6"
                            placeholder="Minimal 6 karakter"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
                        <Link
                            :href="route('users.index')"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold flex items-center gap-2 transition disabled:opacity-50"
                        >
                            <Save class="h-4 w-4" />
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
