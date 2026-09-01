<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Mail } from '@lucide/vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    // Feature currently disabled
};
</script>

<template>
    <GuestLayout :hide-logo="true" :no-padding="true" max-width="max-w-[420px]">
        <Head title="Lupa Kata Sandi" />

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
                Layanan pemulihan dan atur ulang kata sandi akun SIPUAS
            </p>
        </div>

        <!-- Card Body / Form Area (Flat Solid) -->
        <div class="p-7 sm:p-9 bg-white dark:bg-slate-900">
            <!-- Warning Alert Banner for Disabled Feature -->
            <div class="mb-5 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-900/60 flex items-start gap-3 shadow-none">
                <AlertTriangle class="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                <div class="text-xs text-amber-800 dark:text-amber-200 leading-relaxed font-medium">
                    Fitur atur ulang kata sandi mandiri via email sementara belum tersedia. Silakan hubungi Administrator sistem untuk bantuan pemulihan kata sandi akun Anda.
                </div>
            </div>

            <div
                v-if="status"
                class="mb-5 rounded-xl border border-emerald-500/20 bg-emerald-50 p-3 text-center text-xs font-semibold text-emerald-600 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-400"
            >
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="space-y-1.5">
                    <label
                        for="email"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Email
                    </label>
                    <div class="relative">
                        <Mail
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="email"
                            type="email"
                            name="email"
                            disabled
                            autocomplete="username"
                            placeholder="nama@email.com"
                            v-model="form.email"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-100 pl-10 pr-4 text-sm text-slate-500 placeholder:text-slate-400 cursor-not-allowed opacity-75 focus:outline-none dark:border-slate-800 dark:bg-slate-950/50 dark:text-slate-400"
                        />
                    </div>
                    <InputError :message="form.errors.email" />
                </div>

                <div class="pt-1">
                    <button
                        type="submit"
                        disabled
                        class="flex h-11 w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-white opacity-50 shadow-none focus:outline-none"
                    >
                        Kirim Tautan Atur Ulang
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
                Kembali ke halaman
                <Link
                    :href="route('login')"
                    class="ml-1 font-semibold text-emerald-600 hover:underline focus:outline-none dark:text-emerald-400 transition"
                >
                    Masuk
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
