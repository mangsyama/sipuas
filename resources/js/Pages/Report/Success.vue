<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Copy, FileText, Search, AlertCircle, ArrowLeft } from '@lucide/vue';
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
    <Head title="Konfirmasi Laporan" />

    <div class="min-h-screen flex flex-col justify-between sm:justify-center items-center relative overflow-x-hidden font-sans bg-white dark:bg-slate-900 sm:bg-transparent p-0 sm:p-6 text-slate-900 dark:text-slate-100">
        <!-- Background Image with Blur & Dark Overlay (Desktop/Tablet Fixed) -->
        <div class="hidden sm:block fixed inset-0 z-0 bg-cover bg-center bg-no-repeat filter blur-xs sm:blur-sm scale-105 pointer-events-none" style="background-image: url('/images/hospital-hero.jpg');"></div>
        <div class="hidden sm:block fixed inset-0 z-0 bg-slate-900/55 dark:bg-slate-950/80 transition-colors duration-200 pointer-events-none"></div>

        <!-- Ambient Glow Elements matching SIPUAS emerald theme (Desktop/Tablet) -->
        <div class="pointer-events-none hidden sm:block fixed top-[-15%] left-[-10%] h-[70vw] w-[70vw] max-w-[800px] max-h-[800px] rounded-full bg-emerald-600/[0.08] blur-[130px] dark:bg-emerald-600/20"></div>
        <div class="pointer-events-none hidden sm:block fixed right-[-10%] bottom-[-10%] h-[60vw] w-[60vw] max-w-[700px] max-h-[700px] rounded-full bg-teal-600/[0.08] blur-[120px] dark:bg-teal-900/30"></div>

        <main class="w-full sm:max-w-[420px] mx-auto z-10 flex flex-col flex-1 sm:flex-initial my-auto">
            <!-- Solid Container Card with Integrated Header -->
            <div class="w-full flex-1 sm:flex-initial flex flex-col overflow-hidden sm:rounded-2xl sm:border border-slate-200 bg-white sm:shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900">
                <!-- Card Header Section for Logo & Subtitle (Flat Solid) -->
                <div
                    class="border-b border-slate-200 bg-slate-50 p-5 sm:p-7 text-center dark:border-slate-800 dark:bg-slate-950 sm:p-9"
                >
                    <Link
                        href="/"
                        class="inline-flex items-center justify-center transition-transform hover:scale-105 cursor-pointer"
                        title="SIPUAS"
                    >
                        <img
                            src="/images/logo-sidebar.png"
                            alt="SIPUAS Logo"
                            class="h-8 sm:h-10 w-auto object-contain mx-auto dark:brightness-0 dark:invert"
                        />
                    </Link>

                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Sistem Integrasi Pelayanan Publik & Akuntabilitas Staf
                    </p>
                </div>

                <!-- Card Body -->
                <div class="flex-1 flex flex-col justify-center p-6 sm:p-9 bg-white dark:bg-slate-900 text-center">
                    <!-- Success Icon Badge -->
                    <div class="h-20 w-20 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-full border border-emerald-500/20 dark:border-emerald-500/30 flex items-center justify-center mx-auto mb-4 shadow-none">
                        <CheckCircle2 class="h-10 w-10" />
                    </div>

                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Laporan Terkirim!</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed font-medium">
                        Terima kasih atas partisipasi Anda. Laporan Anda telah dicatat oleh sistem dan diteruskan ke Kepala Ruangan terkait.
                    </p>

                    <!-- Receipt Code Box -->
                    <div class="mt-6 bg-slate-50 dark:bg-slate-950 rounded-2xl p-4 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mb-1">Nomor Registrasi Laporan</span>
                        <div class="flex items-center justify-center gap-2">
                            <span class="text-xl font-sans font-extrabold text-emerald-600 dark:text-emerald-400 tracking-wider">{{ id }}</span>
                            <button
                                @click="copyReceipt"
                                class="p-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer focus:outline-none"
                                title="Salin Kode Laporan"
                            >
                                <Copy class="h-4 w-4" />
                            </button>
                        </div>
                        <span v-if="copied" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block mt-1">Kode berhasil disalin!</span>
                    </div>

                    <!-- Warning Catat / Simpan Nomor Registrasi -->
                    <div class="mt-4 p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/80 flex items-start gap-3 text-left">
                        <AlertCircle class="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                        <div class="text-xs leading-relaxed text-slate-700 dark:text-slate-300">
                            <strong class="text-amber-900 dark:text-amber-200 font-bold block mb-0.5">Catatan Penting:</strong>
                            Jika Anda ingin melacak progres laporan ini ke depannya, pastikan untuk <strong>menyimpan atau mencatat Nomor Registrasi</strong> di atas sebelum meninggalkan halaman ini.
                        </div>
                    </div>

                    <div class="mt-6">
                        <Link
                            :href="route('report.create')"
                            class="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm transition cursor-pointer active:scale-[0.99] shadow-sm"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali ke Halaman Pertama</span>
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
