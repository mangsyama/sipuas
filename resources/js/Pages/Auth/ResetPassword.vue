<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Mail, Lock } from '@lucide/vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout :hide-logo="true" :no-padding="true" max-width="max-w-[440px]">
        <Head title="Atur Ulang Kata Sandi" />

        <!-- Card Header Section for Logo & Subtitle (Flat Solid) -->
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

            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
                Silakan masukkan kata sandi baru untuk akun Anda
            </p>
        </div>

        <!-- Card Body / Form Area (Flat Solid) -->
        <div class="p-7 sm:p-9 bg-white dark:bg-slate-900">
            <form @submit.prevent="submit" class="space-y-5">
                <!-- Email Field -->
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
                            required
                            :readonly="!!props.email"
                            autocomplete="username"
                            placeholder="nama@email.com"
                            v-model="form.email"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-slate-950 read-only:opacity-80 read-only:cursor-not-allowed"
                        />
                    </div>
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Password Field -->
                <div class="space-y-1.5">
                    <label
                        for="password"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Kata Sandi Baru
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
                            autocomplete="new-password"
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

                <!-- Password Confirmation Field -->
                <div class="space-y-1.5">
                    <label
                        for="password_confirmation"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Konfirmasi Kata Sandi Baru
                    </label>
                    <div class="relative">
                        <Lock
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="password_confirmation"
                            :type="showPasswordConfirmation ? 'text' : 'password'"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                            v-model="form.password_confirmation"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-10 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-slate-950"
                        />
                        <button
                            type="button"
                            @click="showPasswordConfirmation = !showPasswordConfirmation"
                            class="absolute top-1/2 right-3.5 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none dark:text-slate-500 dark:hover:text-white cursor-pointer"
                            :title="showPasswordConfirmation ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            aria-label="Toggle password confirmation visibility"
                        >
                            <EyeOff v-if="showPasswordConfirmation" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-white shadow-none transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        ></span>
                        <span>{{ form.processing ? 'Memproses Perubahan...' : 'Atur Ulang Kata Sandi' }}</span>
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
