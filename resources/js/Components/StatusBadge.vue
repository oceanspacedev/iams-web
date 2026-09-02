<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const config = computed(() => {
    switch (props.status?.toUpperCase()) {
        case 'OPEN':
            return { label: 'Open', bg: 'bg-rose-50 text-rose-700 border-rose-200' };
        case 'IN_PROGRESS':
            return { label: 'In Progress', bg: 'bg-amber-50 text-amber-800 border-amber-200' };
        case 'WAITING_VERIFICATION':
            return { label: 'Waiting Review', bg: 'bg-blue-50 text-blue-700 border-blue-200' };
        case 'VERIFIED':
            return { label: 'Verified', bg: 'bg-slate-100 text-slate-700 border-slate-200' };
        case 'CLOSED':
            return { label: 'Closed', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'PLANNED':
            return { label: 'Planned', bg: 'bg-slate-50 text-slate-600 border-slate-200' };
        case 'COMPLETED':
            return { label: 'Completed', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'PENDING':
            return { label: 'Pending', bg: 'bg-amber-50 text-amber-800 border-amber-200' };
        case 'APPROVED':
            return { label: 'Approved', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'REJECTED':
            return { label: 'Rejected', bg: 'bg-red-50 text-red-700 border-red-200' };
        case 'OVERDUE':
            return { label: 'Overdue', bg: 'bg-red-50 text-red-700 border-red-200 font-semibold' };
        default:
            return { label: props.status || '—', bg: 'bg-gray-50 text-gray-600 border-gray-200' };
    }
});
</script>

<template>
    <span
        class="inline-block px-2 py-0.5 rounded text-[11px] font-medium border"
        :class="config.bg"
    >
        {{ config.label }}
    </span>
</template>
