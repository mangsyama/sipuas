<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
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
    <GuestLayout>
        <Head title="Verifikasi Email - SIPUAS" />

        <div class="text-center mb-6">
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Verifikasi Email</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Terima kasih telah mendaftar! Sebelum memulai, silakan verifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirimkan.</p>
        </div>

        <div
            class="mb-4 text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 p-3 rounded-2xl border border-emerald-200 dark:border-emerald-800 text-center"
            v-if="verificationLinkSent"
        >
            Tautan verifikasi baru telah dikirimkan ke alamat email yang Anda berikan saat pendaftaran.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-2.5"
                    :loading="form.processing"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Mengirim Ulang...</span>
                    <span v-else>Kirim Ulang Email Verifikasi</span>
                </PrimaryButton>
            </div>

            <div class="mt-4 text-center text-xs text-slate-600 dark:text-slate-400">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="font-semibold text-emerald-600 dark:text-white hover:text-emerald-500 dark:hover:text-slate-200 hover:underline transition"
                >
                    Keluar dari Akun
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
