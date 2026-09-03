<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, User, Mail, Phone, Lock, IdCard, Camera, Upload, Trash2, CheckCircle2, Loader2, Building2 } from '@lucide/vue';

const props = defineProps({
    units: {
        type: Array,
        default: () => []
    }
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const cameraInput = ref(null);
const galleryInput = ref(null);
const photoPreview = ref(null);
const isCompressing = ref(false);
const photoError = ref('');

const form = useForm({
    name: '',
    username: '',
    nip: '',
    unit_id: '',
    phone_number: '',
    email: '',
    profile_photo: null,
    password: '',
    password_confirmation: '',
});

const triggerCamera = () => {
    photoError.value = '';
    cameraInput.value?.click();
};

const triggerGallery = () => {
    photoError.value = '';
    galleryInput.value?.click();
};

const compressImage = (file, maxWidth = 1000, maxHeight = 1000, quality = 0.8) => {
    return new Promise((resolve) => {
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

const onPhotoSelected = async (event) => {
    photoError.value = '';
    const file = event.target.files?.[0];
    if (!file) return;

    // Strict validation: Only accept JPG, JPEG, PNG, WEBP
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    const extension = file.name.split('.').pop()?.toLowerCase();
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    if (!allowedTypes.includes(file.type) || !allowedExtensions.includes(extension)) {
        photoError.value = 'Format file tidak didukung! Mohon hanya unggah gambar berformat JPG, PNG, atau WebP.';
        event.target.value = '';
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        photoError.value = 'Ukuran file foto terlalu besar. Maksimal 10 MB!';
        event.target.value = '';
        return;
    }

    isCompressing.value = true;

    try {
        const compressedFile = await compressImage(file);
        form.profile_photo = compressedFile;

        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreview.value = e.target.result;
        };
        reader.readAsDataURL(compressedFile);
    } catch (err) {
        console.error('Gagal mengompresi pasfoto:', err);
        form.profile_photo = file;
        photoPreview.value = URL.createObjectURL(file);
    } finally {
        isCompressing.value = false;
        event.target.value = '';
    }
};

const removePhoto = () => {
    form.profile_photo = null;
    photoPreview.value = null;
    photoError.value = '';
    if (cameraInput.value) cameraInput.value.value = '';
    if (galleryInput.value) galleryInput.value.value = '';
};

const allowOnlyNumbers = (e) => {
    // Allow control/navigation keys
    if (
        ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key) ||
        e.ctrlKey || e.metaKey
    ) {
        return;
    }
    // Block non-numeric keys
    if (!/^[0-9]$/.test(e.key)) {
        e.preventDefault();
    }
};

const handleNipInput = (e) => {
    form.nip = e.target.value.replace(/\D/g, '').slice(0, 18);
};

const handlePhoneInput = (e) => {
    form.phone_number = e.target.value.replace(/\D/g, '').slice(0, 15);
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout :hide-logo="true" :no-padding="true" max-width="max-w-[440px]">
        <Head title="Daftar Akun Baru" />

        <!-- Card Header Section for Logo & Subtitle (Flat Solid) -->
        <div
            class="border-b border-slate-200 bg-slate-50 p-6 text-center dark:border-slate-800 dark:bg-slate-950 sm:p-8"
        >
            <Link
                href="/"
                class="inline-flex items-center justify-center transition-transform hover:scale-105 cursor-pointer"
                title="SIPUAS"
            >
                <img
                    src="/images/logo-sidebar.png"
                    alt="SIPUAS Logo"
                    class="h-9 sm:h-10 w-auto object-contain mx-auto dark:brightness-0 dark:invert"
                />
            </Link>

            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
                Lengkapi data diri dan pasfoto untuk membuat akun SIPUAS
            </p>
        </div>

        <!-- Card Body / Form Area (Flat Solid) -->
        <div class="p-6 sm:p-8 bg-white dark:bg-slate-900">
            <form @submit.prevent="submit" class="space-y-5">
                <!-- PASFOTO DIRI (WAJIB) -->
                <div class="space-y-3 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/50">
                    <!-- DIATAS: Judul & Subtitle -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                            Unggah Pasfoto Formal <span class="text-rose-500">*</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            Wajah jelas, format JPG/PNG/WebP maks. 10MB.
                        </p>
                    </div>

                    <!-- Hidden Inputs for Native Camera and File Upload -->
                    <input
                        ref="cameraInput"
                        type="file"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        capture="user"
                        class="hidden"
                        @change="onPhotoSelected"
                    />
                    <input
                        ref="galleryInput"
                        type="file"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        class="hidden"
                        @change="onPhotoSelected"
                    />

                    <!-- SPACE AREA FOTO (TENGAH) -->
                    <div class="flex flex-col items-center justify-center py-1">
                        <div class="relative">
                            <!-- Loader saat kompresi -->
                            <div
                                v-if="isCompressing"
                                class="h-28 w-28 rounded-2xl border-2 border-emerald-500 bg-white dark:bg-slate-900 flex flex-col items-center justify-center text-emerald-600 gap-1.5 shadow-sm"
                            >
                                <Loader2 class="h-7 w-7 animate-spin" />
                                <span class="text-[10px] font-semibold">Mengompresi...</span>
                            </div>

                            <!-- Jika foto sudah dipilih -->
                            <div
                                v-else-if="photoPreview"
                                class="h-28 w-28 rounded-2xl overflow-hidden border-2 border-emerald-500 bg-white dark:bg-slate-900 flex items-center justify-center relative shadow-sm"
                            >
                                <img :src="photoPreview" alt="Preview Pasfoto" class="h-full w-full object-cover" />
                            </div>

                            <!-- Jika belum ada foto -->
                            <div
                                v-else
                                class="h-28 w-28 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 gap-1.5"
                            >
                                <Camera class="h-8 w-8 text-slate-400 dark:text-slate-500" />
                                <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500">Area Pasfoto</span>
                            </div>
                        </div>

                        <!-- Status Pasfoto Siap Diunggah -->
                        <div v-if="photoPreview && !isCompressing" class="mt-2 text-center">
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                <CheckCircle2 class="h-3.5 w-3.5" />
                                <span>Pasfoto siap diunggah</span>
                            </span>
                        </div>
                    </div>

                    <!-- DIBAWAH: KONTROL TOMBOL -->
                    <!-- Jika foto sudah dipilih: Tampilkan tombol Hapus Pasfoto (Tombol Camera & Upload tersembunyi) -->
                    <div v-if="photoPreview" class="flex items-center justify-center pt-1">
                        <button
                            type="button"
                            @click="removePhoto"
                            class="h-9 w-full rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 text-xs font-bold flex items-center justify-center gap-1.5 transition cursor-pointer"
                            title="Hapus pasfoto untuk memilih ulang"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            <span>Hapus Pasfoto</span>
                        </button>
                    </div>

                    <!-- Jika belum ada foto: Tampilkan tombol Buka Kamera & Pilih File -->
                    <div v-else class="grid grid-cols-2 gap-2.5 pt-1">
                        <button
                            type="button"
                            @click="triggerCamera"
                            :disabled="isCompressing"
                            class="h-10 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer disabled:opacity-50"
                            title="Buka kamera depan/belakang hp"
                        >
                            <Camera class="h-4 w-4" />
                            <span>Buka Kamera</span>
                        </button>

                        <button
                            type="button"
                            @click="triggerGallery"
                            :disabled="isCompressing"
                            class="h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer disabled:opacity-50"
                            title="Pilih file dari galeri atau berkas"
                        >
                            <Upload class="h-4 w-4" />
                            <span>Pilih File</span>
                        </button>
                    </div>

                    <!-- Client-side Error Notice -->
                    <div v-if="photoError" class="text-rose-500 text-[11px] font-medium text-center">
                        {{ photoError }}
                    </div>

                    <!-- Backend Error Message -->
                    <InputError :message="form.errors.profile_photo" />
                </div>

                <!-- NAMA LENGKAP -->
                <div class="space-y-1.5">
                    <label
                        for="name"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <User
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="name"
                            type="text"
                            name="name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Contoh: dr. H. Rahmat, Sp.B"
                            v-model="form.name"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-slate-950"
                        />
                    </div>
                    <InputError :message="form.errors.name" />
                </div>

                <!-- USERNAME -->
                <div class="space-y-1.5">
                    <label
                        for="username"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Username <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <User
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="username"
                            type="text"
                            name="username"
                            required
                            autocomplete="username"
                            placeholder="Contoh: rahmat_rs"
                            v-model="form.username"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-slate-950"
                        />
                    </div>
                    <InputError :message="form.errors.username" />
                </div>

                <!-- NIP -->
                <div class="space-y-1.5">
                    <label
                        for="nip"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        NIP (Nomor Induk Pegawai) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <IdCard
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="nip"
                            type="text"
                            name="nip"
                            required
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="18"
                            placeholder="198207102008011003"
                            :value="form.nip"
                            @keydown="allowOnlyNumbers"
                            @input="handleNipInput"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-slate-950"
                        />
                    </div>
                    <InputError :message="form.errors.nip" />
                </div>

                <!-- PENUGASAN UNIT KERJA -->
                <div class="space-y-1.5">
                    <label
                        for="unit_id"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Penugasan Unit Kerja
                    </label>
                    <div class="relative">
                        <Building2
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <select
                            id="unit_id"
                            name="unit_id"
                            v-model="form.unit_id"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-emerald-400 dark:focus:bg-slate-950 cursor-pointer"
                        >
                            <option value="">-- Pilih Unit Kerja --</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.name }}
                            </option>
                        </select>
                    </div>
                    <InputError :message="form.errors.unit_id" />
                </div>

                <!-- NO HP -->
                <div class="space-y-1.5">
                    <label
                        for="phone_number"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        No. HP / WhatsApp
                    </label>
                    <div class="relative">
                        <Phone
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="phone_number"
                            type="tel"
                            name="phone_number"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="15"
                            placeholder="081234567890"
                            :value="form.phone_number"
                            @keydown="allowOnlyNumbers"
                            @input="handlePhoneInput"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-slate-950"
                        />
                    </div>
                    <InputError :message="form.errors.phone_number" />
                </div>

                <!-- EMAIL -->
                <div class="space-y-1.5">
                    <label
                        for="email"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Email Resmi <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <Mail
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autocomplete="email"
                            placeholder="nama@rs.local"
                            v-model="form.email"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-slate-950"
                        />
                    </div>
                    <InputError :message="form.errors.email" />
                </div>

                <!-- KATA SANDI -->
                <div class="space-y-1.5">
                    <label
                        for="password"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Kata Sandi <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <Lock
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                            v-model="form.password"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-10 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-slate-950"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute top-1/2 right-3.5 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none dark:text-slate-500 dark:hover:text-white cursor-pointer"
                            :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            aria-label="Toggle password visibility"
                        >
                            <EyeOff v-if="showPassword" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <!-- KONFIRMASI KATA SANDI -->
                <div class="space-y-1.5">
                    <label
                        for="password_confirmation"
                        class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300 select-none"
                    >
                        Konfirmasi Kata Sandi <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <Lock
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            id="password_confirmation"
                            :type="showPasswordConfirmation ? 'text' : 'password'"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                            v-model="form.password_confirmation"
                            class="block w-full h-11 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-10 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-0 focus:shadow-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-slate-950"
                        />
                        <button
                            type="button"
                            @click="showPasswordConfirmation = !showPasswordConfirmation"
                            class="absolute top-1/2 right-3.5 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none dark:text-slate-500 dark:hover:text-white cursor-pointer"
                            :title="showPasswordConfirmation ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            aria-label="Toggle password confirmation visibility"
                        >
                            <EyeOff v-if="showPasswordConfirmation" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing || isCompressing"
                        class="flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-white shadow-none transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span
                            v-if="form.processing || isCompressing"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        ></span>
                        <span>{{ form.processing ? 'Memproses Pendaftaran...' : isCompressing ? 'Mengompresi Pasfoto...' : 'Daftar Akun' }}</span>
                    </button>
                </div>
            </form>

            <!-- Footer: Login Link -->
            <div class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
                Sudah memiliki akun?
                <Link
                    :href="route('login')"
                    class="ml-1 font-semibold text-emerald-600 hover:underline focus:outline-none dark:text-emerald-400 transition"
                >
                    Masuk di sini
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
