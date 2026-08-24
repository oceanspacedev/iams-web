<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

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
    <div class="min-h-screen flex flex-col lg:flex-row font-sans antialiased text-slate-900 bg-white selection:bg-blue-600 selection:text-white">
        <Head title="Sign In — AuditFlow Enterprise" />

        <!-- ================= LEFT COLUMN: HERO & BRAND INFO ================= -->
        <div class="w-full lg:w-[45%] xl:w-[42%] bg-[#F8FAFC] border-b lg:border-b-0 lg:border-r border-slate-200/80 p-8 sm:p-12 lg:p-16 flex flex-col justify-between shrink-0 relative overflow-hidden">
            <!-- Top: Brand Logo -->
            <div class="flex items-center gap-3">
                <!-- Custom Geometric Clean Logo -->
                <div class="w-10 h-10 flex items-center justify-center">
                    <svg class="w-9 h-9" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 6L5 28H11L14 20L17 28H23L14 6Z" fill="#0B2545" />
                        <path d="M22 14L18 24L21 30H27L31 20L22 14Z" fill="#2563EB" />
                    </svg>
                </div>
                <div>
                    <div class="text-lg font-black tracking-tight text-[#0B2545] leading-none">AUDITFLOW</div>
                    <div class="text-[9px] font-bold tracking-[0.28em] text-slate-500 uppercase leading-none mt-1">ENTERPRISE</div>
                </div>
            </div>

            <!-- Middle: Title, Intro & Feature List -->
            <div class="my-10 lg:my-auto max-w-lg">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-[#0B2545] tracking-tight leading-[1.2]">
                    Retail Internal Audit Management System
                </h1>
                
                <!-- Blue Accent Underline Bar -->
                <div class="w-12 h-1 bg-blue-600 rounded-full my-5"></div>

                <p class="text-sm text-slate-500 leading-relaxed mb-8">
                    Manage audit planning, store inspections, findings, and audit reports in one centralized system.
                </p>

                <!-- Feature Items List -->
                <div class="space-y-0 divide-y divide-slate-200/80 text-xs font-semibold text-[#0B2545]">
                    <div class="py-3 flex items-center gap-3">
                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Audit Planning</span>
                    </div>

                    <div class="py-3 flex items-center gap-3">
                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Store Inspections</span>
                    </div>

                    <div class="py-3 flex items-center gap-3">
                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Findings Management</span>
                    </div>

                    <div class="py-3 flex items-center gap-3">
                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Audit Reports</span>
                    </div>
                </div>
            </div>

            <!-- Bottom: Internal Use Only -->
            <div class="pt-6 flex items-center gap-2 text-xs font-medium text-slate-400">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Internal Use Only</span>
            </div>
        </div>

        <!-- ================= RIGHT COLUMN: SIGN IN FORM ================= -->
        <div class="flex-1 flex flex-col justify-between bg-white p-8 sm:p-12 lg:p-16">
            <div class="max-w-md w-full mx-auto my-auto py-4">
                <!-- Heading -->
                <div class="mb-8">
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                        Welcome back
                    </h2>
                    <p class="text-sm text-slate-500 mt-1.5">
                        Sign in to your account
                    </p>
                </div>

                <!-- Flash Status Message -->
                <div v-if="status" class="mb-5 text-xs font-medium text-emerald-700 bg-emerald-50 p-3 rounded-lg border border-emerald-200">
                    {{ status }}
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Email Address
                        </label>
                        <div class="relative">
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Enter your email address"
                                class="w-full text-xs sm:text-sm rounded-lg border-slate-200 pl-3.5 pr-10 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 transition-colors shadow-2xs"
                            />
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        </div>
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="w-full text-xs sm:text-sm rounded-lg border-slate-200 pl-10 pr-10 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 transition-colors shadow-2xs"
                            />
                            <!-- Eye toggle -->
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none"
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
                    <div class="flex items-center justify-between pt-0.5">
                        <label class="flex items-center text-xs text-slate-600 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="rounded border-slate-300 text-blue-600 shadow-2xs focus:ring-blue-500/20"
                            />
                            <span class="ml-2 font-medium">Remember me</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex justify-center py-2.5 px-4 rounded-lg shadow-xs text-xs sm:text-sm font-semibold text-white bg-[#0B2A6B] hover:bg-[#081F4D] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0B2A6B] disabled:opacity-50 transition-colors"
                        >
                            {{ form.processing ? 'Signing in...' : 'Sign In' }}
                        </button>
                    </div>

                    <!-- Forgot password link -->
                    <div class="text-center pt-2">
                        <a
                            href="#"
                            @click.prevent
                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors"
                        >
                            Forgot password?
                        </a>
                    </div>
                </form>

                <!-- Quick Test Credentials (Clean Corporate Style) -->
                <div class="mt-8 pt-6 border-t border-slate-200/80">
                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-2.5 text-center">
                        Quick Demo Credentials (Password: password)
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button
                            type="button"
                            @click="fillCredentials('admin@auditflow.com')"
                            class="px-2.5 py-1.5 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 text-[11px] font-semibold text-slate-800 text-center transition-colors shadow-2xs"
                        >
                            Admin
                        </button>
                        <button
                            type="button"
                            @click="fillCredentials('kordinator@auditflow.com')"
                            class="px-2.5 py-1.5 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 text-[11px] font-semibold text-slate-800 text-center transition-colors shadow-2xs"
                        >
                            Koordinator
                        </button>
                        <button
                            type="button"
                            @click="fillCredentials('auditor@auditflow.com')"
                            class="px-2.5 py-1.5 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 text-[11px] font-semibold text-slate-800 text-center transition-colors shadow-2xs"
                        >
                            Auditor
                        </button>
                        <button
                            type="button"
                            @click="fillCredentials('auditee@auditflow.com')"
                            class="px-2.5 py-1.5 rounded-md border border-slate-200 bg-slate-50 hover:bg-slate-100 text-[11px] font-semibold text-slate-800 text-center transition-colors shadow-2xs"
                        >
                            Auditee
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-slate-400 pt-6">
                © 2026 AuditFlow Enterprise. All rights reserved.
            </div>
        </div>
    </div>
</template>
