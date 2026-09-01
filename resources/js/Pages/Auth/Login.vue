<script setup>
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, User, Lock } from '@lucide/vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'));
};
</script>

<template>
    <GuestLayout :hide-logo="true" :no-padding="true" max-width="max-w-[420px]">
        <Head title="Masuk" />

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
                Sistem Integrasi Pelayanan Publik & Akuntabilitas Staf
            </p>
        </div>

        <!-- Card Body / Form Area (Flat Solid) -->
        <div class="p-7 sm:p-9 bg-white dark:bg-slate-900">
            <!-- Status message -->
            <div
                v-if="status"
                class="mb-5 rounded-xl border border-emerald-500/20 bg-emerald-50 p-3 text-center text-xs font-semibold text-emerald-600 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-400"
            >
                {{ status }}
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submit" class="space-y-5">
                <!-- Username / Email Field -->
                <div class="space-y-1.5">
                    <label
                        for="username"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Username / Email
                    </label>
                    <div class="relative">
                        <User
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="username"
                            type="text"
                            name="username"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="Masukkan username atau email"
                            v-model="form.username"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-slate-950"
                        />
                    </div>
                    <InputError :message="form.errors.username" />
                </div>

                <!-- Password Field -->
                <div class="space-y-1.5">
                    <label
                        for="password"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Kata Sandi
                    </label>
                    <div class="relative">
                        <Lock
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            v-model="form.password"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-10 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-slate-950"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute top-1/2 right-3.5 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none dark:text-slate-500 dark:hover:text-white cursor-pointer"
                            :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            aria-label="Toggle password visibility"
                        >
                            <EyeOff v-if="showPassword" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <!-- Options: Remember Me & Forgot Password -->
                <div class="flex items-center justify-between pt-0.5">
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="remember"
                            name="remember"
                            v-model:checked="form.remember"
                            class="h-4 w-4 rounded-md border-slate-300 text-emerald-600 bg-white transition-colors focus:ring-0 focus:ring-offset-0 focus:outline-none dark:border-slate-700 dark:bg-slate-950 cursor-pointer"
                        />
                        <label
                            for="remember"
                            class="cursor-pointer text-xs font-medium text-slate-600 dark:text-slate-300 select-none"
                        >
                            Ingat Saya
                        </label>
                    </div>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-medium text-emerald-600 transition-colors hover:underline focus:outline-none dark:text-emerald-400"
                    >
                        Lupa kata sandi?
                    </Link>
                </div>

                <!-- Submit Button -->
                <div class="pt-1">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-white shadow-none transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        ></span>
                        <span>{{ form.processing ? 'Memproses...' : 'Masuk' }}</span>
                    </button>
                </div>
            </form>

            <!-- Footer: Register Link -->
            <div
                v-if="canRegister"
                class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400"
            >
                Belum memiliki akun?
                <Link
                    :href="route('register')"
                    class="ml-1 font-semibold text-emerald-600 hover:underline focus:outline-none dark:text-emerald-400 transition"
                >
                    Daftar di sini
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
