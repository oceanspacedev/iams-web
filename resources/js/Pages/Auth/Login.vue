<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    showDemoAccounts: {
        type: Boolean,
        default: true,
    },
    otpSent: {
        type: Boolean,
        default: false,
    },
    verifiedPhone: {
        type: String,
        default: '',
    },
    maskedPhone: {
        type: String,
        default: '',
    },
});

const activeTab = ref(props.otpSent ? 'otp' : 'password');
const otpStep = ref(props.otpSent ? 'verify' : 'request');
const showPassword = ref(false);

// --- Password Form ---
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submitPassword = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const fillCredentials = (email) => {
    activeTab.value = 'password';
    form.email = email;
    form.password = 'password';
};

// --- WhatsApp OTP Request Form ---
const otpRequestForm = useForm({
    phone: props.verifiedPhone || '',
});

// 5 Minutes Countdown (300 seconds)
const countdown = ref(300);
let timerInterval = null;

const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
};

const startCountdown = (seconds = 300) => {
    countdown.value = seconds;
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--;
        } else {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }, 1000);
};

const requestOtp = () => {
    otpRequestForm.post(route('login.otp.request'), {
        preserveScroll: true,
        onSuccess: () => {
            otpStep.value = 'verify';
            otpVerifyForm.phone = otpRequestForm.phone;
            startCountdown(300);
            nextTick(() => {
                focusOtpInput(0);
            });
        },
    });
};

// --- WhatsApp OTP Verify Form ---
const otpDigits = ref(['', '', '', '', '', '']);
const otpInputs = ref([]);

const otpVerifyForm = useForm({
    phone: props.verifiedPhone || '',
    otp: '',
    remember: true,
});

const handleDigitInput = (index, event) => {
    const val = event.target.value.replace(/[^0-9]/g, '');
    otpDigits.value[index] = val ? val.slice(-1) : '';

    if (val && index < 5) {
        focusOtpInput(index + 1);
    }

    const fullOtp = otpDigits.value.join('');
    if (fullOtp.length === 6) {
        otpVerifyForm.otp = fullOtp;
        submitOtp();
    }
};

const handleDigitKeydown = (index, event) => {
    if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
        focusOtpInput(index - 1);
    }
};

const handleDigitPaste = (event) => {
    event.preventDefault();
    const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
    if (pasted.length >= 6) {
        for (let i = 0; i < 6; i++) {
            otpDigits.value[i] = pasted[i];
        }
        otpVerifyForm.otp = pasted.slice(0, 6);
        focusOtpInput(5);
        submitOtp();
    }
};

const focusOtpInput = (index) => {
    if (otpInputs.value && otpInputs.value[index]) {
        otpInputs.value[index].focus();
    }
};

const submitOtp = () => {
    otpVerifyForm.otp = otpDigits.value.join('');
    otpVerifyForm.phone = otpRequestForm.phone || props.verifiedPhone;
    if (otpVerifyForm.otp.length < 6) return;

    otpVerifyForm.post(route('login.otp.verify'), {
        preserveScroll: true,
        onError: () => {
            otpDigits.value = ['', '', '', '', '', ''];
            focusOtpInput(0);
        },
    });
};

const changePhoneNumber = () => {
    otpStep.value = 'request';
    otpDigits.value = ['', '', '', '', '', ''];
    otpVerifyForm.reset('otp');
};

watch(() => props.otpSent, (newVal) => {
    if (newVal) {
        otpStep.value = 'verify';
        startCountdown(300);
    }
});
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col items-center justify-center p-3 sm:p-5 font-sans antialiased text-slate-800">
        <Head title="Masuk ke Akun — Sistem Audit (IAMS)" />

        <!-- Main 2-Panel Card: Balanced and compact for 100% zoom -->
        <div class="w-full max-w-4xl bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden my-auto">
            <div class="flex flex-col md:flex-row">
                
                <!-- LEFT PANEL: Dark Corporate Branding (Preserved & Optimized) -->
                <div class="w-full md:w-[320px] lg:w-[340px] bg-slate-900 text-white p-6 sm:p-7 flex flex-col justify-between shrink-0">
                    <div>
                        <div class="flex items-center gap-2.5 mb-6">
                            <ApplicationLogo size="w-9 h-9" />
                            <div>
                                <div class="font-bold text-sm tracking-tight text-white leading-tight">IAMS</div>
                                <div class="text-[10px] text-slate-400">Internal Audit Management</div>
                            </div>
                        </div>

                        <h2 class="text-lg sm:text-xl font-bold tracking-tight text-white leading-snug">
                            Portal Audit & Pemeriksaan
                        </h2>
                        <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                            Sistem terintegrasi untuk perencanaan audit, pencatatan temuan lapangan, dan tindak lanjut perbaikan.
                        </p>
                    </div>

                    <!-- Features checklist -->
                    <div class="my-6 space-y-2 border-t border-slate-800 pt-4 text-xs text-slate-300">
                        <div class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Jadwal & Penugasan Auditor</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Input Temuan & Action Plan</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Notifikasi WhatsApp Terintegrasi</span>
                        </div>
                    </div>

                    <!-- Footer note -->
                    <div class="text-[11px] text-slate-500">
                        © 2026 IAMS Enterprise System.
                    </div>
                </div>

                <!-- RIGHT PANEL: Clean Compact Form -->
                <div class="flex-1 p-5 sm:p-6 md:p-7 flex flex-col justify-center bg-white">
                    <div class="max-w-[400px] w-full mx-auto">
                        
                        <!-- Header -->
                        <div class="mb-3.5">
                            <h1 class="text-xl sm:text-[22px] font-bold text-slate-900 tracking-tight leading-tight">
                                Masuk ke Akun
                            </h1>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Silakan masuk untuk melanjutkan ke sistem
                            </p>
                        </div>

                        <!-- Status Alert -->
                        <div v-if="status" class="mb-3 text-xs text-emerald-800 bg-emerald-50 p-2.5 rounded-lg border border-emerald-200">
                            {{ status }}
                        </div>

                        <!-- Tabs: Email & Kata Sandi vs WhatsApp -->
                        <div class="flex border-b border-slate-200 mb-4">
                            <button
                                type="button"
                                @click="activeTab = 'password'"
                                :class="activeTab === 'password'
                                    ? 'border-blue-600 text-blue-600 font-semibold'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'"
                                class="flex-1 pb-2 text-xs border-b-2 text-center transition cursor-pointer"
                            >
                                Email & Kata Sandi
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'otp'"
                                :class="activeTab === 'otp'
                                    ? 'border-blue-600 text-blue-600 font-semibold'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'"
                                class="flex-1 pb-2 text-xs border-b-2 text-center transition cursor-pointer"
                            >
                                WhatsApp
                            </button>
                        </div>

                        <!-- ============================================== -->
                        <!-- TAB 1: EMAIL & KATA SANDI                      -->
                        <!-- ============================================== -->
                        <div v-if="activeTab === 'password'">
                            <form @submit.prevent="submitPassword" class="space-y-2.5">
                                <div>
                                    <label for="email" class="block text-xs font-medium text-slate-700 mb-1">
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
                                        class="w-full text-xs rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-1.5 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 outline-none transition"
                                    />
                                    <InputError class="mt-0.5 text-[11px]" :message="form.errors.email" />
                                </div>

                                <div>
                                    <label for="password" class="block text-xs font-medium text-slate-700 mb-1">
                                        Kata Sandi
                                    </label>
                                    <div class="relative">
                                        <input
                                            id="password"
                                            :type="showPassword ? 'text' : 'password'"
                                            v-model="form.password"
                                            required
                                            autocomplete="current-password"
                                            placeholder="••••••••"
                                            class="w-full text-xs rounded-lg border border-slate-200 bg-slate-50/60 px-3 pr-8 py-1.5 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 outline-none transition"
                                        />
                                        <button
                                            type="button"
                                            @click="showPassword = !showPassword"
                                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 cursor-pointer"
                                        >
                                            <svg v-if="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                            </svg>
                                        </button>
                                    </div>
                                    <InputError class="mt-0.5 text-[11px]" :message="form.errors.password" />
                                </div>

                                <div class="flex items-center pt-0.5">
                                    <label class="flex items-center text-xs text-slate-600 cursor-pointer select-none">
                                        <input
                                            type="checkbox"
                                            v-model="form.remember"
                                            class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="ml-1.5 text-xs">Ingat saya</span>
                                    </label>
                                </div>

                                <div class="pt-0.5">
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="w-full py-2 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer shadow-xs"
                                    >
                                        {{ form.processing ? 'Memproses...' : 'Masuk' }}
                                    </button>
                                </div>

                                <!-- Link to Register -->
                                <div class="text-center text-xs text-slate-500 pt-0.5">
                                    Belum memiliki akun?
                                    <Link :href="route('register')" class="text-blue-600 hover:text-blue-700 font-semibold hover:underline ml-1">
                                        Daftar Akun Baru
                                    </Link>
                                </div>
                            </form>

                            <!-- ATAU Divider -->
                            <div class="relative flex py-3 items-center">
                                <div class="flex-grow border-t border-slate-200"></div>
                                <span class="flex-shrink mx-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">ATAU</span>
                                <div class="flex-grow border-t border-slate-200"></div>
                            </div>

                            <!-- Login menggunakan WhatsApp Card Button -->
                            <button
                                type="button"
                                @click="activeTab = 'otp'"
                                class="w-full p-2.5 flex items-center justify-between rounded-lg border border-slate-200/90 hover:border-slate-300 hover:bg-slate-50/50 transition cursor-pointer text-left group"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-[#25D366] flex items-center justify-center text-white shrink-0 shadow-2xs">
                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-slate-800">Login menggunakan WhatsApp</div>
                                        <div class="text-[11px] text-slate-500">Masuk dengan akun WhatsApp Anda</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>

                        <!-- ============================================== -->
                        <!-- TAB 2: WHATSAPP OTP LOGIN                      -->
                        <!-- ============================================== -->
                        <div v-if="activeTab === 'otp'">
                            <!-- STEP 1: REQUEST OTP -->
                            <form v-if="otpStep === 'request'" @submit.prevent="requestOtp" class="space-y-2.5">
                                <div>
                                    <label for="wa_phone" class="block text-xs font-medium text-slate-700 mb-1">
                                        Nomor WhatsApp atau Email Terdaftar
                                    </label>
                                    <input
                                        id="wa_phone"
                                        type="text"
                                        v-model="otpRequestForm.phone"
                                        required
                                        autofocus
                                        placeholder="Contoh: 081224290502"
                                        class="w-full text-xs rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-1.5 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 outline-none transition"
                                    />
                                    <InputError class="mt-0.5 text-[11px]" :message="otpRequestForm.errors.phone" />
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Kode OTP 6-digit akan dikirimkan ke WhatsApp yang terdaftar.
                                    </p>
                                </div>

                                <div class="pt-0.5">
                                    <button
                                        type="submit"
                                        :disabled="otpRequestForm.processing"
                                        class="w-full py-2 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer shadow-xs"
                                    >
                                        {{ otpRequestForm.processing ? 'Mengirim...' : 'Kirim Kode OTP' }}
                                    </button>
                                </div>

                                <!-- Link to Register -->
                                <div class="text-center text-xs text-slate-500 pt-0.5">
                                    Belum memiliki akun?
                                    <Link :href="route('register')" class="text-blue-600 hover:text-blue-700 font-semibold hover:underline ml-1">
                                        Daftar Akun Baru
                                    </Link>
                                </div>
                            </form>

                            <!-- STEP 2: VERIFY OTP -->
                            <form v-else @submit.prevent="submitOtp" class="space-y-2.5">
                                <!-- Clean Info Row -->
                                <div class="flex items-center justify-between text-xs py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-600">
                                    <div class="truncate mr-2">
                                        <span class="text-slate-500 text-[11px]">Nomor: </span>
                                        <span class="font-semibold text-slate-900 text-xs">{{ maskedPhone || otpRequestForm.phone }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="changePhoneNumber"
                                        class="text-blue-600 hover:text-blue-800 text-[11px] font-medium hover:underline cursor-pointer shrink-0"
                                    >
                                        Ganti Nomor
                                    </button>
                                </div>

                                <!-- 6-Digit Boxes -->
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-2 text-center">
                                        Masukkan 6 Digit Kode OTP
                                    </label>
                                    <div class="flex justify-center gap-1.5">
                                        <input
                                            v-for="(digit, index) in otpDigits"
                                            :key="index"
                                            ref="otpInputs"
                                            type="text"
                                            maxlength="1"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            :value="digit"
                                            @input="handleDigitInput(index, $event)"
                                            @keydown="handleDigitKeydown(index, $event)"
                                            @paste="handleDigitPaste($event)"
                                            class="w-9 h-10 text-center text-base font-bold rounded-lg border border-slate-200 text-slate-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 outline-none transition bg-slate-50/60 focus:bg-white"
                                        />
                                    </div>
                                    <InputError class="mt-1 text-center text-[11px]" :message="otpVerifyForm.errors.otp" />
                                </div>

                                <!-- Timer 5 Menit (300 Detik) & Kirim Ulang -->
                                <div class="text-center text-[11px] text-slate-500 pt-0.5">
                                    <span v-if="countdown > 0">
                                        Kode berlaku: <strong class="text-slate-800 font-mono">{{ formatTime(countdown) }}</strong>
                                    </span>
                                    <span v-else class="text-red-500 font-medium">
                                        Kode kedaluwarsa
                                    </span>
                                    <span class="mx-1.5 text-slate-300">·</span>
                                    <button
                                        type="button"
                                        @click="requestOtp"
                                        :disabled="otpRequestForm.processing || countdown > 240"
                                        class="text-blue-600 hover:text-blue-800 hover:underline disabled:text-slate-400 disabled:no-underline cursor-pointer"
                                    >
                                        Kirim Ulang
                                    </button>
                                </div>

                                <div class="flex items-center justify-center pt-0.5">
                                    <label class="flex items-center text-xs text-slate-600 cursor-pointer select-none">
                                        <input
                                            type="checkbox"
                                            v-model="otpVerifyForm.remember"
                                            class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="ml-1.5">Ingat saya</span>
                                    </label>
                                </div>

                                <div class="pt-0.5">
                                    <button
                                        type="submit"
                                        :disabled="otpVerifyForm.processing || otpDigits.join('').length < 6"
                                        class="w-full py-2 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer shadow-xs"
                                    >
                                        {{ otpVerifyForm.processing ? 'Memverifikasi...' : 'Verifikasi & Masuk' }}
                                    </button>
                                </div>

                                <!-- Link to Register -->
                                <div class="text-center text-xs text-slate-500 pt-1">
                                    Belum memiliki akun?
                                    <Link :href="route('register')" class="text-blue-600 hover:text-blue-700 font-semibold hover:underline ml-1">
                                        Daftar Akun Baru
                                    </Link>
                                </div>
                            </form>

                            <!-- ATAU Divider for OTP tab -->
                            <div class="relative flex py-3 items-center">
                                <div class="flex-grow border-t border-slate-200"></div>
                                <span class="flex-shrink mx-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">ATAU</span>
                                <div class="flex-grow border-t border-slate-200"></div>
                            </div>

                            <!-- Back to Email login card button -->
                            <button
                                type="button"
                                @click="activeTab = 'password'"
                                class="w-full p-2.5 flex items-center justify-between rounded-lg border border-slate-200/90 hover:border-slate-300 hover:bg-slate-50/50 transition cursor-pointer text-left group"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white shrink-0 shadow-2xs">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-slate-800">Login dengan Email & Kata Sandi</div>
                                        <div class="text-[11px] text-slate-500">Masuk dengan email dan kata sandi Anda</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>

                        <!-- ============================================== -->
                        <!-- AKUN DEMO (PASSWORD: PASSWORD)                 -->
                        <!-- ============================================== -->
                        <div v-if="showDemoAccounts">
                            <div class="relative flex py-3 items-center">
                                <div class="flex-grow border-t border-slate-200"></div>
                                <span class="flex-shrink mx-2.5 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                                    AKUN DEMO (PASSWORD: PASSWORD)
                                </span>
                                <div class="flex-grow border-t border-slate-200"></div>
                            </div>

                            <div class="grid grid-cols-3 gap-1.5">
                                <button
                                    type="button"
                                    @click="fillCredentials('admin@auditflow.com')"
                                    class="p-1.5 px-2 rounded-lg border border-slate-200/90 bg-slate-50/70 hover:bg-slate-100 hover:border-slate-300 text-left transition cursor-pointer"
                                >
                                    <div class="text-[11px] font-bold text-slate-800 truncate">Admin</div>
                                    <div class="text-[9.5px] text-slate-500 truncate mt-0.5">admin@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('chief@auditflow.com')"
                                    class="p-1.5 px-2 rounded-lg border border-slate-200/90 bg-slate-50/70 hover:bg-slate-100 hover:border-slate-300 text-left transition cursor-pointer"
                                >
                                    <div class="text-[11px] font-bold text-slate-800 truncate">Chief Auditor</div>
                                    <div class="text-[9.5px] text-slate-500 truncate mt-0.5">chief@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('asmen@auditflow.com')"
                                    class="p-1.5 px-2 rounded-lg border border-slate-200/90 bg-slate-50/70 hover:bg-slate-100 hover:border-slate-300 text-left transition cursor-pointer"
                                >
                                    <div class="text-[11px] font-bold text-slate-800 truncate">Asmen</div>
                                    <div class="text-[9.5px] text-slate-500 truncate mt-0.5">asmen@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('kordinator@auditflow.com')"
                                    class="p-1.5 px-2 rounded-lg border border-slate-200/90 bg-slate-50/70 hover:bg-slate-100 hover:border-slate-300 text-left transition cursor-pointer"
                                >
                                    <div class="text-[11px] font-bold text-slate-800 truncate">Koordinator</div>
                                    <div class="text-[9.5px] text-slate-500 truncate mt-0.5">kordinator@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('auditor@auditflow.com')"
                                    class="p-1.5 px-2 rounded-lg border border-slate-200/90 bg-slate-50/70 hover:bg-slate-100 hover:border-slate-300 text-left transition cursor-pointer"
                                >
                                    <div class="text-[11px] font-bold text-slate-800 truncate">Auditor</div>
                                    <div class="text-[9.5px] text-slate-500 truncate mt-0.5">auditor@auditflow.com</div>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
