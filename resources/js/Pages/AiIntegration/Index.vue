<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
    Cpu, 
    KeyRound, 
    CheckCircle2, 
    AlertCircle, 
    ExternalLink, 
    RotateCcw, 
    Sliders, 
    Play, 
    ShieldCheck, 
    Eye, 
    EyeOff, 
    Save, 
    Radio, 
    Gauge,
    Sparkles
} from '@lucide/vue';

const props = defineProps({
    setting: {
        type: Object,
        required: true
    },
    groqModels: {
        type: Array,
        default: () => []
    }
});

const showApiKey = ref(false);
const showSettingsModal = ref(false);
const showTestModal = ref(false);

const tempPresets = [
    { val: 0.0, label: '0.0 Presisi' },
    { val: 0.2, label: '0.2 Standar RS' },
    { val: 0.5, label: '0.5 Seimbang' },
    { val: 0.8, label: '0.8 Variatif' },
    { val: 1.0, label: '1.0 Kreatif' }
];

const form = useForm({
    api_key: props.setting.api_key || '',
    model_name: props.setting.model_name || 'openai/gpt-oss-120b',
    system_prompt: props.setting.system_prompt || '',
    is_active: props.setting.is_active ?? true,
    temperature: props.setting.temperature ?? 0.2
});

watch(() => props.setting.api_key, (newKey) => {
    form.api_key = newKey || '';
});

const tempDescription = computed(() => {
    const t = Number(form.temperature);
    if (t <= 0.2) {
        return {
            badge: 'Akurat & Konsisten',
            badgeClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            desc: 'AI patuh kaku pada aturan triase. Skor KPI & klasifikasi sentimen deterministik dan objektif tanpa bias.'
        };
    }
    if (t <= 0.6) {
        return {
            badge: 'Mode Seimbang',
            badgeClass: 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            desc: 'Logika penilaian stabil dengan sedikit variasi pada pemilihan kata di ringkasan cerita laporan.'
        };
    }
    return {
        badge: 'Mode Variatif / Kreatif',
        badgeClass: 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800',
        desc: 'AI lebih bebas berimajinasi dan menggunakan ragam kosakata santai (kurang disarankan untuk triase medik).'
    };
});

const submitSaveSettings = () => {
    form.post(route('admin.ai-settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showSettingsModal.value = false;
        }
    });
};

// Live AI Playground State
const isTesting = ref(false);
const testText = ref('Ambil obat lama sekali, sudah antre 2 jam petugas di loket 2 malah asyik ngobrol dan lambat melayani.');
const testUnit = ref('Instalasi Farmasi');
const testObject = ref('Loket 2 Penyerahan Obat');
const testResult = ref(null);
const testError = ref(null);

const testPresets = [
    {
        label: 'Komplain Farmasi',
        text: 'Ambil obat lama sekali, sudah antre 2 jam petugas di loket 2 malah asyik ngobrol dan lambat melayani.',
        unit: 'Instalasi Farmasi',
        object: 'Loket 2'
    },
    {
        label: 'Pujian Pelayanan',
        text: 'Suster Sinta sangat ramah, sabar, dan sigap mengarahkan ibu saya saat pendaftaran resep obat.',
        unit: 'Poliklinik Rawat Jalan',
        object: 'Suster Sinta'
    },
    {
        label: 'Fasilitas Rusak',
        text: 'Ruang tunggu IGD sangat panas, AC mati dan ada kursi roda yang bannya macet membahayakan pasien.',
        unit: 'Instalasi Gawat Darurat (IGD)',
        object: 'Ruang Tunggu'
    }
];

const loadPreset = (preset) => {
    testText.value = preset.text;
    testUnit.value = preset.unit;
    testObject.value = preset.object;
};

const runAiTest = async () => {
    if (!testText.value.trim() || isTesting.value) return;

    isTesting.value = true;
    testError.value = null;

    try {
        const response = await fetch(route('admin.ai-settings.test'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                test_text: testText.value,
                unit_name: testUnit.value,
                target_object: testObject.value
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            testResult.value = data;
        } else {
            testError.value = data.error || 'Terjadi kendala saat memproses dengan Groq AI.';
            testResult.value = null;
        }
    } catch (err) {
        testError.value = 'Gagal menghubungi server: ' + err.message;
        testResult.value = null;
    } finally {
        isTesting.value = false;
    }
};

const resetSystemPrompt = () => {
    form.system_prompt = `Anda adalah AI Analisis Pelayanan Pasien Rumah Sakit (SIPUAS). Tugas Anda adalah menganalisis teks laporan atau keluhan dari pasien/keluarga pasien secara objektif.

Berikan output HANYA dalam format JSON valid tanpa markdown dengan struktur berikut:
{
  "sentiment": "POSITIF" atau "NEGATIF" atau "NETRAL",
  "score": 5 (jika pujian) atau -5 (jika keluhan) atau 0 (jika netral),
  "confidence": "persentase keyakinan misal 95%",
  "category": "Kategori spesifik (contoh: Waktu Tunggu & Pelayanan, Sarana & Prasarana, Sikap & Keramahan Staf, Komunikasi Efektif, Kebersihan, Administrasi & Kasir)",
  "summary": "Ringkasan inti keluhan/pujian dalam 1 kalimat padat dan jelas",
  "urgency": "RENDAH" atau "SEDANG" atau "TINGGI" atau "KRITIS",
  "mentioned_entities": ["daftar nama staf, dokter, perawat, atau nomor loket/ruangan yang disebut di teks"],
  "action_recommendation": "Saran tindakan perbaikan nyata untuk pihak manajemen/Kepala Seksi unit terkait"
}`;
};
</script>

<template>
    <Head title="Integrasi AI" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                <!-- Header Panel (ALWAYS VISIBLE - WaGateway Style) -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Cpu class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Integrasi AI
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Status koneksi, model LPU Groq Cloud, dan pengujian analisis cerdas laporan pasien
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3">
                        <a
                            href="https://console.groq.com/keys"
                            target="_blank"
                            rel="noopener"
                            class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center sm:justify-start gap-2 transition cursor-pointer"
                        >
                            <ExternalLink class="h-4 w-4" />
                            <span>Konsol Groq</span>
                        </a>

                        <button 
                            @click="showTestModal = true" 
                            class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center justify-center sm:justify-start gap-2 transition shadow-sm border-0 cursor-pointer"
                        >
                            <Play class="h-4 w-4" />
                            <span>Uji Coba AI</span>
                        </button>
                    </div>
                </div>

                <!-- Main Content Card: Status Layanan AI (WaGateway Style) -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                            Status Layanan AI
                        </h3>

                        <!-- Status Badge -->
                        <span 
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold uppercase border',
                                setting.has_api_key && setting.is_active 
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white dark:border-white/20' 
                                    : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border-rose-200 dark:border-rose-800'
                            ]"
                        >
                            <span class="h-2 w-2 rounded-full" :class="setting.has_api_key && setting.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                            {{ setting.has_api_key && setting.is_active ? 'Terhubung (Online)' : 'Belum Dikonfigurasi' }}
                        </span>
                    </div>

                    <!-- CASE 1: CONNECTED / ACTIVE STATE -->
                    <div v-if="setting.has_api_key && setting.is_active" class="p-5 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-900/50 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="h-11 w-11 shrink-0 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                    <Cpu class="h-6 w-6" />
                                </div>
                                <div>
                                    <div class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                        <span>{{ setting.model_name }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-white/15 dark:text-white">
                                            Groq LPU
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex flex-wrap items-center gap-x-2.5 gap-y-1">
                                        <span class="font-mono">{{ setting.masked_api_key || 'Kunci gsk_...' }}</span>
                                        <span class="hidden sm:inline text-slate-300 dark:text-slate-700">•</span>
                                        <span class="font-sans font-medium">Suhu {{ Number(form.temperature).toFixed(2) }} ({{ tempDescription.badge }})</span>
                                    </div>
                                </div>
                            </div>

                            <button 
                                @click="showSettingsModal = true" 
                                class="w-full sm:w-auto px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold flex items-center justify-center sm:justify-start gap-1.5 transition border border-slate-200 dark:border-slate-700 cursor-pointer order-last sm:order-none"
                            >
                                <Sliders class="h-3.5 w-3.5 text-slate-500" />
                                <span>Konfigurasi</span>
                            </button>
                        </div>

                        <div class="text-xs text-emerald-800 dark:text-emerald-300 leading-relaxed bg-white/80 dark:bg-slate-900/80 p-3.5 rounded-xl border border-emerald-100 dark:border-emerald-900/30 flex items-start gap-2.5">
                            <ShieldCheck class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <span>Layanan AI Groq Cloud terhubung aktif! Semua laporan dan keluhan pasien yang masuk ke SIPUAS otomatis dianalisis secara cerdas untuk klasifikasi sentimen, poin KPI staf, dan rekomendasi tindak lanjut bagi manajemen.</span>
                        </div>
                    </div>

                    <!-- CASE 2: UNCONFIGURED / INACTIVE STATE -->
                    <div v-else class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-center space-y-3">
                        <div class="h-10 w-10 rounded-full bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
                            <AlertCircle class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">API Key Groq Belum Terpasang</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                                Integrasi AI memerlukan API Key dari Groq Cloud untuk menjalankan inferensi model LPU secara otomatis pada setiap aduan masyarakat.
                            </p>
                        </div>
                        <div class="pt-2">
                            <button 
                                @click="showSettingsModal = true"
                                class="w-full sm:w-auto h-10 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold inline-flex items-center justify-center gap-2 transition cursor-pointer"
                            >
                                <KeyRound class="h-4 w-4" />
                                <span>Pasang API Key Sekarang</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL 1: PENGATURAN INTEGRASI AI (WaGateway Modal Pattern) -->
            <Modal :show="showSettingsModal" @close="showSettingsModal = false" max-width="2xl">
                <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900">
                    <!-- Solid Emerald Sticky Header (WaGateway Style) -->
                    <div class="bg-emerald-600 dark:bg-emerald-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-emerald-500/30 dark:border-emerald-800/50 shadow-sm">
                        <div class="flex items-center gap-3 pr-2">
                            <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                                <Sliders class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-white leading-tight">
                                    Pengaturan Integrasi AI
                                </h3>
                                <p class="text-xs text-emerald-100/90 dark:text-emerald-200/90 mt-0.5 font-medium">
                                    Konfigurasi API key, model Groq LPU, temperature, dan system prompt
                                </p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submitSaveSettings" class="flex flex-col flex-1 justify-between min-h-0">
                        <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1 text-xs">
                            <!-- Info Banner -->
                            <div class="p-3.5 sm:p-4 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/60 dark:border-emerald-900/50 text-emerald-900 dark:text-emerald-200 text-xs leading-relaxed flex items-start gap-2.5">
                                <Cpu class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                                <div>
                                    <span class="font-bold">Konfigurasi Mesin Inferensi Groq LPU:</span>
                                    <span class="font-normal block mt-0.5 text-emerald-800 dark:text-emerald-300">
                                        Pastikan API key aktif dari console.groq.com. Kunci disimpan terenkripsi di database dan digunakan untuk klasifikasi otomatis laporan.
                                    </span>
                                </div>
                            </div>

                            <!-- API Key Input -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-slate-700 dark:text-slate-300">API Key Secret (Groq)</label>
                                    <span v-if="setting.has_api_key" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                                        <CheckCircle2 class="h-3.5 w-3.5" />
                                        Tersimpan
                                    </span>
                                </div>
                                <div class="relative">
                                    <input
                                        :type="showApiKey ? 'text' : 'password'"
                                        v-model="form.api_key"
                                        placeholder="Tempelkan kunci gsk_... disini"
                                        class="w-full pl-3.5 pr-10 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-mono text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    />
                                    <button
                                        type="button"
                                        @click="showApiKey = !showApiKey"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                                        :title="showApiKey ? 'Sembunyikan API Key' : 'Tampilkan API Key'"
                                    >
                                        <component :is="showApiKey ? EyeOff : Eye" class="h-4 w-4" />
                                    </button>
                                </div>
                                <div v-if="form.errors.api_key" class="text-[11px] text-rose-500 font-semibold">
                                    {{ form.errors.api_key }}
                                </div>
                            </div>

                            <!-- Model Selection -->
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700 dark:text-slate-300">Pilihan Model AI Groq</label>
                                <select
                                    v-model="form.model_name"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                >
                                    <option v-for="m in groqModels" :key="m.id" :value="m.id">
                                        {{ m.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Temperature Slider -->
                            <div class="p-4 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 space-y-3.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <Gauge class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                        <label class="font-extrabold text-slate-900 dark:text-white text-xs">
                                            Temperature (Konsistensi AI)
                                        </label>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span :class="['px-2 py-0.5 rounded-md text-[10px] font-extrabold border', tempDescription.badgeClass]">
                                            {{ tempDescription.badge }}
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-lg bg-emerald-600 text-white font-sans font-extrabold text-xs shadow-sm">
                                            {{ Number(form.temperature).toFixed(2) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Presets -->
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button
                                        v-for="p in tempPresets"
                                        :key="p.val"
                                        type="button"
                                        @click="form.temperature = p.val"
                                        :class="[
                                            'px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer border',
                                            Number(form.temperature) === p.val
                                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                                                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-emerald-500'
                                        ]"
                                    >
                                        {{ p.label }}
                                    </button>
                                </div>

                                <!-- Range Slider -->
                                <div class="relative pt-1 pb-0.5">
                                    <input
                                        type="range"
                                        min="0"
                                        max="1"
                                        step="0.05"
                                        v-model="form.temperature"
                                        class="custom-range-slider cursor-pointer"
                                        :style="{
                                            background: `linear-gradient(to right, #059669 0%, #10b981 ${form.temperature * 100}%, #cbd5e1 ${form.temperature * 100}%, #cbd5e1 100%)`
                                        }"
                                    />
                                </div>

                                <!-- Markers -->
                                <div class="flex items-center justify-between text-[10px] text-slate-400 font-semibold px-0.5">
                                    <span class="hover:text-emerald-600 cursor-pointer" @click="form.temperature = 0.0">0.0 (Presisi)</span>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-extrabold cursor-pointer" @click="form.temperature = 0.2">0.2 (Rekomendasi RS)</span>
                                    <span class="hover:text-emerald-600 cursor-pointer" @click="form.temperature = 0.5">0.5</span>
                                    <span class="hover:text-emerald-600 cursor-pointer" @click="form.temperature = 1.0">1.0 (Kreatif)</span>
                                </div>

                                <!-- Explanation Box -->
                                <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                                    {{ tempDescription.desc }}
                                </div>
                            </div>

                            <!-- System Prompt Textarea -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-slate-700 dark:text-slate-300">Instruksi Aturan Sistem (System Prompt)</label>
                                    <button
                                        type="button"
                                        @click="resetSystemPrompt"
                                        class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold hover:underline flex items-center gap-1 cursor-pointer"
                                    >
                                        <RotateCcw class="h-3 w-3" />
                                        <span>Reset Standar</span>
                                    </button>
                                </div>
                                <textarea
                                    v-model="form.system_prompt"
                                    rows="6"
                                    class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 font-mono text-[11px] leading-relaxed text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    placeholder="Instruksi prompt sistem..."
                                ></textarea>
                            </div>
                        </div>

                        <!-- Sticky Action Footer -->
                        <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 sticky bottom-0 z-10 shrink-0">
                            <SecondaryButton type="button" @click="showSettingsModal = false" class="h-11 px-5">Batal</SecondaryButton>
                            <PrimaryButton type="submit" :disabled="form.processing" class="h-11 px-6 !bg-emerald-600 hover:!bg-emerald-500 font-bold flex items-center gap-2">
                                <Save class="h-4 w-4" />
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- MODAL 2: UJI COBA ANALISIS AI (WaGateway Modal Pattern) -->
            <Modal :show="showTestModal" @close="showTestModal = false" max-width="2xl">
                <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900">
                    <!-- Solid Emerald Sticky Header (WaGateway Style) -->
                    <div class="bg-emerald-600 dark:bg-emerald-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-emerald-500/30 dark:border-emerald-800/50 shadow-sm">
                        <div class="flex items-center gap-3 pr-2">
                            <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                                <Play class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-white leading-tight">
                                    Uji Coba & Simulator Analisis AI
                                </h3>
                                <p class="text-xs text-emerald-100/90 dark:text-emerald-200/90 mt-0.5 font-medium">
                                    Simulasi klasifikasi sentimen, KPI scoring, dan triase aduan pasien
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1 text-xs">
                        <!-- Info Banner -->
                        <div class="p-3.5 sm:p-4 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/60 dark:border-emerald-900/50 text-emerald-900 dark:text-emerald-200 text-xs leading-relaxed flex items-start gap-2.5">
                            <Radio class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5 animate-pulse" />
                            <div>
                                <span class="font-bold">Pengujian Analisis Realtime:</span>
                                <span class="font-normal block mt-0.5 text-emerald-800 dark:text-emerald-300">
                                    Gunakan simulator ini untuk menguji performa inferensi model Groq terhadap aduan pasien nyata sebelum diterapkan secara sistem.
                                </span>
                            </div>
                        </div>

                        <!-- Presets -->
                        <div class="space-y-1.5">
                            <span class="block text-[11px] font-bold text-slate-400">Pilih Contoh Laporan:</span>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="(preset, idx) in testPresets"
                                    :key="idx"
                                    type="button"
                                    @click="loadPreset(preset)"
                                    class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 hover:border-emerald-500 text-slate-700 dark:text-slate-300 text-xs font-bold transition cursor-pointer"
                                >
                                    {{ preset.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Two Column Context -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Unit Terkait</label>
                                <input
                                    type="text"
                                    v-model="testUnit"
                                    placeholder="Contoh: Instalasi Farmasi"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Objek / Sasaran</label>
                                <input
                                    type="text"
                                    v-model="testObject"
                                    placeholder="Contoh: Loket 2"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Test Text Area -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase">Isi Teks Aduan Pasien</label>
                            <textarea
                                v-model="testText"
                                rows="3"
                                placeholder="Ketik teks laporan pasien..."
                                class="w-full p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs leading-relaxed focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            ></textarea>
                        </div>

                        <!-- Action Button inside body -->
                        <button
                            type="button"
                            @click="runAiTest"
                            :disabled="isTesting || !testText.trim()"
                            class="w-full h-10 rounded-xl bg-slate-900 hover:bg-black text-white dark:bg-emerald-600 dark:hover:bg-emerald-500 font-extrabold text-xs flex items-center justify-center gap-2 shadow-sm transition disabled:opacity-50 cursor-pointer"
                        >
                            <Play class="h-3.5 w-3.5" />
                            <span>{{ isTesting ? 'Memproses dengan Groq AI...' : 'Jalankan Analisis AI Sekarang' }}</span>
                        </button>

                        <!-- Error Box -->
                        <div v-if="testError" class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs flex items-start gap-2.5">
                            <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
                            <div class="space-y-0.5">
                                <p class="font-bold">Gagal Menjalankan Analisis:</p>
                                <p class="text-[11px] leading-relaxed">{{ testError }}</p>
                            </div>
                        </div>

                        <!-- Structured Result Display -->
                        <div v-if="testResult" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                                <span class="text-xs font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <Sparkles class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                    Hasil Analisis Groq AI
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-white/10 dark:text-white">
                                    GROQ_AI • {{ testResult.execution_time_ms }} ms
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase">Sentimen</span>
                                    <div :class="[
                                        'text-xs font-extrabold mt-0.5',
                                        testResult.analysis.sentiment === 'POSITIF' ? 'text-emerald-600' : (testResult.analysis.sentiment === 'NEGATIF' ? 'text-rose-600' : 'text-slate-600')
                                    ]">
                                        {{ testResult.analysis.sentiment }}
                                    </div>
                                </div>
                                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase">Poin KPI</span>
                                    <div :class="['text-xs font-extrabold mt-0.5', testResult.analysis.score > 0 ? 'text-emerald-600' : 'text-rose-600']">
                                        {{ testResult.analysis.score > 0 ? '+' + testResult.analysis.score : testResult.analysis.score }} Poin
                                    </div>
                                </div>
                                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase">Urgensi</span>
                                    <div class="text-xs font-extrabold mt-0.5 text-amber-600 dark:text-amber-400">
                                        {{ testResult.analysis.urgency }}
                                    </div>
                                </div>
                                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase">Confidence</span>
                                    <div class="text-xs font-extrabold mt-0.5 text-blue-600 dark:text-blue-400">
                                        {{ testResult.analysis.confidence }}
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1.5 text-xs">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Kategori:</span>
                                    <span class="font-bold text-slate-900 dark:text-white ml-1.5">{{ testResult.analysis.category }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Ringkasan Inti:</span>
                                    <p class="text-slate-700 dark:text-slate-200 mt-0.5 leading-relaxed font-semibold italic">
                                        "{{ testResult.analysis.summary }}"
                                    </p>
                                </div>
                                <div v-if="testResult.analysis.mentioned_entities && testResult.analysis.mentioned_entities.length > 0">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Entitas Disebut:</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <span 
                                            v-for="(ent, i) in testResult.analysis.mentioned_entities" 
                                            :key="i"
                                            class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-[10px] font-bold"
                                        >
                                            {{ ent }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Rekomendasi Tindakan:</span>
                                    <p class="text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed text-[11px]">
                                        {{ testResult.analysis.action_recommendation }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Action Footer -->
                    <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 sticky bottom-0 z-10 shrink-0">
                        <SecondaryButton type="button" @click="showTestModal = false" class="h-11 px-5">Tutup</SecondaryButton>
                        <PrimaryButton 
                            type="button" 
                            @click="runAiTest" 
                            :disabled="isTesting || !testText.trim()" 
                            class="h-11 px-6 !bg-emerald-600 hover:!bg-emerald-500 font-bold"
                        >
                            {{ isTesting ? 'Menganalisis...' : 'Uji Coba Lagi' }}
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-range-slider {
    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    height: 7px;
    border-radius: 9999px;
    outline: none;
    transition: all 0.2s ease-in-out;
}

.custom-range-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #059669;
    border: 2.5px solid #ffffff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25), 0 0 0 3px rgba(16, 185, 129, 0.3);
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.custom-range-slider::-webkit-slider-thumb:hover {
    transform: scale(1.2);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3), 0 0 0 5px rgba(16, 185, 129, 0.4);
}

.custom-range-slider::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #059669;
    border: 2.5px solid #ffffff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25), 0 0 0 3px rgba(16, 185, 129, 0.3);
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.custom-range-slider::-moz-range-thumb:hover {
    transform: scale(1.2);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3), 0 0 0 5px rgba(16, 185, 129, 0.4);
}
</style>
