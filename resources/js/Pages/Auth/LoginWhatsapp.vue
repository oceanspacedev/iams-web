<script setup>
import { ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import LoginLayout from '@/Layouts/LoginLayout.vue';

const props = defineProps({
    status: {
        type: String,
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

const otpStep = ref(props.otpSent ? 'verify' : 'request');

// --- Minta kode ---
const otpRequestForm = useForm({
    phone: props.verifiedPhone || '',
});

// Hitung mundur 5 menit (300 detik)
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
            nextTick(() => focusOtpInput(0));
        },
    });
};

// --- Verifikasi kode ---
const otpDigits = ref(['', '', '', '', '', '']);
const otpInputs = ref([]);

const otpVerifyForm = useForm({
    phone: props.verifiedPhone || '',
    otp: '',
    remember: true,
});

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

const handleDigitInput = (index, event) => {
    const val = event.target.value.replace(/[^0-9]/g, '');
    otpDigits.value[index] = val ? val.slice(-1) : '';

    if (val && index < 5) {
        focusOtpInput(index + 1);
    }

    if (otpDigits.value.join('').length === 6) {
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
        focusOtpInput(5);
        submitOtp();
    }
};

const changePhoneNumber = () => {
    otpStep.value = 'request';
    otpDigits.value = ['', '', '', '', '', ''];
    otpVerifyForm.reset('otp');
};

watch(() => props.otpSent, (sent) => {
    if (sent) {
        otpStep.value = 'verify';
        startCountdown(300);
    }
});

onMounted(() => {
    if (props.otpSent) {
        startCountdown(300);
        nextTick(() => focusOtpInput(0));
    }
});

onBeforeUnmount(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const inputBase =
    'block h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-900 shadow-sm outline-none transition-colors duration-150 placeholder:text-gray-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20';
// Border normal vs error dipisah: dua kelas border-* sekaligus saling menimpa
const inputClass = inputBase + ' border-gray-300';
const inputErrorClass = inputBase + ' border-red-600';
const primaryButtonClass =
    'flex h-10 w-full items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/40';
</script>

<template>
    <LoginLayout>
        <Head title="Masuk dengan WhatsApp" />

        <h1 class="text-center text-xl font-semibold text-gray-900">Masuk dengan WhatsApp</h1>

        <!-- Langkah 1: nomor -->
        <form v-if="otpStep === 'request'" @submit.prevent="requestOtp" class="mt-6 space-y-5">
            <div>
                <label for="wa_phone" class="mb-2 block text-sm font-medium text-gray-900">
                    Nomor WhatsApp atau email<span class="text-red-600">*</span>
                </label>
                <input
                    id="wa_phone"
                    type="text"
                    inputmode="tel"
                    v-model="otpRequestForm.phone"
                    required
                    autofocus
                    placeholder="Contoh: 081224290502"
                    :class="otpRequestForm.errors.phone ? inputErrorClass : inputClass"
                />
                <p v-if="otpRequestForm.errors.phone" class="mt-2 text-sm text-red-600">{{ otpRequestForm.errors.phone }}</p>
            </div>

            <button type="submit" :disabled="otpRequestForm.processing" :class="primaryButtonClass">
                {{ otpRequestForm.processing ? 'Mengirim…' : 'Kirim kode' }}
            </button>
        </form>

        <!-- Langkah 2: kode -->
        <form v-else @submit.prevent="submitOtp" class="mt-6 space-y-5">
            <div class="text-sm text-gray-500">
                Kode dikirim ke
                <span class="tabular-nums text-gray-900">{{ maskedPhone || otpRequestForm.phone }}</span>
                <button
                    type="button"
                    @click="changePhoneNumber"
                    class="ml-2 text-blue-600 transition-colors duration-150 hover:text-blue-800"
                >
                    Ganti
                </button>
            </div>

            <div>
                <div class="flex gap-2">
                    <input
                        v-for="(digit, index) in otpDigits"
                        :key="index"
                        ref="otpInputs"
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        :aria-label="`Digit ${index + 1}`"
                        :value="digit"
                        @input="handleDigitInput(index, $event)"
                        @keydown="handleDigitKeydown(index, $event)"
                        @paste="handleDigitPaste($event)"
                        class="h-12 w-full min-w-0 rounded-lg border border-gray-300 bg-white text-center text-lg font-medium text-gray-900 shadow-sm tabular-nums outline-none transition-colors duration-150 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20"
                    />
                </div>
                <p v-if="otpVerifyForm.errors.otp" class="mt-2 text-sm text-red-600">{{ otpVerifyForm.errors.otp }}</p>
            </div>

            <div class="flex items-center justify-between text-sm">
                <span v-if="countdown > 0" class="tabular-nums text-gray-500">{{ formatTime(countdown) }}</span>
                <span v-else class="text-red-600">Kode kedaluwarsa</span>
                <button
                    type="button"
                    @click="requestOtp"
                    :disabled="otpRequestForm.processing || countdown > 240"
                    class="text-blue-600 transition-colors duration-150 hover:text-blue-800 disabled:cursor-not-allowed disabled:text-gray-400"
                >
                    Kirim ulang
                </button>
            </div>

            <button
                type="submit"
                :disabled="otpVerifyForm.processing || otpDigits.join('').length < 6"
                :class="primaryButtonClass"
            >
                {{ otpVerifyForm.processing ? 'Memverifikasi…' : 'Masuk' }}
            </button>
        </form>

        <div class="my-5 flex items-center gap-3 text-xs text-gray-400">
            <span class="h-px flex-1 bg-gray-200"></span>
            atau
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>

        <Link
            :href="route('login')"
            class="flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/20"
        >
            <svg class="h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect width="20" height="16" x="2" y="4" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
            </svg>
            Masuk dengan email
        </Link>
    </LoginLayout>
</template>
