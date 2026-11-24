<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import { qualityCategoryColor } from '@/Utils/qualityCategoryColors';

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
    routePrefix: {
        type: String,
        default: '',
    },
});

const page = usePage();
const currentPrefix = computed(() => {
    if (props.routePrefix) return props.routePrefix;
    if (typeof route === 'function' && route().current('coordinator.*')) return 'coordinator.';
    if (typeof route === 'function' && route().current('auditor.*')) return 'auditor.';
    return (page.props.auth?.user?.roles || []).includes('auditor') ? 'auditor.' : 'coordinator.';
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
        route(`${currentPrefix.value}finding-qualities.index`),
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
const categoryColor = qualityCategoryColor;
</script>

<template>
    <AppLayout title="Kualitas temuan">
        <Head title="Kualitas temuan" />

        <!-- Header: judul + jumlah, aksi utama di kanan -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Kualitas temuan</h1>
                <span class="text-2xl leading-8 text-gray-400 tabular-nums">{{ qualityFindings.length }}</span>
            </div>

            <Link
                :href="route(`${currentPrefix}finding-qualities.create`)"
                class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 shadow-sm"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 5v14M5 12h14" />
                </svg>
                Buat laporan
            </Link>
        </div>

        <!-- Satu panel kategori (klik untuk filter) -->
        <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-gray-200 bg-white lg:grid-cols-4 shadow-sm">
            <button
                v-for="(cat, key) in categories"
                :key="key"
                type="button"
                @click="filterByCategory(key)"
                :title="cat.description"
                class="-mb-px -mr-px flex cursor-pointer select-none flex-col border-b border-r border-gray-200 px-5 py-5 text-left text-sm ring-inset transition-colors duration-150"
                :class="selectedCategory === key ? categoryColor(key).selected : categoryColor(key).card"
            >
                <div class="flex min-w-0 items-center gap-2 font-medium" :class="categoryColor(key).label">
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="categoryColor(key).dot"></span>
                    <span class="truncate" :title="cat.label || cat.title">{{ cat.label || cat.title }}</span>
                </div>
                <div class="mt-3 text-4xl font-medium leading-10 text-gray-900 tabular-nums">{{ stats[key] || 0 }}</div>
            </button>
        </div>

        <!-- Daftar laporan -->
        <section class="mt-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                <h2 class="text-base font-semibold text-gray-900">Laporan</h2>
                <button
                    v-if="selectedCategory"
                    type="button"
                    @click="filterByCategory('')"
                    aria-label="Reset filter"
                    title="Reset filter"
                    class="inline-flex h-8 items-center gap-2 rounded-lg border border-slate-200 bg-slate-100 px-3 text-sm font-medium text-slate-800 transition-colors duration-150 hover:text-slate-900"
                >
                    {{ categories[selectedCategory]?.label || selectedCategory }}
                    <svg class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div v-if="qualityFindings.length === 0" class="p-12 text-center text-sm font-medium text-gray-700">
                Belum ada laporan
            </div>

            <template v-else>
                <div>
                    <Link
                        v-for="item in paginatedQualityFindings"
                        :key="item.id"
                        :href="route(`${currentPrefix}finding-qualities.show`, item.id)"
                        class="group block border-b border-gray-100 px-5 py-4 transition-colors duration-150 last:border-b-0 hover:bg-gray-50"
                    >
                        <div class="flex items-start gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <template v-if="item.quality_categories && item.quality_categories.length">
                                        <span
                                            v-for="catKey in item.quality_categories"
                                            :key="catKey"
                                            class="inline-flex items-center gap-1 rounded-md border border-gray-200 px-2 py-0.5 font-medium text-gray-800"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="categoryColor(catKey).dot"></span>
                                            {{ categories[catKey]?.label || catKey }}
                                        </span>
                                    </template>
                                    <span v-else class="inline-flex items-center gap-1 rounded-md border border-gray-200 px-2 py-0.5 font-medium text-gray-800">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="categoryColor(item.quality_category).dot"></span>
                                        {{ categories[item.quality_category]?.label || item.quality_category }}
                                    </span>
                                    <span class="text-gray-500 tabular-nums">{{ item.audit.audit_number }} · {{ item.audit.store_name }} ({{ item.audit.store_code }})</span>
                                </div>

                                <h3 class="mt-2 text-base font-semibold text-gray-900">{{ item.title }}</h3>
                                <p class="mt-1 line-clamp-1 text-sm text-gray-700">{{ item.finding.finding }}</p>

                                <div class="mt-3 grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                                    <div class="line-clamp-2">
                                        <span class="text-gray-500">Root cause </span>
                                        <span class="text-gray-800">{{ item.root_cause }}</span>
                                    </div>
                                    <div class="line-clamp-2">
                                        <span class="text-gray-500">Rekomendasi </span>
                                        <span class="text-gray-800">{{ item.recommendation }}</span>
                                    </div>
                                </div>

                                <!-- Angka & severity (mobile) -->
                                <div class="mt-3 flex flex-wrap items-center gap-3 sm:hidden">
                                    <span class="text-base font-medium text-gray-900 tabular-nums">{{ formatRupiah(item.impact_amount || item.finding.loss_amount) }}</span>
                                    <SeverityBadge :severity="item.finding.severity" :show-timeline="true" />
                                </div>
                                <div class="mt-2 text-xs text-gray-500 tabular-nums sm:hidden">
                                    {{ item.reported_by }} · {{ item.created_at }}
                                </div>
                            </div>

                            <!-- Angka & severity (desktop) -->
                            <div class="hidden w-48 shrink-0 text-right sm:block">
                                <div class="text-base font-medium text-gray-900 tabular-nums" title="Nilai dampak / kerugian">
                                    {{ formatRupiah(item.impact_amount || item.finding.loss_amount) }}
                                </div>
                                <div class="mt-2 flex justify-end">
                                    <SeverityBadge :severity="item.finding.severity" :show-timeline="true" />
                                </div>
                                <div class="mt-2 text-xs text-gray-500">{{ item.reported_by }}</div>
                                <div class="text-xs text-gray-500 tabular-nums">{{ item.created_at }}</div>
                            </div>

                            <svg class="mt-1 hidden h-4 w-4 shrink-0 text-gray-400 transition-colors duration-150 group-hover:text-gray-900 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                        </div>
                    </Link>
                </div>

                <!-- Pagination Footer -->
                <Pagination
                    :current-page="currentPage"
                    :per-page="10"
                    :total-items="qualityFindings.length"
                    @update:current-page="currentPage = $event"
                />
            </template>
        </section>
    </AppLayout>
</template>
