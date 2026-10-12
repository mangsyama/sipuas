<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Lock,
    KeyRound,
    Eye,
    EyeOff,
    Save,
    ShieldCheck,
    CheckCircle2
} from '@lucide/vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const isDirty = computed(() => {
    return Boolean(form.current_password || form.password || form.password_confirmation);
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showCurrentPassword.value = false;
            showNewPassword.value = false;
            showConfirmPassword.value = false;
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section class="space-y-6">
        <div>
            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <Lock class="h-4 w-4 text-emerald-600 dark:text-white" />
                <span>Keamanan Akun & Kata Sandi</span>
            </h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                Gunakan kata sandi yang kuat dan unik (minimal 8 karakter) untuk melindungi akun Anda.
            </p>
        </div>

        <form @submit.prevent="updatePassword" class="space-y-6">
            <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    
                    <!-- Kata Sandi Saat Ini -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                            <input
                                id="current_password"
                                ref="currentPasswordInput"
                                v-model="form.current_password"
                                :type="showCurrentPassword ? 'text' : 'password'"
                                required
                                placeholder="Masukkan kata sandi saat ini..."
                                autocomplete="current-password"
                                class="w-full pl-10 pr-10 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                            />
                            <button
                                type="button"
                                @click="showCurrentPassword = !showCurrentPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 rounded cursor-pointer"
                                tabindex="-1"
                            >
                                <EyeOff v-if="showCurrentPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <div v-if="form.errors.current_password" class="text-rose-500 text-[11px] font-medium">{{ form.errors.current_password }}</div>
                    </div>

                    <!-- Kata Sandi Baru -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <KeyRound class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                            <input
                                id="password"
                                ref="passwordInput"
                                v-model="form.password"
                                :type="showNewPassword ? 'text' : 'password'"
                                required
                                minlength="8"
                                placeholder="Minimal 8 karakter..."
                                autocomplete="new-password"
                                class="w-full pl-10 pr-10 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                            />
                            <button
                                type="button"
                                @click="showNewPassword = !showNewPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 rounded cursor-pointer"
                                tabindex="-1"
                            >
                                <EyeOff v-if="showNewPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <div v-if="form.errors.password" class="text-rose-500 text-[11px] font-medium">{{ form.errors.password }}</div>
                    </div>

                    <!-- Konfirmasi Kata Sandi Baru -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Ulangi Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <KeyRound class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                required
                                minlength="8"
                                placeholder="Ketik ulang kata sandi baru..."
                                autocomplete="new-password"
                                class="w-full pl-10 pr-10 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                            />
                            <button
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 rounded cursor-pointer"
                                tabindex="-1"
                            >
                                <EyeOff v-if="showConfirmPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <div v-if="form.errors.password_confirmation" class="text-rose-500 text-[11px] font-medium">{{ form.errors.password_confirmation }}</div>
                    </div>

                </div>

                <!-- Password hint -->
                <div class="p-3 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 flex items-center gap-2.5 text-[11px] text-emerald-800 dark:text-emerald-300">
                    <ShieldCheck class="h-4 w-4 shrink-0 text-emerald-600 dark:text-white" />
                    <span>Disarankan mengombinasikan huruf besar, huruf kecil, angka, dan simbol untuk keamanan maksimal.</span>
                </div>
            </div>

            <!-- Footer Save Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                <span class="text-xs font-medium text-slate-400 dark:text-slate-500 text-center sm:text-left">
                    {{ isDirty ? 'Pastikan kata sandi baru sudah Anda hafal sebelum menyimpan.' : 'Kata sandi saat ini aktif dan aman.' }}
                </span>
                <button
                    type="submit"
                    :disabled="!isDirty || form.processing"
                    class="w-full sm:w-auto h-10 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition duration-150 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer shrink-0"
                >
                    <KeyRound class="h-4 w-4" />
                    <span>{{ form.processing ? 'Memperbarui...' : 'Perbarui Kata Sandi' }}</span>
                </button>
            </div>
        </form>
    </section>
</template>
