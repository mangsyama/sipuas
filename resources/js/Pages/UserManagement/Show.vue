<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    User, 
    ArrowLeft, 
    Building2, 
    Shield, 
    Mail, 
    Phone, 
    Calendar, 
    FileText, 
    CheckCircle2, 
    XCircle,
    Edit,
    Check,
    Award
} from '@lucide/vue';

const props = defineProps({
    targetUser: {
        type: Object,
        required: true
    }
});

const getRoleBadgeClass = (role) => {
    switch (role) {
        case 'SUPERADMIN':
            return 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800';
        case 'KABID':
            return 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800';
        case 'KASI':
            return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
        default:
            return 'bg-slate-50 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
};

const getRoleLabel = (role) => {
    switch (role) {
        case 'SUPERADMIN': return 'Superadmin IT';
        case 'KABID': return 'Kabid Pelayanan';
        case 'KASI': return 'Kepala Seksi / Ruangan';
        case 'STAFF': return 'Staf Pelaksana';
        default: return role;
    }
};
</script>

<template>
    <Head :title="`Detail Pengguna - ${targetUser.name}`" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4 max-w-5xl mx-auto">
            <!-- Header Panel -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('users.index')"
                        class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                    >
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Profil & Hak Akses Pengguna
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Informasi akun dan riwayat penugasan di sistem SIPUAS.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('users.edit', { user: targetUser.id })"
                        class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-2 shadow-sm transition"
                    >
                        <Edit class="h-4 w-4" />
                        <span>Ubah Data Pengguna</span>
                    </Link>
                </div>
            </div>

            <!-- Profile Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- User Identity Card -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm text-center space-y-4">
                    <div class="h-20 w-20 rounded-full bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center font-black text-2xl mx-auto border-2 border-emerald-200 dark:border-white/20">
                        {{ targetUser.name.charAt(0) }}
                    </div>

                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">{{ targetUser.name }}</h3>
                        <p class="text-xs text-slate-400 mt-0.5">@{{ targetUser.username }}</p>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <span :class="['inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border', getRoleBadgeClass(targetUser.role)]">
                            {{ getRoleLabel(targetUser.role) }}
                        </span>
                        <span :class="['inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-bold', targetUser.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300']">
                            <component :is="targetUser.is_active ? CheckCircle2 : XCircle" class="h-3 w-3" />
                            <span>{{ targetUser.is_active ? 'Akun Aktif' : 'Nonaktif' }}</span>
                        </span>
                    </div>
                </div>

                <!-- Detail Specification -->
                <div class="md:col-span-2 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800 pb-2">
                        Informasi Penugasan & Kontak
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="space-y-1">
                            <span class="text-slate-400 font-medium">NIP (Nomor Induk Pegawai)</span>
                            <div class="font-bold text-slate-800 dark:text-slate-200">{{ targetUser.nip }}</div>
                        </div>

                        <div class="space-y-1">
                            <span class="text-slate-400 font-medium">Unit Instalasi</span>
                            <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <Building2 class="h-4 w-4 text-emerald-600 dark:text-white" />
                                <span>{{ targetUser.unit_name }}</span>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <span class="text-slate-400 font-medium">Alamat Email</span>
                            <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <Mail class="h-4 w-4 text-slate-400" />
                                <span>{{ targetUser.email }}</span>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <span class="text-slate-400 font-medium">Nomor WhatsApp / HP</span>
                            <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <Phone class="h-4 w-4 text-slate-400" />
                                <span>{{ targetUser.phone_number }}</span>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <span class="text-slate-400 font-medium">Terdaftar Sejak</span>
                            <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <Calendar class="h-4 w-4 text-slate-400" />
                                <span>{{ targetUser.created_at }}</span>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <span class="text-slate-400 font-medium">Total Aduan Terverifikasi</span>
                            <div class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                <Award class="h-4 w-4" />
                                <span>{{ targetUser.verified_reports_count }} Tiket Selesai</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
