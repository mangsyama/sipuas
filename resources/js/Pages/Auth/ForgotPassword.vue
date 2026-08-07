<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle } from '@lucide/vue';

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
    <GuestLayout>
        <Head title="Lupa Kata Sandi - SIPUAS" />

        <div class="text-center mb-6">
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Lupa Kata Sandi</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Layanan pemulihan dan atur ulang kata sandi akun SIPUAS.</p>
        </div>

        <!-- Warning Alert Banner for Disabled Feature -->
        <div class="mb-6 p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/50 flex items-start gap-3 shadow-xs">
            <AlertTriangle class="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
            <div class="text-xs text-amber-800 dark:text-amber-200 leading-relaxed font-medium">
                Fitur atur ulang kata sandi mandiri via email sementara belum tersedia. Silakan hubungi Administrator sistem untuk bantuan pemulihan kata sandi akun Anda.
            </div>
        </div>

        <div
            v-if="status"
            class="mb-4 font-medium text-sm text-emerald-600 dark:text-emerald-400 text-center"
        >
            {{ status }}
        </div>

        <form @submit.prevent class="space-y-4">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full opacity-60 cursor-not-allowed"
                    v-model="form.email"
                    disabled
                    autocomplete="username"
                    placeholder="nama@email.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-2.5 opacity-50 cursor-not-allowed"
                    disabled
                >
                    Kirim Tautan Atur Ulang
                </PrimaryButton>
            </div>

            <div class="mt-4 text-center text-xs text-slate-600 dark:text-slate-400">
                Kembali ke halaman
                <Link
                    :href="route('login')"
                    class="font-semibold text-emerald-600 dark:text-white hover:text-emerald-500 dark:hover:text-slate-200 hover:underline ms-1 transition"
                >
                    Masuk
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
