<script setup>
defineProps({
    maxWidth: {
        type: String,
        default: 'max-w-lg',
    },
    hideLogo: {
        type: Boolean,
        default: false,
    },
    noPadding: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 text-slate-900 dark:text-slate-100 relative transition-colors duration-200 overflow-hidden font-sans">
        <!-- Background Image with Soft Blur and Overlay (Fixed Full Viewport, Optimized WebP) -->
        <div class="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat filter blur-xs sm:blur-sm scale-105 pointer-events-none" style="background-image: url('/images/hospital-hero.webp');"></div>
        <div class="fixed inset-0 z-0 bg-slate-900/40 dark:bg-slate-950/75 transition-colors duration-200 pointer-events-none"></div>

        <!-- Ambient Glow Elements matching SIPUAS emerald theme -->
        <div class="pointer-events-none fixed top-[-15%] left-[-10%] h-[60vw] w-[60vw] max-w-[700px] max-h-[700px] rounded-full bg-emerald-500/15 blur-3xl"></div>
        <div class="pointer-events-none fixed right-[-10%] bottom-[-10%] h-[50vw] w-[50vw] max-w-[600px] max-h-[600px] rounded-full bg-teal-500/15 blur-3xl"></div>

        <!-- Centered Card Container -->
        <div :class="['w-full z-10 my-auto flex flex-col items-center', maxWidth]">
            <!-- Centered Logo directly above container (if not hidden) -->
            <div v-if="!hideLogo" class="text-center mb-5">
                <img src="/images/logo-sidebar.png" alt="SIPUAS Logo" class="h-12 sm:h-14 w-auto object-contain mx-auto brightness-0 invert drop-shadow-md" />
            </div>

            <!-- Main Auth Card (Solid Flat Colors, No Transparency) -->
            <div
                class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900"
                :class="noPadding ? '' : 'p-6 sm:p-8'"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
