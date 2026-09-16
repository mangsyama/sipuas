<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import {
    Chart,
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend
} from 'chart.js';

Chart.register(
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend
);

const props = defineProps({
    labels: {
        type: Array,
        default: () => ['Positif', 'Negatif', 'Netral']
    },
    data: {
        type: Array,
        default: () => [0, 0, 0]
    },
    percentages: {
        type: Array,
        default: () => [0, 0, 0]
    },
    height: {
        type: Number,
        default: 170
    },
    centerText: {
        type: String,
        default: ''
    },
    centerSubtext: {
        type: String,
        default: 'Kepuasan'
    }
});

const canvasRef = ref(null);
let chartInstance = null;
let themeObserver = null;

const isDarkMode = () => {
    return typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
};

const getColors = () => {
    return {
        bg: ['#10b981', '#f43f5e', '#38bdf8'], // Emerald-500, Rose-500, Sky-400
        hoverBg: ['#059669', '#e11d48', '#0284c7'],
        borderColor: isDarkMode() ? '#0f172a' : '#ffffff'
    };
};

const getChartOptions = () => {
    const dark = isDarkMode();
    const tooltipBg = dark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.95)';
    const tooltipText = dark ? '#f8fafc' : '#0f172a';
    const tooltipBorder = dark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
            legend: {
                display: false // We render a custom richer legend below
            },
            tooltip: {
                backgroundColor: tooltipBg,
                titleColor: tooltipText,
                bodyColor: tooltipText,
                borderColor: tooltipBorder,
                borderWidth: 1,
                padding: 10,
                cornerRadius: 8,
                titleFont: {
                    family: 'Poppins, system-ui, sans-serif',
                    size: 11,
                    weight: '700'
                },
                bodyFont: {
                    family: 'Poppins, system-ui, sans-serif',
                    size: 11
                },
                callbacks: {
                    label: function (context) {
                        const val = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        if (total === 0 || context.label === 'Belum ada laporan') {
                            return ' Belum ada data laporan';
                        }
                        const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                        return ` ${context.label}: ${val} Laporan (${pct}%)`;
                    }
                }
            }
        }
    };
};

const renderChart = () => {
    if (!canvasRef.value) return;

    if (chartInstance) {
        chartInstance.destroy();
    }

    const colors = getColors();
    const ctx = canvasRef.value.getContext('2d');
    const total = props.data.reduce((a, b) => a + b, 0);

    // Fallback data if 0 reports
    const chartData = total > 0 ? props.data : [1];
    const chartColors = total > 0 ? colors.bg : [isDarkMode() ? '#334155' : '#e2e8f0'];

    chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: total > 0 ? props.labels : ['Belum ada laporan'],
            datasets: [{
                data: chartData,
                backgroundColor: chartColors,
                hoverBackgroundColor: total > 0 ? colors.hoverBg : chartColors,
                borderColor: colors.borderColor,
                borderWidth: 2,
            }]
        },
        options: getChartOptions()
    });
};

onMounted(() => {
    nextTick(() => {
        renderChart();

        themeObserver = new MutationObserver((mutations) => {
            mutations.forEach((m) => {
                if (m.attributeName === 'class') {
                    renderChart();
                }
            });
        });
        themeObserver.observe(document.documentElement, { attributes: true });
    });
});

watch([() => props.data, () => props.labels], () => {
    nextTick(() => {
        renderChart();
    });
}, { deep: true });

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
    if (themeObserver) {
        themeObserver.disconnect();
    }
});
</script>

<template>
    <div class="flex flex-col items-center justify-center w-full">
        <!-- Canvas Container with Center Label -->
        <div class="relative flex items-center justify-center" :style="{ height: `${height}px`, width: `${height}px` }">
            <canvas ref="canvasRef"></canvas>
            
            <div v-if="centerText" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
                <span class="text-2xl font-black text-slate-900 dark:text-white leading-none tracking-tight">
                    {{ centerText }}
                </span>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-1">
                    {{ centerSubtext }}
                </span>
            </div>
        </div>

        <!-- Rich Custom Legend Below -->
        <div class="grid grid-cols-3 gap-2 w-full mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 text-center">
            <div class="p-2 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-100/60 dark:border-emerald-900/40">
                <div class="flex items-center justify-center gap-1.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span>
                    Positif
                </div>
                <div class="text-base font-black text-slate-900 dark:text-white mt-0.5">
                    {{ data[0] || 0 }}
                </div>
                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">
                    {{ percentages[0] || 0 }}%
                </div>
            </div>

            <div class="p-2 rounded-xl bg-rose-50/60 dark:bg-rose-950/20 border border-rose-100/60 dark:border-rose-900/40">
                <div class="flex items-center justify-center gap-1.5 text-[11px] font-bold text-rose-700 dark:text-rose-400">
                    <span class="h-2 w-2 rounded-full bg-rose-500 shrink-0"></span>
                    Negatif
                </div>
                <div class="text-base font-black text-slate-900 dark:text-white mt-0.5">
                    {{ data[1] || 0 }}
                </div>
                <div class="text-[10px] text-rose-600 dark:text-rose-400 font-bold">
                    {{ percentages[1] || 0 }}%
                </div>
            </div>

            <div class="p-2 rounded-xl bg-sky-50/60 dark:bg-sky-950/20 border border-sky-100/60 dark:border-sky-900/40">
                <div class="flex items-center justify-center gap-1.5 text-[11px] font-bold text-sky-700 dark:text-sky-400">
                    <span class="h-2 w-2 rounded-full bg-sky-400 shrink-0"></span>
                    Netral
                </div>
                <div class="text-base font-black text-slate-900 dark:text-white mt-0.5">
                    {{ data[2] || 0 }}
                </div>
                <div class="text-[10px] text-sky-600 dark:text-sky-400 font-bold">
                    {{ percentages[2] || 0 }}%
                </div>
            </div>
        </div>
    </div>
</template>
