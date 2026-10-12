<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import {
    User,
    Mail,
    Phone,
    Building2,
    Shield,
    Save,
    Check,
    Hash,
    AtSign,
    UserCheck,
    Info,
    Calendar
} from '@lucide/vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
        default: false
    },
    status: {
        type: String,
        default: null
    },
    user: {
        type: Object,
        default: () => ({})
    }
});

const pageUser = computed(() => props.user?.id ? props.user : (usePage().props.auth?.user || {}));

const initialValues = {
    name: pageUser.value.name || '',
    nip: pageUser.value.nip || '',
    username: pageUser.value.username || '',
    email: pageUser.value.email || '',
    phone_number: pageUser.value.phone_number || '',
};

const form = useForm({
    name: pageUser.value.name || '',
    nip: pageUser.value.nip || '',
    username: pageUser.value.username || '',
    email: pageUser.value.email || '',
    phone_number: pageUser.value.phone_number || '',
});

const isDirty = computed(() => {
    return (
        form.name !== initialValues.name ||
        form.nip !== initialValues.nip ||
        form.username !== initialValues.username ||
        form.email !== initialValues.email ||
        form.phone_number !== initialValues.phone_number
    );
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            initialValues.name = form.name;
            initialValues.nip = form.nip;
            initialValues.username = form.username;
            initialValues.email = form.email;
            initialValues.phone_number = form.phone_number;
        }
    });
};

const roleBadgeColor = computed(() => {
    const role = (pageUser.value.role || '').toUpperCase();
    if (role.includes('ADMIN')) return 'bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border-emerald-200 dark:border-white/20';
    if (role.includes('DIREKTUR')) return 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border-purple-200 dark:border-purple-800';
    if (role.includes('KABID')) return 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border-blue-200 dark:border-blue-800';
    if (role.includes('KASI')) return 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200 dark:border-amber-800';
    return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
});
</script>

<template>
    <section class="space-y-6">
        <div>
            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <User class="h-4 w-4 text-emerald-600 dark:text-white" />
                <span>Identitas & Informasi Akun</span>
            </h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                Perbarui data profil nama, NIP pegawai, alamat email resmi, dan kontak WhatsApp Anda.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Profile Identity Card Box -->
            <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row items-stretch gap-6">
                    
                    <!-- Avatar Display Card -->
                    <div class="flex flex-col items-center justify-center shrink-0 w-full sm:w-56 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 text-center space-y-3 shadow-xs">
                        <div v-if="pageUser.profile_photo_path" class="h-20 w-20 rounded-full overflow-hidden border-2 border-emerald-500 bg-slate-100 dark:bg-slate-800 shadow-sm">
                            <img :src="pageUser.profile_photo_path" :alt="form.name" class="h-full w-full object-cover" />
                        </div>
                        <div v-else class="h-20 w-20 rounded-full bg-emerald-50 dark:bg-white/5 border-2 border-slate-200 dark:border-slate-700 flex items-center justify-center text-emerald-600 dark:text-white shadow-xs">
                            <User class="h-10 w-10" />
                        </div>
                        <div class="space-y-1 w-full">
                            <div class="text-xs font-bold text-slate-900 dark:text-white truncate max-w-[190px] mx-auto">{{ form.name || 'Pengguna' }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">{{ form.nip || 'Belum ada NIP' }}</div>
                            <div class="pt-1 flex flex-wrap items-center justify-center gap-1.5">
                                <span :class="['px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide border', roleBadgeColor]">
                                    {{ pageUser.role || 'STAFF' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Input Fields -->
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nama Lengkap -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Nama Lengkap <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <User class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                                <input
                                    type="text"
                                    v-model="form.name"
                                    required
                                    placeholder="Nama lengkap dan gelar..."
                                    class="w-full pl-10 pr-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                />
                            </div>
                            <div v-if="form.errors.name" class="text-rose-500 text-[11px] font-medium">{{ form.errors.name }}</div>
                        </div>

                        <!-- NIP / ID Pegawai -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                NIP / ID Pegawai
                            </label>
                            <div class="relative">
                                <Hash class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                                <input
                                    type="text"
                                    v-model="form.nip"
                                    placeholder="Contoh: 198501012010011001"
                                    class="w-full pl-10 pr-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                />
                            </div>
                            <div v-if="form.errors.nip" class="text-rose-500 text-[11px] font-medium">{{ form.errors.nip }}</div>
                        </div>

                        <!-- Username -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Username Login
                            </label>
                            <div class="relative">
                                <AtSign class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                                <input
                                    type="text"
                                    v-model="form.username"
                                    placeholder="Username akun..."
                                    class="w-full pl-10 pr-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                />
                            </div>
                            <div v-if="form.errors.username" class="text-rose-500 text-[11px] font-medium">{{ form.errors.username }}</div>
                        </div>

                        <!-- Email Resmi -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Alamat Email Resmi <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                                <input
                                    type="email"
                                    v-model="form.email"
                                    required
                                    placeholder="email@rsud.go.id"
                                    class="w-full pl-10 pr-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                />
                            </div>
                            <div v-if="form.errors.email" class="text-rose-500 text-[11px] font-medium">{{ form.errors.email }}</div>
                        </div>

                        <!-- Nomor WhatsApp / HP -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Nomor WhatsApp / HP
                            </label>
                            <div class="relative">
                                <Phone class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                                <input
                                    type="text"
                                    v-model="form.phone_number"
                                    placeholder="08xxxxxxxxxx"
                                    class="w-full pl-10 pr-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                                />
                            </div>
                            <div v-if="form.errors.phone_number" class="text-rose-500 text-[11px] font-medium">{{ form.errors.phone_number }}</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Unit & Role Info Read-Only Box -->
            <div class="p-4 rounded-xl bg-slate-100/70 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-3">
                    <Building2 class="h-4.5 w-4.5 text-emerald-600 dark:text-white shrink-0" />
                    <div>
                        <span class="font-bold text-slate-800 dark:text-slate-200">Penugasan Unit / Ruangan:</span>
                        <span class="ml-1.5 text-slate-600 dark:text-slate-300">{{ pageUser.room_name || 'Semua Ruangan (Global)' }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-[11px] text-slate-400 shrink-0">
                    <Info class="h-3.5 w-3.5" />
                    <span>Peran dan unit diatur oleh Administrator RS.</span>
                </div>
            </div>

            <!-- Footer Save Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                <span class="text-xs font-medium text-slate-400 dark:text-slate-500 text-center sm:text-left">
                    {{ isDirty ? 'Ada perubahan informasi profil yang belum disimpan.' : 'Informasi profil Anda sudah mutakhir.' }}
                </span>
                <button
                    type="submit"
                    :disabled="!isDirty || form.processing"
                    class="w-full sm:w-auto h-10 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition duration-150 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer shrink-0"
                >
                    <Save class="h-4 w-4" />
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Profil' }}</span>
                </button>
            </div>
        </form>
    </section>
</template>
