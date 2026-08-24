<script setup>
import { computed } from 'vue';

const props = defineProps({
    severity: {
        type: String,
        required: true,
    },
});

const config = computed(() => {
    switch (props.severity?.toUpperCase()) {
        case 'CRITICAL':
            return { label: 'CRITICAL', bg: 'bg-red-600 text-white font-semibold' };
        case 'MAJOR':
            return { label: 'MAJOR', bg: 'bg-amber-100 text-amber-800 border border-amber-300' };
        case 'MINOR':
            return { label: 'MINOR', bg: 'bg-blue-100 text-blue-800 border border-blue-200' };
        case 'OBSERVATION':
            return { label: 'OBSERVATION', bg: 'bg-gray-100 text-gray-700 border border-gray-300' };
        default:
            return { label: props.severity || '-', bg: 'bg-gray-100 text-gray-600' };
    }
});
</script>

<template>
    <span
        class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium tracking-wide uppercase"
        :class="config.bg"
    >
        {{ config.label }}
    </span>
</template>
