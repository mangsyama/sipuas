<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import {
    Chart,
    BarController,
    BarElement,
    LinearScale,
    CategoryScale,
    Tooltip,
    Legend
} from 'chart.js';

Chart.register(
    BarController,
    BarElement,
    LinearScale,
    CategoryScale,
    Tooltip,
    Legend
);

const props = defineProps({
    labels: {
        type: Array,
        default: () => []
    },
    positive: {
        type: Array,
        default: () => []
    },
    negative: {
        type: Array,
        default: () => []
    },
    neutral: {
        type: Array,
        default: () => []
    },
    height: {
        type: Number,
        default: 260
    }
});

const canvasRef = ref(null);
let chartInstance = null;
let themeObserver = null;

const isDarkMode = () => {
    return typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
};

const getChartOptions = () => {
    const dark = isDarkMode();
    const gridColor = dark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)';
    const textColor = dark ? '#94a3b8' : '#64748b';
    const tooltipBg = dark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.95)';
    const tooltipText = dark ? '#f8fafc' : '#0f172a';
    const tooltipBorder = dark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    return {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y', // Horizontal bars for great category readability
        plugins: {
            legend: {
                display: true,
                position: 'top',
                align: 'end',
                labels: {
                    color: textColor,
                    boxWidth: 12,
                    boxHeight: 12,
                    usePointStyle: true,
                    pointStyle: 'rectRounded',
                    font: {
                        family: 'Poppins, system-ui, sans-serif',
                        size: 11,
                        weight: '600'
                    },
                    padding: 14
                }
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
                boxPadding: 4,
                usePointStyle: true
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                stacked: true,
                grid: {
                    color: gridColor,
                    drawBorder: false
                },
                ticks: {
                    color: textColor,
                    font: {
                        family: 'Poppins, system-ui, sans-serif',
                        size: 10
                    },
                    precision: 0,
                    stepSize: 1
                }
            },
            y: {
                stacked: true,
                grid: {
                    display: false
                },
                ticks: {
                    color: textColor,
                    font: {
                        family: 'Poppins, system-ui, sans-serif',
                        size: 10
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

    const ctx = canvasRef.value.getContext('2d');
    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: props.labels,
            datasets: [
                {
                    label: 'Pujian / Positif',
                    data: props.positive,
                    backgroundColor: '#10b981', // Emerald-500
                    borderRadius: 4,
                    barPercentage: 0.7,
                },
                {
                    label: 'Keluhan / Negatif',
                    data: props.negative,
                    backgroundColor: '#f43f5e', // Rose-500
                    borderRadius: 4,
                    barPercentage: 0.7,
                },
                {
                    label: 'Saran / Netral',
                    data: props.neutral,
                    backgroundColor: '#0284c7', // Sky-600
                    borderRadius: 4,
                    barPercentage: 0.7,
                }
            ]
        },
        options: getChartOptions()
    });
};

onMounted(() => {
    nextTick(() => {
        renderChart();

        themeObserver = new MutationObserver((mutations) => {
            mutations.forEach((m) => {
                if (m.attributeName === 'class' && chartInstance) {
                    chartInstance.options = getChartOptions();
                    chartInstance.update();
                }
            });
        });
        themeObserver.observe(document.documentElement, { attributes: true });
    });
});

watch([() => props.labels, () => props.positive, () => props.negative, () => props.neutral], () => {
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
    <div class="w-full relative" :style="{ height: `${height}px` }">
        <canvas ref="canvasRef"></canvas>
    </div>
</template>
