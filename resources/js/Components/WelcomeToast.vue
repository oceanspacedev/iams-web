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
        class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden animate-toast-in text-slate-800"
    >
        <div class="p-4 flex items-start gap-3.5">
            <!-- Icon Badge -->
            <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Login Berhasil</span>
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                </div>
                <h3 class="text-sm font-bold text-slate-900 truncate mt-0.5">
                    Selamat Datang, {{ welcomeData.name }}!
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Masuk sebagai <span class="font-semibold text-slate-700">{{ welcomeData.role }}</span>
                </p>
            </div>

            <!-- Close Button -->
            <button
                @click="closeToast"
                type="button"
                class="text-slate-400 hover:text-slate-600 p-1 rounded-md transition-colors"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Progress Countdown Bar -->
        <div class="h-1 w-full bg-slate-100">
            <div
                class="h-full bg-emerald-600 transition-all duration-75 ease-linear"
                :style="{ width: progress + '%' }"
            ></div>
        </div>
    </div>
</template>
