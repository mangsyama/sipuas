<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import {
    User,
    Lock,
    Bell,
    Globe,
    Shield,
    Sun,
    Moon,
    Building2,
    SlidersHorizontal,
    Languages,
    MessageSquare,
    CheckCircle2
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

const page = usePage();
const currentUser = computed(() => props.user?.id ? props.user : (page.props.auth?.user || {}));

const systemNotifyEnabled = ref(currentUser.value.system_notify_enabled !== false);
const waNotifyEnabled = ref(currentUser.value.wa_notify_enabled !== false);
const defaultLang = ref(page.props.locale || 'id');
const isDark = ref(false);

const languageOptions = [
    { value: 'id', name: 'Bahasa Indonesia' },
    { value: 'en', name: 'English' },
];

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});

const saveNotificationSettings = () => {
    router.patch(route('profile.update-notifications'), {
        system_notify_enabled: systemNotifyEnabled.value,
        wa_notify_enabled: waNotifyEnabled.value,
    }, {
        preserveScroll: true
    });
};

const toggleSystemNotify = () => {
    systemNotifyEnabled.value = !systemNotifyEnabled.value;
    saveNotificationSettings();
};

const toggleWaNotify = () => {
    waNotifyEnabled.value = !waNotifyEnabled.value;
    saveNotificationSettings();
};

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
    window.dispatchEvent(new Event('theme-changed'));
};

const handleLangChange = (selected) => {
    const targetLang = typeof selected === 'object' ? selected.value : selected;
    if (targetLang) {
        router.visit(route('lang.switch', targetLang));
    }
};

const roleBadgeColor = computed(() => {
    const role = (currentUser.value.role || '').toUpperCase();
    if (role.includes('ADMIN')) return 'bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border-emerald-200 dark:border-white/20';
    if (role.includes('DIREKTUR')) return 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border-purple-200 dark:border-purple-800';
    if (role.includes('KABID')) return 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border-blue-200 dark:border-blue-800';
    if (role.includes('KASI')) return 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200 dark:border-amber-800';
    return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
});
</script>

<template>
    <Head title="Pengaturan Profil Saya" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4 max-w-5xl mx-auto">
            
            <!-- Header Panel (Standardized across SIPUAS & Pesupeluh) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <User class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Pengaturan Profil & Akun
                            </h2>
                            <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide border', roleBadgeColor]">
                                {{ currentUser.role || 'PENGGUNA' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Kelola identitas akun, kredensial keamanan kata sandi, dan preferensi notifikasi Anda.
                        </p>
                    </div>
                </div>

                <!-- Room / Unit Tag -->
                <div v-if="currentUser.room_name" class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-300 self-start sm:self-center shrink-0">
                    <Building2 class="h-4 w-4 text-emerald-600 dark:text-white shrink-0" />
                    <span class="font-bold truncate max-w-[200px]">{{ currentUser.room_name }}</span>
                </div>
            </div>

            <!-- CARD 1: INFORMASI PROFIL & IDENTITAS AKUN -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm p-6 overflow-hidden">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    :user="currentUser"
                />
            </div>

            <!-- CARD 2: KEAMANAN & KATA SANDI -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm p-6 overflow-hidden">
                <UpdatePasswordForm />
            </div>

            <!-- CARD 3: PREFERENSI TAMPILAN & NOTIFIKASI -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm p-6 overflow-hidden space-y-6">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <SlidersHorizontal class="h-4 w-4 text-emerald-600 dark:text-white" />
                        <span>Preferensi Tampilan & Notifikasi</span>
                    </h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                        Atur tema warna antarmuka, bahasa aplikasi, dan kanal pemberitahuan notifikasi.
                    </p>
                </div>

                <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 space-y-5">
                    
                    <!-- Toggle Dark Mode -->
                    <div class="flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <Sun v-if="!isDark" class="h-4 w-4 text-amber-500" />
                                <Moon v-else class="h-4 w-4 text-white" />
                                <span>Mode Gelap (Dark Mode)</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                Aktifkan tampilan gelap yang lebih nyaman untuk mata saat bertugas malam hari.
                            </p>
                        </div>
                        <button
                            @click="toggleTheme"
                            type="button"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none shrink-0"
                            :class="isDark ? 'bg-emerald-600 dark:bg-white' : 'bg-slate-200 dark:bg-slate-800'"
                        >
                            <span
                                class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white dark:bg-slate-900 shadow ring-0 transition duration-200 ease-in-out"
                                :class="isDark ? 'translate-x-4' : 'translate-x-0'"
                            />
                        </button>
                    </div>

                    <div class="border-t border-slate-200/60 dark:border-slate-800"></div>

                    <!-- Pilihan Bahasa -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <Globe class="h-4 w-4 text-emerald-600 dark:text-white" />
                                <span>Bahasa Antarmuka</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                Pilih bahasa default yang digunakan pada teks aplikasi.
                            </p>
                        </div>
                        <SearchableSelect
                            v-model="defaultLang"
                            :options="languageOptions"
                            :searchable="false"
                            value-key="value"
                            label-key="name"
                            class="w-full sm:w-[210px] flex-shrink-0"
                            @change="handleLangChange"
                        />
                    </div>

                    <div class="border-t border-slate-200/60 dark:border-slate-800"></div>

                    <!-- Notifikasi Sistem -->
                    <div class="flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <Bell class="h-4 w-4 text-emerald-600 dark:text-white" />
                                <span>Notifikasi Dalam Aplikasi</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                Tampilkan lonceng merah di navbar dan popup toast saat ada aduan baru atau tugas dinas.
                            </p>
                        </div>
                        <button
                            @click="toggleSystemNotify"
                            type="button"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none shrink-0"
                            :class="systemNotifyEnabled ? 'bg-emerald-600 dark:bg-white' : 'bg-slate-200 dark:bg-slate-800'"
                        >
                            <span
                                class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white dark:bg-slate-900 shadow ring-0 transition duration-200 ease-in-out"
                                :class="systemNotifyEnabled ? 'translate-x-4' : 'translate-x-0'"
                            />
                        </button>
                    </div>

                    <div class="border-t border-slate-200/60 dark:border-slate-800"></div>

                    <!-- Notifikasi WhatsApp -->
                    <div class="flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <MessageSquare class="h-4 w-4 text-emerald-600 dark:text-white" />
                                <span>Notifikasi WhatsApp Gateway</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                Kirimkan pesan WhatsApp otomatis ke nomor HP Anda saat ada disposisi atau pembaruan status aduan.
                            </p>
                        </div>
                        <button
                            @click="toggleWaNotify"
                            type="button"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none shrink-0"
                            :class="waNotifyEnabled ? 'bg-emerald-600 dark:bg-white' : 'bg-slate-200 dark:bg-slate-800'"
                        >
                            <span
                                class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white dark:bg-slate-900 shadow ring-0 transition duration-200 ease-in-out"
                                :class="waNotifyEnabled ? 'translate-x-4' : 'translate-x-0'"
                            />
                        </button>
                    </div>

                </div>
            </div>

            <!-- CARD 4: HAPUS AKUN -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm p-6 overflow-hidden">
                <DeleteUserForm />
            </div>

        </div>
    </AuthenticatedLayout>
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
</style>
