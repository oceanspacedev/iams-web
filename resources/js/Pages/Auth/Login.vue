<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const fillCredentials = (email) => {
    form.email = email;
    form.password = 'password';
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col items-center justify-center p-4 sm:p-6 font-sans antialiased text-slate-800">
        <Head title="Login — Sistem Audit (IAMS)" />

        <!-- Main Center Card -->
        <div class="w-full max-w-4xl bg-white rounded-2xl shadow-lg border border-slate-200/80 overflow-hidden my-auto">
            <div class="flex flex-col md:flex-row">
                
                <!-- LEFT PANEL: Clean Corporate Info -->
                <div class="w-full md:w-[380px] bg-slate-900 text-white p-8 sm:p-10 flex flex-col justify-between shrink-0">
                    <!-- Brand -->
                    <div>
                        <div class="flex items-center gap-3.5 mb-8">
                            <ApplicationLogo size="w-11 h-11" />
                            <div>
                                <div class="font-bold text-lg tracking-tight text-white leading-tight">IAMS</div>
                                <div class="text-[11px] font-medium text-slate-400">Internal Audit Management</div>
                            </div>
                        </div>

                        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-white leading-snug">
                            Portal Audit & Pemeriksaan
                        </h2>
                        <p class="text-sm text-slate-300 mt-2.5 leading-relaxed">
                            Sistem terintegrasi untuk perencanaan audit, pencatatan temuan lapangan, dan tindak lanjut perbaikan.
                        </p>
                    </div>

                    <!-- Features checklist -->
                    <div class="my-8 space-y-3 border-t border-slate-800 pt-6 text-xs text-slate-300">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Jadwal & Penugasan Auditor</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Input Temuan & Action Plan</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Notifikasi WhatsApp Real-time</span>
                        </div>
                    </div>

                    <!-- Footer note -->
                    <div class="text-[11px] text-slate-400">
                        © 2026 IAMS Enterprise System.
                    </div>
                </div>

                <!-- RIGHT PANEL: Normal Clean Form -->
                <div class="flex-1 p-8 sm:p-10 lg:p-12 flex flex-col justify-center bg-white">
                    
                    <div class="max-w-sm w-full mx-auto">
                        <!-- Heading -->
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-slate-900">Masuk ke Akun</h1>
                            <p class="text-sm text-slate-500 mt-1">Silakan masukkan email dan kata sandi Anda</p>
                        </div>

                        <!-- Status Alert -->
                        <div v-if="status" class="mb-4 text-xs font-medium text-emerald-800 bg-emerald-50 p-3 rounded-lg border border-emerald-200">
                            {{ status }}
                        </div>

                        <!-- Form -->
                        <form @submit.prevent="submit" class="space-y-4">
                            <!-- Email Input -->
                            <div>
                                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="nama@perusahaan.com"
                                    class="w-full text-sm rounded-lg border border-slate-300 px-3.5 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition"
                                />
                                <InputError class="mt-1" :message="form.errors.email" />
                            </div>

                            <!-- Password Input -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="password" class="block text-xs font-semibold text-slate-700">
                                        Kata Sandi
                                    </label>
                                    <a
                                        href="#"
                                        @click.prevent
                                        class="text-xs text-blue-600 hover:text-blue-700 font-medium"
                                    >
                                        Lupa sandi?
                                    </a>
                                </div>
                                <div class="relative">
                                    <input
                                        id="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        v-model="form.password"
                                        required
                                        autocomplete="current-password"
                                        placeholder="••••••••"
                                        class="w-full text-sm rounded-lg border border-slate-300 px-3.5 pr-10 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
                                    >
                                        <svg v-if="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                    </button>
                                </div>
                                <InputError class="mt-1" :message="form.errors.password" />
                            </div>

                            <!-- Remember me -->
                            <div class="flex items-center pt-0.5">
                                <label class="flex items-center text-xs text-slate-600 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        v-model="form.remember"
                                        class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="ml-2">Ingat saya di perangkat ini</span>
                                </label>
                            </div>

                            <!-- Submit button -->
                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full py-2.5 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold transition disabled:opacity-50 shadow-sm"
                                >
                                    {{ form.processing ? 'Memproses...' : 'Masuk' }}
                                </button>
                            </div>
                        </form>

                        <!-- Quick Demo Credentials -->
                        <div class="mt-8 pt-5 border-t border-slate-200">
                            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider text-center mb-2.5">
                                Akun Demo (Password: password)
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="fillCredentials('admin@auditflow.com')"
                                    class="px-2.5 py-1.5 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-medium text-slate-700 text-left transition cursor-pointer"
                                >
                                    <div class="font-semibold text-slate-900">Admin</div>
                                    <div class="text-[10px] text-slate-500 truncate">admin@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('chief@auditflow.com')"
                                    class="px-2.5 py-1.5 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-medium text-slate-700 text-left transition cursor-pointer"
                                >
                                    <div class="font-semibold text-slate-900">Chief Auditor</div>
                                    <div class="text-[10px] text-slate-500 truncate">chief@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('asmen@auditflow.com')"
                                    class="px-2.5 py-1.5 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-medium text-slate-700 text-left transition cursor-pointer"
                                >
                                    <div class="font-semibold text-slate-900">Asmen</div>
                                    <div class="text-[10px] text-slate-500 truncate">asmen@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('kordinator@auditflow.com')"
                                    class="px-2.5 py-1.5 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-medium text-slate-700 text-left transition cursor-pointer"
                                >
                                    <div class="font-semibold text-slate-900">Koordinator</div>
                                    <div class="text-[10px] text-slate-500 truncate">kordinator@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('auditor@auditflow.com')"
                                    class="px-2.5 py-1.5 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-medium text-slate-700 text-left transition cursor-pointer"
                                >
                                    <div class="font-semibold text-slate-900">Auditor</div>
                                    <div class="text-[10px] text-slate-500 truncate">auditor@auditflow.com</div>
                                </button>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </div>

        <!-- Clean simple footer -->
        <div class="text-xs text-slate-400 mt-5 text-center">
            Sistem Audit (IAMS) &copy; 2026. Hak Cipta Dilindungi.
        </div>

    </div>
</template>
