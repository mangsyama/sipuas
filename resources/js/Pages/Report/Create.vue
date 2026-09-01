<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { 
    Send, 
    MessageSquare, 
    Building2, 
    Sparkles, 
    CheckCircle2,
    Copy,
    ArrowRight,
    ArrowLeft,
    FileText,
    RefreshCw,
    Paperclip,
    UploadCloud,
    UserCheck,
    Shield,
    X,
    User,
    Phone,
    Award,
    Camera,
    Image as ImageIcon,
    Video as VideoIcon,
    Trash2,
    AlertCircle
} from '@lucide/vue';

const props = defineProps({
    unitId: {
        type: String,
        default: ''
    },
    units: {
        type: Array,
        default: () => [
            { id: 'FARMASI', name: 'Instalasi Farmasi' },
            { id: 'IGD', name: 'Instalasi Gawat Darurat (IGD)' },
            { id: 'POLIKLINIK', name: 'Poliklinik Rawat Jalan' },
            { id: 'RAWAT_INAP', name: 'Ruang Rawat Inap' },
            { id: 'LABORATORIUM', name: 'Laboratorium Utama' },
            { id: 'RADIOLOGI', name: 'Instalasi Radiologi' },
            { id: 'KASIR', name: 'Kasir & Pendaftaran' },
            { id: 'IPSRS', name: 'Pemeliharaan Sarpras (IPSRS)' }
        ]
    }
});

const currentStep = ref(1);
const isSubmitting = ref(false);
const copied = ref(false);
const generatedReportId = ref('');
const fileName = ref('');
const isKeyboardOpen = ref(false);
const uploadError = ref('');
let focusTimeout = null;

const handleFocusIn = (e) => {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e?.target?.tagName)) {
        if (focusTimeout) clearTimeout(focusTimeout);
        isKeyboardOpen.value = true;
    }
};

const handleFocusOut = (e) => {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e?.target?.tagName)) {
        if (focusTimeout) clearTimeout(focusTimeout);
        focusTimeout = setTimeout(() => {
            const activeTag = document.activeElement?.tagName;
            if (!['INPUT', 'TEXTAREA', 'SELECT'].includes(activeTag)) {
                isKeyboardOpen.value = false;
            }
        }, 150);
    }
};

let resizeHandler = null;

onMounted(() => {
    if (window.visualViewport) {
        const initialHeight = window.visualViewport.height;
        resizeHandler = () => {
            if (window.visualViewport) {
                isKeyboardOpen.value = (initialHeight - window.visualViewport.height) > 140;
            }
        };
        window.visualViewport.addEventListener('resize', resizeHandler);
    }
});

onUnmounted(() => {
    if (focusTimeout) clearTimeout(focusTimeout);
    if (window.visualViewport && resizeHandler) {
        window.visualViewport.removeEventListener('resize', resizeHandler);
    }
});

const selectedUnitObj = computed(() => {
    return props.units.find(u => u.id === form.value.unit_id);
});

const cameraInput = ref(null);
const galleryInput = ref(null);
const uploadedAttachment = ref(null);
const isTransitioning = ref(false);
const isCompressing = ref(false);

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'instant' });
    const cardElement = document.querySelector('main');
    if (cardElement) cardElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const form = ref({
    unit_id: props.unitId || '',
    target_object: '',
    isi_laporan: '',
    uploaded_files: [],
    reporter_name: '',
    reporter_phone: ''
});

const openCamera = () => {
    if (isTransitioning.value || isCompressing.value) return;
    uploadError.value = '';
    cameraInput.value?.click();
};

const openGallery = () => {
    if (isTransitioning.value || isCompressing.value) return;
    uploadError.value = '';
    galleryInput.value?.click();
};

const compressImage = (file, maxWidth = 1920, maxHeight = 1920, quality = 0.82) => {
    return new Promise((resolve) => {
        if (!file.type.startsWith('image/') || file.type === 'image/gif' || file.type === 'image/svg+xml') {
            return resolve(file);
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                let width = img.width;
                let height = img.height;

                if (width > maxWidth || height > maxHeight) {
                    if (width > height) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    } else {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(
                    (blob) => {
                        if (!blob || blob.size >= file.size) {
                            return resolve(file);
                        }
                        const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        resolve(compressedFile);
                    },
                    'image/jpeg',
                    quality
                );
            };
            img.onerror = () => resolve(file);
        };
        reader.onerror = () => resolve(file);
    });
};

const onFileSelected = async (event) => {
    uploadError.value = '';
    const originalFile = event.target.files?.[0];
    if (!originalFile) return;

    // Strict validation: Only Photo or Video
    if (!originalFile.type.startsWith('image/') && !originalFile.type.startsWith('video/')) {
        uploadError.value = 'Format file tidak didukung. Mohon hanya melampirkan file Foto atau Video!';
        event.target.value = '';
        return;
    }

    // Size limit: Strictly 10MB
    if (originalFile.size > 10 * 1024 * 1024) {
        uploadError.value = 'Ukuran file terlalu besar. Maksimal file yang dapat diunggah adalah 10MB!';
        event.target.value = '';
        return;
    }

    isCompressing.value = true;

    try {
        let finalFile = originalFile;
        // Compress photo on client-side
        if (originalFile.type.startsWith('image/')) {
            finalFile = await compressImage(originalFile);
        }

        if (uploadedAttachment.value?.previewUrl) {
            URL.revokeObjectURL(uploadedAttachment.value.previewUrl);
        }

        const isImage = finalFile.type.startsWith('image/');
        const isVideo = finalFile.type.startsWith('video/');
        const previewUrl = URL.createObjectURL(finalFile);
        const sizeInMB = (finalFile.size / (1024 * 1024)).toFixed(1);
        const sizeFormatted = sizeInMB < 0.1 ? Math.round(finalFile.size / 1024) + ' KB' : sizeInMB + ' MB';

        uploadedAttachment.value = {
            file: finalFile,
            name: finalFile.name,
            sizeFormatted,
            isImage,
            isVideo,
            previewUrl
        };

        form.value.uploaded_files = [uploadedAttachment.value];
    } catch (e) {
        console.error('Gagal memproses media:', e);
    } finally {
        isCompressing.value = false;
        event.target.value = '';
    }
};

const removeAttachment = () => {
    uploadError.value = '';
    if (uploadedAttachment.value?.previewUrl) {
        URL.revokeObjectURL(uploadedAttachment.value.previewUrl);
    }
    uploadedAttachment.value = null;
    form.value.uploaded_files = [];
};

const goToStep2 = () => {
    if (!form.value.unit_id || isTransitioning.value) return;
    isTransitioning.value = true;
    currentStep.value = 2;
    scrollToTop();
    setTimeout(() => {
        isTransitioning.value = false;
    }, 350);
};

const goToStep3 = () => {
    if (!form.value.isi_laporan.trim() || isTransitioning.value) return;
    isTransitioning.value = true;
    currentStep.value = 3;
    scrollToTop();
    setTimeout(() => {
        isTransitioning.value = false;
    }, 350);
};

const goToStep1 = () => {
    if (isTransitioning.value) return;
    isTransitioning.value = true;
    currentStep.value = 1;
    scrollToTop();
    setTimeout(() => {
        isTransitioning.value = false;
    }, 350);
};

const goToStep2From3 = () => {
    if (isTransitioning.value) return;
    isTransitioning.value = true;
    currentStep.value = 2;
    scrollToTop();
    setTimeout(() => {
        isTransitioning.value = false;
    }, 350);
};

const submitReport = () => {
    if (!form.value.isi_laporan.trim() || isSubmitting.value) return;
    
    isSubmitting.value = true;
    setTimeout(() => {
        isSubmitting.value = false;
        generatedReportId.value = 'LP-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000);
        currentStep.value = 4;
        scrollToTop();
    }, 1200);
};

const resetForm = () => {
    if (uploadedAttachment.value?.previewUrl) {
        URL.revokeObjectURL(uploadedAttachment.value.previewUrl);
    }
    uploadedAttachment.value = null;
    form.value = {
        unit_id: '',
        target_object: '',
        isi_laporan: '',
        uploaded_files: [],
        reporter_name: '',
        reporter_phone: ''
    };
    fileName.value = '';
    generatedReportId.value = '';
    currentStep.value = 1;
};

const copyReceipt = () => {
    navigator.clipboard.writeText(generatedReportId.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};
</script>

<template>
    <Head title="Formulir Suara Pasien" />

    <div 
        @focusin="handleFocusIn" 
        @focusout="handleFocusOut"
        class="min-h-screen flex flex-col justify-between sm:justify-center items-center relative overflow-x-hidden font-sans bg-white dark:bg-slate-900 sm:bg-transparent p-0 sm:p-6 text-slate-900 dark:text-slate-100"
    >
        <!-- Background Image with Blur & Dark Overlay (Desktop/Tablet Fixed) -->
        <div class="hidden sm:block fixed inset-0 z-0 bg-cover bg-center bg-no-repeat filter blur-xs sm:blur-sm scale-105 pointer-events-none" style="background-image: url('/images/hospital-hero.jpg');"></div>
        <div class="hidden sm:block fixed inset-0 z-0 bg-slate-900/55 dark:bg-slate-950/80 transition-colors duration-200 pointer-events-none"></div>

        <!-- Ambient Glow Elements matching SIPUAS emerald theme (Desktop/Tablet) -->
        <div class="pointer-events-none hidden sm:block fixed top-[-15%] left-[-10%] h-[70vw] w-[70vw] max-w-[800px] max-h-[800px] rounded-full bg-emerald-600/[0.08] blur-[130px] dark:bg-emerald-600/20"></div>
        <div class="pointer-events-none hidden sm:block fixed right-[-10%] bottom-[-10%] h-[60vw] w-[60vw] max-w-[700px] max-h-[700px] rounded-full bg-teal-600/[0.08] blur-[120px] dark:bg-teal-900/30"></div>

        <!-- Main Form Card Container (Full-screen on Mobile, Card Modal on Desktop) -->
        <main class="w-full sm:max-w-xl mx-auto z-20 my-auto flex flex-col flex-1 sm:flex-initial py-0 sm:py-4">
            <!-- Solid Container Card with Integrated Header -->
            <div class="w-full flex-1 sm:flex-initial flex flex-col overflow-hidden sm:rounded-2xl sm:border border-slate-200 bg-white sm:shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900">
                <!-- Card Header Section for Logo & Subtitle (Flat Solid) -->
                <div
                    class="border-b border-slate-200 bg-slate-50 p-5 sm:p-7 text-center dark:border-slate-800 dark:bg-slate-950"
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
                        Sampaikan kritik, saran, aduan, atau apresiasi Anda untuk perbaikan layanan kami
                    </p>
                </div>

                <!-- Card Body -->
                <div class="flex-1 p-5 sm:p-8 bg-white dark:bg-slate-900 pb-44 sm:pb-8" :class="{ 'pointer-events-none': isTransitioning }">
                    <!-- 4 Step Progress Bar Indicator -->
                    <div class="mb-6 sm:mb-8 px-1 sm:px-2">
                        <div class="grid grid-cols-4 relative">
                            <!-- Connecting Progress Line -->
                            <div class="absolute top-[18px] left-[12.5%] right-[12.5%] -translate-y-1/2 h-1 bg-slate-100 dark:bg-slate-800 z-0">
                                <div 
                                    class="h-full bg-emerald-500 transition-all duration-300 rounded-full"
                                    :style="{ width: currentStep === 1 ? '0%' : currentStep === 2 ? '33.33%' : currentStep === 3 ? '66.66%' : '100%' }"
                                ></div>
                            </div>

                            <!-- Step 1 Node -->
                            <div class="flex flex-col items-center text-center z-10 relative">
                                <div 
                                    :class="[
                                        'h-9 w-9 rounded-full flex items-center justify-center font-extrabold text-xs transition-all duration-300 border-2',
                                        currentStep >= 1 ? 'bg-emerald-600 text-white border-emerald-600 shadow-md ring-4 ring-emerald-50 dark:ring-emerald-950' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    <CheckCircle2 v-if="currentStep > 1" class="h-5 w-5" />
                                    <span v-else>1</span>
                                </div>
                                <span class="text-[10px] sm:text-[11px] font-extrabold mt-2 uppercase tracking-wide text-slate-700 dark:text-slate-200">Lokasi</span>
                            </div>

                            <!-- Step 2 Node -->
                            <div class="flex flex-col items-center text-center z-10 relative">
                                <div 
                                    :class="[
                                        'h-9 w-9 rounded-full flex items-center justify-center font-extrabold text-xs transition-all duration-300 border-2',
                                        currentStep >= 2 ? 'bg-emerald-600 text-white border-emerald-600 shadow-md ring-4 ring-emerald-50 dark:ring-emerald-950' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    <CheckCircle2 v-if="currentStep > 2" class="h-5 w-5" />
                                    <span v-else>2</span>
                                </div>
                                <span class="text-[10px] sm:text-[11px] font-extrabold mt-2 uppercase tracking-wide text-slate-700 dark:text-slate-200">Detail</span>
                            </div>

                            <!-- Step 3 Node -->
                            <div class="flex flex-col items-center text-center z-10 relative">
                                <div 
                                    :class="[
                                        'h-9 w-9 rounded-full flex items-center justify-center font-extrabold text-xs transition-all duration-300 border-2',
                                        currentStep >= 3 ? 'bg-emerald-600 text-white border-emerald-600 shadow-md ring-4 ring-emerald-50 dark:ring-emerald-950' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    <CheckCircle2 v-if="currentStep > 3" class="h-5 w-5" />
                                    <span v-else>3</span>
                                </div>
                                <span class="text-[10px] sm:text-[11px] font-extrabold mt-2 uppercase tracking-wide text-slate-700 dark:text-slate-200">Identitas</span>
                            </div>

                            <!-- Step 4 Node -->
                            <div class="flex flex-col items-center text-center z-10 relative">
                                <div 
                                    :class="[
                                        'h-9 w-9 rounded-full flex items-center justify-center font-extrabold text-xs transition-all duration-300 border-2',
                                        currentStep === 4 ? 'bg-emerald-600 text-white border-emerald-600 shadow-md ring-4 ring-emerald-50 dark:ring-emerald-950' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    <CheckCircle2 v-if="currentStep === 4" class="h-5 w-5" />
                                    <span v-else>4</span>
                                </div>
                                <span class="text-[10px] sm:text-[11px] font-extrabold mt-2 uppercase tracking-wide text-slate-700 dark:text-slate-200">Selesai</span>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 1: Pilih Lokasi Unit & Target Subjek/Fasilitas -->
                    <div v-if="currentStep === 1" class="space-y-4 animate-spa-fade-in">
                        <div class="bg-slate-50 dark:bg-slate-950 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 mb-5 text-center">
                            <div class="flex items-center justify-center gap-2 mb-1.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 dark:border-emerald-500/30">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Langkah 1 dari 4
                                </span>
                            </div>
                            <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Pilih Lokasi & Subjek</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pilih unit pelayanan rumah sakit dan objek yang ingin dilaporkan.</p>
                        </div>

                        <!-- Pilih Unit Pelayanan via SearchableSelect -->
                        <div class="space-y-1.5">
                            <InputLabel for="unit_id" value="Unit Pelayanan *" />
                            <div>
                                <SearchableSelect
                                    v-model="form.unit_id"
                                    :options="units"
                                    valueKey="id"
                                    labelKey="name"
                                    placeholder="-- Pilih Lokasi Pelayanan Rumah Sakit --"
                                    searchPlaceholder="Cari unit pelayanan..."
                                />
                            </div>
                        </div>

                        <!-- Nama / Fasilitas / Barang (Opsional) -->
                        <div class="space-y-1.5">
                            <InputLabel for="target_object" value="Nama / Fasilitas / Barang (Opsional)" />
                            <TextInput
                                id="target_object"
                                type="text"
                                class="block w-full"
                                v-model="form.target_object"
                                placeholder="Contoh: AC Rusak / Kloset Bocor / Nurse Sinta Dewi"
                            />
                        </div>

                        <!-- Desktop Step 1 Action (Inside Card) -->
                        <div class="hidden sm:block pt-5">
                            <button
                                type="button"
                                @click.stop.prevent="goToStep2"
                                :disabled="!form.unit_id || isTransitioning"
                                class="flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span>Lanjut ke Detail Laporan</span>
                                <ArrowRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: Tulis Detail Masukan & Upload Bukti -->
                    <div v-else-if="currentStep === 2" class="space-y-4 animate-spa-fade-in">
                        <div class="bg-slate-50 dark:bg-slate-950 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 mb-5 text-center">
                            <div class="flex items-center justify-center gap-2 mb-1.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 dark:border-emerald-500/30">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Langkah 2 dari 4
                                </span>
                            </div>
                            <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Detail & Bukti Foto</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Unit: <strong class="text-emerald-600 dark:text-emerald-400 font-extrabold">{{ selectedUnitObj?.name }}</strong>
                                <span v-if="form.target_object"> • Target: <strong class="text-emerald-600 dark:text-emerald-400 font-extrabold">{{ form.target_object }}</strong></span>
                            </p>
                        </div>

                        <!-- Detail Teks Laporan -->
                        <div class="space-y-1.5">
                            <InputLabel for="isi_laporan" value="Isi Pujian / Masukan / Keluhan *" />
                            <textarea
                                id="isi_laporan"
                                v-model="form.isi_laporan"
                                rows="4"
                                required
                                placeholder="Tuliskan pengalaman pelayanan, apresiasi pujian, atau kendala keluhan Anda di sini secara rinci..."
                                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-4 text-xs sm:text-sm focus:border-emerald-500 dark:focus:border-emerald-400 focus:bg-white dark:focus:bg-slate-950 focus:outline-none focus:ring-0 focus:shadow-none transition duration-150 leading-relaxed"
                            ></textarea>
                        </div>

                        <!-- Upload Bukti Foto / Video (Space Upload Area + Tombol Buka Kamera) -->
                        <div class="space-y-2.5">
                            <div>
                                <InputLabel value="Lampirkan Bukti Foto / Video" />
                            </div>

                            <!-- Inline Upload Error Alert Banner -->
                            <div 
                                v-if="uploadError" 
                                class="flex items-center justify-between gap-2 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-600 dark:text-rose-400 text-xs animate-spa-fade-in"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <AlertCircle class="h-4 w-4 shrink-0 text-rose-500" />
                                    <span class="font-medium text-[11px] sm:text-xs leading-tight">{{ uploadError }}</span>
                                </div>
                                <button 
                                    type="button" 
                                    @click.stop="uploadError = ''" 
                                    class="p-1 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-400 hover:text-rose-600 transition cursor-pointer shrink-0"
                                    title="Tutup Peringatan"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <!-- Hidden Inputs for Camera and Gallery (Accept ONLY Photo & Video) -->
                            <input 
                                ref="cameraInput" 
                                type="file" 
                                accept="image/*,video/*" 
                                capture="environment" 
                                class="hidden" 
                                @change="onFileSelected" 
                            />
                            <input 
                                ref="galleryInput" 
                                type="file" 
                                accept="image/*,video/*" 
                                class="hidden" 
                                @change="onFileSelected" 
                            />

                            <!-- Space Upload Area (Frame Dropzone / Preview) -->
                            <div 
                                class="relative w-full rounded-2xl overflow-hidden border transition-all duration-200"
                                :class="uploadedAttachment ? 'border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 shadow-none' : 'border-dashed border-slate-300 dark:border-slate-700 hover:border-emerald-500 bg-slate-50 dark:bg-slate-950 cursor-pointer'"
                                @click.stop="!uploadedAttachment && !isTransitioning && openGallery()"
                            >
                                <!-- State 1: Sedang Mengoptimalkan Media -->
                                <div v-if="isCompressing" class="flex flex-col items-center justify-center p-8 text-center select-none">
                                    <div class="h-8 w-8 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent mb-2.5"></div>
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                        Mengoptimalkan Ukuran Media...
                                    </p>
                                </div>

                                <!-- State 2: Kosong (Space Upload Area Siap Klik/Pilih) -->
                                <div v-else-if="!uploadedAttachment" class="flex flex-col items-center justify-center p-6 sm:p-7 text-center select-none">
                                    <div class="h-12 w-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-2.5">
                                        <UploadCloud class="h-6 w-6" />
                                    </div>
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Klik untuk Pilih Foto / Video dari Galeri
                                    </p>
                                    <span class="text-[11px] text-slate-400 font-medium mt-0.5">
                                        (Opsional)
                                    </span>
                                </div>

                                <!-- State 3: Ada Media (Preview Foto / Video Zoom & Menyesuaikan Space Area, Bersih Tanpa Gelap) -->
                                <div v-else class="relative w-full h-52 sm:h-64 flex items-center justify-center bg-slate-100 dark:bg-slate-900">
                                    <!-- Photo Preview -->
                                    <img 
                                        v-if="uploadedAttachment.isImage" 
                                        :src="uploadedAttachment.previewUrl" 
                                        :alt="uploadedAttachment.name"
                                        class="w-full h-full object-cover"
                                    />

                                    <!-- Video Preview -->
                                    <video 
                                        v-else-if="uploadedAttachment.isVideo" 
                                        :src="uploadedAttachment.previewUrl" 
                                        controls 
                                        class="w-full h-full object-cover"
                                    ></video>

                                    <!-- Floating Info Pill (Bottom Left) -->
                                    <div class="absolute bottom-3 left-3 flex items-center gap-1.5 bg-slate-900/80 backdrop-blur-md px-3 py-1.5 rounded-xl text-white shadow-md">
                                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-400 shrink-0" />
                                        <span class="text-xs font-bold">{{ uploadedAttachment.isImage ? 'Foto Terlampir' : 'Video Terlampir' }}</span>
                                        <span class="text-[10px] text-slate-300 font-semibold ml-0.5">{{ uploadedAttachment.sizeFormatted }}</span>
                                    </div>

                                    <!-- Floating Delete Button (Bottom Right) -->
                                    <button
                                        type="button"
                                        @click.stop="removeAttachment"
                                        class="absolute bottom-3 right-3 p-2.5 rounded-xl bg-rose-600/90 hover:bg-rose-600 text-white transition cursor-pointer shadow-lg hover:scale-105 active:scale-95"
                                        title="Hapus Lampiran"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Tombol Buka Kamera HP (Di Bawah Space Upload Area) -->
                            <div>
                                <button
                                    type="button"
                                    @click.stop.prevent="openCamera"
                                    :disabled="isTransitioning"
                                    class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl border border-emerald-500/30 hover:border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 font-semibold text-xs transition cursor-pointer focus:outline-none disabled:opacity-50"
                                >
                                    <Camera class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                    <span>{{ uploadedAttachment ? 'Ambil Ulang dari Kamera HP' : 'Buka Kamera HP (Foto / Video Langsung)' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Desktop Step 2 Actions (Inside Card) -->
                        <div class="hidden sm:flex items-center gap-3 pt-5">
                            <button
                                type="button"
                                @click.stop.prevent="goToStep1"
                                :disabled="isTransitioning"
                                class="flex h-11 px-5 cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-xs text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none disabled:opacity-50 shrink-0"
                            >
                                <ArrowLeft class="h-4 w-4" />
                                <span>Kembali</span>
                            </button>
                            <button
                                type="button"
                                @click.stop.prevent="goToStep3"
                                :disabled="!form.isi_laporan.trim() || isTransitioning"
                                class="flex-1 flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span>Lanjut ke Data Pelapor</span>
                                <ArrowRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: Identitas Pelapor -->
                    <div v-else-if="currentStep === 3" class="space-y-4 animate-spa-fade-in">
                        <div class="bg-slate-50 dark:bg-slate-950 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 mb-5 text-center">
                            <div class="flex items-center justify-center gap-2 mb-1.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 dark:border-emerald-500/30">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Langkah 3 dari 4
                                </span>
                            </div>
                            <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Identitas Pelapor</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Dapat dikosongkan untuk melaporkan secara Anonim.</p>
                        </div>

                        <!-- Gamification & Privacy Info Note Card -->
                        <div class="bg-emerald-50 dark:bg-emerald-950/30 rounded-2xl p-4 border border-emerald-500/20 dark:border-emerald-500/30 flex items-start gap-3 text-slate-700 dark:text-slate-300">
                            <Award class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <div class="text-xs leading-relaxed">
                                <strong class="text-slate-900 dark:text-white font-bold block mb-0.5">Keuntungan Mengisi Identitas:</strong>
                                Riwayat laporan Anda akan terhubung di sistem dan berpotensi mendapatkan **Apresiasi Pelapor Terdaftar**. Bila dikosongkan, laporan akan tetap diproses secara **ANONIM**.
                            </div>
                        </div>

                        <!-- Input Nama Pelapor -->
                        <div class="space-y-1.5">
                            <InputLabel for="reporter_name" value="Nama Lengkap Pelapor (Opsional / Anonim)" />
                            <TextInput
                                id="reporter_name"
                                type="text"
                                class="block w-full"
                                v-model="form.reporter_name"
                                placeholder="Biarkan kosong jika ingin ANONIM..."
                            />
                        </div>

                        <!-- Input No. HP / WA Pelapor -->
                        <div class="space-y-1.5">
                            <InputLabel for="reporter_phone" value="No. WhatsApp / Telepon (Opsional)" />
                            <TextInput
                                id="reporter_phone"
                                type="tel"
                                class="block w-full"
                                v-model="form.reporter_phone"
                                placeholder="Contoh: 081234567890..."
                            />
                        </div>

                        <!-- Desktop Step 3 Actions (Inside Card) -->
                        <div class="hidden sm:flex items-center gap-3 pt-5">
                            <button
                                type="button"
                                @click.stop.prevent="goToStep2From3"
                                :disabled="isSubmitting || isTransitioning"
                                class="flex h-11 px-5 cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-xs text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none disabled:opacity-50 shrink-0"
                            >
                                <ArrowLeft class="h-4 w-4" />
                                <span>Kembali</span>
                            </button>
                            <button
                                type="button"
                                @click.stop.prevent="submitReport"
                                :disabled="isSubmitting || isTransitioning"
                                class="flex-1 flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <span
                                    v-if="isSubmitting"
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                                ></span>
                                <template v-else>
                                    <Send class="h-4 w-4" />
                                    <span>Kirim Laporan</span>
                                </template>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 4: Laporan Berhasil & Struk Registrasi Digital -->
                    <div v-else-if="currentStep === 4" class="space-y-5 text-center animate-spa-fade-in py-2">
                        <!-- Success Icon Badge -->
                        <div class="h-16 w-16 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-full border border-emerald-500/20 dark:border-emerald-500/30 flex items-center justify-center mx-auto shadow-none">
                            <CheckCircle2 class="h-8 w-8" />
                        </div>

                        <div class="mb-4">
                            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Laporan Terkirim!</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                Terima kasih. Laporan Anda telah dicatat oleh sistem dan diteruskan ke Kepala Ruangan unit terkait.
                            </p>
                        </div>

                        <!-- Reporter Status Badge -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200">
                            <Shield v-if="!form.reporter_name" class="h-4 w-4 text-emerald-500" />
                            <User v-else class="h-4 w-4 text-emerald-500" />
                            <span>Status Pelapor: {{ form.reporter_name ? form.reporter_name + ' (Terdaftar)' : 'ANONIM' }}</span>
                        </div>

                        <!-- Receipt Code Box -->
                        <div class="bg-slate-50 dark:bg-slate-950 rounded-2xl p-4 border border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mb-1">Nomor Registrasi Laporan</span>
                            <div class="flex items-center justify-center gap-2">
                                <span class="text-xl font-mono font-extrabold text-emerald-600 dark:text-emerald-400 tracking-wider">{{ generatedReportId }}</span>
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

                        <div class="pt-3">
                            <PrimaryButton
                                class="w-full justify-center py-3"
                                @click="resetForm"
                            >
                                <RefreshCw class="h-4 w-4 me-2" />
                                <span>Buat Laporan Baru</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- Floating Bottom Navigation Bar for Steps 1, 2, 3 (ONLY MOBILE, Auto-hides when keyboard opens) -->
                <div
                    v-if="currentStep < 4"
                    v-show="!isKeyboardOpen"
                    class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-4 pt-3.5 pb-8 shadow-2xl transition-all duration-200"
                    :class="{ 'pointer-events-none': isTransitioning }"
                >
                    <!-- STEP 1 Action -->
                    <div v-if="currentStep === 1" class="w-full">
                        <button
                            type="button"
                            @click.stop.prevent="goToStep2"
                            :disabled="!form.unit_id || isTransitioning"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span>Lanjut ke Detail Laporan</span>
                            <ArrowRight class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- STEP 2 Action (Stacked) -->
                    <div v-else-if="currentStep === 2" class="flex flex-col gap-2.5 w-full">
                        <button
                            type="button"
                            @click.stop.prevent="goToStep3"
                            :disabled="!form.isi_laporan.trim() || isTransitioning"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span>Lanjut ke Data Pelapor</span>
                            <ArrowRight class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            @click.stop.prevent="goToStep1"
                            :disabled="isTransitioning"
                            class="flex h-10 w-full cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-semibold text-xs text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none disabled:opacity-50"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali ke Pilih Lokasi</span>
                        </button>
                    </div>

                    <!-- STEP 3 Action (Stacked) -->
                    <div v-else-if="currentStep === 3" class="flex flex-col gap-2.5 w-full">
                        <button
                            type="button"
                            @click.stop.prevent="submitReport"
                            :disabled="isSubmitting || isTransitioning"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <span
                                v-if="isSubmitting"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                            ></span>
                            <template v-else>
                                <Send class="h-4 w-4" />
                                <span>Kirim Laporan Sekarang</span>
                            </template>
                        </button>
                        <button
                            type="button"
                            @click.stop.prevent="goToStep2From3"
                            :disabled="isSubmitting || isTransitioning"
                            class="flex h-10 w-full cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-semibold text-xs text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none disabled:opacity-50"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali ke Detail & Bukti</span>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
