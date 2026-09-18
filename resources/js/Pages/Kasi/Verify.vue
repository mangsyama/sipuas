<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    UserCheck, 
    Clock, 
    CheckCircle2, 
    AlertCircle, 
    ShieldCheck, 
    TrendingDown, 
    TrendingUp, 
    Users, 
    MessageSquare,
    Send,
    Sparkles,
    Building2,
    Phone,
    User,
    Image,
    Maximize2,
    X,
    ExternalLink,
    AlertTriangle,
    FileText,
    ArrowLeft,
    Check,
    Calendar,
    Minus,
    Plus,
    Wrench,
    RefreshCw,
    Info,
    Paperclip,
    Trash2,
    Download
} from '@lucide/vue';

const props = defineProps({
    id: {
        type: String,
        default: ''
    },
    reportDetail: {
        type: Object,
        default: () => null
    },
    staffList: {
        type: Array,
        default: () => []
    },
    staffMembers: {
        type: Array,
        default: () => []
    },
    pesupeluhCategories: {
        type: Array,
        default: () => []
    },
    pesupeluhRooms: {
        type: Array,
        default: () => []
    }
});

const isSubmitting = ref(false);
const showSuccessModal = ref(false);
const showConfirmModal = ref(false);
const showValidationModal = ref(false);
const selectedImagePreview = ref(null);

// Modal Keyboard (Escape) & Browser Back (popstate) standard handling
const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
        if (showConfirmModal.value) {
            e.preventDefault();
            showConfirmModal.value = false;
        } else if (showValidationModal.value) {
            e.preventDefault();
            showValidationModal.value = false;
        } else if (selectedImagePreview.value) {
            e.preventDefault();
            selectedImagePreview.value = null;
        } else if (showSuccessModal.value) {
            e.preventDefault();
            finishVerification();
        }
    }
};

const handlePopState = () => {
    if (showConfirmModal.value) {
        showConfirmModal.value = false;
    } else if (showValidationModal.value) {
        showValidationModal.value = false;
    } else if (selectedImagePreview.value) {
        selectedImagePreview.value = null;
    } else if (showSuccessModal.value) {
        finishVerification();
    }
};

let pushHistoryFlag = false;

watch(
    [showConfirmModal, showValidationModal, showSuccessModal, selectedImagePreview],
    ([confirmOpen, validOpen, successOpen, imgOpen], [oldConfirm, oldValid, oldSuccess, oldImg]) => {
        if (typeof document !== 'undefined') {
            const isAnyOpen = confirmOpen || validOpen || successOpen || !!imgOpen;
            const wasAnyOpen = oldConfirm || oldValid || oldSuccess || !!oldImg;

            if (isAnyOpen) {
                document.body.style.overflow = 'hidden';
                if (!window.history.state?.verifyModalOpen) {
                    try {
                        window.history.pushState({ verifyModalOpen: true }, '');
                        pushHistoryFlag = true;
                    } catch (e) {}
                }
            } else {
                document.body.style.overflow = '';
                if (wasAnyOpen && pushHistoryFlag && window.history.state?.verifyModalOpen) {
                    pushHistoryFlag = false;
                    try {
                        window.history.back();
                    } catch (e) {}
                } else {
                    pushHistoryFlag = false;
                }
            }
        }
    }
);

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('popstate', handlePopState);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('popstate', handlePopState);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});

const report = computed(() => props.reportDetail);
const isVerified = computed(() => report.value?.status === 'VERIFIED');

const isFacilityComplaint = computed(() => {
    const unit = (report.value?.unit || '').toLowerCase();
    const isi = (report.value?.isi_laporan || '').toLowerCase();
    const obj = (report.value?.target_object || '').toLowerCase();
    const facilityKeywords = [
        'ac', 'rusak', 'toilet', 'wc', 'kran', 'lampu', 'pintu', 'air', 'bocor', 
        'lift', 'sarpras', 'fasilitas', 'kebersihan', 'mati lampu', 'wastafel'
    ];
    return facilityKeywords.some(k => unit.includes(k) || isi.includes(k) || obj.includes(k));
});

const defaultActionType = computed(() => {
    // 1. Prioritaskan tindakan historis yang sudah diverifikasi jika laporan berstatus VERIFIED
    if (report.value?.verified_action_type) {
        return report.value.verified_action_type;
    }
    if (report.value?.verified_points !== null && report.value?.verified_points !== undefined) {
        if (report.value.verified_points > 0) return 'PENAMBAHAN';
        if (report.value.verified_points < 0) return 'PEMOTONGAN';
        return 'NETRAL';
    }
    // 2. Jika laporan terkait sarpras/fasilitas, arahkan default ke NETRAL
    if (isFacilityComplaint.value) {
        return 'NETRAL';
    }
    // 3. Rekomendasi berdasarkan AI Sentiment
    if (report.value?.ai_sentiment === 'POSITIF') {
        return 'PENAMBAHAN';
    }
    if (report.value?.ai_sentiment === 'NEGATIF') {
        return 'PEMOTONGAN';
    }
    return 'NETRAL';
});

const actionType = ref(defaultActionType.value);
const pointValue = ref(
    report.value?.verified_points !== null && report.value?.verified_points !== undefined 
        ? report.value.verified_points 
        : (actionType.value === 'NETRAL' ? 0 : 5)
);
const supervisorNotes = ref(report.value?.supervisor_notes || '');

// Attachment Upload State (Bisa multiple, dibatasi 1 file saat ini untuk kemudahan ekspansi mendatang)
const MAX_ATTACHMENTS = 1;
const verificationFiles = ref([]);
const fileInputRef = ref(null);
const fileError = ref(null);
const isDragging = ref(false);

const triggerFileInput = () => {
    if (isVerified.value) return;
    fileInputRef.value?.click();
};

const formatBytes = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const getFileType = (file) => {
    const name = file.name.toLowerCase();
    if (file.type.startsWith('image/')) return 'image';
    if (file.type === 'application/pdf' || name.endsWith('.pdf')) return 'pdf';
    if (name.endsWith('.doc') || name.endsWith('.docx')) return 'word';
    if (name.endsWith('.xls') || name.endsWith('.xlsx')) return 'excel';
    return 'document';
};

const handleFiles = (files) => {
    fileError.value = null;
    const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'];
    const maxSizeBytes = 10 * 1024 * 1024; // 10MB

    const newFiles = [];
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const ext = file.name.split('.').pop().toLowerCase();
        
        if (!allowedExtensions.includes(ext)) {
            fileError.value = `Format file .${ext} tidak didukung. Harap unggah file PDF, Dokumen Word/Excel, atau Foto (JPG/PNG).`;
            return;
        }

        if (file.size > maxSizeBytes) {
            fileError.value = `Ukuran file "${file.name}" melebihi batas maksimal 10 MB.`;
            return;
        }

        const type = getFileType(file);
        let previewUrl = null;
        if (type === 'image') {
            previewUrl = URL.createObjectURL(file);
        }

        newFiles.push({
            file,
            name: file.name,
            size: formatBytes(file.size),
            type,
            previewUrl
        });
    }

    if (MAX_ATTACHMENTS === 1) {
        // Dibatasi 1 file untuk saat ini
        verificationFiles.value = newFiles.slice(-1);
    } else {
        const combined = [...verificationFiles.value, ...newFiles];
        verificationFiles.value = combined.slice(0, MAX_ATTACHMENTS);
    }
};

const onFileInputChange = (event) => {
    const files = event.target.files;
    if (files && files.length > 0) {
        handleFiles(files);
    }
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const onFileDrop = (event) => {
    isDragging.value = false;
    if (isVerified.value) return;
    const files = event.dataTransfer.files;
    if (files && files.length > 0) {
        handleFiles(files);
    }
};

const removeVerificationFile = (index) => {
    if (isVerified.value) return;
    const removed = verificationFiles.value[index];
    if (removed?.previewUrl) {
        URL.revokeObjectURL(removed.previewUrl);
    }
    verificationFiles.value.splice(index, 1);
};

watch(() => props.reportDetail, (newVal) => {
    if (newVal) {
        actionType.value = defaultActionType.value;
        pointValue.value = newVal.verified_points !== null && newVal.verified_points !== undefined 
            ? newVal.verified_points 
            : (defaultActionType.value === 'NETRAL' ? 0 : 5);
        supervisorNotes.value = newVal.supervisor_notes || '';
        verificationFiles.value.forEach(f => {
            if (f.previewUrl) URL.revokeObjectURL(f.previewUrl);
        });
        verificationFiles.value = [];
        fileError.value = null;
    }
}, { deep: true });

const getEffectiveStaffList = () => {
    const list = (props.staffList && props.staffList.length > 0) 
        ? props.staffList 
        : (props.staffMembers || []);
    // Jika laporan belum diverifikasi dan jenis tindakan NETRAL, jangan checklist staf bertugas
    const isNeutral = !isVerified.value && actionType.value === 'NETRAL';
    return list.map(s => ({ 
        ...s, 
        selected_default: s.selected || false,
        selected: isNeutral ? false : (s.selected || false) 
    }));
};

const staffList = ref(getEffectiveStaffList());

watch(() => [props.staffList, props.staffMembers], () => {
    staffList.value = getEffectiveStaffList();
}, { deep: true });

// PESU PELUH Disposisi Integration
const alreadyDispatchedToPesupeluh = computed(() => !!report.value?.pesupeluh_ticket_number);
const pesupeluhTicketNumber = ref(report.value?.pesupeluh_ticket_number || null);

watch(() => props.reportDetail?.pesupeluh_ticket_number, (val) => {
    if (val) {
        pesupeluhTicketNumber.value = val;
    }
}, { immediate: true });

// Disposisi ke PESU PELUH HANYA aktif untuk tindakan NETRAL (karena sarpras/fasilitas tanpa KPI poin staf)
const forwardToPesupeluh = ref(!alreadyDispatchedToPesupeluh.value && actionType.value === 'NETRAL');
const isDispatchedToPesupeluh = computed(() => {
    return !!pesupeluhTicketNumber.value || !!props.reportDetail?.pesupeluh_ticket_number || (forwardToPesupeluh.value && actionType.value === 'NETRAL') || alreadyDispatchedToPesupeluh.value;
});

// Ketika jenis tindakan diubah: jika NETRAL auto jangan checklist staf bertugas
watch(actionType, (newAction) => {
    if (newAction === 'NETRAL') {
        // Otomatis bersihkan checklist staf bertugas
        if (!isVerified.value) {
            staffList.value.forEach(s => {
                s.selected = false;
            });
        }
        pointValue.value = 0;
        if (!alreadyDispatchedToPesupeluh.value) {
            forwardToPesupeluh.value = true;
        }
    } else {
        forwardToPesupeluh.value = false;
        if (pointValue.value === 0) {
            pointValue.value = 5;
        }
        // Jika beralih kembali ke Tambah/Potong poin dan belum ada staf terpilih, pulihkan centang staf yang bertugas saat kejadian
        if (!isVerified.value) {
            const hasSelected = staffList.value.some(s => s.selected);
            if (!hasSelected) {
                staffList.value.forEach(s => {
                    if (s.active_at_time || s.selected_default) {
                        s.selected = true;
                    }
                });
            }
        }
    }
});

// Smart category match
const getInitialCategoryId = () => {
    if (!props.pesupeluhCategories || props.pesupeluhCategories.length === 0) return null;
    const text = ((report.value?.isi_laporan || '') + ' ' + (report.value?.target_object || '')).toLowerCase();
    
    // 1. Perlengkapan Kantor & Mebel (kursi, meja, antrian, tempat duduk, ac, furnitur, mebel, lemari)
    if (text.includes('kursi') || text.includes('antri') || text.includes('meja') || text.includes('duduk') || text.includes('furnitur') || text.includes('mebel') || text.includes('lemari') || text.includes('ac') || text.includes('dingin') || text.includes('panas') || text.includes('kipas')) {
        const cat = props.pesupeluhCategories.find(c => {
            const cn = c.name.toLowerCase();
            return cn.includes('kantor') || cn.includes('perlengkapan') || cn.includes('mebel') || cn.includes('ac');
        });
        if (cat) return cat.id;
    }

    // 2. Sanitasi / Saniter / Plumbing (air, toilet, wc, kran, wastafel, bocor pipa)
    if (text.includes('air') || text.includes('toilet') || text.includes('wc') || text.includes('kran') || text.includes('keran') || text.includes('bocor') || text.includes('wastafel') || text.includes('pipa') || text.includes('saniter')) {
        const cat = props.pesupeluhCategories.find(c => {
            const cn = c.name.toLowerCase();
            return cn.includes('saniter') || cn.includes('sanitasi') || cn.includes('air') || cn.includes('plumbing') || cn.includes('toilet');
        });
        if (cat) return cat.id;
    }

    // 3. Mekanikal Elektrikal / Listrik (lampu, listrik, mati lampu, saklar, stopkontak, kabel)
    if (text.includes('lampu') || text.includes('listrik') || text.includes('mati lampu') || text.includes('saklar') || text.includes('stopkontak') || text.includes('kabel')) {
        const cat = props.pesupeluhCategories.find(c => {
            const cn = c.name.toLowerCase();
            return cn.includes('elektrik') || cn.includes('listrik') || cn.includes('mekanikal');
        });
        if (cat) return cat.id;
    }

    // 4. Fisik Gedung (pintu, atap, plafon, lantai, dinding, jendela, keramik)
    if (text.includes('pintu') || text.includes('atap') || text.includes('plafon') || text.includes('lantai') || text.includes('dinding') || text.includes('jendela') || text.includes('keramik')) {
        const cat = props.pesupeluhCategories.find(c => {
            const cn = c.name.toLowerCase();
            return cn.includes('fisik') || cn.includes('gedung') || cn.includes('bangunan');
        });
        if (cat) return cat.id;
    }

    return props.pesupeluhCategories[0]?.id || null;
};

const pesupeluhCategoryId = ref(getInitialCategoryId());
const pesupeluhPriority = ref(report.value?.ai_urgency === 'TINGGI' || report.value?.priority === 'HIGH' ? 'URGENT' : 'ROUTINE');

// Smart initial room matching
const getInitialRoomId = () => {
    if (!props.pesupeluhRooms || props.pesupeluhRooms.length === 0) return null;
    const reportRoomName = report.value?.unit || '';
    if (!reportRoomName) return props.pesupeluhRooms[0]?.id || null;

    const lowerReport = reportRoomName.toLowerCase();

    // 1. Exact match (jika kelak ada ruangan spesifik seperti 'Loket Pendaftaran & Registrasi' di PESU PELUH, langsung tepat terpilih di sini)
    const exact = props.pesupeluhRooms.find(r => r.name.toLowerCase() === lowerReport);
    if (exact) return exact.id;

    // 2. Direct Substring Match
    const directSub = props.pesupeluhRooms.find(r => {
        const pr = r.name.toLowerCase();
        return (pr.length >= 3 && lowerReport.includes(pr)) || (lowerReport.length >= 3 && pr.includes(lowerReport));
    });
    if (directSub) return directSub.id;

    // 3. Alias / Synonym Mapping cerdas untuk ruangan front-office & administrasi:
    if (lowerReport.includes('pendaftaran') || lowerReport.includes('registrasi') || lowerReport.includes('antrian') || lowerReport.includes('antri')) {
        const pendaftaranRoom = props.pesupeluhRooms.find(r => r.name.toLowerCase().includes('pendaftaran') || r.name.toLowerCase().includes('registrasi'));
        if (pendaftaranRoom) return pendaftaranRoom.id;

        const adminPelayananRoom = props.pesupeluhRooms.find(r => r.name.toLowerCase().includes('administrasi pelayanan') || r.name.toLowerCase().includes('administrasi'));
        if (adminPelayananRoom) return adminPelayananRoom.id;
    }

    if (lowerReport.includes('kasir') || lowerReport.includes('pembayaran') || lowerReport.includes('keuangan')) {
        const kasirRoom = props.pesupeluhRooms.find(r => r.name.toLowerCase().includes('kasir') || r.name.toLowerCase().includes('pembayaran'));
        if (kasirRoom) return kasirRoom.id;

        const keuanganRoom = props.pesupeluhRooms.find(r => r.name.toLowerCase().includes('keuangan'));
        if (keuanganRoom) return keuanganRoom.id;
    }

    if (lowerReport.includes('igd') || lowerReport.includes('gawat darurat') || lowerReport.includes('emergency')) {
        const ugd = props.pesupeluhRooms.find(r => r.name.toLowerCase().includes('ugd') || r.name.toLowerCase().includes('igd'));
        if (ugd) return ugd.id;
    }

    // 4. Keyword fuzzy match (misal 'Rawat Inap Kasuari' -> 'RAWAT INAP KASWUARI')
    const clean = lowerReport.replace(/[^a-z0-9\s]/g, ' ');
    const stopWords = ['ruang', 'ruangan', 'rawat', 'inap', 'gedung', 'lantai', 'kelas', 'unit', 'area', 'loket', 'instalasi'];
    const words = clean.split(' ').filter(w => w.length >= 3 && !stopWords.includes(w));

    for (const w of words) {
        const found = props.pesupeluhRooms.find(r => {
            const prClean = r.name.toLowerCase();
            if (prClean.includes(w) || w.includes(prClean)) return true;
            if ((w.includes('kasu') || w.includes('kasw')) && (prClean.includes('kasu') || prClean.includes('kasw'))) return true;
            return false;
        });
        if (found) return found.id;
    }

    return props.pesupeluhRooms[0]?.id || null;
};

const pesupeluhRoomId = ref(getInitialRoomId());

// Options formatting for SearchableSelect
const pesupeluhRoomOptions = computed(() => {
    return (props.pesupeluhRooms || []).map(r => ({
        id: r.id,
        name: r.display_label || r.name,
    }));
});

const pesupeluhCategoryOptions = computed(() => {
    return (props.pesupeluhCategories || []).map(c => {
        let label = c.display_label || c.name || '';
        label = label.replace(/\[IPSRS\]\s*/gi, '').trim();
        return {
            id: c.id,
            name: label,
        };
    });
});

const pesupeluhPriorityOptions = [
    { id: 'ROUTINE', name: 'Standar / Rutin (ROUTINE)' },
    { id: 'URGENT', name: 'Mendesak / Cepat (URGENT)' },
];

const incrementPoint = () => {
    if (isVerified.value) return;
    if (pointValue.value < 100) {
        pointValue.value++;
    }
};

const decrementPoint = () => {
    if (isVerified.value) return;
    if (pointValue.value > 1) {
        pointValue.value--;
    }
};

const selectedStaffList = computed(() => staffList.value.filter(s => s.selected));

const submitVerification = () => {
    if (isVerified.value || isSubmitting.value) return;
    
    if (actionType.value !== 'NETRAL' && selectedStaffList.value.length === 0 && staffList.value.length > 0) {
        showValidationModal.value = true;
        return;
    }

    showConfirmModal.value = true;
};

const confirmAndExecute = () => {
    showConfirmModal.value = false;
    executeSubmit();
};

const executeSubmit = () => {
    isSubmitting.value = true;
    const selectedIds = selectedStaffList.value.map(s => s.id);

    router.post(route('kasi.verify.process', { id: report.value.id }), {
        selected_staff_ids: selectedIds,
        action_type: actionType.value,
        points: actionType.value === 'NETRAL' ? 0 : pointValue.value,
        supervisor_notes: supervisorNotes.value,
        forward_to_pesupeluh: actionType.value === 'NETRAL' ? forwardToPesupeluh.value : false,
        pesupeluh_category_id: pesupeluhCategoryId.value,
        pesupeluh_room_id: pesupeluhRoomId.value,
        pesupeluh_priority: pesupeluhPriority.value,
        attachments: verificationFiles.value.map(f => f.file),
    }, {
        onSuccess: (page) => {
            isSubmitting.value = false;
            verificationFiles.value.forEach(f => {
                if (f.previewUrl) URL.revokeObjectURL(f.previewUrl);
            });
            verificationFiles.value = [];
            const flashed = page?.props?.flash?.pesupeluh_ticket_number 
                         || page?.props?.reportDetail?.pesupeluh_ticket_number 
                         || props.reportDetail?.pesupeluh_ticket_number;
            if (flashed) {
                pesupeluhTicketNumber.value = flashed;
            }
            showSuccessModal.value = true;
        },
        onError: () => {
            isSubmitting.value = false;
        },
        onFinish: () => {
            if (!showSuccessModal.value) {
                isSubmitting.value = false;
            }
        }
    });
};

const finishVerification = () => {
    router.get(route('kasi.feed'));
};
</script>

<template>
    <Head :title="report ? `Verifikasi Laporan ${report.id}` : 'Verifikasi Laporan'" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            
            <!-- Empty State: When No Report Exists in Database -->
            <div v-if="!report" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-12 text-center shadow-sm space-y-4">
                <div class="h-14 w-14 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                    <UserCheck class="h-7 w-7" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Tidak Ada Laporan yang Dipilih</h3>
                    <p class="text-xs text-slate-400 max-w-md mx-auto">
                        Silakan pilih laporan dari feed aduan unit untuk melakukan verifikasi aduan dan distribusi poin KPI staf.
                    </p>
                </div>
                <div>
                    <Link
                        :href="route('kasi.feed')"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-sm"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Kembali ke Feed Aduan</span>
                    </Link>
                </div>
            </div>

            <!-- When Report Exists -->
            <template v-else>
                <!-- Top Header Panel with Ticket Code & Status -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex">
                            <UserCheck class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                Verifikasi Laporan {{ report.id }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                Verifikasi staf bertugas, evaluasi analisis AI, dan kelola saldo poin KPI staf unit.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold',
                            report.status === 'VERIFIED'
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                                : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800'
                        ]">
                            {{ report.status === 'VERIFIED' ? '✓ Telah Diverifikasi' : 'Menunggu Verifikasi Kasi' }}
                        </span>
                    </div>
                </div>

                <!-- Stacked Containers Layout (Tumpukan Atas ke Bawah) -->
                <div class="space-y-4">
                    
                    <!-- Top Row: Dua Kontainer Mandiri Berdampingan (Tanpa Container di dalam Container) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                        
                        <!-- 1. Container: Data & Uraian Aduan Pasien (8 Kolom) -->
                        <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            <!-- Header Kontainer -->
                            <div class="p-4 sm:p-5 bg-slate-50/60 dark:bg-slate-950/60 flex items-center justify-between gap-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Data & Uraian Aduan
                                </h3>
                                <span :class="[
                                    'px-2.5 py-1 rounded-lg text-[10px] font-medium',
                                    report.is_anonymous ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                ]">
                                    {{ report.is_anonymous ? 'Mode Anonim' : 'Identitas Terverifikasi' }}
                                </span>
                            </div>

                            <!-- Section 1: Identitas & Kontak Pelapor -->
                            <div class="p-4 sm:p-5 space-y-2">
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Identitas & Kontak Pelapor</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Nama Pasien</span>
                                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                            <div class="h-5 w-5 rounded-full bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center text-[10px] font-medium">
                                                {{ report.reporter_name.charAt(0) }}
                                            </div>
                                            <span>{{ report.reporter_name }}</span>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">No. WhatsApp / Telepon</span>
                                        <div v-if="report.reporter_phone" class="text-xs font-semibold text-slate-800 dark:text-slate-100">
                                            {{ report.reporter_phone }}
                                        </div>
                                        <span v-else class="text-xs text-slate-400 italic">Tidak dicantumkan</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Rincian Teks Aduan Pasien -->
                            <div class="p-4 sm:p-5 space-y-2">
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Rincian Teks Aduan Pasien</span>
                                <div class="bg-slate-50/70 dark:bg-slate-950/50 p-3.5 sm:p-4 rounded-xl border border-slate-200/70 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 text-xs sm:text-sm leading-relaxed font-normal">
                                    "{{ report.isi_laporan }}"
                                </div>
                            </div>

                            <!-- Section 3: Parameter & Lokasi Aduan -->
                            <div class="p-4 sm:p-5 space-y-2">
                                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Parameter & Lokasi Aduan</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <!-- Ruangan / Instalasi -->
                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Ruangan / Instalasi</span>
                                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">
                                            {{ report.unit }}
                                        </div>
                                    </div>

                                    <!-- Sasaran Aduan / Petugas / Loket -->
                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Sasaran / Petugas</span>
                                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">
                                            {{ report.target_object || 'Pelayanan Umum / Semua Staf' }}
                                        </div>
                                    </div>

                                    <!-- Waktu & Tanggal Laporan -->
                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Waktu Aduan</span>
                                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-100">
                                            {{ report.timestamp }}
                                        </div>
                                    </div>

                                    <!-- Tingkat Prioritas -->
                                    <div class="p-3 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 space-y-1">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Tingkat Prioritas</span>
                                        <div>
                                            <span :class="[
                                                'px-2 py-0.5 rounded text-[11px] font-semibold',
                                                report.priority === 'HIGH' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                            ]">
                                                {{ report.priority === 'HIGH' ? 'Prioritas Tinggi' : 'Prioritas Standar' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 4: Lampiran Foto / Bukti -->
                            <div class="p-4 sm:p-5 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500">Lampiran Foto & Bukti</span>
                                    <span class="text-[10px] text-slate-400">
                                        {{ report.attachments.length }} Berkas
                                    </span>
                                </div>

                                <div v-if="report.attachments.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                    <div
                                        v-for="att in report.attachments"
                                        :key="att.id"
                                        class="group relative rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-950 aspect-video flex items-center justify-center cursor-pointer"
                                        @click="selectedImagePreview = att.url"
                                    >
                                        <img
                                            :src="att.url"
                                            :alt="att.file_name"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-200"
                                        />
                                        <div class="absolute inset-0 bg-slate-950/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white gap-1.5 text-xs font-medium">
                                            <Maximize2 class="h-3.5 w-3.5" />
                                            <span>Perbesar</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="py-2.5 text-center text-slate-400 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/70 dark:border-slate-800/80 text-xs">
                                    <span>Pelapor tidak menyertakan foto lampiran.</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Container: Hasil Analisis AI Pintar (4 Kolom - Lebih Ramping & Ditumpuk) -->
                        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-emerald-300/80 dark:border-emerald-800/80 rounded-2xl shadow-sm overflow-hidden divide-y divide-emerald-100 dark:divide-slate-800 relative">
                            <!-- Header Hijau Solid yang Keren & Jelas -->
                            <div class="p-4 sm:p-5 bg-emerald-600 dark:bg-emerald-700 text-white flex items-center justify-between gap-3 shadow-xs">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-7 w-7 rounded-lg bg-white/20 text-white flex items-center justify-center shrink-0">
                                        <Sparkles class="h-3.5 w-3.5" />
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-white">
                                            Hasil Analisis AI
                                        </h3>
                                        <p class="text-[10px] text-emerald-100 font-normal">
                                            Klasifikasi real-time & deteksi urgensi
                                        </p>
                                    </div>
                                </div>
                                <span class="relative flex h-2 w-2 shrink-0">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                                </span>
                            </div>

                            <!-- Body Analisis AI -->
                            <div class="p-4 sm:p-5 space-y-4">
                                <!-- 4 Kartu Metrik AI Ditumpuk Vertikal (1 Kolom) -->
                                <div class="grid grid-cols-1 gap-2.5">
                                    <!-- Metrik 1: Sentimen AI -->
                                    <div class="bg-slate-50/70 dark:bg-slate-950/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 transition space-y-1">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 block font-normal">Sentimen Pasien</span>
                                        <div class="flex items-center justify-between">
                                            <span :class="[
                                                'text-xs font-semibold flex items-center gap-1',
                                                report.ai_sentiment === 'POSITIF' ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                                            ]">
                                                <TrendingUp v-if="report.ai_sentiment === 'POSITIF'" class="h-3 w-3 shrink-0" />
                                                <TrendingDown v-else class="h-3 w-3 shrink-0" />
                                                <span>{{ report.ai_sentiment }}</span>
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-normal">({{ report.ai_confidence }})</span>
                                        </div>
                                    </div>

                                    <!-- Metrik 2: Kategori Masalah -->
                                    <div class="bg-slate-50/70 dark:bg-slate-950/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 transition space-y-1">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 block font-normal">Kategori Masalah</span>
                                        <div class="text-xs font-semibold text-slate-800 dark:text-white truncate" :title="report.ai_category">
                                            {{ report.ai_category }}
                                        </div>
                                    </div>

                                    <!-- Metrik 3: Urgensi Tindakan -->
                                    <div class="bg-slate-50/70 dark:bg-slate-950/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 transition space-y-1">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 block font-normal">Urgensi Tindakan</span>
                                        <div>
                                            <span :class="[
                                                'px-2 py-0.5 rounded text-[10px] font-semibold inline-block',
                                                report.ai_urgency === 'TINGGI' || report.ai_urgency === 'KRITIS'
                                                    ? 'bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-500/20 dark:text-rose-300 dark:border-rose-500/30'
                                                    : 'bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30'
                                            ]">
                                                {{ report.ai_urgency }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Metrik 4: Rekomendasi Poin KPI -->
                                    <div class="bg-slate-50/70 dark:bg-slate-950/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 transition space-y-1">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 block font-normal">Arahan Aksi KPI</span>
                                        <div class="text-xs font-semibold">
                                            <span v-if="report.ai_sentiment === 'POSITIF'" class="text-emerald-700 dark:text-emerald-400">
                                                Reward (+ Poin)
                                            </span>
                                            <span v-else-if="isFacilityComplaint && report.ai_sentiment !== 'POSITIF'" class="text-blue-700 dark:text-blue-300">
                                                Netral (0 Poin)
                                            </span>
                                            <span v-else-if="report.ai_sentiment === 'NEGATIF'" class="text-rose-600 dark:text-rose-400">
                                                Evaluasi (- Poin)
                                            </span>
                                            <span v-else class="text-blue-700 dark:text-blue-300">
                                                Netral (0 Poin)
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Box Rekomendasi Solusi & Arahan AI -->
                                <div v-if="report.ai_recommendation" class="bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-500/30 rounded-xl p-4 space-y-2">
                                    <div class="flex items-center gap-1.5 text-emerald-800 dark:text-emerald-400 font-semibold text-[11px]">
                                        <Sparkles class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                        <span>Rekomendasi Solusi & Evaluasi:</span>
                                    </div>
                                    <p class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed font-normal">
                                        {{ report.ai_recommendation }}
                                    </p>
                                    <div v-if="report.ai_summary" class="pt-2 border-t border-emerald-200/80 dark:border-emerald-500/20 text-[11px] text-slate-500 dark:text-slate-400 font-normal">
                                        <span class="font-medium text-emerald-900/80 dark:text-emerald-300">Ringkasan AI:</span> {{ report.ai_summary }}
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Bar / Footer Analisis AI -->
                            <div class="px-4 py-2.5 bg-slate-50/80 dark:bg-slate-950/60 flex items-center gap-2">
                                <ShieldCheck class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                <p class="text-[10.5px] text-slate-500 dark:text-slate-400 italic leading-snug">
                                    Diproses otomatis oleh <span class="font-semibold not-italic text-slate-700 dark:text-slate-300">{{ report.ai_provider || 'Groq AI (Llama-3)' }}</span> sebagai validasi bukti telaah aduan.
                                </p>
                            </div>
                        </div>

                    </div>

                    <!-- 2. Container: Shift Staff Matching Card -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            
                            <!-- Staff Matching Container Header -->
                            <div class="p-4 sm:p-5 bg-slate-50/60 dark:bg-slate-950/60 flex flex-wrap items-center justify-between gap-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Pencocokan Staf Bertugas
                                </h3>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                    <Clock class="h-3 w-3 text-slate-400" />
                                    <span>{{ report.created_at_time || report.timestamp }}</span>
                                </span>
                            </div>

                            <!-- Staff Matching Container Body -->
                            <div class="p-4 sm:p-5 space-y-3">
                                <p v-if="isVerified" class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-normal">
                                    Daftar staf unit yang telah ditautkan dan dievaluasi pada verifikasi laporan ini:
                                </p>
                                <div v-else-if="actionType === 'NETRAL'" class="p-2.5 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/50 flex items-center gap-2.5 text-xs text-blue-700 dark:text-blue-300">
                                    <Info class="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" />
                                    <span class="font-medium">Tindakan KPI Netral (0 Poin): Pencocokan staf bertugas dinonaktifkan (disabled) karena tidak ada evaluasi poin reward/punishment staf.</span>
                                </div>
                                <p v-else class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-normal">
                                    Centang staf yang bertugas saat aduan terjadi untuk verifikasi & evaluasi poin KPI (otomatis ditandai dari data Presensi Masuk):
                                </p>

                                <!-- Staff List Checkbox Grid -->
                                <div v-if="staffList.length > 0" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                                    <label
                                        v-for="staff in staffList"
                                        :key="staff.id"
                                        @click="(isVerified || actionType === 'NETRAL') ? $event.preventDefault() : null"
                                        :class="[
                                            'flex items-center justify-between p-3 rounded-xl border select-none transition',
                                            isVerified
                                                ? (staff.selected 
                                                    ? (actionType === 'PEMOTONGAN' 
                                                        ? 'cursor-not-allowed bg-rose-50/70 border-rose-300 dark:bg-rose-950/30 dark:border-rose-800/80' 
                                                        : (actionType === 'NETRAL' 
                                                            ? 'cursor-not-allowed bg-blue-50/70 border-blue-300 dark:bg-blue-950/30 dark:border-blue-800/80' 
                                                            : 'cursor-not-allowed bg-emerald-50/70 border-emerald-300 dark:bg-emerald-950/30 dark:border-emerald-800/80'))
                                                    : 'cursor-not-allowed opacity-50 bg-slate-100/50 dark:bg-slate-900/30 border-slate-200 dark:border-slate-800')
                                                : (actionType === 'NETRAL' 
                                                    ? 'cursor-not-allowed opacity-60 bg-slate-100/70 dark:bg-slate-900/40 border-slate-200 dark:border-slate-800' 
                                                    : (staff.selected 
                                                        ? (actionType === 'PEMOTONGAN'
                                                            ? 'cursor-pointer bg-rose-50/80 border-rose-500 dark:bg-rose-950/40 dark:border-rose-700'
                                                            : 'cursor-pointer bg-emerald-50/80 border-emerald-500 dark:bg-emerald-950/40 dark:border-emerald-700')
                                                        : 'cursor-pointer bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 hover:border-slate-300'))
                                        ]"
                                    >
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="checkbox"
                                                v-model="staff.selected"
                                                :disabled="isVerified || actionType === 'NETRAL'"
                                                :class="[
                                                    'rounded h-4 w-4 disabled:cursor-not-allowed',
                                                    actionType === 'PEMOTONGAN'
                                                        ? 'text-rose-600 focus:ring-rose-500 accent-rose-600'
                                                        : 'text-emerald-600 focus:ring-emerald-500 accent-emerald-600'
                                                ]"
                                            />
                                            <div>
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ staff.name }}</span>
                                                    <span
                                                        v-if="staff.attendance_type === 'ACTIVE_AT_REPORT'"
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800"
                                                        title="Tercatat berdinas saat jam aduan diterima"
                                                    >
                                                        ✓ On-Duty saat Kejadian
                                                    </span>
                                                    <span
                                                        v-else-if="staff.attendance_type === 'TODAY'"
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
                                                    >
                                                        Hadir Hari Ini
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-normal mt-0.5">
                                                    {{ staff.role }} • NIP: {{ staff.nip }}
                                                    <span v-if="staff.clock_in_time" class="ml-1 text-slate-400">
                                                        (Presensi: {{ staff.clock_in_time }})
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <span class="text-[11px] font-semibold block" :class="actionType === 'PEMOTONGAN' && isVerified ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'">
                                                {{ staff.total_points }} Poin
                                            </span>
                                            <span v-if="staff.selected" class="text-[10px] font-semibold" :class="actionType === 'PEMOTONGAN' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'">
                                                {{ isVerified ? (actionType === 'PEMOTONGAN' ? `Dievaluasi (-${pointValue})` : (actionType === 'NETRAL' ? 'Tercatat (0 Poin)' : `Diberi Reward (+${pointValue})`)) : 'Terpilih' }}
                                            </span>
                                        </div>
                                    </label>
                                </div>

                                <!-- If No Staff Registered in this Unit -->
                                <div v-else class="text-center py-6 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl space-y-2">
                                    <div class="h-9 w-9 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                                        <Users class="h-4 w-4" />
                                    </div>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 font-normal">
                                        Belum ada staf terdaftar di unit {{ report.unit }}.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Container: KPI Point Execution & Supervisor Notes Form -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            
                            <!-- KPI Form Container Header -->
                            <div class="p-4 sm:p-5 bg-slate-50/60 dark:bg-slate-950/60 flex items-center justify-between gap-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Eksekusi Poin KPI & Berita Acara
                                </h3>
                                <span v-if="isVerified" :class="[
                                    'px-2.5 py-1 rounded-lg text-[10px] font-semibold flex items-center gap-1 shrink-0 border',
                                    actionType === 'PEMOTONGAN' 
                                        ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border-rose-300 dark:border-rose-800' 
                                        : (actionType === 'NETRAL'
                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-300 dark:border-blue-800'
                                            : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800')
                                ]">
                                    <Check class="h-3 w-3" />
                                    <span>Telah Dieksekusi ({{ actionType === 'PEMOTONGAN' ? 'Potong Poin' : (actionType === 'NETRAL' ? 'Netral' : 'Tambah Poin') }})</span>
                                </span>
                                <span v-else class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white shrink-0">
                                    Poin KPI
                                </span>
                            </div>

                            <!-- KPI Form Container Body -->
                            <div class="p-4 sm:p-5 space-y-4">
                                <!-- Action Type Selector -->
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Jenis Tindakan KPI:</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                         <button
                                            type="button"
                                            :disabled="isVerified"
                                            @click="if (!isVerified) { actionType = 'PEMOTONGAN'; if (pointValue === 0) pointValue = 5; }"
                                            :class="[
                                                'py-2.5 sm:py-2 px-3 sm:px-2 rounded-xl border text-xs font-semibold flex items-center justify-center gap-2 sm:gap-1.5 transition outline-none focus:outline-none focus:ring-0 select-none',
                                                isVerified ? 'cursor-not-allowed' : 'cursor-pointer active:scale-[0.98]',
                                                actionType === 'PEMOTONGAN' 
                                                    ? 'bg-rose-600 border-rose-600 text-white shadow-xs' 
                                                    : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900'
                                            ]"
                                        >
                                            <TrendingDown :class="['h-4 w-4 sm:h-3.5 sm:w-3.5 shrink-0', actionType === 'PEMOTONGAN' ? 'text-white' : 'text-rose-500']" />
                                            <span>Potong Poin (-)</span>
                                        </button>

                                        <button
                                            type="button"
                                            :disabled="isVerified"
                                            @click="if (!isVerified) { actionType = 'PENAMBAHAN'; if (pointValue === 0) pointValue = 5; }"
                                            :class="[
                                                'py-2.5 sm:py-2 px-3 sm:px-2 rounded-xl border text-xs font-semibold flex items-center justify-center gap-2 sm:gap-1.5 transition outline-none focus:outline-none focus:ring-0 select-none',
                                                isVerified ? 'cursor-not-allowed' : 'cursor-pointer active:scale-[0.98]',
                                                actionType === 'PENAMBAHAN' 
                                                    ? 'bg-emerald-600 border-emerald-600 text-white shadow-xs' 
                                                    : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900'
                                            ]"
                                        >
                                            <TrendingUp :class="['h-4 w-4 sm:h-3.5 sm:w-3.5 shrink-0', actionType === 'PENAMBAHAN' ? 'text-white' : 'text-emerald-500']" />
                                            <span>Tambah Poin (+)</span>
                                        </button>

                                        <button
                                            type="button"
                                            :disabled="isVerified"
                                            @click="if (!isVerified) { actionType = 'NETRAL'; pointValue = 0; }"
                                            :class="[
                                                'py-2.5 sm:py-2 px-3 sm:px-2 rounded-xl border text-xs font-semibold flex items-center justify-center gap-2 sm:gap-1.5 transition outline-none focus:outline-none focus:ring-0 select-none',
                                                isVerified ? 'cursor-not-allowed' : 'cursor-pointer active:scale-[0.98]',
                                                actionType === 'NETRAL' 
                                                    ? 'bg-blue-600 border-blue-600 text-white shadow-xs' 
                                                    : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900'
                                            ]"
                                        >
                                            <ShieldCheck :class="['h-4 w-4 sm:h-3.5 sm:w-3.5 shrink-0', actionType === 'NETRAL' ? 'text-white' : 'text-blue-500']" />
                                            <span>Netral (0 Poin)</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Point Value Input: Clean Stepper with Direct Typed Sign -->
                                <div v-if="actionType !== 'NETRAL'" class="space-y-1.5">
                                    <label class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Besaran Poin Per Staf Terpilih:</label>
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <button
                                            type="button"
                                            :disabled="isVerified"
                                            @click="decrementPoint"
                                            :class="[
                                                'h-11 w-11 sm:h-10 sm:w-10 rounded-xl border flex items-center justify-center transition shrink-0 font-bold bg-slate-50 dark:bg-slate-950',
                                                actionType === 'PEMOTONGAN' 
                                                    ? 'border-rose-200 dark:border-rose-900/50 text-rose-600 dark:text-rose-400' 
                                                    : 'border-emerald-200 dark:border-emerald-900/50 text-emerald-600 dark:text-emerald-400',
                                                isVerified 
                                                    ? 'opacity-40 cursor-not-allowed' 
                                                    : (actionType === 'PEMOTONGAN' 
                                                        ? 'hover:bg-rose-50 hover:border-rose-300 dark:hover:bg-rose-950/40 active:scale-95 cursor-pointer' 
                                                        : 'hover:bg-emerald-50 hover:border-emerald-300 dark:hover:bg-emerald-950/40 active:scale-95 cursor-pointer')
                                            ]"
                                        >
                                            <Minus class="h-4 w-4" />
                                        </button>
                                        
                                        <div class="relative flex-1">
                                            <input
                                                :value="actionType === 'PEMOTONGAN' ? `-${pointValue}` : `+${pointValue}`"
                                                :disabled="isVerified"
                                                inputmode="numeric"
                                                @input="e => {
                                                    if (isVerified) return;
                                                    const cleaned = e.target.value.replace(/[^0-9]/g, '');
                                                    pointValue = cleaned ? parseInt(cleaned) : 1;
                                                }"
                                                type="text"
                                                :class="[
                                                    'w-full h-11 sm:h-10 text-center font-bold text-base sm:text-sm rounded-xl border bg-slate-50 dark:bg-slate-950 focus:outline-none transition px-12',
                                                    isVerified ? 'cursor-not-allowed opacity-80' : '',
                                                    actionType === 'PEMOTONGAN' 
                                                        ? 'text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-900/50 focus:border-rose-500' 
                                                        : 'text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/50 focus:border-emerald-500'
                                                ]"
                                            />
                                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400 pointer-events-none">
                                                Poin
                                            </span>
                                        </div>

                                        <button
                                            type="button"
                                            :disabled="isVerified"
                                            @click="incrementPoint"
                                            :class="[
                                                'h-11 w-11 sm:h-10 sm:w-10 rounded-xl border flex items-center justify-center transition shrink-0 font-bold bg-slate-50 dark:bg-slate-950',
                                                actionType === 'PEMOTONGAN' 
                                                    ? 'border-rose-200 dark:border-rose-900/50 text-rose-600 dark:text-rose-400' 
                                                    : 'border-emerald-200 dark:border-emerald-900/50 text-emerald-600 dark:text-emerald-400',
                                                isVerified 
                                                    ? 'opacity-40 cursor-not-allowed' 
                                                    : (actionType === 'PEMOTONGAN' 
                                                        ? 'hover:bg-rose-50 hover:border-rose-300 dark:hover:bg-rose-950/40 active:scale-95 cursor-pointer' 
                                                        : 'hover:bg-emerald-50 hover:border-emerald-300 dark:hover:bg-emerald-950/40 active:scale-95 cursor-pointer')
                                            ]"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Info Callout for Netral Action (0 Poin) -->
                                <div v-else class="p-3.5 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-900/50 text-blue-800 dark:text-blue-300 text-xs leading-relaxed space-y-1">
                                    <div class="font-semibold flex items-center gap-1.5">
                                        <CheckCircle2 class="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                        <span>Status Tindakan Netral (0 Poin KPI)</span>
                                    </div>
                                    <p class="text-[11px] text-blue-700/90 dark:text-blue-300/90 font-normal">
                                        Laporan ini dikategorikan sebagai masukan fasilitas/operasional umum. <strong>Tidak ada saldo poin KPI staf yang dipotong maupun ditambah</strong>.
                                    </p>
                                </div>

                                <!-- Panel Disposisi PESU PELUH (Layanan Penunjang & Sarpras) - Hanya Aktif untuk Laporan NETRAL -->
                                <div 
                                    v-if="actionType === 'NETRAL' || alreadyDispatchedToPesupeluh"
                                    :class="[
                                        'rounded-xl border transition p-4 space-y-3',
                                        alreadyDispatchedToPesupeluh || forwardToPesupeluh 
                                            ? 'bg-emerald-50/60 dark:bg-emerald-950/25 border-emerald-300/80 dark:border-emerald-800/60' 
                                            : 'bg-slate-50/70 dark:bg-slate-950/40 border-slate-200 dark:border-slate-800'
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-emerald-600 text-white shrink-0 shadow-xs">
                                                <Wrench class="h-4 w-4" />
                                            </div>
                                            <div>
                                                <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                                                    <span>Disposisi ke PESU PELUH (Unit Penunjang / IPSRS)</span>
                                                    <span v-if="alreadyDispatchedToPesupeluh" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                                        TERKIRIM
                                                    </span>
                                                </h4>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                                    Teruskan aduan kerusakan fisik/fasilitas ke teknisi penunjang rumah sakit.
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Toggle Switch (disabled if already verified or already dispatched) -->
                                        <div v-if="!alreadyDispatchedToPesupeluh && !isVerified" class="flex items-center shrink-0">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" v-model="forwardToPesupeluh" class="sr-only peer" />
                                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Status If Already Dispatched -->
                                    <div v-if="alreadyDispatchedToPesupeluh" class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-emerald-200 dark:border-emerald-900/50 flex flex-wrap items-center justify-between gap-2 text-xs">
                                        <div class="flex items-center gap-2">
                                            <CheckCircle2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                            <span class="text-slate-700 dark:text-slate-200 font-medium">
                                                Tiket berhasil diteruskan dengan Nomor: <strong class="font-bold text-emerald-700 dark:text-emerald-400">{{ report.pesupeluh_ticket_number }}</strong>
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-slate-400">{{ report.dispatched_to_pesupeluh_at || '' }}</span>
                                    </div>

                                    <!-- Form Settings when forwardToPesupeluh is active -->
                                    <div v-else-if="forwardToPesupeluh" class="space-y-3 pt-2 border-t border-emerald-200/60 dark:border-emerald-900/40">
                                        
                                        <!-- Pilihan Target Ruangan di PESU PELUH via SearchableSelect -->
                                        <div class="space-y-1.5">
                                            <div class="flex items-center justify-between gap-2">
                                                <label class="text-[10.5px] font-semibold text-slate-700 dark:text-slate-300 block">
                                                    Target Ruangan di PESU PELUH:
                                                </label>
                                                <span class="text-[10px] text-slate-400">
                                                    Asal Aduan: <strong class="text-slate-600 dark:text-slate-300">{{ report.unit }}</strong>
                                                </span>
                                            </div>
                                            <SearchableSelect
                                                v-model="pesupeluhRoomId"
                                                :options="pesupeluhRoomOptions"
                                                valueKey="id"
                                                labelKey="name"
                                                :disabled="isVerified"
                                                maxHeight="max-h-40 sm:max-h-44"
                                                placeholder="-- Pilih Target Ruangan di PESU PELUH --"
                                                searchPlaceholder="Cari nama ruangan atau gedung..."
                                            />
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <!-- Pilihan Kategori di Pesu Peluh via SearchableSelect -->
                                            <div class="space-y-1.5">
                                                <label class="text-[10.5px] font-semibold text-slate-700 dark:text-slate-300 block">
                                                    Kategori Penunjang (PESU PELUH):
                                                </label>
                                                <SearchableSelect
                                                    v-model="pesupeluhCategoryId"
                                                    :options="pesupeluhCategoryOptions"
                                                    valueKey="id"
                                                    labelKey="name"
                                                    :disabled="isVerified"
                                                    maxHeight="max-h-40 sm:max-h-44"
                                                    placeholder="-- Pilih Kategori Penunjang --"
                                                    searchPlaceholder="Cari kategori masalah..."
                                                />
                                            </div>

                                            <!-- Prioritas Penunjang via SearchableSelect -->
                                            <div class="space-y-1.5">
                                                <label class="text-[10.5px] font-semibold text-slate-700 dark:text-slate-300 block">
                                                    Urgensi Perbaikan Penunjang:
                                                </label>
                                                <SearchableSelect
                                                    v-model="pesupeluhPriority"
                                                    :options="pesupeluhPriorityOptions"
                                                    valueKey="id"
                                                    labelKey="name"
                                                    :searchable="false"
                                                    :disabled="isVerified"
                                                    maxHeight="max-h-32"
                                                    placeholder="Pilih Urgensi..."
                                                />
                                            </div>
                                        </div>

                                        <!-- Info Data yang Diteruskan -->
                                        <div class="bg-emerald-100/60 dark:bg-emerald-950/35 p-2.5 rounded-lg border border-emerald-200 dark:border-emerald-900/40 flex items-start gap-2 text-[11px] text-emerald-900 dark:text-emerald-200">
                                            <Sparkles class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                                            <div class="space-y-1 leading-relaxed">
                                                <p>
                                                    <strong>Identitas Pelapor di PESU PELUH:</strong>
                                                    <span class="font-bold underline ml-1">
                                                        {{ report.is_anonymous ? 'Masyarakat / Pasien (Anonim via SIPUAS)' : `${report.reporter_name} (Publik via SIPUAS)` }}
                                                    </span>
                                                    <span v-if="!report.is_anonymous && report.reporter_phone" class="text-[10px] ml-1">
                                                        (HP: {{ report.reporter_phone }})
                                                    </span>
                                                </p>
                                                <p class="text-[10.5px] text-emerald-800/90 dark:text-emerald-300/80">
                                                    Teknisi penunjang dapat langsung melihat nama pelapor, nomor telepon warga<template v-if="report.attachments && report.attachments.length > 0">, serta <strong>{{ report.attachments.length }} lampiran foto bukti</strong></template> langsung di aplikasi PESU PELUH.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Supervisor Notes -->
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-medium text-slate-400 dark:text-slate-500 block">Catatan Berita Acara / Tindak Lanjut:</label>
                                    <textarea
                                        v-model="supervisorNotes"
                                        :disabled="isVerified"
                                        :readonly="isVerified"
                                        rows="3"
                                        placeholder="Tuliskan klarifikasi kejadian, evaluasi tindakan, atau catatan apresiasi untuk staf..."
                                        :class="[
                                            'w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs sm:text-sm text-slate-800 dark:text-slate-200 leading-relaxed font-normal focus:outline-none transition',
                                            isVerified ? 'cursor-not-allowed opacity-80' : 'focus:border-emerald-500'
                                        ]"
                                    ></textarea>
                                </div>

                                <!-- Attachment Section: SP / Surat Teguran / Berkas Apresiasi (Opsional, dibatasi 1 file saat ini) -->
                                <div v-if="actionType !== 'NETRAL' || (isVerified && report?.verification_attachments?.length > 0)" class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-[11px] font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                                            <Paperclip class="h-3.5 w-3.5" />
                                            <span>
                                                {{ 
                                                    actionType === 'PEMOTONGAN' 
                                                        ? 'Lampiran Berkas Evaluasi / SP (Opsional):' 
                                                        : (actionType === 'PENAMBAHAN' 
                                                            ? 'Lampiran Berkas Apresiasi / Sertifikat (Opsional):' 
                                                            : 'Berkas Pendukung / Berita Acara (Opsional):') 
                                                }}
                                            </span>
                                        </label>
                                        <span v-if="!isVerified" class="text-[10px] text-slate-400 font-normal">
                                            Maks. {{ MAX_ATTACHMENTS }} Berkas (PDF, Dokumen, atau Foto)
                                        </span>
                                    </div>

                                    <!-- Hidden File Input -->
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
                                        class="hidden"
                                        @change="onFileInputChange"
                                    />

                                    <!-- Mode VERIFIED: Tampilkan berkas yang telah diunggah saat verifikasi -->
                                    <div v-if="isVerified">
                                        <div v-if="report?.verification_attachments && report.verification_attachments.length > 0" class="space-y-2">
                                            <div
                                                v-for="att in report.verification_attachments"
                                                :key="'verif-att-' + att.id"
                                                class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 flex items-center justify-between gap-3"
                                            >
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div :class="[
                                                        'h-10 w-10 rounded-xl flex items-center justify-center shrink-0 border',
                                                        att.file_type === 'pdf' 
                                                            ? 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-950/40 dark:border-rose-900/50' 
                                                            : (att.file_type === 'image' 
                                                                ? 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900/50' 
                                                                : 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-950/40 dark:border-blue-900/50')
                                                    ]">
                                                        <FileText v-if="att.file_type === 'pdf' || att.file_type === 'document'" class="h-5 w-5" />
                                                        <Image v-else class="h-5 w-5" />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate" :title="att.file_name">
                                                            {{ att.file_name }}
                                                        </p>
                                                        <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-2 flex-wrap">
                                                            <span>{{ att.file_size }}</span>
                                                            <span v-if="att.created_at">• {{ att.created_at }}</span>
                                                            <span v-if="att.uploaded_by">• Oleh: {{ att.uploaded_by }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    <button
                                                        v-if="att.file_type === 'image'"
                                                        type="button"
                                                        @click="selectedImagePreview = att.url"
                                                        class="h-8 px-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-1 transition cursor-pointer"
                                                    >
                                                        <Maximize2 class="h-3.5 w-3.5" />
                                                        <span class="hidden sm:inline">Lihat</span>
                                                    </button>
                                                    <a
                                                        :href="att.url"
                                                        target="_blank"
                                                        download
                                                        class="h-8 px-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-1 transition cursor-pointer"
                                                    >
                                                        <Download class="h-3.5 w-3.5" />
                                                        <span>Unduh</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="p-3 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-950/30 text-center text-xs text-slate-400">
                                            Tidak ada berkas fisik/SP dilampirkan pada verifikasi ini.
                                        </div>
                                    </div>

                                    <!-- Mode PENDING (Input Upload) -->
                                    <div v-else class="space-y-2">
                                        <!-- If files already selected -->
                                        <div v-if="verificationFiles.length > 0" class="space-y-2">
                                            <div
                                                v-for="(vf, idx) in verificationFiles"
                                                :key="'vf-' + idx"
                                                class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/60 flex items-center justify-between gap-3 shadow-xs"
                                            >
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div :class="[
                                                        'h-10 w-10 rounded-xl flex items-center justify-center shrink-0 border overflow-hidden',
                                                        vf.type === 'pdf' 
                                                            ? 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-950/40 dark:border-rose-900/50' 
                                                            : (vf.type === 'image' 
                                                                ? 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900/50' 
                                                                : 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-950/40 dark:border-blue-900/50')
                                                    ]">
                                                        <FileText v-if="vf.type === 'pdf' || vf.type === 'word' || vf.type === 'excel' || vf.type === 'document'" class="h-5 w-5" />
                                                        <img v-else-if="vf.previewUrl" :src="vf.previewUrl" class="h-full w-full object-cover rounded-xl" />
                                                        <Image v-else class="h-5 w-5" />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate" :title="vf.name">
                                                            {{ vf.name }}
                                                        </p>
                                                        <p class="text-[10px] text-slate-400 mt-0.5">
                                                            {{ vf.size }} • <span class="text-emerald-600 dark:text-emerald-400 font-medium">Siap diunggah</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <button
                                                        type="button"
                                                        @click="triggerFileInput"
                                                        class="text-[11px] font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 underline cursor-pointer"
                                                        title="Ganti berkas"
                                                    >
                                                        Ganti
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="removeVerificationFile(idx)"
                                                        class="h-7 w-7 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 flex items-center justify-center transition cursor-pointer"
                                                        title="Hapus berkas"
                                                    >
                                                        <Trash2 class="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Upload Dropzone (jika belum mencapai batas maksimal upload) -->
                                        <div
                                            v-if="verificationFiles.length < MAX_ATTACHMENTS"
                                            @click="triggerFileInput"
                                            @dragover.prevent="isDragging = true"
                                            @dragleave.prevent="isDragging = false"
                                            @drop.prevent="onFileDrop"
                                            :class="[
                                                'border-2 border-dashed rounded-xl p-3.5 sm:p-4 text-center cursor-pointer transition flex flex-col items-center justify-center gap-1.5 select-none',
                                                isDragging 
                                                    ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20' 
                                                    : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/30 hover:border-slate-300 dark:hover:border-slate-700 hover:bg-slate-100/40'
                                            ]"
                                        >
                                            <div class="h-8 w-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex items-center justify-center">
                                                <Paperclip class="h-4 w-4" />
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                    Pilih Berkas Lampiran <span class="text-slate-400 font-normal">atau seret ke sini</span>
                                                </p>
                                                <p class="text-[10px] text-slate-400 mt-0.5">
                                                    PDF, Word, Excel, atau Foto JPG/PNG (Maks. 10 MB • Opsional)
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Error message if invalid file -->
                                        <p v-if="fileError" class="text-[11px] text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1 mt-1">
                                            <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                            <span>{{ fileError }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Verified State Banner or Submit Button -->
                                <div v-if="isVerified" :class="[
                                    'p-3.5 sm:p-4 rounded-xl text-center space-y-1.5 select-none shadow-xs border',
                                    actionType === 'PEMOTONGAN'
                                        ? 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-900/60'
                                        : (actionType === 'NETRAL'
                                            ? 'bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-900/60'
                                            : 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800')
                                ]">
                                    <div :class="[
                                        'flex items-center justify-center gap-2 font-bold text-xs sm:text-sm',
                                        actionType === 'PEMOTONGAN'
                                            ? 'text-rose-800 dark:text-rose-300'
                                            : (actionType === 'NETRAL' ? 'text-blue-800 dark:text-blue-300' : 'text-emerald-800 dark:text-emerald-300')
                                    ]">
                                        <CheckCircle2 class="h-4 w-4 sm:h-4.5 sm:w-4.5 shrink-0" :class="actionType === 'PEMOTONGAN' ? 'text-rose-600 dark:text-rose-400' : (actionType === 'NETRAL' ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400')" />
                                        <span>Laporan Ini Telah Selesai Diverifikasi</span>
                                    </div>
                                    <p :class="[
                                        'text-[11px] sm:text-xs font-normal leading-relaxed',
                                        actionType === 'PEMOTONGAN'
                                            ? 'text-rose-700/90 dark:text-rose-300/80'
                                            : (actionType === 'NETRAL' ? 'text-blue-700/90 dark:text-blue-300/80' : 'text-emerald-700/90 dark:text-emerald-300/80')
                                    ]">
                                        Verifikasi dieksekusi pada <strong>{{ report.verified_at || '-' }}</strong><template v-if="report.verified_by"> oleh <strong>{{ report.verified_by }}</strong></template>. Saldo poin staf unit telah tercatat di logbook dan tidak dapat diubah kembali.
                                    </p>
                                </div>

                                <button
                                    v-else
                                    type="button"
                                    @click="submitVerification"
                                    :disabled="isSubmitting || isVerified"
                                    :class="[
                                        'w-full py-3.5 sm:py-3 text-white rounded-xl text-xs sm:text-sm font-semibold flex items-center justify-center gap-2 shadow-sm transition disabled:opacity-50 cursor-pointer bg-emerald-600 hover:bg-emerald-500 active:scale-[0.99] shadow-emerald-500/20',
                                        (isSubmitting || isVerified) ? 'opacity-60 cursor-not-allowed pointer-events-none' : ''
                                    ]"
                                >
                                    <RefreshCw v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                                    <Send v-else-if="forwardToPesupeluh && !alreadyDispatchedToPesupeluh" class="h-4 w-4" />
                                    <CheckCircle2 v-else class="h-4 w-4" />
                                    <span>
                                        {{ 
                                            isSubmitting 
                                                ? 'Memproses Verifikasi & Disposisi...' 
                                                : (forwardToPesupeluh && !alreadyDispatchedToPesupeluh 
                                                    ? 'Simpan Verifikasi & Teruskan ke PESU PELUH' 
                                                    : 'Simpan Verifikasi & Catat Logbook') 
                                        }}
                                    </span>
                                </button>
                            </div>
                        </div>

                </div>
            </template>

        </div>

        <!-- Lightbox Image Preview Modal -->
        <div v-if="selectedImagePreview" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md">
            <div class="relative max-w-4xl w-full max-h-[90vh] flex flex-col items-center justify-center">
                <button
                    @click="selectedImagePreview = null"
                    class="absolute -top-10 right-0 text-white hover:text-slate-300 p-2 rounded-full cursor-pointer"
                >
                    <X class="h-6 w-6" />
                </button>
                <img
                    :src="selectedImagePreview"
                    alt="Lampiran Bukti Penuh"
                    class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl border border-white/10"
                />
            </div>
        </div>

        <!-- Standard SIPUAS Swal-Style Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showConfirmModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                    <!-- Backdrop overlay (Click outside to close disabled as per standard) -->
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity select-none"></div>

                    <!-- Modal Card -->
                    <div class="relative bg-white/95 dark:bg-slate-900/95 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden p-6 sm:p-7 flex flex-col items-center text-center transform transition-all duration-200 scale-100 backdrop-blur-md">
                        <!-- Status Icon -->
                        <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-full flex items-center justify-center mb-4 sm:mb-5 flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
                            <ShieldCheck class="h-8 w-8 sm:h-10 sm:w-10" />
                        </div>

                        <!-- Info Content -->
                        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white leading-tight px-2">
                            Konfirmasi Verifikasi Laporan
                        </h3>

                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed px-1">
                            Periksa kembali rincian verifikasi sebelum disimpan ke logbook dan saldo poin staf unit.
                        </p>

                        <!-- Summary Card -->
                        <div class="mt-4 p-4 bg-slate-50/80 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800 rounded-2xl text-left w-full space-y-2.5 text-xs">
                            <div class="flex justify-between items-center pb-2 border-b border-slate-200/60 dark:border-slate-800">
                                <span class="text-slate-400 font-medium">Nomor Tiket:</span>
                                <span class="font-bold text-slate-900 dark:text-white font-['Poppins',sans-serif]">{{ report?.id || '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-slate-200/60 dark:border-slate-800">
                                <span class="text-slate-400 font-medium">Ruangan / Unit:</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ report?.unit || '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-slate-200/60 dark:border-slate-800">
                                <span class="text-slate-400 font-medium">Aksi Distribusi KPI:</span>
                                <div>
                                    <span v-if="actionType === 'PENAMBAHAN'" class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        +{{ pointValue }} Poin (Apresiasi / Pujian)
                                    </span>
                                    <span v-else-if="actionType === 'PEMOTONGAN'" class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                        -{{ pointValue }} Poin (Evaluasi / Aduan)
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                        0 Poin (Netral / Sarpras)
                                    </span>
                                </div>
                            </div>
                            <div class="pb-2" :class="(actionType === 'NETRAL' && forwardToPesupeluh) || verificationFiles.length > 0 ? 'border-b border-slate-200/60 dark:border-slate-800' : ''">
                                <span class="text-slate-400 font-medium block mb-1">Staf Bertugas Terkait:</span>
                                <div v-if="selectedStaffList.length > 0" class="font-semibold text-slate-800 dark:text-slate-200 text-xs">
                                    {{ selectedStaffList.map(s => s.name).join(', ') }}
                                </div>
                                <div v-else class="text-slate-400 italic text-xs">
                                    Tidak ada staf dikaitkan
                                </div>
                            </div>
                            <div v-if="verificationFiles.length > 0" class="flex justify-between items-center pb-2" :class="actionType === 'NETRAL' && forwardToPesupeluh ? 'border-b border-slate-200/60 dark:border-slate-800' : ''">
                                <span class="text-slate-400 font-medium">Lampiran Berkas:</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5 truncate max-w-[200px]">
                                    <Paperclip class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                    <span class="truncate">{{ verificationFiles[0].name }} ({{ verificationFiles[0].size }})</span>
                                </span>
                            </div>
                            <div v-if="actionType === 'NETRAL' && forwardToPesupeluh" class="p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200/80 dark:border-sky-800/80 text-sky-800 dark:text-sky-300 text-xs font-medium flex items-center gap-2">
                                <Wrench class="h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400" />
                                <span>Laporan ini akan otomatis <strong>diteruskan ke PESU PELUH</strong> untuk teknisi sarana.</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3 w-full mt-6">
                            <button
                                type="button"
                                @click="showConfirmModal = false"
                                class="flex-1 h-11 text-xs sm:text-sm font-bold rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 transition duration-150 focus:outline-none cursor-pointer"
                            >
                                Batal / Periksa Lagi
                            </button>
                            <button
                                type="button"
                                @click="confirmAndExecute"
                                class="flex-1 h-11 text-xs sm:text-sm font-bold rounded-xl text-white shadow-sm transition duration-150 focus:outline-none bg-emerald-600 hover:bg-emerald-500 active:scale-[0.99] cursor-pointer"
                            >
                                Ya, Simpan Verifikasi
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Standard SIPUAS Swal-Style Validation Warning Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showValidationModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                    <!-- Backdrop overlay (Click outside to close disabled as per standard) -->
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity select-none"></div>

                    <!-- Modal Card -->
                    <div class="relative bg-white/95 dark:bg-slate-900/95 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden p-6 sm:p-7 flex flex-col items-center text-center transform transition-all duration-200 scale-100 backdrop-blur-md">
                        <!-- Status Icon -->
                        <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-full flex items-center justify-center mb-4 sm:mb-5 flex-shrink-0 bg-amber-50 dark:bg-amber-950/40 text-amber-500">
                            <AlertCircle class="h-8 w-8 sm:h-10 sm:w-10" />
                        </div>

                        <!-- Info Content -->
                        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white leading-tight px-2">
                            Pilih Staf Bertugas
                        </h3>

                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2.5 leading-relaxed px-1">
                            Mohon centang setidaknya 1 staf yang bertugas saat kejadian untuk mengaitkan poin KPI evaluasi/apresiasi.
                        </p>

                        <!-- Action Button -->
                        <div class="w-full mt-6">
                            <button
                                type="button"
                                @click="showValidationModal = false"
                                class="w-full h-11 text-sm font-bold rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 transition duration-150 focus:outline-none cursor-pointer active:scale-[0.99]"
                            >
                                OK, Mengerti
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Success Verification Modal (Styled as Standard Swal Alert) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                    <!-- Backdrop overlay (Click outside to close disabled as per standard) -->
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity select-none"></div>

                    <!-- Modal Card -->
                    <div class="relative bg-white/95 dark:bg-slate-900/95 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden p-7 flex flex-col items-center text-center transform transition-all duration-200 scale-100 backdrop-blur-md">
                        <!-- Status Icon -->
                        <div class="h-20 w-20 rounded-full flex items-center justify-center mb-5 flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500">
                            <CheckCircle2 class="h-10 w-10" />
                        </div>

                        <!-- Info Content -->
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight px-2">
                            {{ isDispatchedToPesupeluh ? 'Verifikasi & Disposisi Berhasil Disimpan!' : 'Verifikasi Berhasil Disimpan!' }}
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-3 leading-relaxed px-1">
                            <template v-if="isDispatchedToPesupeluh">
                                Status laporan telah diperbarui menjadi <strong class="font-semibold text-slate-700 dark:text-slate-200">TERVERIFIKASI</strong> dan logbook unit telah dicatat. Laporan aduan fasilitas ini juga telah <strong class="text-emerald-600 dark:text-emerald-400 font-semibold">berhasil didisposisikan ke PESU PELUH</strong> untuk penanganan teknisi sarana.
                            </template>
                            <template v-else>
                                Status laporan telah diperbarui menjadi <strong class="font-semibold text-slate-700 dark:text-slate-200">TERVERIFIKASI</strong> dan logbook unit telah dicatat.
                            </template>
                        </p>

                        <!-- Callout Info Tiket PESU PELUH jika diteruskan -->
                        <div v-if="isDispatchedToPesupeluh" class="mt-4 p-4 bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-300/80 dark:border-emerald-800/80 rounded-xl text-left w-full space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-xs font-bold text-emerald-800 dark:text-emerald-300">
                                    <div class="h-6 w-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-xs">
                                        <Wrench class="h-3.5 w-3.5" />
                                    </div>
                                    <span>Disposisi ke PESU PELUH Berhasil</span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    TERKIRIM
                                </span>
                            </div>
                            <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-emerald-200/80 dark:border-emerald-900/60 flex items-center justify-between gap-2">
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Nomor Tiket PESU PELUH:</span>
                                <span class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400 tracking-wide font-['Poppins',sans-serif]">
                                    {{ pesupeluhTicketNumber || props.reportDetail?.pesupeluh_ticket_number || 'Sedang Diproses' }}
                                </span>
                            </div>
                            <p class="text-[11px] text-emerald-800/90 dark:text-emerald-300/90 leading-relaxed font-medium">
                                📌 Keterangan: Data keluhan fasilitas, foto bukti, dan lokasi ruangan telah otomatis terdisposisi ke tim teknisi pemeliharaan sarana (IPSRS).
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3 w-full mt-6">
                            <button
                                type="button"
                                @click="finishVerification"
                                class="flex-1 h-11 text-sm font-bold rounded-xl text-white shadow-sm transition duration-150 focus:outline-none bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] cursor-pointer"
                            >
                                Kembali ke Feed Aduan Kasi
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
