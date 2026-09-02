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
            class="relative overflow-hidden bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-md"
        >
            <div class="flex items-center justify-between px-3.5 py-2.5">
                <div class="flex items-center gap-2.5 text-xs">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <div class="font-medium">
                        <span class="font-semibold text-emerald-950">Data saved:</span>
                        <span class="ml-1 text-emerald-900">{{ flash.success.replace(/^Data saved!\s*/i, '') }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 ml-3">
                    <button
                        @click="close"
                        class="p-1 rounded text-emerald-700 hover:text-emerald-950 hover:bg-emerald-100 transition-colors"
                        title="Tutup"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- 7s Countdown Line -->
            <div class="w-full bg-emerald-100 h-0.5">
                <div
                    class="bg-emerald-600 h-0.5 transition-all ease-linear"
                    :style="{ width: `${progress}%` }"
                ></div>
            </div>
        </div>

        <!-- Clean Error Alert -->
        <div
            v-if="flash.error"
            class="bg-red-50 border border-red-200 text-red-900 rounded-md px-3.5 py-2.5 flex items-center justify-between text-xs"
        >
            <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="font-medium text-red-900">{{ flash.error }}</span>
            </div>
            <button
                @click="close"
                class="p-1 text-red-700 hover:text-red-950 rounded hover:bg-red-100 transition-colors ml-3"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>
