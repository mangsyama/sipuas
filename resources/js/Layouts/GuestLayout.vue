<script setup>
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 text-slate-900 dark:text-slate-100 relative transition-colors duration-200 overflow-hidden">
        <!-- Background Image with Blur and Overlay -->
        <div class="absolute inset-0 z-0 bg-cover bg-center filter blur-sm scale-105" style="background-image: url('/images/hospital-hero.jpg');"></div>
        <div class="absolute inset-0 z-0 bg-slate-900/50 dark:bg-slate-950/75 transition-colors duration-200"></div>

        <!-- Centered Card Container -->
        <div class="w-full max-w-lg z-10 my-auto flex flex-col items-center">
            <!-- Centered Logo directly above container -->
            <div class="text-center mb-5">
                <img src="/images/logo-sidebar.png" alt="SIPUAS Logo" class="h-12 sm:h-14 w-auto object-contain mx-auto brightness-0 invert drop-shadow-md" />
            </div>

            <!-- Main Auth Card -->
            <div class="w-full bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-3xl shadow-2xl p-6 sm:p-8">
                <slot />
            </div>

            <!-- Footer Copyright -->
            <div class="mt-6 text-center text-xs text-white/80 dark:text-slate-400 font-medium drop-shadow">
                &copy; {{ new Date().getFullYear() }} SIPUAS - Sistem Integrasi Pelayanan Publik & Akuntabilitas Staf
            </div>
        </div>
    </div>
</template>
