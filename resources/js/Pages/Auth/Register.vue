<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff } from '@lucide/vue';

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    name: '',
    username: '',
    nip: '',
    phone_number: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Pendaftaran Akun SIPUAS" />

        <div class="text-center mb-6">
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Daftar Akun Baru</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Lengkapi data diri Anda untuk membuat akun SIPUAS.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- NAMA LENGKAP -->
            <div>
                <InputLabel for="name" value="Nama Lengkap" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Masukkan nama lengkap Anda"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- USERNAME & NIP -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="username" value="Username" />

                    <TextInput
                        id="username"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.username"
                        required
                        autocomplete="username"
                        placeholder="Contoh: user123"
                    />

                    <InputError class="mt-2" :message="form.errors.username" />
                </div>

                <div>
                    <InputLabel for="nip" value="NIP" />

                    <TextInput
                        id="nip"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.nip"
                        required
                        placeholder="Masukkan NIP Anda"
                    />

                    <InputError class="mt-2" :message="form.errors.nip" />
                </div>
            </div>

            <!-- NO HP & EMAIL -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="phone_number" value="No. HP / WhatsApp" />

                    <TextInput
                        id="phone_number"
                        type="tel"
                        class="mt-1 block w-full"
                        v-model="form.phone_number"
                        placeholder="081234567890"
                    />

                    <InputError class="mt-2" :message="form.errors.phone_number" />
                </div>

                <div>
                    <InputLabel for="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autocomplete="email"
                        placeholder="nama@email.com"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
            </div>

            <!-- KATA SANDI & KONFIRMASI KATA SANDI -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="password" value="Kata Sandi" />

                    <div class="relative mt-1">
                        <TextInput
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full pr-10"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
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

                <div>
                    <InputLabel for="password_confirmation" value="Konfirmasi Kata Sandi" />

                    <div class="relative mt-1">
                        <TextInput
                            id="password_confirmation"
                            :type="showPasswordConfirmation ? 'text' : 'password'"
                            class="block w-full pr-10"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />

                        <button
                            type="button"
                            @click="showPasswordConfirmation = !showPasswordConfirmation"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition focus:outline-none"
                            :title="showPasswordConfirmation ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        >
                            <EyeOff v-if="showPasswordConfirmation" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-2.5"
                    :loading="form.processing"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Memproses Pendaftaran...</span>
                    <span v-else>Daftar Akun</span>
                </PrimaryButton>
            </div>

            <div class="mt-4 text-center text-xs text-slate-600 dark:text-slate-400">
                Sudah memiliki akun?
                <Link
                    :href="route('login')"
                    class="font-semibold text-emerald-600 dark:text-white hover:text-emerald-500 dark:hover:text-slate-200 hover:underline ms-1 transition"
                >
                    Masuk di sini
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
