<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Copy, FileText, ShieldCheck } from '@lucide/vue';
import { ref } from 'vue';

const props = defineProps({
    id: {
        type: String,
        default: 'LP-2026-08-001'
    }
});

const copied = ref(false);

const copyReceipt = () => {
    navigator.clipboard.writeText(props.id);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};
</script>

<template>
    <Head title="Konfirmasi Laporan — SI-PUAS" />

    <div class="min-h-screen flex flex-col justify-between relative overflow-hidden font-sans p-4 sm:p-6 text-slate-900 dark:text-slate-100">
        <!-- Background Image with Blur & Overlay (Sama seperti Halaman Login) -->
        <div class="absolute inset-0 z-0 bg-cover bg-center filter blur-sm scale-105" style="background-image: url('/images/hospital-hero.jpg');"></div>
        <div class="absolute inset-0 z-0 bg-slate-900/50 dark:bg-slate-950/75 transition-colors duration-200"></div>

        <main class="w-full max-w-md mx-auto z-10 flex-1 flex flex-col justify-center items-center text-center my-auto">
            <div class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl w-full">
                <!-- Header Logo centered -->
                <div class="mb-4">
                    <img src="/images/logo-sidebar.png" alt="SIPUAS Logo" class="h-10 w-auto object-contain mx-auto mb-2 dark:brightness-0 dark:invert" />
                </div>

                <!-- Success Icon Badge -->
                <div class="h-20 w-20 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-full border border-emerald-200 dark:border-emerald-800 flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <CheckCircle2 class="h-10 w-10" />
                </div>

                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Laporan Berhasil Terkirim!</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                    Terima kasih atas partisipasi Anda. Laporan Anda telah dicatat oleh sistem dan diteruskan ke Kepala Ruangan terkait.
                </p>

                <!-- Receipt Code Box -->
                <div class="mt-6 bg-slate-50 dark:bg-slate-950 rounded-2xl p-4 border border-slate-200 dark:border-slate-800">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mb-1">Nomor Registrasi Laporan</span>
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-xl font-mono font-extrabold text-emerald-600 dark:text-emerald-400 tracking-wider">{{ id }}</span>
                        <button
                            @click="copyReceipt"
                            class="p-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 transition cursor-pointer"
                            title="Salin Kode Laporan"
                        >
                            <Copy class="h-4 w-4" />
                        </button>
                    </div>
                    <span v-if="copied" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block mt-1">Kode berhasil disalin!</span>
                </div>

                <div class="mt-6">
                    <Link
                        :href="route('report.create')"
                        class="w-full bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl py-3.5 px-4 font-extrabold text-xs flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 transition"
                    >
                        <FileText class="h-4 w-4" />
                        <span>Kirim Laporan Lainnya</span>
                    </Link>
                </div>
            </div>
        </main>

        <footer class="w-full max-w-md mx-auto px-4 pt-4 pb-2 text-center text-xs text-white/80 dark:text-slate-400 z-10 font-medium drop-shadow">
            &copy; {{ new Date().getFullYear() }} SIPUAS — Sistem Integrasi Pelayanan Publik & Akuntabilitas Staf
        </footer>
    </div>
</template>
