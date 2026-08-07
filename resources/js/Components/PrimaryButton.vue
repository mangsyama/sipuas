<script setup>
defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <button
        :disabled="loading"
        class="inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 px-4 py-2.5 text-sm font-extrabold transition duration-150 ease-in-out focus:outline-none outline-none disabled:opacity-60 disabled:cursor-not-allowed shadow-sm"
    >
        <svg v-if="loading" class="animate-spin -ms-1 me-2 h-4 w-4 text-current shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <slot />
    </button>
</template>
