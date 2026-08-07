<script setup>
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff } from '@lucide/vue';

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
    <GuestLayout>
        <Head title="Masuk SIPUAS" />

        <div v-if="status" class="mb-4 font-medium text-sm text-emerald-600 dark:text-emerald-400 text-center">
            {{ status }}
        </div>

        <div class="text-center mb-6">
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Masuk ke Akun Anda</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Silakan masuk dengan akun Anda untuk melanjutkan.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="username" value="Username / Email" />

                <TextInput
                    id="username"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.username"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Masukkan username atau email"
                />

                <InputError class="mt-2" :message="form.errors.username" />
            </div>

            <div>
                <InputLabel for="password" value="Kata Sandi" />

                <div class="relative mt-1">
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="block w-full pr-10"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />

                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition focus:outline-none"
                        :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                    >
                        <EyeOff v-if="showPassword" class="h-4 w-4" />
                        <Eye v-else class="h-4 w-4" />
                    </button>
                </div>

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer select-none">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-xs text-slate-600 dark:text-slate-400">Ingat Saya</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-xs font-medium text-emerald-600 dark:text-white hover:text-emerald-500 dark:hover:text-slate-200 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition"
                >
                    Lupa kata sandi?
                </Link>
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-2.5"
                    :loading="form.processing"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Memproses Login...</span>
                    <span v-else>Masuk</span>
                </PrimaryButton>
            </div>

            <div v-if="canRegister" class="mt-4 text-center text-xs text-slate-600 dark:text-slate-400">
                Belum memiliki akun?
                <Link
                    :href="route('register')"
                    class="font-semibold text-emerald-600 dark:text-white hover:text-emerald-500 dark:hover:text-slate-200 hover:underline ms-1 transition"
                >
                    Daftar di sini
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
