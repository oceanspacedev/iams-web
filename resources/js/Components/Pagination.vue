<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentPage: {
        type: Number,
        default: 1,
    },
    perPage: {
        type: Number,
        default: 10,
    },
    totalItems: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['update:currentPage']);

const totalPages = computed(() => Math.max(1, Math.ceil(props.totalItems / props.perPage)));

const startItem = computed(() => {
    if (props.totalItems === 0) return 0;
    return (props.currentPage - 1) * props.perPage + 1;
});

const endItem = computed(() => {
    return Math.min(props.currentPage * props.perPage, props.totalItems);
});

const pages = computed(() => {
    const current = props.currentPage;
    const total = totalPages.value;
    const items = [];

    if (total <= 7) {
        for (let i = 1; i <= total; i++) items.push(i);
    } else {
        items.push(1);
        if (current > 3) items.push('...');
        
        const start = Math.max(2, current - 1);
        const end = Math.min(total - 1, current + 1);
        for (let i = start; i <= end; i++) {
            if (!items.includes(i)) items.push(i);
        }
        
        if (current < total - 2) items.push('...');
        if (!items.includes(total)) items.push(total);
    }
    return items;
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value && page !== props.currentPage) {
        emit('update:currentPage', page);
    }
};
</script>

<template>
    <div v-if="totalItems > 0" class="flex flex-col gap-3 border-t border-gray-200 bg-white px-5 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="text-gray-500 tabular-nums">
            Menampilkan <span class="font-medium text-gray-800">{{ startItem }}</span> - <span class="font-medium text-gray-800">{{ endItem }}</span> dari <span class="font-medium text-gray-800">{{ totalItems }}</span> data
        </div>

        <div v-if="totalPages > 1" class="flex items-center gap-1 self-center sm:self-auto">
            <!-- Prev Button -->
            <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                title="Halaman sebelumnya"
                aria-label="Halaman sebelumnya"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
            </button>

            <!-- Page Number Buttons -->
            <template v-for="(p, idx) in pages" :key="idx">
                <span v-if="p === '...'" class="px-2 text-gray-400">...</span>
                <button
                    v-else
                    @click="goToPage(p)"
                    class="h-8 min-w-[32px] rounded-lg border px-2 text-sm tabular-nums transition-colors duration-150"
                    :class="p === currentPage ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                >
                    {{ p }}
                </button>
            </template>

            <!-- Next Button -->
            <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                title="Halaman berikutnya"
                aria-label="Halaman berikutnya"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
            </button>
        </div>
    </div>
</template>
