<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash || {});
const visible = ref(true);

watch(flash, () => {
    visible.value = true;
});
</script>

<template>
    <div v-if="visible && (flash.success || flash.error || flash.message)" class="mb-5">
        <div
            v-if="flash.success"
            class="flex items-center justify-between p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-md"
        >
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ flash.success }}</span>
            </div>
            <button @click="visible = false" class="text-emerald-600 hover:text-emerald-800 text-xs ml-4">✕</button>
        </div>

        <div
            v-if="flash.error"
            class="flex items-center justify-between p-3.5 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md"
        >
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>{{ flash.error }}</span>
            </div>
            <button @click="visible = false" class="text-red-600 hover:text-red-800 text-xs ml-4">✕</button>
        </div>
    </div>
</template>
