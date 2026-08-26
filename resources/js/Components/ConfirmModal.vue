<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Konfirmasi Tindakan',
    },
    message: {
        type: String,
        default: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    },
    confirmText: {
        type: String,
        default: 'Ya, Lanjutkan',
    },
    cancelText: {
        type: String,
        default: 'Batal',
    },
    type: {
        type: String,
        default: 'primary', // 'primary', 'danger', 'success', 'warning'
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'close']);

const iconClasses = computed(() => {
    switch (props.type) {
        case 'danger':
            return 'bg-red-50 text-red-600 border border-red-200';
        case 'success':
            return 'bg-emerald-50 text-emerald-600 border border-emerald-200';
        case 'warning':
            return 'bg-amber-50 text-amber-600 border border-amber-200';
        default:
            return 'bg-blue-50 text-blue-600 border border-blue-200';
    }
});

const confirmButtonClasses = computed(() => {
    switch (props.type) {
        case 'danger':
            return 'bg-red-600 hover:bg-red-700 focus:ring-red-500 text-white';
        case 'success':
            return 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 text-white';
        case 'warning':
            return 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500 text-white';
        default:
            return 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 text-white';
    }
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6"
    >
        <!-- Backdrop Overlay -->
        <div
            @click="!loading && emit('close')"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity animate-in fade-in duration-200"
        ></div>

        <!-- Modal Dialog Card -->
        <div
            class="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 z-10 animate-in fade-in zoom-in-95 duration-200 text-slate-800"
        >
            <div class="flex items-start gap-4">
                <!-- Icon Badge -->
                <div
                    class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                    :class="iconClasses"
                >
                    <!-- Success Icon -->
                    <svg
                        v-if="type === 'success'"
                        class="w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>

                    <!-- Danger / Trash Icon -->
                    <svg
                        v-else-if="type === 'danger'"
                        class="w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>

                    <!-- Warning Icon -->
                    <svg
                        v-else-if="type === 'warning'"
                        class="w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>

                    <!-- Default / Info Icon -->
                    <svg
                        v-else
                        class="w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <!-- Text Content -->
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-bold text-slate-900 leading-tight">
                        {{ title }}
                    </h3>
                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                        {{ message }}
                    </p>
                </div>
            </div>

            <!-- Action Buttons Footer -->
            <div class="mt-6 flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                <button
                    type="button"
                    @click="emit('close')"
                    :disabled="loading"
                    class="px-4 py-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-300 disabled:opacity-50"
                >
                    {{ cancelText }}
                </button>

                <button
                    type="button"
                    @click="emit('confirm')"
                    :disabled="loading"
                    class="px-4 py-2 text-xs font-semibold rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 inline-flex items-center gap-2"
                    :class="confirmButtonClasses"
                >
                    <svg
                        v-if="loading"
                        class="w-3.5 h-3.5 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ confirmText }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
