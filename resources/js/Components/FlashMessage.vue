<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onUnmounted } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash || {});
const visible = ref(false);
let timer = null;
const progress = ref(100);
let progressInterval = null;

const startAutoDismiss = () => {
    if (timer) clearTimeout(timer);
    if (progressInterval) clearInterval(progressInterval);

    visible.value = true;
    progress.value = 100;

    const totalDuration = 7000; // 7 seconds
    const intervalStep = 50;
    const stepDecrement = (intervalStep / totalDuration) * 100;

    progressInterval = setInterval(() => {
        progress.value = Math.max(0, progress.value - stepDecrement);
    }, intervalStep);

    timer = setTimeout(() => {
        visible.value = false;
        if (progressInterval) clearInterval(progressInterval);
    }, totalDuration);
};

watch(
    () => [flash.value.success, flash.value.error, flash.value.message],
    ([newSuccess, newError, newMessage]) => {
        if (newSuccess || newError || newMessage) {
            startAutoDismiss();
        } else {
            visible.value = false;
        }
    },
    { immediate: true }
);

const close = () => {
    visible.value = false;
    if (timer) clearTimeout(timer);
    if (progressInterval) clearInterval(progressInterval);
};

onUnmounted(() => {
    if (timer) clearTimeout(timer);
    if (progressInterval) clearInterval(progressInterval);
});
</script>

<template>
    <div v-if="visible && (flash.success || flash.error || flash.message)" class="mb-4">
        <!-- Clean Success Alert with 7s timer -->
        <div
            v-if="flash.success"
            class="relative overflow-hidden rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900"
        >
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3 text-sm">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium text-emerald-900">{{ flash.success.replace(/^Data saved!\s*/i, '') }}</span>
                </div>

                <div class="flex items-center gap-2 shrink-0 ml-3">
                    <button
                        @click="close"
                        class="flex h-7 w-7 items-center justify-center rounded-md text-emerald-700 transition-colors duration-150 hover:bg-emerald-100 hover:text-emerald-950" aria-label="Tutup"
                        title="Tutup"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- 7s Countdown Line -->
            <div class="w-full bg-emerald-100 h-0.5">
                <div
                    class="h-0.5 origin-left bg-emerald-600 transition-transform ease-linear"
                    :style="{ transform: `scaleX(${progress / 100})` }"
                ></div>
            </div>
        </div>

        <!-- Clean Error Alert -->
        <div
            v-if="flash.error"
            class="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
        >
            <div class="flex items-center gap-3">
                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="font-medium text-red-900">{{ flash.error }}</span>
            </div>
            <button
                @click="close"
                class="ml-3 flex h-7 w-7 items-center justify-center rounded-md text-red-700 transition-colors duration-150 hover:bg-red-100 hover:text-red-950" aria-label="Tutup"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Info -->
        <div
            v-if="flash.message && !flash.success && !flash.error"
            class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900"
        >
            <div class="flex items-center gap-3">
                <svg class="h-4 w-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8h.01M11 12h1v4h1m-1-13a9 9 0 110 18 9 9 0 010-18z" />
                </svg>
                <span class="font-medium text-blue-900">{{ flash.message }}</span>
            </div>
            <button
                @click="close"
                class="ml-3 flex h-7 w-7 items-center justify-center rounded-md text-blue-700 transition-colors duration-150 hover:bg-blue-100 hover:text-blue-950"
                aria-label="Tutup"
                title="Tutup"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>
