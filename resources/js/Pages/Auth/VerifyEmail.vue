<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout :hide-logo="true" :no-padding="true" max-width="max-w-[420px]">
        <Head title="Verifikasi Email" />

        <!-- Card Header Section for Logo & Subtitle (Flat Solid) -->
        <div
            class="border-b border-slate-200 bg-slate-50 p-7 text-center dark:border-slate-800 dark:bg-slate-950 sm:p-9"
        >
            <Link
                href="/"
                class="inline-flex items-center justify-center transition-transform hover:scale-105 cursor-pointer"
                title="SIPUAS"
            >
                <img
                    src="/images/logo-sidebar.png"
                    alt="SIPUAS Logo"
                    class="h-9 sm:h-10 w-auto object-contain mx-auto dark:brightness-0 dark:invert"
                />
            </Link>

            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
                Silakan verifikasi alamat email Anda untuk mengaktifkan akun
            </p>
        </div>

        <!-- Card Body / Form Area (Flat Solid) -->
        <div class="p-7 sm:p-9 bg-white dark:bg-slate-900">
            <p class="mb-5 text-xs text-slate-600 dark:text-slate-400 leading-relaxed text-center">
                Terima kasih telah mendaftar! Sebelum memulai, silakan klik tautan verifikasi yang baru saja kami kirimkan ke email Anda.
            </p>

            <div
                class="mb-5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 p-3.5 rounded-xl border border-emerald-500/20 dark:border-emerald-500/30 text-center"
                v-if="verificationLinkSent"
            >
                Tautan verifikasi baru telah berhasil dikirimkan ke alamat email Anda.
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-white shadow-none transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        ></span>
                        <span>{{ form.processing ? 'Mengirim Ulang...' : 'Kirim Ulang Email Verifikasi' }}</span>
                    </button>
                </div>

                <div class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="font-semibold text-emerald-600 hover:underline focus:outline-none dark:text-emerald-400 transition cursor-pointer"
                    >
                        Keluar dari Akun
                    </Link>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
