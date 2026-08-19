<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
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
    Award
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

const selectedUnitObj = computed(() => {
    return props.units.find(u => u.id === form.value.unit_id);
});

const form = ref({
    unit_id: props.unitId || '',
    target_object: '',
    isi_laporan: '',
    uploaded_files: [],
    reporter_name: '',
    reporter_phone: ''
});

const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);
    if (!files.length) return;

    const availableSlots = 5 - form.value.uploaded_files.length;
    const filesToAdd = files.slice(0, availableSlots);

    filesToAdd.forEach(file => {
        const isImage = file.type.startsWith('image/');
        let previewUrl = null;
        if (isImage) {
            previewUrl = URL.createObjectURL(file);
        }
        form.value.uploaded_files.push({
            file,
            name: file.name,
            isImage,
            previewUrl
        });
    });

    event.target.value = '';
};

const removeFile = (index) => {
    const fileItem = form.value.uploaded_files[index];
    if (fileItem && fileItem.previewUrl) {
        URL.revokeObjectURL(fileItem.previewUrl);
    }
    form.value.uploaded_files.splice(index, 1);
};

const goToStep2 = () => {
    if (!form.value.unit_id) return;
    currentStep.value = 2;
};

const goToStep3 = () => {
    if (!form.value.isi_laporan.trim()) return;
    currentStep.value = 3;
};

const goToStep1 = () => {
    currentStep.value = 1;
};

const goToStep2From3 = () => {
    currentStep.value = 2;
};

const submitReport = () => {
    if (!form.value.isi_laporan.trim()) return;
    
    isSubmitting.value = true;
    setTimeout(() => {
        isSubmitting.value = false;
        generatedReportId.value = 'LP-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000);
        currentStep.value = 4;
    }, 1200);
};

const resetForm = () => {
    form.value.uploaded_files.forEach(f => {
        if (f.previewUrl) URL.revokeObjectURL(f.previewUrl);
    });
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

    <div class="min-h-screen flex flex-col justify-between relative overflow-hidden font-sans p-4 sm:p-6 text-slate-900 dark:text-slate-100">
        <!-- Background Image with Blur & Dark Overlay -->
        <div class="absolute inset-0 z-0 bg-cover bg-center filter blur-sm scale-105" style="background-image: url('/images/hospital-hero.jpg');"></div>
        <div class="absolute inset-0 z-0 bg-slate-900/50 dark:bg-slate-950/75 transition-colors duration-200"></div>

        <!-- Main Form Card Container with Logo -->
        <main class="w-full max-w-xl mx-auto z-20 my-auto flex flex-col items-center w-full py-4">
            <!-- Centered Logo directly above container -->
            <div class="text-center mb-5">
                <img src="/images/logo-sidebar.png" alt="SIPUAS Logo" class="h-12 sm:h-14 w-auto object-contain mx-auto brightness-0 invert drop-shadow-md" />
            </div>

            <!-- Solid White Container Card -->
            <div class="w-full bg-white dark:bg-slate-900 border border-white dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl transition-all duration-300">
                
                <!-- 4 Step Progress Bar Indicator -->
                <div class="mb-8 px-1 sm:px-2">
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
                    <div class="bg-slate-50/80 dark:bg-slate-950/60 p-4 sm:p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-5 text-center">
                        <div class="flex items-center justify-center gap-2 mb-1.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Langkah 1 dari 4
                            </span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Pilih Lokasi & Subjek</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pilih unit pelayanan rumah sakit dan objek yang ingin dilaporkan.</p>
                    </div>

                    <!-- Pilih Unit Pelayanan via SearchableSelect -->
                    <div>
                        <InputLabel for="unit_id" value="Unit Pelayanan *" />
                        <div class="mt-1">
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
                    <div>
                        <InputLabel for="target_object" value="Nama / Fasilitas / Barang (Opsional)" />
                        <TextInput
                            id="target_object"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.target_object"
                            placeholder="Contoh: AC Rusak / Kloset Bocor / Nurse Sinta Dewi"
                        />
                    </div>

                    <div class="pt-2">
                        <PrimaryButton
                            class="w-full justify-center py-2.5"
                            @click="goToStep2"
                            :disabled="!form.unit_id"
                        >
                            <span>Lanjut ke Detail Laporan</span>
                            <ArrowRight class="h-4 w-4 ms-2" />
                        </PrimaryButton>
                    </div>
                </div>

                <!-- STEP 2: Tulis Detail Masukan & Upload Bukti -->
                <div v-else-if="currentStep === 2" class="space-y-4 animate-spa-fade-in">
                    <div class="bg-slate-50/80 dark:bg-slate-950/60 p-4 sm:p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-5 text-center">
                        <div class="flex items-center justify-center gap-2 mb-1.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
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
                    <div>
                        <InputLabel for="isi_laporan" value="Isi Pujian / Masukan / Keluhan *" />
                        <textarea
                            id="isi_laporan"
                            v-model="form.isi_laporan"
                            rows="4"
                            required
                            placeholder="Tuliskan pengalaman pelayanan, apresiasi pujian, atau kendala keluhan Anda di sini secara rinci..."
                            class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-4 text-xs sm:text-sm focus:border-emerald-500 dark:focus:border-white focus:ring-0 transition duration-150 outline-none leading-relaxed"
                        ></textarea>
                    </div>

                    <!-- Upload Bukti Foto / Dokumen (Maks 5 File) -->
                    <div>
                        <InputLabel :value="'Upload Lampiran Foto / Dokumen (' + form.uploaded_files.length + '/5 File)'" />

                        <!-- Dropzone Area (if less than 5 files) -->
                        <div v-if="form.uploaded_files.length < 5" class="mt-1">
                            <label class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800 hover:border-emerald-500 bg-slate-50/50 dark:bg-slate-950/50 cursor-pointer transition">
                                <UploadCloud class="h-6 w-6 text-slate-400 mb-1" />
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Klik untuk Pilih Foto / File Bukti</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">Format: JPG, PNG, PDF • Maks. 5 File (5MB per file)</span>
                                <input type="file" multiple @change="handleFileUpload" accept="image/*,.pdf" class="hidden" />
                            </label>
                        </div>

                        <!-- Non-clickable Small Image Thumbnail Previews & File List -->
                        <div v-if="form.uploaded_files.length > 0" class="mt-2 space-y-2">
                            <div 
                                v-for="(item, idx) in form.uploaded_files" 
                                :key="idx" 
                                class="flex items-center justify-between p-2.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-xs"
                            >
                                <div class="flex items-center gap-3 min-w-0 pr-2">
                                    <!-- Non-clickable Small Thumbnail Preview -->
                                    <div v-if="item.isImage && item.previewUrl" class="h-10 w-10 shrink-0 rounded-xl overflow-hidden border border-emerald-200 dark:border-emerald-700 bg-slate-100 dark:bg-slate-800 pointer-events-none select-none">
                                        <img :src="item.previewUrl" :alt="item.name" class="h-full w-full object-cover" />
                                    </div>
                                    <div v-else class="h-10 w-10 shrink-0 rounded-xl flex items-center justify-center bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                        <Paperclip class="h-5 w-5" />
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ item.name }}</p>
                                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">File #{{ idx + 1 }} • Terlampir</span>
                                    </div>
                                </div>

                                <button 
                                    type="button" 
                                    @click="removeFile(idx)" 
                                    class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-950/80 transition cursor-pointer shrink-0"
                                    title="Hapus Lampiran"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 space-y-2.5">
                        <PrimaryButton
                            class="w-full justify-center py-2.5"
                            @click="goToStep3"
                            :disabled="!form.isi_laporan.trim()"
                        >
                            <span>Lanjut ke Data Pelapor</span>
                            <ArrowRight class="h-4 w-4 ms-2" />
                        </PrimaryButton>

                        <SecondaryButton
                            class="w-full justify-center py-2.5"
                            @click="goToStep1"
                        >
                            <ArrowLeft class="h-4 w-4 me-2" />
                            <span>Kembali ke Pilih Lokasi</span>
                        </SecondaryButton>
                    </div>
                </div>

                <!-- STEP 3: Identitas Pelapor -->
                <div v-else-if="currentStep === 3" class="space-y-4 animate-spa-fade-in">
                    <div class="bg-slate-50/80 dark:bg-slate-950/60 p-4 sm:p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 mb-5 text-center">
                        <div class="flex items-center justify-center gap-2 mb-1.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Langkah 3 dari 4
                            </span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Identitas Pelapor</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Dapat dikosongkan untuk melaporkan secara Anonim.</p>
                    </div>

                    <!-- Gamification & Privacy Info Note Card -->
                    <div class="bg-emerald-50/70 dark:bg-emerald-950/30 rounded-2xl p-4 border border-emerald-200/80 dark:border-emerald-800/40 flex items-start gap-3 text-slate-700 dark:text-slate-300">
                        <Award class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                        <div class="text-xs leading-relaxed">
                            <strong class="text-slate-900 dark:text-white font-bold block mb-0.5">Keuntungan Mengisi Identitas:</strong>
                            Riwayat laporan Anda akan terhubung di sistem dan berpotensi mendapatkan **Apresiasi Pelapor Terdaftar**. Bila dikosongkan, laporan akan tetap diproses secara **ANONIM**.
                        </div>
                    </div>

                    <!-- Input Nama Pelapor -->
                    <div>
                        <InputLabel for="reporter_name" value="Nama Lengkap Pelapor (Opsional / Anonim)" />
                        <TextInput
                            id="reporter_name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.reporter_name"
                            placeholder="Biarkan kosong jika ingin ANONIM..."
                        />
                    </div>

                    <!-- Input No. HP / WA Pelapor -->
                    <div>
                        <InputLabel for="reporter_phone" value="No. WhatsApp / Telepon (Opsional)" />
                        <TextInput
                            id="reporter_phone"
                            type="tel"
                            class="mt-1 block w-full"
                            v-model="form.reporter_phone"
                            placeholder="Contoh: 081234567890..."
                        />
                    </div>

                    <div class="pt-2 space-y-2.5">
                        <PrimaryButton
                            class="w-full justify-center py-2.5"
                            @click="submitReport"
                            :loading="isSubmitting"
                            :disabled="isSubmitting"
                        >
                            <span v-if="isSubmitting">Mengirim Laporan...</span>
                            <template v-else>
                                <Send class="h-4 w-4 me-2" />
                                <span>Kirim Laporan Sekarang</span>
                            </template>
                        </PrimaryButton>

                        <SecondaryButton
                            class="w-full justify-center py-2.5"
                            @click="goToStep2From3"
                        >
                            <ArrowLeft class="h-4 w-4 me-2" />
                            <span>Kembali ke Detail & Bukti</span>
                        </SecondaryButton>
                    </div>
                </div>

                <!-- STEP 4: Laporan Berhasil & Struk Registrasi Digital -->
                <div v-else-if="currentStep === 4" class="space-y-5 text-center animate-spa-fade-in">
                    <!-- Success Icon Badge -->
                    <div class="h-16 w-16 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-full border border-emerald-200 dark:border-emerald-800 flex items-center justify-center mx-auto">
                        <CheckCircle2 class="h-8 w-8" />
                    </div>

                    <div class="mb-6">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Laporan Berhasil Terkirim!</h2>
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
                                class="p-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 transition cursor-pointer"
                                title="Salin Kode Laporan"
                            >
                                <Copy class="h-4 w-4" />
                            </button>
                        </div>
                        <span v-if="copied" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block mt-1">Kode berhasil disalin!</span>
                    </div>

                    <div class="pt-2">
                        <PrimaryButton
                            class="w-full justify-center py-2.5"
                            @click="resetForm"
                        >
                            <RefreshCw class="h-4 w-4 me-2" />
                            <span>Buat Laporan Baru</span>
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer Copyright -->
        <footer class="w-full max-w-xl mx-auto px-4 pt-4 pb-2 text-center text-xs text-white/80 dark:text-slate-400 z-0 font-medium drop-shadow">
            &copy; {{ new Date().getFullYear() }} SIPUAS - Sistem Integrasi Pelayanan Publik & Akuntabilitas Staf
        </footer>
    </div>
</template>
