<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const page = usePage();
const welcomeData = computed(() => page.props.flash?.welcome);
const isVisible = ref(false);
const progress = ref(100);
let timer = null;
let progressInterval = null;

const showToast = () => {
    if (!welcomeData.value) return;
    
    isVisible.value = true;
    progress.value = 100;
    
    if (timer) clearTimeout(timer);
    if (progressInterval) clearInterval(progressInterval);

    // Progress bar countdown (4000ms)
    const startTime = Date.now();
    const duration = 4000;

    progressInterval = setInterval(() => {
        const elapsed = Date.now() - startTime;
        const remaining = Math.max(0, 100 - (elapsed / duration) * 100);
        progress.value = remaining;

        if (remaining <= 0) {
            clearInterval(progressInterval);
        }
    }, 50);

    timer = setTimeout(() => {
        isVisible.value = false;
    }, duration);
};

watch(welcomeData, (newVal) => {
    if (newVal) {
        showToast();
    }
}, { immediate: true });

onMounted(() => {
    if (welcomeData.value) {
        showToast();
    }
});

const closeToast = () => {
    isVisible.value = false;
    if (timer) clearTimeout(timer);
    if (progressInterval) clearInterval(progressInterval);
};
</script>

<template>
    <div
        v-if="isVisible && welcomeData"
        class="animate-toast-in fixed right-5 top-5 z-50 w-full max-w-sm overflow-hidden rounded-xl border border-slate-200 bg-white text-slate-800 shadow-[0_8px_24px_rgba(0,0,0,0.12)]"
    >
        <div class="flex items-start gap-3 p-4">
            <!-- Icon Badge -->
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-emerald-700">Login berhasil</span>
                    
                </div>
                <h3 class="mt-0.5 truncate text-sm font-medium text-slate-900">
                    Selamat datang, {{ welcomeData.name }}
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Masuk sebagai <span class="font-medium text-slate-700">{{ welcomeData.role }}</span>
                </p>
            </div>

            <!-- Close Button -->
            <button
                @click="closeToast"
                type="button"
                class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors duration-150 hover:text-slate-600" aria-label="Tutup"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Progress Countdown Bar -->
        <div class="h-1 w-full bg-slate-100">
            <div
                class="h-full origin-left bg-emerald-600 transition-transform duration-75 ease-linear"
                :style="{ transform: 'scaleX(' + progress / 100 + ')' }"
            ></div>
        </div>
    </div>
</template>
