<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    availableRoles: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    phone: '',
    email: '',
    requested_role: 'auditor',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col items-center justify-center p-4 sm:p-6 font-sans antialiased text-slate-800">
        <Head title="Pendaftaran Akun Baru — Sistem Audit (IAMS)" />

        <div class="w-full max-w-4xl bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden my-auto">
            <div class="flex flex-col md:flex-row">
                
                <!-- LEFT PANEL -->
                <div class="w-full md:w-[340px] bg-slate-900 text-white p-8 flex flex-col justify-between shrink-0">
                    <div>
                        <div class="flex items-center gap-3 mb-8">
                            <ApplicationLogo size="w-10 h-10" />
                            <div>
                                <div class="font-bold text-base tracking-tight text-white leading-tight">IAMS</div>
                                <div class="text-[11px] text-slate-400">Internal Audit Management</div>
                            </div>
                        </div>

                        <h2 class="text-xl font-bold tracking-tight text-white leading-snug">
                            Pendaftaran Akun Baru
                        </h2>
                        <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                            Daftarkan akun Anda untuk mengakses portal pemeriksaan, pelaporan temuan, dan tindak lanjut perbaikan.
                        </p>
                    </div>

                    <!-- Features checklist -->
                    <div class="my-8 space-y-2.5 border-t border-slate-800 pt-5 text-xs text-slate-300">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Jadwal & Penugasan Auditor</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Input Temuan & Action Plan</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Notifikasi WhatsApp Terintegrasi</span>
                        </div>
                    </div>

                    <div class="text-[11px] text-slate-500">
                        © 2026 IAMS Enterprise System.
                    </div>
                </div>

                <!-- RIGHT PANEL: Registration Form -->
                <div class="flex-1 p-6 sm:p-8 flex flex-col justify-center bg-white">
                    <div class="max-w-md w-full mx-auto">
                        
                        <div class="mb-5">
                            <h1 class="text-xl font-bold text-slate-900">Formulir Pendaftaran</h1>
                            <p class="text-xs text-slate-500 mt-0.5">Lengkapi data Anda dengan benar</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-3.5">
                            <!-- Nama Lengkap -->
                            <div>
                                <label for="name" class="block text-xs font-medium text-slate-700 mb-1">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    placeholder="Nama sesuai identitas kantor"
                                    class="w-full text-xs rounded-md border border-slate-300 px-3 py-2 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition"
                                />
                                <InputError class="mt-1" :message="form.errors.name" />
                            </div>

                            <!-- Baris 2: Nomor WhatsApp & Email -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="phone" class="block text-xs font-medium text-slate-700 mb-1">
                                        Nomor WhatsApp <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="phone"
                                        type="text"
                                        v-model="form.phone"
                                        required
                                        placeholder="Contoh: 081234567890"
                                        class="w-full text-xs rounded-md border border-slate-300 px-3 py-2 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition"
                                    />
                                    <InputError class="mt-1" :message="form.errors.phone" />
                                </div>
                                <div>
                                    <label for="email" class="block text-xs font-medium text-slate-700 mb-1">
                                        Alamat Email <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        v-model="form.email"
                                        required
                                        placeholder="nama@perusahaan.com"
                                        class="w-full text-xs rounded-md border border-slate-300 px-3 py-2 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition"
                                    />
                                    <InputError class="mt-1" :message="form.errors.email" />
                                </div>
                            </div>

                            <!-- Pengajuan Jabatan / Role -->
                            <div>
                                <label for="requested_role" class="block text-xs font-medium text-slate-700 mb-1">
                                    Pengajuan Jabatan / Role <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="requested_role"
                                    v-model="form.requested_role"
                                    required
                                    class="w-full text-xs rounded-md border border-slate-300 px-3 py-2 text-slate-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition font-medium"
                                >
                                    <option v-for="r in availableRoles" :key="r.value" :value="r.value">
                                        {{ r.label }}
                                    </option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.requested_role" />
                            </div>

                            <!-- Kata Sandi & Konfirmasi -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="password" class="block text-xs font-medium text-slate-700 mb-1">
                                        Kata Sandi <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="password"
                                        type="password"
                                        v-model="form.password"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Minimal 8 karakter"
                                        class="w-full text-xs rounded-md border border-slate-300 px-3 py-2 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition"
                                    />
                                    <InputError class="mt-1" :message="form.errors.password" />
                                </div>
                                <div>
                                    <label for="password_confirmation" class="block text-xs font-medium text-slate-700 mb-1">
                                        Konfirmasi Kata Sandi <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        v-model="form.password_confirmation"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Ketik ulang kata sandi"
                                        class="w-full text-xs rounded-md border border-slate-300 px-3 py-2 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition"
                                    />
                                    <InputError class="mt-1" :message="form.errors.password_confirmation" />
                                </div>
                            </div>

                            <!-- Notice -->
                            <div class="p-2.5 rounded bg-slate-50 border border-slate-200 text-[11px] text-slate-600 leading-relaxed">
                                ℹ️ Akun baru akan diverifikasi oleh Administrator sebelum dapat login ke sistem.
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-1">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full py-2.5 px-4 rounded-md bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer"
                                >
                                    {{ form.processing ? 'Memproses Pendaftaran...' : 'Daftar Sekarang' }}
                                </button>
                            </div>

                            <!-- Link to Login -->
                            <div class="text-center text-xs text-slate-500 pt-2">
                                Sudah memiliki akun?
                                <Link :href="route('login')" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                    Masuk di sini
                                </Link>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
