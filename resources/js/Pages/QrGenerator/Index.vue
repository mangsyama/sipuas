<script setup>
import { ref, computed, onMounted, watch, getCurrentInstance } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { 
    QrCode, 
    Download, 
    Check, 
    Globe, 
    RefreshCw, 
    Palette, 
    Building2, 
    CheckCircle2, 
    Info 
} from '@lucide/vue';

const props = defineProps({
    baseUrl: {
        type: String,
        default: ''
    },
    rooms: {
        type: Array,
        default: () => []
    },
    units: {
        type: Array,
        default: () => []
    }
});

const { proxy } = getCurrentInstance();

// Dynamic base origin
const currentOrigin = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.origin;
    }
    return props.baseUrl || 'http://localhost';
});

// All available rooms
const roomList = computed(() => {
    return props.rooms.length > 0 ? props.rooms : props.units;
});

// Mode state: 'global' (all hospital) vs 'room' (specific room auto-selected)
const targetMode = ref('global'); // 'global' | 'room'
const selectedRoomId = ref('');

// Form states
const customPath = ref('/report');
const qrColor = ref('#059669'); // Emerald 600 default
const includeLogo = ref(false); // Default OFF sesuai permintaan
const qrCanvasRef = ref(null);
const isGenerating = ref(false);
const copied = ref(false);

// Matched room based on customPath query parameter
const pathMatchedRoom = computed(() => {
    const path = customPath.value || '';
    if (!path.includes('room_id=') && !path.includes('unit=') && !path.includes('room=')) {
        return null;
    }
    try {
        const queryPart = path.split('?')[1] || '';
        const params = new URLSearchParams(queryPart);
        const roomId = params.get('room_id') || params.get('room') || params.get('unit');
        if (roomId && roomId !== '[object Object]') {
            return roomList.value.find(r => String(r.id) === String(roomId)) || { id: roomId, name: `Ruangan #${roomId}` };
        }
    } catch (e) {
        return null;
    }
    return null;
});

// Final Target Full URL
const fullTargetUrl = computed(() => {
    let path = customPath.value || '';
    if (!path.startsWith('/')) {
        path = '/' + path;
    }
    return `${currentOrigin.value}${path}`;
});

// Apply Global Preset
const applyGlobalMode = () => {
    targetMode.value = 'global';
    selectedRoomId.value = '';
    customPath.value = '/report';
};

// Apply Room Specific Preset
const applyRoomMode = (roomId = '') => {
    targetMode.value = 'room';
    selectedRoomId.value = roomId ? String(roomId) : '';
    if (selectedRoomId.value) {
        customPath.value = `/report?room_id=${selectedRoomId.value}`;
    } else {
        customPath.value = '/report';
    }
};

// Handle room select change
const handleRoomChange = (val) => {
    let id = '';
    if (val && typeof val === 'object') {
        id = val.id !== undefined ? String(val.id) : '';
    } else if (val !== null && val !== undefined && val !== '') {
        id = String(val);
    }
    selectedRoomId.value = id;
    if (id && id !== '[object Object]') {
        customPath.value = `/report?room_id=${id}`;
    } else {
        customPath.value = '/report';
    }
};

watch(selectedRoomId, (newVal) => {
    let id = '';
    if (newVal && typeof newVal === 'object') {
        id = newVal.id !== undefined ? String(newVal.id) : '';
    } else if (newVal !== null && newVal !== undefined && newVal !== '') {
        id = String(newVal);
    }
    if (targetMode.value === 'room') {
        if (id && id !== '[object Object]') {
            customPath.value = `/report?room_id=${id}`;
        } else {
            customPath.value = '/report';
        }
    }
});

const resetConfig = () => {
    qrColor.value = '#059669';
    includeLogo.value = false;
    applyGlobalMode();
};

const colorPresets = [
    { name: 'Emerald', hex: '#059669' },
    { name: 'Black', hex: '#000000' },
    { name: 'Navy', hex: '#1e3a8a' },
    { name: 'Slate', hex: '#0f172a' },
    { name: 'Purple', hex: '#7e22ce' },
    { name: 'Crimson', hex: '#991b1b' },
];

const isCustomColor = computed(() => {
    return !colorPresets.some(c => c.hex.toLowerCase() === qrColor.value.toLowerCase());
});

const generateQR = async () => {
    if (!qrCanvasRef.value) return;
    isGenerating.value = true;

    try {
        const canvas = qrCanvasRef.value;
        const ctx = canvas.getContext('2d');

        // 1. Generate Ultra Crisp High-Res QR Code (1200px resolution)
        await QRCode.toCanvas(canvas, fullTargetUrl.value, {
            width: 1200,
            margin: 2,
            color: {
                dark: qrColor.value,
                light: '#FFFFFF'
            },
            errorCorrectionLevel: 'H'
        });

        // 2. Draw Center Logo Overlay if enabled
        if (includeLogo.value) {
            const logoImg = new Image();
            logoImg.crossOrigin = 'Anonymous';
            logoImg.src = '/images/logo-sidebar.png';

            await new Promise((resolve) => {
                logoImg.onload = () => {
                    const canvasWidth = canvas.width;
                    const logoSize = canvasWidth * 0.22; // 22% of QR width
                    const logoX = (canvasWidth - logoSize) / 2;
                    const logoY = (canvasWidth - logoSize) / 2;

                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';

                    // Background circle for logo with crisp shadow radius
                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(canvasWidth / 2, canvasWidth / 2, (logoSize / 2) + 14, 0, 2 * Math.PI);
                    ctx.fillStyle = '#FFFFFF';
                    ctx.shadowColor = 'rgba(0, 0, 0, 0.15)';
                    ctx.shadowBlur = 20;
                    ctx.fill();
                    ctx.restore();

                    // Draw Logo Image inside
                    ctx.drawImage(logoImg, logoX, logoY, logoSize, logoSize);
                    resolve();
                };
                logoImg.onerror = () => {
                    // Fallback attempt with icon-sipuas.png if logo-sidebar fails
                    const fallbackImg = new Image();
                    fallbackImg.src = '/images/icon-sipuas.png';
                    fallbackImg.onload = () => {
                        const canvasWidth = canvas.width;
                        const logoSize = canvasWidth * 0.22;
                        const logoX = (canvasWidth - logoSize) / 2;
                        const logoY = (canvasWidth - logoSize) / 2;
                        ctx.drawImage(fallbackImg, logoX, logoY, logoSize, logoSize);
                        resolve();
                    };
                    fallbackImg.onerror = () => resolve();
                };
            });
        }
    } catch (err) {
        console.error('Gagal generate QR Code:', err);
    } finally {
        isGenerating.value = false;
    }
};

const downloadQR = () => {
    if (!qrCanvasRef.value) return;
    const canvas = qrCanvasRef.value;
    const dataUrl = canvas.toDataURL('image/png');
    const roomKey = pathMatchedRoom.value ? pathMatchedRoom.value.name.replace(/[^a-zA-Z0-9]/g, '_') : 'GLOBAL';
    const filename = `QR_SIPUAS_${roomKey}_${Date.now()}.png`;

    const a = document.createElement('a');
    a.href = dataUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    if (proxy?.$toast) {
        proxy.$toast('QR Code Berhasil Diunduh!', 'success');
    }
};

const copyUrl = () => {
    navigator.clipboard.writeText(fullTargetUrl.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);

    if (proxy?.$toast) {
        proxy.$toast('Tautan disalin ke clipboard!', 'success');
    }
};

watch([customPath, qrColor, includeLogo], () => {
    generateQR();
});

onMounted(() => {
    generateQR();
});
</script>

<template>
    <Head title="Generator QR Code" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">

                <!-- Header Panel -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <QrCode class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Generator QR Code
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Buat dan unduh QR Code aduan untuk umum atau ruangan spesifik
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3 w-full xl:w-auto">
                        <!-- Domain Origin Badge -->
                        <div class="inline-flex items-center justify-center sm:justify-start gap-2 px-3.5 h-10 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-semibold bg-slate-50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-300" title="Domain URL Aktif">
                            <Globe class="h-4 w-4 text-emerald-500 shrink-0" />
                            <span class="truncate font-bold text-emerald-600 dark:text-white">{{ currentOrigin }}</span>
                        </div>

                        <!-- Reset / Refresh Button -->
                        <button 
                            @click="resetConfig" 
                            type="button"
                            class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
                        >
                            <RefreshCw class="h-4 w-4" />
                            <span>Reset Form</span>
                        </button>
                    </div>
                </div>

                <!-- Main Content (Sequential 1 Column layout) -->
                <div class="space-y-4 w-full">
                    
                    <!-- Card 1: Target URL QR Code & Pilihan Mode -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                        <div>
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                1. Target URL QR Code
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                Pilih mode QR Code untuk aduan umum seluruh RS atau langsung terhubung ke ruangan tertentu
                            </p>
                        </div>

                        <!-- 2 Pilihan Mode Bersih (Tanpa outline/ring aneh) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Pilihan 1: Link Global RS -->
                            <div 
                                @click="applyGlobalMode"
                                :class="[
                                    'p-4 rounded-xl border transition-all cursor-pointer flex items-start gap-3.5 select-none',
                                    targetMode === 'global'
                                        ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/20' 
                                        : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 hover:border-slate-300 dark:hover:border-slate-700'
                                ]"
                            >
                                <div :class="[
                                    'h-9 w-9 rounded-xl flex items-center justify-center shrink-0 transition',
                                    targetMode === 'global' ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'
                                ]">
                                    <Globe class="h-5 w-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="text-xs font-extrabold text-slate-900 dark:text-white">Link Global (Semua Ruangan)</h4>
                                        <div :class="[
                                            'w-4 h-4 rounded-full border flex items-center justify-center shrink-0',
                                            targetMode === 'global' ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300 dark:border-slate-700'
                                        ]">
                                            <div v-if="targetMode === 'global'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                        Pasien/keluarga bebas memilih lokasi ruangan sendiri di formulir laporan.
                                    </p>
                                </div>
                            </div>

                            <!-- Pilihan 2: Ruangan Spesifik (Auto-Select) -->
                            <div 
                                @click="applyRoomMode(selectedRoomId)"
                                :class="[
                                    'p-4 rounded-xl border transition-all cursor-pointer flex items-start gap-3.5 select-none',
                                    targetMode === 'room'
                                        ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/20' 
                                        : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 hover:border-slate-300 dark:hover:border-slate-700'
                                ]"
                            >
                                <div :class="[
                                    'h-9 w-9 rounded-xl flex items-center justify-center shrink-0 transition',
                                    targetMode === 'room' ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'
                                ]">
                                    <Building2 class="h-5 w-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="text-xs font-extrabold text-slate-900 dark:text-white">Ruangan Spesifik (Auto-Select)</h4>
                                        <div :class="[
                                            'w-4 h-4 rounded-full border flex items-center justify-center shrink-0',
                                            targetMode === 'room' ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300 dark:border-slate-700'
                                        ]">
                                            <div v-if="targetMode === 'room'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                        Pelapor <strong>langsung mengisi aduan</strong> untuk ruangan tersebut tanpa perlu memilih ruangan lagi.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown Ruangan via SearchableSelect (Desain Persis Report/Create.vue, Tanpa Container) -->
                        <div v-if="targetMode === 'room'" class="space-y-1.5 animate-spa-fade-in">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Ruangan / Unit Pelayanan <span class="text-red-500">*</span>
                            </label>
                            <div>
                                <SearchableSelect
                                    v-model="selectedRoomId"
                                    :options="roomList"
                                    valueKey="id"
                                    labelKey="name"
                                    subtitleKey="location_info"
                                    :absolute="false"
                                    placeholder="-- Pilih Ruangan Pelayanan --"
                                    searchPlaceholder="Cari nama ruangan atau gedung..."
                                    @change="handleRoomChange"
                                />
                            </div>
                        </div>

                        <!-- Smart Status Alert Banner -->
                        <div 
                            v-if="targetMode === 'room' && pathMatchedRoom" 
                            class="p-3.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800/60 flex items-center gap-3 text-xs"
                        >
                            <CheckCircle2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <p class="text-emerald-800 dark:text-emerald-300 leading-relaxed">
                                <strong>Mode Ruangan Terhubung:</strong> Pengunjung yang scan QR otomatis melapor ke <strong>{{ pathMatchedRoom.name }}</strong>.
                            </p>
                        </div>
                        <div 
                            v-else-if="targetMode === 'room' && !selectedRoomId" 
                            class="p-3.5 rounded-xl bg-amber-50/70 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/60 flex items-center gap-3 text-xs"
                        >
                            <Info class="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0" />
                            <p class="text-amber-800 dark:text-amber-300 leading-relaxed">
                                <strong>Pilih Ruangan:</strong> Silakan pilih ruangan/unit pada dropdown di atas untuk menghasilkan QR Code spesifik ruangan.
                            </p>
                        </div>
                        <div 
                            v-else 
                            class="p-3.5 rounded-xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-800/60 flex items-center gap-3 text-xs"
                        >
                            <Info class="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
                            <p class="text-blue-800 dark:text-blue-300 leading-relaxed">
                                <strong>Mode Global Aktif:</strong> Cocok untuk area umum (lobi RS/ruang tunggu sentral). Pasien memilih ruangan tujuan secara mandiri.
                            </p>
                        </div>
                    </div>

                    <!-- Card 2: Kustomisasi Desain QR -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                        <div>
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                2. Kustomisasi Desain QR
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Atur skema warna kode QR dan opsi penyematan logo resmi di tengah</p>
                        </div>

                        <div class="space-y-4">
                            <!-- Color Selection Swatches + Custom Picker -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pilihan Warna Kode QR</label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        v-for="color in colorPresets"
                                        :key="color.hex"
                                        type="button"
                                        @click="qrColor = color.hex"
                                        :class="[
                                            'h-9 px-3 rounded-xl border text-xs font-bold flex items-center gap-2 transition cursor-pointer',
                                            qrColor.toLowerCase() === color.hex.toLowerCase()
                                                ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/30 text-emerald-950 dark:text-emerald-200 shadow-xs'
                                                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                                        ]"
                                    >
                                        <span class="h-3.5 w-3.5 rounded-full shadow-xs shrink-0" :style="{ backgroundColor: color.hex }"></span>
                                        <span>{{ color.name }}</span>
                                    </button>

                                    <!-- Custom Color Picker Button -->
                                    <label 
                                        :class="[
                                            'h-9 px-3 rounded-xl border text-xs font-bold flex items-center gap-2 transition cursor-pointer relative select-none',
                                            isCustomColor
                                                ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/30 text-emerald-950 dark:text-emerald-200 shadow-xs'
                                                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                                        ]"
                                    >
                                        <Palette class="h-3.5 w-3.5 text-emerald-500 shrink-0" />
                                        <span>Custom Warna: <strong class="font-bold uppercase">{{ qrColor }}</strong></span>
                                        <input 
                                            v-model="qrColor"
                                            type="color" 
                                            class="absolute inset-0 opacity-0 w-full h-full cursor-pointer"
                                        />
                                    </label>
                                </div>
                            </div>

                            <!-- Center Logo Toggle Card (Default OFF) -->
                            <div 
                                @click="includeLogo = !includeLogo"
                                :class="[
                                    'p-4 rounded-xl border transition cursor-pointer flex items-center justify-between gap-4 select-none',
                                    includeLogo 
                                        ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/20' 
                                        : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950'
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <div :class="[
                                        'h-10 w-10 rounded-xl flex items-center justify-center shrink-0 transition p-2 bg-emerald-600 text-white shadow-xs',
                                        includeLogo ? 'bg-emerald-600' : 'opacity-40 grayscale'
                                    ]">
                                        <img src="/images/logo-sidebar.png" alt="SIPUAS" class="h-full w-full object-contain brightness-0 invert" />
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-extrabold text-slate-900 dark:text-white">Tampilkan Logo SIPUAS di Tengah</h4>
                                        <p class="hidden sm:block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Sematkan lambang resmi rumah sakit dengan latar belakang bulat bersih</p>
                                    </div>
                                </div>

                                <!-- Switch Toggle Button -->
                                <div :class="[
                                    'w-11 h-6 rounded-full transition-colors p-0.5 shrink-0 flex items-center',
                                    includeLogo ? 'bg-emerald-600 justify-end' : 'bg-slate-300 dark:bg-slate-700 justify-start'
                                ]">
                                    <div class="w-5 h-5 rounded-full bg-white shadow-xs"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Preview QR Code & Unduh -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                        <div>
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                3. Preview QR Code & Unduh
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Tampilan gambar QR Code siap simpan & unduh</p>
                        </div>

                        <div class="space-y-4 w-full">
                            <!-- QR Frame -->
                            <div class="bg-slate-50/80 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 text-center shadow-xs flex items-center justify-center w-full">
                                <div class="p-3.5 sm:p-4 bg-white rounded-2xl shadow-md border border-slate-100 flex items-center justify-center aspect-square w-full max-w-[240px] sm:max-w-[280px] overflow-hidden relative">
                                    <canvas ref="qrCanvasRef" class="w-full h-full max-w-full max-h-full object-contain block"></canvas>
                                    <div v-if="isGenerating" class="absolute inset-0 bg-white/80 flex items-center justify-center">
                                        <RefreshCw class="h-6 w-6 text-emerald-600 animate-spin" />
                                    </div>
                                </div>
                            </div>

                            <!-- Target URL Badge Container (Click to Copy & Centered) -->
                            <button
                                type="button"
                                @click="copyUrl"
                                title="Klik untuk menyalin URL target"
                                class="w-full px-4 py-3 rounded-xl bg-slate-50/80 hover:bg-emerald-50/60 dark:bg-slate-950/60 dark:hover:bg-emerald-950/30 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-300 dark:hover:border-emerald-800 flex items-center justify-center gap-2.5 overflow-hidden shadow-2xs transition cursor-pointer group select-none"
                            >
                                <Check v-if="copied" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                <Globe v-else class="h-4 w-4 text-emerald-500 group-hover:scale-110 transition-transform shrink-0" />
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 group-hover:text-emerald-900 dark:group-hover:text-emerald-200 truncate">
                                    {{ fullTargetUrl }}
                                </span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 shrink-0">
                                    {{ copied ? 'Tersalin!' : 'Klik untuk Salin' }}
                                </span>
                            </button>

                            <!-- Single Action Button: Download PNG -->
                            <div class="w-full">
                                <button
                                    type="button"
                                    @click="downloadQR"
                                    class="w-full h-11 bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 text-xs font-bold rounded-xl shadow-sm flex items-center justify-center gap-2 transition cursor-pointer border-0"
                                >
                                    <Download class="h-4 w-4" />
                                    <span>Download Gambar QR (PNG)</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
