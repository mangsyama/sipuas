<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    Sparkles, KeyRound, CheckCircle2, 
    Zap, Eye, EyeOff, Save, Play, ExternalLink, Activity, 
    AlertCircle, RotateCcw, Cpu, Sliders, MessageSquareText,
    ChevronDown, ChevronUp, Gauge
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
const showPromptDropdown = ref(false);

const tempPresets = [
    { val: 0.0, label: '0.0 Presisi' },
    { val: 0.2, label: '0.2 Standar RS' },
    { val: 0.5, label: '0.5 Seimbang' },
    { val: 0.8, label: '0.8 Variatif' },
    { val: 1.0, label: '1.0 Kreatif' }
];

const form = useForm({
    api_key: '',
    model_name: props.setting.model_name || 'openai/gpt-oss-120b',
    system_prompt: props.setting.system_prompt || '',
    is_active: props.setting.is_active ?? true,
    temperature: props.setting.temperature ?? 0.2
});

const tempDescription = computed(() => {
    const t = Number(form.temperature);
    if (t <= 0.2) {
        return {
            badge: 'Akurat & Konsisten (Sangat Disarankan)',
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
            form.api_key = '';
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
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Sparkles class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                Integrasi AI
                            </h2>
                            <span 
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase border',
                                    setting.has_api_key && setting.is_active 
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white dark:border-white/20'
                                        : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border-rose-200 dark:border-rose-800'
                                ]"
                            >
                                <span :class="['h-1.5 w-1.5 rounded-full', setting.has_api_key && setting.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500']"></span>
                                {{ setting.has_api_key && setting.is_active ? 'Groq AI Online' : 'Belum Terhubung' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Konfigurasi model AI Groq Cloud (LPU Hardware) untuk klasifikasi sentimen, auto-summary, dan rekomendasi laporan pasien
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <a
                        href="https://console.groq.com/keys"
                        target="_blank"
                        rel="noopener"
                        class="h-10 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center gap-2 transition shadow-sm"
                    >
                        <ExternalLink class="h-4 w-4" />
                        <span>Buka Konsol Groq</span>
                    </a>
                </div>
            </div>

            <!-- Top Metric Cards (3 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Mesin AI</span>
                        <div class="text-lg font-extrabold text-slate-900 dark:text-white leading-tight">Groq Cloud (LPU)</div>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block truncate max-w-[200px]">
                            {{ setting.model_name }}
                        </span>
                    </div>
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <Cpu class="h-6 w-6" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Status API Key</span>
                        <div class="text-lg font-extrabold text-slate-900 dark:text-white leading-tight">
                            {{ setting.has_api_key ? 'Tersimpan & Aktif' : 'Belum Diisi' }}
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono block">
                            {{ setting.masked_api_key || 'Kunci gsk_...' }}
                        </span>
                    </div>
                    <div :class="['h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0', setting.has_api_key ? 'bg-emerald-50 text-emerald-600 dark:bg-white/10 dark:text-white' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/40']">
                        <KeyRound class="h-6 w-6" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Koneksi Realtime</span>
                        <div class="text-lg font-extrabold text-slate-900 dark:text-white leading-tight">
                            {{ setting.last_test_status === 'CONNECTED' ? 'Terhubung (Online)' : (setting.last_test_status || 'Siap Diuji') }}
                        </div>
                        <span class="text-[11px] text-slate-400 block truncate max-w-[200px]">
                            {{ setting.last_tested_at ? 'Uji: ' + setting.last_tested_at : 'Belum ada pengujian' }}
                        </span>
                    </div>
                    <div :class="['h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0', setting.last_test_status === 'CONNECTED' ? 'bg-emerald-50 text-emerald-600 dark:bg-white/10 dark:text-white' : 'bg-slate-100 text-slate-500 dark:bg-slate-800']">
                        <Activity class="h-6 w-6" />
                    </div>
                </div>
            </div>

            <!-- Two Columns Layout: Settings & Playground -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                <!-- Column 1: Configuration Form (6 Cols) -->
                <div class="xl:col-span-6 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <Sliders class="h-4 w-4 text-emerald-600 dark:text-white" />
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Pengaturan Kunci & Model Groq</h3>
                        </div>
                    </div>

                    <form @submit.prevent="submitSaveSettings" class="space-y-4 text-xs">
                        <!-- API Key Input -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
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
                                    :placeholder="setting.has_api_key ? '••••••••••••••••••••••••••••••••••••••••' : 'Tempelkan kunci gsk_... disini'"
                                    class="w-full pl-3.5 pr-10 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono text-xs focus:outline-none focus:border-emerald-500"
                                />
                                <button
                                    type="button"
                                    @click="showApiKey = !showApiKey"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                                >
                                    <component :is="showApiKey ? EyeOff : Eye" class="h-4 w-4" />
                                </button>
                            </div>
                            <div v-if="form.errors.api_key" class="text-[11px] text-rose-500 mt-1 font-semibold">
                                {{ form.errors.api_key }}
                            </div>
                        </div>

                        <!-- Model Selection -->
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Pilihan Model AI Groq</label>
                            <select
                                v-model="form.model_name"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:border-emerald-500"
                            >
                                <option v-for="m in groqModels" :key="m.id" :value="m.id">
                                    {{ m.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Custom Premium Temperature Slider -->
                        <div class="p-4 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 space-y-3.5">
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
                                    <span class="px-2.5 py-0.5 rounded-lg bg-emerald-600 text-white font-mono font-extrabold text-xs shadow-sm">
                                        {{ Number(form.temperature).toFixed(2) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Clickable Preset Chips -->
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

                            <!-- Custom Slider Input with Dynamic Gradient Track -->
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

                            <!-- Visual Scale Markers -->
                            <div class="flex items-center justify-between text-[10px] text-slate-400 font-semibold px-0.5">
                                <span class="hover:text-emerald-600 cursor-pointer" @click="form.temperature = 0.0">0.0 (Presisi)</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-extrabold cursor-pointer" @click="form.temperature = 0.2">0.2 (Rekomendasi RS)</span>
                                <span class="hover:text-emerald-600 cursor-pointer" @click="form.temperature = 0.5">0.5</span>
                                <span class="hover:text-emerald-600 cursor-pointer" @click="form.temperature = 1.0">1.0 (Kreatif)</span>
                            </div>

                            <!-- Live Explanation Helper Box -->
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                                {{ tempDescription.desc }}
                            </div>
                        </div>

                        <!-- System Prompt (Collapsible Dropdown Accordion) -->
                        <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                            <button
                                type="button"
                                @click="showPromptDropdown = !showPromptDropdown"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-between text-xs font-bold text-slate-800 dark:text-slate-200 transition cursor-pointer"
                            >
                                <div class="flex items-center gap-2">
                                    <MessageSquareText class="h-4 w-4 text-slate-400" />
                                    <span>Instruksi Aturan Sistem (System Prompt)</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-400 font-normal">
                                    <span>{{ showPromptDropdown ? 'Sembunyikan' : 'Buka Pengaturan' }}</span>
                                    <component :is="showPromptDropdown ? ChevronUp : ChevronDown" class="h-4 w-4" />
                                </div>
                            </button>

                            <div v-if="showPromptDropdown" class="p-4 space-y-2.5 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-slate-400 font-medium">Aturan format respon JSON & instruksi triase:</span>
                                    <button
                                        type="button"
                                        @click="resetSystemPrompt"
                                        class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold hover:underline flex items-center gap-1 cursor-pointer"
                                    >
                                        <RotateCcw class="h-3 w-3" />
                                        Reset Standar
                                    </button>
                                </div>
                                <textarea
                                    v-model="form.system_prompt"
                                    rows="6"
                                    class="w-full p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-mono text-[11px] leading-relaxed text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500"
                                    placeholder="Masukkan instruksi prompt..."
                                ></textarea>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="pt-2 flex justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="h-10 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition disabled:opacity-50 cursor-pointer"
                            >
                                <Save class="h-4 w-4" />
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Column 2: Live Simulator & Playground (6 Cols) -->
                <div class="xl:col-span-6 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                            <div class="flex items-center gap-2">
                                <Play class="h-4 w-4 text-emerald-600 dark:text-white" />
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Simulator & Uji Live AI</h3>
                            </div>
                            <span class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded bg-emerald-50 dark:bg-white/10">
                                Realtime Engine
                            </span>
                        </div>

                        <!-- Preset Cases -->
                        <div>
                            <span class="block text-[11px] font-bold text-slate-400 mb-1.5">Pilih Contoh Laporan:</span>
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

                        <!-- Inputs for Context -->
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Unit Terkait</label>
                                <input
                                    type="text"
                                    v-model="testUnit"
                                    placeholder="Contoh: Instalasi Farmasi"
                                    class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Objek / Sasaran</label>
                                <input
                                    type="text"
                                    v-model="testObject"
                                    placeholder="Contoh: Loket 2"
                                    class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                        </div>

                        <!-- Test Text Area -->
                        <div class="text-xs">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Isi Teks Aduan Pasien</label>
                            <textarea
                                v-model="testText"
                                rows="3"
                                placeholder="Ketik teks laporan pasien..."
                                class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs leading-relaxed focus:outline-none focus:border-emerald-500"
                            ></textarea>
                        </div>

                        <!-- Run Button -->
                        <button
                            type="button"
                            @click="runAiTest"
                            :disabled="isTesting || !testText.trim()"
                            class="w-full h-10 rounded-xl bg-slate-900 hover:bg-black text-white dark:bg-emerald-600 dark:hover:bg-emerald-500 font-extrabold text-xs flex items-center justify-center gap-2 shadow-sm transition disabled:opacity-50 cursor-pointer"
                        >
                            <Play class="h-3.5 w-3.5" />
                            <span>{{ isTesting ? 'Memproses dengan Groq AI...' : 'Uji Analisis AI Sekarang' }}</span>
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
                        <div v-if="testResult" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-2">
                                <span class="text-xs font-extrabold text-slate-900 dark:text-white">Hasil Analisis Groq AI</span>
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
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Rekomendasi Tindakan (Untuk Kasi):</span>
                                    <p class="text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed text-[11px]">
                                        {{ testResult.analysis.action_recommendation }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

