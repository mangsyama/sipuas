<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { User, ShieldCheck } from '@lucide/vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profil Saya" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel (WA Gateway Style) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <User class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Pengaturan Profil Saya
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20">
                                AKUN SAYA
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Kelola informasi akun pengguna, pembaruan kata sandi, dan keamanan profil Anda.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content Card Containers (WA Gateway Style) -->
            <div class="space-y-4">
                <div class="p-6 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 shadow-sm rounded-2xl">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="p-6 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 shadow-sm rounded-2xl">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="p-6 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 shadow-sm rounded-2xl">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
