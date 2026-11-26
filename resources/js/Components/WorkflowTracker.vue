<script setup>
import { computed } from 'vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
        default: 'OPEN',
    },
    // Tetap diterima agar pemanggil lama tidak rusak
    hasDocuments: {
        type: Boolean,
        default: false,
    },
    hasActionPlan: {
        type: Boolean,
        default: false,
    },
    documentsCount: {
        type: Number,
        default: 0,
    },
    showNote: {
        type: Boolean,
        default: true,
    },
});

// Urutan status finding: OPEN → IN_PROGRESS → WAITING_VERIFICATION → VERIFIED → CLOSED
const ORDER = ['OPEN', 'IN_PROGRESS', 'WAITING_VERIFICATION', 'VERIFIED', 'CLOSED'];

const level = computed(() => {
    const idx = ORDER.indexOf(String(props.status || 'OPEN').toUpperCase());
    return idx === -1 ? 0 : idx;
});

// state: 'done' | 'current' | 'upcoming'
const steps = computed(() => {
    const l = level.value;

    return [
        {
            label: 'Temuan dicatat',
            state: 'done',
            note: null,
        },
        {
            label: 'Action plan',
            state: l >= 1 ? 'done' : 'current',
            note: l >= 1 || props.hasActionPlan ? 'Terisi' : 'Menunggu',
        },
        {
            label: 'Verifikasi evidence',
            state: l >= 3 ? 'done' : (l >= 1 ? 'current' : 'upcoming'),
            note: l >= 3
                ? 'Terverifikasi'
                : (l === 2 ? 'Menunggu verifikasi' : (l === 1 ? 'Menunggu evidence' : null)),
        },
        {
            label: 'Ditutup',
            state: l >= 4 ? 'done' : (l === 3 ? 'current' : 'upcoming'),
            note: l >= 4 ? 'Selesai' : (l === 3 ? 'Siap ditutup koordinator' : null),
        },
    ];
});

const circleClass = (state) => ({
    done: 'bg-emerald-600 text-white border-emerald-600',
    current: 'bg-blue-50 text-blue-600 border-blue-300',
    upcoming: 'bg-gray-100 text-gray-400 border-gray-200',
}[state]);

const labelClass = (state) => ({
    done: 'text-gray-900',
    current: 'text-gray-900',
    upcoming: 'text-gray-400',
}[state]);

const noteClass = (state) => ({
    done: 'text-emerald-700',
    current: 'text-amber-700',
    upcoming: 'text-gray-400',
}[state]);
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3 border-b border-gray-100 pb-4">
            <h3 class="text-base font-semibold text-gray-900">Alur</h3>
            <StatusBadge :status="status" />
        </div>

        <ol class="space-y-4">
            <li v-for="(step, i) in steps" :key="step.label" class="flex items-start gap-3">
                <div
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-xs font-medium tabular-nums transition-colors duration-150"
                    :class="circleClass(step.state)"
                >
                    <svg v-if="step.state === 'done'" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span v-else>{{ i + 1 }}</span>
                </div>
                <div class="flex-1 pt-0.5">
                    <div class="font-medium" :class="labelClass(step.state)">{{ step.label }}</div>
                    <div v-if="step.note" class="mt-0.5 text-xs" :class="noteClass(step.state)">{{ step.note }}</div>
                </div>
            </li>
        </ol>

        <div v-if="documentsCount > 0" class="mt-4 border-t border-gray-100 pt-4 text-xs text-gray-500 tabular-nums">
            LHP/BAP · {{ documentsCount }} file
        </div>
    </div>
</template>
