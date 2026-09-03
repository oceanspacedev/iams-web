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
    <div class="min-h-screen bg-slate-100 flex flex-col items-center justify-center p-4 sm:p-6 font-sans antialiased text-slate-800">
        <Head title="Login — Sistem Audit (IAMS)" />

        <!-- Main Center Card -->
        <div class="w-full max-w-4xl bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden my-auto">
            <div class="flex flex-col md:flex-row">
                
                <!-- LEFT PANEL: Clean Corporate Info -->
                <div class="w-full md:w-[360px] bg-slate-900 text-white p-8 flex flex-col justify-between shrink-0">
                    <div>
                        <div class="flex items-center gap-3 mb-8">
                            <ApplicationLogo size="w-10 h-10" />
                            <div>
                                <div class="font-bold text-base tracking-tight text-white leading-tight">IAMS</div>
                                <div class="text-[11px] text-slate-400">Internal Audit Management</div>
                            </div>
                        </div>

                        <h2 class="text-xl font-bold tracking-tight text-white leading-snug">
                            Portal Audit & Pemeriksaan
                        </h2>
                        <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                            Sistem terintegrasi untuk perencanaan audit, pencatatan temuan lapangan, dan tindak lanjut perbaikan.
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

                    <!-- Footer note -->
                    <div class="text-[11px] text-slate-500">
                        © 2026 IAMS Enterprise System.
                    </div>
                </div>

                <!-- RIGHT PANEL: Clean Form -->
                <div class="flex-1 p-8 sm:p-10 flex flex-col justify-center bg-white">
                    <div class="max-w-sm w-full mx-auto">
                        
                        <!-- Heading -->
                        <div class="mb-5">
                            <h1 class="text-xl font-bold text-slate-900">Masuk ke Akun</h1>
                            <p class="text-xs text-slate-500 mt-0.5">Silakan masuk untuk melanjutkan ke sistem</p>
                        </div>

                        <!-- Status Alert (Only shown on Password tab or initial visit) -->
                        <div v-if="status && (activeTab === 'password' || otpStep === 'request')" class="mb-4 text-xs text-emerald-800 bg-emerald-50 p-2.5 rounded-md border border-emerald-200">
                            {{ status }}
                        </div>

                        <!-- Clean Tabs Switch (No badges, no AI-slop) -->
                        <div class="flex border-b border-slate-200 mb-5">
                            <button
                                type="button"
                                @click="activeTab = 'password'"
                                :class="activeTab === 'password' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="flex-1 pb-2.5 text-xs border-b-2 text-center transition cursor-pointer"
                            >
                                Email & Kata Sandi
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'otp'"
                                :class="activeTab === 'otp' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="flex-1 pb-2.5 text-xs border-b-2 text-center transition cursor-pointer"
                            >
                                WhatsApp
                            </button>
                        </div>

                        <!-- ============================================== -->
                        <!-- TAB 1: EMAIL & PASSWORD LOGIN                  -->
                        <!-- ============================================== -->
                        <form v-if="activeTab === 'password'" @submit.prevent="submitPassword" class="space-y-3.5">
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
                                    class="w-full text-xs rounded-md border border-slate-300 px-3 py-2 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition"
                                />
                                <InputError class="mt-1" :message="form.errors.email" />
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
                                        class="w-full text-xs rounded-md border border-slate-300 px-3 pr-9 py-2 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 cursor-pointer"
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

                            <div class="flex items-center pt-0.5">
                                <label class="flex items-center text-xs text-slate-600 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        v-model="form.remember"
                                        class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="ml-2">Ingat saya</span>
                                </label>
                            </div>

                            <div class="pt-1">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full py-2 px-4 rounded-md bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer"
                                >
                                    {{ form.processing ? 'Memproses...' : 'Masuk' }}
                                </button>
                            </div>
                        </form>

                        <!-- ============================================== -->
                        <!-- TAB 2: WHATSAPP OTP LOGIN                      -->
                        <!-- ============================================== -->
                        <div v-if="activeTab === 'otp'">
                            
                            <!-- STEP 1: REQUEST OTP -->
                            <form v-if="otpStep === 'request'" @submit.prevent="requestOtp" class="space-y-3.5">
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
                                        class="w-full text-xs rounded-md border border-slate-300 px-3 py-2 text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition"
                                    />
                                    <InputError class="mt-1" :message="otpRequestForm.errors.phone" />
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Kode OTP 6-digit akan dikirimkan ke WhatsApp yang terdaftar.
                                    </p>
                                </div>

                                <div class="pt-1">
                                    <button
                                        type="submit"
                                        :disabled="otpRequestForm.processing"
                                        class="w-full py-2 px-4 rounded-md bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer"
                                    >
                                        {{ otpRequestForm.processing ? 'Mengirim...' : 'Kirim Kode OTP' }}
                                    </button>
                                </div>
                            </form>

                            <!-- STEP 2: VERIFY OTP -->
                            <form v-else @submit.prevent="submitOtp" class="space-y-3.5">
                                <!-- Clean Info Row -->
                                <div class="flex items-center justify-between text-xs py-2 px-3 bg-slate-50 border border-slate-200 rounded-md text-slate-600">
                                    <div class="truncate mr-2">
                                        <span class="text-slate-500">Nomor: </span>
                                        <span class="font-semibold text-slate-900">{{ maskedPhone || otpRequestForm.phone }}</span>
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
                                    <div class="flex justify-center gap-2">
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
                                            class="w-10 h-11 text-center text-base font-bold rounded-md border border-slate-300 text-slate-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none transition bg-white"
                                        />
                                    </div>
                                    <InputError class="mt-1 text-center" :message="otpVerifyForm.errors.otp" />
                                </div>

                                <!-- Timer 5 Menit (300 Detik) & Kirim Ulang -->
                                <div class="text-center text-xs text-slate-500 pt-1">
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
                                        <span class="ml-2">Ingat saya</span>
                                    </label>
                                </div>

                                <div class="pt-1">
                                    <button
                                        type="submit"
                                        :disabled="otpVerifyForm.processing || otpDigits.join('').length < 6"
                                        class="w-full py-2 px-4 rounded-md bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold transition disabled:opacity-50 cursor-pointer"
                                    >
                                        {{ otpVerifyForm.processing ? 'Memverifikasi...' : 'Verifikasi & Masuk' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Link to Register -->
                        <div class="mt-4 text-center text-xs text-slate-500">
                            Belum memiliki akun?
                            <Link :href="route('register')" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                Daftar Akun Baru
                            </Link>
                        </div>

                        <!-- Quick Demo Credentials -->
                        <div v-if="showDemoAccounts" class="mt-6 pt-4 border-t border-slate-200">
                            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider text-center mb-2">
                                Akun Demo (Password: password)
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="fillCredentials('admin@auditflow.com')"
                                    class="px-2 py-1.5 rounded border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs text-slate-700 text-left transition cursor-pointer"
                                >
                                    <div class="font-medium text-slate-900 truncate">Admin</div>
                                    <div class="text-[10px] text-slate-500 truncate">admin@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('chief@auditflow.com')"
                                    class="px-2 py-1.5 rounded border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs text-slate-700 text-left transition cursor-pointer"
                                >
                                    <div class="font-medium text-slate-900 truncate">Chief Auditor</div>
                                    <div class="text-[10px] text-slate-500 truncate">chief@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('asmen@auditflow.com')"
                                    class="px-2 py-1.5 rounded border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs text-slate-700 text-left transition cursor-pointer"
                                >
                                    <div class="font-medium text-slate-900 truncate">Asmen</div>
                                    <div class="text-[10px] text-slate-500 truncate">asmen@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('kordinator@auditflow.com')"
                                    class="px-2 py-1.5 rounded border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs text-slate-700 text-left transition cursor-pointer"
                                >
                                    <div class="font-medium text-slate-900 truncate">Koordinator</div>
                                    <div class="text-[10px] text-slate-500 truncate">kordinator@auditflow.com</div>
                                </button>
                                <button
                                    type="button"
                                    @click="fillCredentials('auditor@auditflow.com')"
                                    class="px-2 py-1.5 rounded border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs text-slate-700 text-left transition cursor-pointer"
                                >
                                    <div class="font-medium text-slate-900 truncate">Auditor</div>
                                    <div class="text-[10px] text-slate-500 truncate">auditor@auditflow.com</div>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
