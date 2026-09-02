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
    <div v-if="totalItems > 0" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 bg-white border-t border-gray-200 text-xs">
        <div class="text-gray-500">
            Menampilkan <span class="font-semibold text-gray-800">{{ startItem }}</span> - <span class="font-semibold text-gray-800">{{ endItem }}</span> dari <span class="font-semibold text-gray-800">{{ totalItems }}</span> data
        </div>

        <div v-if="totalPages > 1" class="flex items-center gap-1 self-center sm:self-auto">
            <!-- Prev Button -->
            <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-2.5 py-1 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                title="Halaman Sebelumnya"
            >
                ‹ Prev
            </button>

            <!-- Page Number Buttons -->
            <template v-for="(p, idx) in pages" :key="idx">
                <span v-if="p === '...'" class="px-2 text-gray-400">...</span>
                <button
                    v-else
                    @click="goToPage(p)"
                    class="min-w-[28px] h-7 px-2 rounded text-xs font-medium transition-colors border"
                    :class="p === currentPage ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                >
                    {{ p }}
                </button>
            </template>

            <!-- Next Button -->
            <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="px-2.5 py-1 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                title="Halaman Selanjutnya"
            >
                Next ›
            </button>
        </div>
    </div>
</template>
