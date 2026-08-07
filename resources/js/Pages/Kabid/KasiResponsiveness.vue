<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    BarChart3, 
    AlertTriangle, 
    CheckCircle2, 
    Clock, 
    Building2, 
    Users,
    Download
} from '@lucide/vue';

const kasiData = ref([
    {
        id: 1,
        name: 'Ahmad, S.Farm',
        role: 'Kasi Farmasi',
        unit: 'Instalasi Farmasi',
        total_incoming: 42,
        verified_count: 40,
        pending_count: 2,
        avg_response: '1.2 Jam',
        response_rate: 95.2,
        status: 'EXCELLENT'
    },
    {
        id: 2,
        name: 'dr. H. Rahmat, Sp.B',
        role: 'Kasi Pelayanan Medik',
        unit: 'Instalasi Gawat Darurat (IGD)',
        total_incoming: 35,
        verified_count: 32,
        pending_count: 3,
        avg_response: '1.5 Jam',
        response_rate: 91.4,
        status: 'EXCELLENT'
    },
    {
        id: 3,
        name: 'Drs. Supriyadi, M.Kes',
        role: 'Kasi Administrasi & Keuangan',
        unit: 'Kasir & Pendaftaran',
        total_incoming: 28,
        verified_count: 20,
        pending_count: 8,
        avg_response: '4.8 Jam',
        response_rate: 71.4,
        status: 'WARNING'
    },
    {
        id: 4,
        name: 'Nrs. Maria Ulfa, S.Kep',
        role: 'Kasi Keperawatan',
        unit: 'Ruang Rawat Inap',
        total_incoming: 12,
        verified_count: 11,
        pending_count: 1,
        avg_response: '1.8 Jam',
        response_rate: 91.6,
        status: 'NORMAL'
    }
]);
</script>

<template>
    <Head title="Responsivitas Kasi — Executive Portal" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel (WA Gateway Style - Navbar handles Back button) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                        <BarChart3 class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Laporan Responsivitas Kepala Seksi
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20">
                                AKUNTABILITAS KASI
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Pemantauan kecepatan respons & persentase verifikasi aduan unit oleh Kepala Ruangan / Kasi.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 text-xs font-bold flex items-center gap-2 shadow-sm transition">
                        <Download class="h-4 w-4" />
                        <span>Ekspor Laporan (PDF/Excel)</span>
                    </button>
                </div>
            </div>

            <!-- Content Card Container Table (WA Gateway Style) -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Tabel Pemantauan Akuntabilitas Kasi Unit</h3>
                    <span class="text-xs text-slate-400">Total: {{ kasiData.length }} Kepala Ruangan</span>
                </div>

                <!-- Formula Banner PRD (Inside Table Card Container) -->
                <div class="bg-slate-50/80 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3.5 flex items-start gap-3 text-xs text-slate-700 dark:text-slate-300">
                    <BarChart3 class="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                    <div class="leading-relaxed">
                        <strong class="text-slate-900 dark:text-white block font-bold mb-0.5">Rumus Tingkat Responsivitas Supervisor:</strong>
                        Tingkat Respons (%) = (Jumlah Komplain Divalidasi / Total Komplain Masuk Unit) × 100%. Kasi dengan tingkat responsivitas di bawah <strong>80%</strong> akan diberi penanda peringatan khusus (*Warning Highlight*).
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 uppercase tracking-wider font-extrabold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-3.5 px-4">Nama Supervisor & Unit</th>
                                <th class="py-3.5 px-4 text-center">Total Komplain</th>
                                <th class="py-3.5 px-4 text-center">Divalidasi</th>
                                <th class="py-3.5 px-4 text-center">Pending / Diabaikan</th>
                                <th class="py-3.5 px-4 text-center">Rata2 Respon</th>
                                <th class="py-3.5 px-4 text-center">Tingkat Respons (%)</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                            <tr
                                v-for="kasi in kasiData"
                                :key="kasi.id"
                                :class="[
                                    'transition',
                                    kasi.status === 'WARNING' ? 'bg-rose-50/50 dark:bg-rose-950/20' : 'hover:bg-slate-50/60 dark:hover:bg-slate-950/40'
                                ]"
                            >
                                <td class="py-4 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white text-sm">{{ kasi.name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ kasi.role }} • {{ kasi.unit }}</div>
                                </td>
                                <td class="py-4 px-4 text-center font-bold">{{ kasi.total_incoming }}</td>
                                <td class="py-4 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400">{{ kasi.verified_count }}</td>
                                <td class="py-4 px-4 text-center font-bold text-amber-600 dark:text-amber-400">{{ kasi.pending_count }}</td>
                                <td class="py-4 px-4 text-center font-mono font-medium">{{ kasi.avg_response }}</td>
                                <td class="py-4 px-4 text-center">
                                    <span class="text-sm font-extrabold" :class="kasi.response_rate < 80 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'">
                                        {{ kasi.response_rate }}%
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span
                                        v-if="kasi.status === 'WARNING'"
                                        class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-200 dark:border-rose-800 inline-flex items-center gap-1"
                                    >
                                        <AlertTriangle class="h-3 w-3" /> Warning (&lt;80%)
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 dark:bg-white/10 dark:text-white border border-emerald-200 dark:border-white/20 inline-flex items-center gap-1"
                                    >
                                        <CheckCircle2 class="h-3 w-3" /> Sangat Baik
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
