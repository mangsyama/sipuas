<script setup>
import { computed } from 'vue';
import { 
    UserPlus, 
    FileText, 
    Check, 
    CheckCheck 
} from '@lucide/vue';

const props = defineProps({
    notification: {
        type: Object,
        required: true
    },
    // Optional variant: 'dropdown' for top bar, 'list' for full page
    variant: {
        type: String,
        default: 'dropdown'
    }
});

const emit = defineEmits(['click', 'mark-read']);

// Helper to determine notification category
const category = computed(() => {
    const notif = props.notification;
    const type = notif.type || notif.data?.type || '';
    const title = (notif.title || notif.data?.title || '').toLowerCase();

    if (type === 'user' || title.includes('pendaftaran') || title.includes('pengguna') || title.includes('user')) {
        return 'user_registration';
    }
    return 'ticket_report';
});

// Harmonious uniform styling: emerald/slate palette for both categories with clean, identical base colors
const styling = computed(() => {
    if (category.value === 'user_registration') {
        return {
            iconComponent: UserPlus,
            bgClass: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-900/60',
            badgeText: 'Pendaftaran',
            badgeClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-bold',
            cardBorderClass: 'border-slate-200/60 dark:border-slate-800/80',
            roleTag: 'Persetujuan Akun',
            roleTagClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        };
    }

    return {
        iconComponent: FileText,
        bgClass: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-900/60',
        badgeText: 'Aduan',
        badgeClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-bold',
        cardBorderClass: 'border-slate-200/60 dark:border-slate-800/80',
        roleTag: 'Aduan Pasien',
        roleTagClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    };
});
</script>

<template>
    <div 
        @click="emit('click', notification)"
        role="button"
        tabindex="0"
        @keydown.enter="emit('click', notification)"
        :class="[
            'flex gap-3 transition cursor-pointer relative select-none',
            variant === 'dropdown' ? 'px-4 py-3 border-b border-slate-100 dark:border-slate-800/60 hover:bg-slate-50/80 dark:hover:bg-slate-800/40' : 'p-4 rounded-xl shadow-2xs hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition',
            variant === 'list' ? styling.cardBorderClass : '',
            !notification.read_at ? (variant === 'dropdown' ? 'bg-emerald-50/20 dark:bg-white/5' : 'bg-emerald-50/20 dark:bg-slate-800/40') : ''
        ]"
    >
        <!-- Icon Container -->
        <div :class="[
            'rounded-xl flex items-center justify-center shrink-0 shadow-xs',
            variant === 'dropdown' ? 'h-9 w-9 mt-0.5' : 'h-10 w-10 mt-0.5',
            styling.bgClass
        ]">
            <component :is="styling.iconComponent" :class="variant === 'dropdown' ? 'h-4.5 w-4.5' : 'h-5 w-5'" />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-1.5 min-w-0">
                    <p :class="[
                        'font-bold truncate',
                        variant === 'dropdown' ? 'text-xs' : 'text-xs sm:text-sm',
                        !notification.read_at ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300'
                    ]">
                        {{ notification.title }}
                    </p>
                    <span 
                        v-if="styling.badgeText"
                        :class="['px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider shrink-0', styling.badgeClass]"
                    >
                        {{ styling.badgeText }}
                    </span>
                </div>

                <!-- Read / Unread Status Indicator -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <template v-if="!notification.read_at">
                        <button
                            v-if="variant === 'dropdown'"
                            @click.stop="emit('mark-read', notification)"
                            type="button"
                            class="p-0.5 rounded text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-slate-800 transition"
                            title="Tandai sudah dibaca"
                        >
                            <Check class="h-3.5 w-3.5" />
                        </button>
                        <span 
                            class="h-2 w-2 rounded-full bg-emerald-500 ring-2 ring-emerald-200 dark:ring-emerald-900"
                            title="Belum dibaca"
                        />
                    </template>
                    <template v-else>
                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-1.5 py-0.5 rounded">
                            <CheckCheck class="h-3 w-3" />
                            <span>Dibaca</span>
                        </span>
                    </template>
                </div>
            </div>

            <!-- Message Body -->
            <p :class="[
                'text-slate-500 dark:text-slate-400 leading-relaxed mt-0.5',
                variant === 'dropdown' ? 'text-[11px] line-clamp-2' : 'text-xs'
            ]">
                {{ notification.message }}
            </p>

            <!-- Metadata Footer -->
            <div class="flex items-center justify-between gap-2 mt-1.5">
                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                    {{ notification.time || 'Baru saja' }}
                </span>

                <span 
                    v-if="variant === 'list'"
                    :class="['px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider', styling.roleTagClass]"
                >
                    {{ styling.roleTag }}
                </span>
            </div>
        </div>
    </div>
</template>
