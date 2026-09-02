<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    qualityFindings: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
    selectedCategory: {
        type: String,
        default: '',
    },
});

const currentPage = ref(1);

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};

const filterByCategory = (categoryKey) => {
    router.get(
        route('auditor.finding-qualities.index'),
        { category: categoryKey === props.selectedCategory ? '' : categoryKey },
        { preserveState: true, replace: true }
    );
};

watch(() => props.selectedCategory, () => {
    currentPage.value = 1;
});

const paginatedQualityFindings = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return props.qualityFindings.slice(start, start + 10);
});
</script>

<template>
    <AppLayout title="Laporan Finding Quality">
        <Head title="Laporan Finding Quality" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Laporan Finding Quality</h1>
                <p class="text-xs text-gray-500 mt-1">
                    Monitoring dan eskalasi temuan audit berdampak tinggi (High-Impact Audit Findings)
                </p>
            </div>

            <Link
                :href="route('auditor.finding-qualities.create')"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-2xs"
            >
                + Buat Laporan Baru
            </Link>
        </div>

        <!-- 4 Pilar KPI Cards (Clean Monochromatic Enterprise) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <button
                v-for="(cat, key) in categories"
                :key="key"
                @click="filterByCategory(key)"
                class="text-left p-4 rounded-lg border transition-all text-xs"
                :class="selectedCategory === key ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-gray-800 border-gray-200 hover:border-gray-300 hover:bg-gray-50/70 shadow-2xs'"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="font-mono text-[11px] font-semibold" :class="selectedCategory === key ? 'text-slate-300' : 'text-slate-500'">
                        {{ cat.code }}
                    </span>
                    <span
                        class="px-2 py-0.5 rounded text-[11px] font-bold font-mono"
                        :class="selectedCategory === key ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-700'"
                    >
                        {{ stats[key] || 0 }}
                    </span>
                </div>
                <div class="font-semibold text-sm leading-snug mb-1" :class="selectedCategory === key ? 'text-white' : 'text-gray-900'">
                    {{ cat.title }}
                </div>
                <div class="text-[11px] line-clamp-2" :class="selectedCategory === key ? 'text-slate-300' : 'text-gray-500'">
                    {{ cat.description }}
                </div>
            </button>
        </div>

        <!-- Active Filter Indicator -->
        <div v-if="selectedCategory" class="mb-4 flex items-center justify-between bg-slate-100 px-4 py-2 rounded text-xs text-slate-800 border border-slate-200">
            <div class="flex items-center gap-2">
                <span>Filter Kategori: <strong>{{ categories[selectedCategory]?.label || selectedCategory }}</strong></span>
            </div>
            <button @click="filterByCategory('')" class="text-slate-600 hover:text-slate-900 font-medium text-xs">
                Reset Filter ✕
            </button>
        </div>

        <!-- Clean Table / List of Quality Findings with 10-Item Pagination -->
        <div v-if="qualityFindings.length === 0" class="bg-white rounded-lg border border-gray-200 p-12 text-center text-xs text-gray-500">
            <p class="font-medium text-gray-700">Belum ada data laporan Finding Quality.</p>
            <p class="text-gray-400 mt-1">Klik tombol "Buat Laporan Baru" di atas untuk menambahkan temuan berkualitas tinggi.</p>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="item in paginatedQualityFindings"
                :key="item.id"
                class="bg-white rounded-lg border border-gray-200 p-4 shadow-2xs hover:border-gray-300 transition-colors"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 mb-3 border-b border-gray-100">
                    <div class="flex items-center flex-wrap gap-2.5 text-xs">
                        <span class="font-semibold text-gray-900 px-2 py-0.5 rounded bg-slate-100 border border-slate-200">
                            {{ categories[item.quality_category]?.label || item.quality_category }}
                        </span>
                        <span class="font-mono text-gray-700 bg-gray-50 px-2 py-0.5 rounded border border-gray-200">
                            {{ item.audit.audit_number }}
                        </span>
                        <span class="text-gray-500">• {{ item.audit.store_name }} ({{ item.audit.store_code }})</span>
                    </div>

                    <Link
                        :href="route('auditor.finding-qualities.show', item.id)"
                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        Lihat Laporan →
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-xs">
                    <div class="md:col-span-2 space-y-2">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">{{ item.title }}</h3>
                        </div>

                        <div class="text-gray-700 bg-gray-50 p-2.5 rounded border border-gray-100 line-clamp-2">
                            {{ item.finding.finding }}
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 text-[11px]">
                            <div>
                                <span class="text-gray-500 font-medium">Root Cause: </span>
                                <span class="text-gray-800">{{ item.root_cause }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 font-medium">Rekomendasi: </span>
                                <span class="text-gray-800">{{ item.recommendation }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t md:border-t-0 md:border-l md:border-gray-100 md:pl-5 space-y-2">
                        <div>
                            <div class="text-[11px] text-gray-500 font-medium">Nilai Dampak / Kerugian</div>
                            <div class="text-sm font-semibold text-gray-900 font-mono mt-0.5">
                                {{ formatRupiah(item.impact_amount || item.finding.loss_amount) }}
                            </div>
                        </div>

                        <div>
                            <div class="text-[11px] text-gray-500 font-medium">Severity Finding</div>
                            <div class="mt-1">
                                <SeverityBadge :severity="item.finding.severity" :show-timeline="true" />
                            </div>
                        </div>

                        <div class="pt-2 border-t border-gray-100 text-[11px] text-gray-400 space-y-0.5">
                            <div>Pelapor: <span class="text-gray-700">{{ item.reported_by }}</span></div>
                            <div>Tanggal: <span class="font-mono text-gray-600">{{ item.created_at }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination Footer -->
            <Pagination
                :current-page="currentPage"
                :per-page="10"
                :total-items="qualityFindings.length"
                @update:current-page="currentPage = $event"
            />
        </div>
    </AppLayout>
</template>
