<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    labels: {
        type: Array,
        default: () => []
    },
    datasets: {
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
        interaction: {
            mode: 'index',
            intersect: false,
        },
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
                    pointStyle: 'circle',
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
                cornerRadius: 10,
                titleFont: {
                    family: 'Poppins, system-ui, sans-serif',
                    size: 12,
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
                grid: {
                    display: false
                },
                ticks: {
                    color: textColor,
                    font: {
                        family: 'Poppins, system-ui, sans-serif',
                        size: 10
                    },
                    maxRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: 10
                }
            },
            y: {
                beginAtZero: true,
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
        type: 'line',
        data: {
            labels: props.labels,
            datasets: props.datasets.map(ds => ({
                ...ds,
                pointRadius: 3,
                pointHoverRadius: 6,
                pointBackgroundColor: ds.borderColor,
                pointBorderColor: '#fff',
                pointBorderWidth: 1.5,
            }))
        },
        options: getChartOptions()
    });
};

onMounted(() => {
    nextTick(() => {
        renderChart();

        // Listen for dark mode toggle on html class
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

watch([() => props.labels, () => props.datasets], () => {
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
