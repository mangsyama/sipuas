<script setup>
import { ref, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Trash2,
    ShieldAlert,
    Lock,
    X,
    AlertTriangle
} from '@lucide/vue';
import Modal from '@/Components/Modal.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <div>
            <h3 class="text-sm font-extrabold text-rose-600 dark:text-rose-400 uppercase tracking-wider flex items-center gap-2">
                <Trash2 class="h-4 w-4 text-rose-600 dark:text-rose-400" />
                <span>Zona Berbahaya: Hapus Akun</span>
            </h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                Tindakan ini akan menghapus akun Anda secara permanen. Pastikan Anda sudah yakin sebelum melanjutkan.
            </p>
        </div>

        <div class="bg-rose-50/50 dark:bg-rose-950/20 border border-rose-200/70 dark:border-rose-900/40 rounded-2xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5 max-w-xl">
                <div class="h-10 w-10 rounded-xl bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 mt-0.5">
                    <ShieldAlert class="h-5 w-5" />
                </div>
                <div class="space-y-0.5">
                    <div class="text-xs font-bold text-slate-900 dark:text-white">Penghapusan Akun Permanen</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        Setelah akun dihapus, sesi login Anda akan dihentikan dan seluruh hak akses dicabut secara permanen.
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="confirmUserDeletion"
                class="w-full sm:w-auto h-10 px-5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs transition duration-150 shadow-sm flex items-center justify-center gap-2 cursor-pointer shrink-0"
            >
                <Trash2 class="h-4 w-4" />
                <span>Hapus Akun Saya</span>
            </button>
        </div>

        <!-- Confirmation Modal -->
        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 rounded-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2.5 text-rose-600 dark:text-rose-400">
                        <AlertTriangle class="h-5 w-5" />
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Konfirmasi Hapus Akun</h3>
                    </div>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1 rounded-lg">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    Apakah Anda benar-benar yakin ingin menghapus akun Anda? Silakan masukkan kata sandi login Anda saat ini untuk mengonfirmasi tindakan ini.
                </p>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Kata Sandi Anda <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                        <input
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            placeholder="Masukkan kata sandi..."
                            @keyup.enter="deleteUser"
                            class="w-full pl-10 pr-3.5 py-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 transition"
                        />
                    </div>
                    <div v-if="form.errors.password" class="text-rose-500 text-[11px] font-medium">{{ form.errors.password }}</div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="closeModal"
                        class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="deleteUser"
                        :disabled="form.processing"
                        class="h-10 px-5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-1.5"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span>{{ form.processing ? 'Menghapus...' : 'Ya, Hapus Akun' }}</span>
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
