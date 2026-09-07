<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { User, Phone, CheckCircle2, ArrowRight, Send, AlertCircle } from '@lucide/vue';

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    account: '',
});

const submit = () => {
    form.post(route('password.email'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('account');
        },
    });
};
</script>

<template>
    <GuestLayout :hide-logo="true" :no-padding="true" max-width="max-w-[440px]">
        <Head title="Lupa Kata Sandi" />

        <!-- Card Header Section for Logo & Subtitle -->
        <div
            class="border-b border-slate-200 bg-slate-50 p-7 text-center dark:border-slate-800 dark:bg-slate-950 sm:p-9"
        >
            <div class="inline-flex items-center justify-center select-none">
                <img
                    src="/images/logo-sidebar.png"
                    alt="SIPUAS Logo"
                    class="h-9 sm:h-10 w-auto object-contain mx-auto dark:brightness-0 dark:invert pointer-events-none"
                />
            </div>

            <h2 class="mt-3 text-base font-bold text-slate-800 dark:text-slate-100">
                Atur Ulang Kata Sandi
            </h2>

            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-medium">
                Tautan pemulihan akan dikirimkan langsung ke nomor WhatsApp akun Anda
            </p>
        </div>

        <!-- Card Body / Form Area -->
        <div class="p-7 sm:p-9 bg-white dark:bg-slate-900">
            <!-- Success Notification Alert -->
            <div
                v-if="status"
                class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 flex items-start gap-3 text-left"
            >
                <CheckCircle2 class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                <div class="text-xs text-emerald-800 dark:text-emerald-200 leading-relaxed font-medium">
                    {{ status }}
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-5">
                <div class="space-y-1.5">
                    <label
                        for="account"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Username, NIP, atau No. WhatsApp
                    </label>
                    <div class="relative">
                        <User
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="account"
                            type="text"
                            name="account"
                            required
                            autofocus
                            placeholder="Contoh: 19850101..., username, atau 0812..."
                            v-model="form.account"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-white pl-10 pr-4 text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 transition"
                        />
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Masukkan salah satu identitas yang terdaftar pada akun Anda.
                    </p>
                    <InputError :message="form.errors.account" />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-xs text-white shadow-sm hover:bg-emerald-500 active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer"
                    >
                        <Send v-if="!form.processing" class="h-4 w-4" />
                        <span v-if="form.processing">Mengirim Tautan...</span>
                        <span v-else>Kirim Tautan via WhatsApp</span>
                    </button>
                </div>
            </form>

            <div class="mt-7 pt-5 border-t border-slate-100 dark:border-slate-800 text-center text-xs text-slate-500 dark:text-slate-400">
                Ingat kata sandi Anda?
                <Link
                    :href="route('login')"
                    class="ml-1 font-semibold text-emerald-600 hover:text-emerald-500 hover:underline focus:outline-none dark:text-emerald-400 transition"
                >
                    Kembali Masuk
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
