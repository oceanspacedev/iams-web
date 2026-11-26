<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    by_severity: {
        type: Object,
        required: true,
    },
    by_status: {
        type: Object,
        required: true,
    },
    by_category: {
        type: Array,
        default: () => [],
    },
    store_losses: {
        type: Array,
        default: () => [],
    },
    total_loss: {
        type: Number,
        default: 0,
    },
    total_surplus: {
        type: Number,
        default: 0,
    },
    total_findings: {
        type: Number,
        default: 0,
    },
    completion_rate: {
        type: Number,
        default: 0,
    },
    closed_on_time: {
        type: Number,
        default: 0,
    },
    closed_overdue: {
        type: Number,
        default: 0,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    years: {
        type: Array,
        default: () => [],
    },
    selected_category: {
        type: [String, Number],
        default: 'all',
    },
});

const storeLossList = computed(() => props.store_losses || []);

// Pagination 10 baris per halaman; kembali ke halaman 1 saat filter berubah
const currentPage = ref(1);
const perPage = 10;
watch(() => storeLossList.value.length, (n) => {
    const last = Math.max(1, Math.ceil(n / perPage));
    if (currentPage.value > last) currentPage.value = last;
});
const paginatedStoreLosses = computed(() => storeLossList.value.slice((currentPage.value - 1) * perPage, currentPage.value * perPage));
// Baris kosong di halaman terakhir supaya tinggi tabel tidak berubah saat ganti halaman
const fillerRows = computed(() => (storeLossList.value.length > perPage ? perPage - paginatedStoreLosses.value.length : 0));

const exportDropdownOpen = ref(false);
const selectedYear = ref(props.years[0] ?? new Date().getFullYear());
const selectedCategory = ref(String(props.selected_category || 'all'));

const page = usePage();
const currentPrefix = computed(() => {
    if (typeof route === 'function' && route().current('auditor.*')) return 'auditor.';
    if (typeof route === 'function' && route().current('coordinator.*')) return 'coordinator.';
    return (page.props.auth?.user?.roles || []).includes('auditor') ? 'auditor.' : 'coordinator.';
});

watch(() => props.selected_category, (newVal) => {
    selectedCategory.value = String(newVal || 'all');
});

const handleCategoryChange = () => {
    router.get(
        route(`${currentPrefix.value}reports.index`),
        selectedCategory.value !== 'all' ? { category_id: selectedCategory.value } : {},
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const setCategory = (catId) => {
    const target = String(catId);
    selectedCategory.value = selectedCategory.value === target ? 'all' : target;
    handleCategoryChange();
};

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};

// Robust calculations to ensure counts are always accurate
const totalFindingsCount = computed(() => {
    const fromSev = Object.values(props.by_severity || {}).reduce((a, b) => a + b, 0);
    if (fromSev > 0) return fromSev;
    const fromStatus = Object.values(props.by_status || {}).reduce((a, b) => a + b, 0);
    if (fromStatus > 0) return fromStatus;
    return props.total_findings || 0;
});

const calculatedCompletionRate = computed(() => {
    const total = Object.values(props.by_status || {}).reduce((a, b) => a + b, 0);
    const closed = props.by_status?.CLOSED || 0;
    if (total > 0) {
        return Math.round((closed / total) * 100);
    }
    return props.completion_rate || 0;
});

const highRiskCount = computed(() => {
    return (props.by_severity.MAJOR || 0) + (props.by_severity.CRITICAL || 0);
});

// Color definitions
const severityColors = {
    MAJOR: '#e11d48',
    MEDIUM: '#f59e0b',
    MINOR: '#10b981',
    CONTINUOUS: '#8b5cf6',
    CRITICAL: '#e11d48',
    OBSERVATION: '#10b981',
};

const statusColors = {
    OPEN: '#ef4444',
    IN_PROGRESS: '#f59e0b',
    WAITING_VERIFICATION: '#0284c7',
    VERIFIED: '#6366f1',
    CLOSED: '#10b981',
};

const statusLabels = {
    OPEN: 'Terbuka',
    IN_PROGRESS: 'Dalam proses',
    WAITING_VERIFICATION: 'Menunggu verifikasi',
    VERIFIED: 'Terverifikasi',
    CLOSED: 'Ditutup',
};

// SVG Donut Calculations for Severity
const severitySegments = computed(() => {
    const total = Object.values(props.by_severity).reduce((a, b) => a + b, 0);
    const C = 2 * Math.PI * 38; // ~238.761
    let accumulated = 0;

    return Object.entries(props.by_severity).map(([sev, count]) => {
        const percent = total > 0 ? count / total : 0;
        const length = percent * C;
        const offset = -accumulated;
        accumulated += length;
        return {
            key: sev,
            count,
            percent: Math.round(percent * 100),
            color: severityColors[sev] || '#94a3b8',
            dasharray: `${length} ${C - length}`,
            dashoffset: offset,
        };
    });
});

// SVG Donut Calculations for Status
const statusSegments = computed(() => {
    const total = Object.values(props.by_status).reduce((a, b) => a + b, 0);
    const C = 2 * Math.PI * 38;
    let accumulated = 0;

    return Object.entries(props.by_status).map(([st, count]) => {
        const percent = total > 0 ? count / total : 0;
        const length = percent * C;
        const offset = -accumulated;
        accumulated += length;
        return {
            key: st,
            count,
            percent: Math.round(percent * 100),
            color: statusColors[st] || '#94a3b8',
            dasharray: `${length} ${C - length}`,
            dashoffset: offset,
        };
    });
});

// Max store loss for proportional bar chart
const maxStoreLoss = computed(() => {
    if (!props.store_losses.length) return 1;
    const max = Math.max(...props.store_losses.map(s => s.total_loss));
    return max > 0 ? max : 1;
});

// Top stores by loss
const topLossStores = computed(() => {
    return props.store_losses.filter(s => s.total_loss > 0).slice(0, 5);
});
</script>

<template>
    <AppLayout title="Laporan">
        <Head title="Laporan" />

        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Laporan</h1>
                <span v-if="selectedCategory !== 'all'" class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 py-0.5 pl-2 pr-1 text-xs font-medium text-blue-800">
                    <span>{{ categories.find(c => String(c.id) === selectedCategory)?.name || selectedCategory }}</span>
                    <button type="button" @click="setCategory('all')" class="inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded-md transition-colors duration-150 hover:text-blue-950" title="Reset filter" aria-label="Reset filter">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                            <path d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Filter kategori -->
                <div class="relative flex h-9 items-center rounded-lg border border-gray-300 bg-white transition-colors duration-150 hover:border-gray-400">
                    <svg class="pointer-events-none ml-3 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <select
                        v-model="selectedCategory"
                        @change="handleCategoryChange"
                        aria-label="Kategori"
                        class="h-full cursor-pointer rounded-lg border-0 bg-transparent py-0 pl-2 pr-9 text-sm font-medium text-gray-800 focus:outline-hidden focus:ring-0"
                    >
                        <option value="all">Semua kategori</option>
                        <option
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="String(cat.id)"
                        >
                            {{ cat.name }}
                        </option>
                    </select>
                </div>

                <!-- Download -->
                <div class="relative">
                    <button
                        type="button"
                        @click="exportDropdownOpen = !exportDropdownOpen"
                        class="inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-lg bg-emerald-800 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-emerald-900"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v12m0 0-4-4m4 4 4-4M4 20h16" /></svg>
                        <span>Excel</span>
                        <svg
                            class="h-4 w-4 text-emerald-200 transition-transform duration-200"
                            :class="{ 'rotate-180': exportDropdownOpen }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Overlay untuk menutup dropdown -->
                    <div
                        v-if="exportDropdownOpen"
                        class="fixed inset-0 z-20"
                        @click="exportDropdownOpen = false"
                    ></div>

                    <Transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="transform opacity-0 scale-95 -translate-y-1"
                        enter-to-class="transform opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="transform opacity-100 scale-100 translate-y-0"
                        leave-to-class="transform opacity-0 scale-95 -translate-y-1"
                    >
                        <div
                            v-if="exportDropdownOpen"
                            class="absolute right-0 z-30 mt-2 w-64 origin-top-right divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white py-1 text-sm shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
                        >
                            <div class="px-4 py-3">
                                <label class="flex items-center justify-between gap-3 text-sm text-gray-500">
                                    <span>Tahun</span>
                                    <select
                                        v-model="selectedYear"
                                        class="h-8 rounded-lg border-gray-300 bg-white py-0 pl-3 pr-8 text-sm text-gray-900 shadow-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20"
                                    >
                                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                                    </select>
                                </label>
                                <a
                                    :href="route(`${currentPrefix}reports.export-period`, { year: selectedYear })"
                                    @click="exportDropdownOpen = false"
                                    class="mt-3 flex h-9 items-center justify-center rounded-lg bg-emerald-800 px-3 text-sm font-medium text-white transition-colors duration-150 hover:bg-emerald-900"
                                >
                                    Rekap status {{ selectedYear }}
                                </a>
                            </div>

                            <a
                                :href="route(`${currentPrefix}reports.export-stores`, selectedCategory !== 'all' ? { category_id: selectedCategory } : {})"
                                @click="exportDropdownOpen = false"
                                class="block px-4 py-2 font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900"
                            >
                                Rekap per toko
                            </a>

                            <a
                                :href="route(`${currentPrefix}reports.export-summary`, selectedCategory !== 'all' ? { category_id: selectedCategory } : {})"
                                @click="exportDropdownOpen = false"
                                class="block px-4 py-2 font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900"
                            >
                                Ringkasan eksekutif
                            </a>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- Satu panel angka -->
        <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-gray-200 bg-white sm:grid-cols-3 xl:grid-cols-5 shadow-sm">
            <div class="-mb-px -mr-px min-w-0 border-b border-r border-gray-200 px-5 py-5" title="Nilai barang hilang">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-700"></span>
                    NBH
                </div>
                <div class="mt-3 truncate text-2xl font-medium leading-10 text-gray-900 tabular-nums">{{ formatRupiah(total_loss) }}</div>
            </div>

            <div class="-mb-px -mr-px min-w-0 border-b border-r border-gray-200 px-5 py-5" title="Nilai barang lebih">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-700"></span>
                    NBL
                </div>
                <div class="mt-3 truncate text-2xl font-medium leading-10 text-gray-900 tabular-nums">{{ formatRupiah(total_surplus) }}</div>
            </div>

            <div class="-mb-px -mr-px border-b border-r border-gray-200 px-5 py-5">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-sky-700"></span>
                    Temuan
                </div>
                <div class="mt-3 text-4xl font-medium leading-10 text-gray-900 tabular-nums">{{ totalFindingsCount }}</div>
                <div class="mt-1 text-xs text-gray-500 tabular-nums">{{ by_status.OPEN || 0 }} terbuka</div>
            </div>

            <div class="-mb-px -mr-px border-b border-r border-gray-200 px-5 py-5">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-700"></span>
                    Ditutup
                </div>
                <div class="mt-3 text-4xl font-medium leading-10 text-gray-900 tabular-nums">{{ calculatedCompletionRate }}%</div>
                <div class="mt-1 text-xs text-gray-500 tabular-nums">{{ closed_on_time || 0 }} tepat · {{ closed_overdue || 0 }} telat</div>
            </div>

            <div class="-mb-px -mr-px border-b border-r border-gray-200 px-5 py-5" title="Temuan major">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-700"></span>
                    Risiko tinggi
                </div>
                <div class="mt-3 text-4xl font-medium leading-10 text-gray-900 tabular-nums">{{ highRiskCount }}</div>
            </div>
        </div>

        <!-- Severity & Status -->
        <div class="mt-6 grid grid-cols-1 gap-6 text-sm lg:grid-cols-2">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Severity</h2>
                    <span class="text-xs text-gray-500 tabular-nums">{{ totalFindingsCount }} total</span>
                </div>

                <div class="flex flex-col items-center gap-8 p-5 sm:flex-row">
                    <div class="relative h-32 w-32 shrink-0">
                        <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90 transform">
                            <circle cx="50" cy="50" r="38" fill="none" stroke="currentColor" stroke-width="10" class="text-gray-100" />
                            <circle
                                v-for="seg in severitySegments"
                                :key="seg.key"
                                v-show="seg.count > 0"
                                cx="50"
                                cy="50"
                                r="38"
                                fill="none"
                                :stroke="seg.color"
                                stroke-width="10"
                                :stroke-dasharray="seg.dasharray"
                                :stroke-dashoffset="seg.dashoffset"
                                class="transition-[stroke-dasharray,stroke-dashoffset] duration-500"
                            />
                        </svg>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-medium leading-none text-gray-900 tabular-nums">{{ totalFindingsCount }}</span>
                            <span class="mt-1 text-xs text-gray-400">temuan</span>
                        </div>
                    </div>

                    <ul class="w-full flex-1 divide-y divide-gray-100">
                        <li v-for="seg in severitySegments" :key="seg.key" class="flex items-center justify-between py-2">
                            <span class="flex items-center gap-2 text-gray-700">
                                <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: seg.color }"></span>
                                <span class="capitalize">{{ seg.key.toLowerCase() }}</span>
                            </span>
                            <span class="font-medium text-gray-900 tabular-nums">
                                {{ seg.count }}<span class="ml-2 font-normal text-gray-400">{{ seg.percent }}%</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Status</h2>
                    <span class="text-xs text-gray-500 tabular-nums">{{ calculatedCompletionRate }}% selesai</span>
                </div>

                <div class="flex flex-col items-center gap-8 p-5 sm:flex-row">
                    <div class="relative h-32 w-32 shrink-0">
                        <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90 transform">
                            <circle cx="50" cy="50" r="38" fill="none" stroke="currentColor" stroke-width="10" class="text-gray-100" />
                            <circle
                                v-for="seg in statusSegments"
                                :key="seg.key"
                                v-show="seg.count > 0"
                                cx="50"
                                cy="50"
                                r="38"
                                fill="none"
                                :stroke="seg.color"
                                stroke-width="10"
                                :stroke-dasharray="seg.dasharray"
                                :stroke-dashoffset="seg.dashoffset"
                                class="transition-[stroke-dasharray,stroke-dashoffset] duration-500"
                            />
                        </svg>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-medium leading-none text-gray-900 tabular-nums">{{ calculatedCompletionRate }}%</span>
                            <span class="mt-1 text-xs text-gray-400">ditutup</span>
                        </div>
                    </div>

                    <ul class="w-full flex-1 divide-y divide-gray-100">
                        <li v-for="seg in statusSegments" :key="seg.key" class="flex items-center justify-between py-2">
                            <span class="flex items-center gap-2 text-gray-700">
                                <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: seg.color }"></span>
                                {{ statusLabels[seg.key] || seg.key }}
                            </span>
                            <span class="font-medium text-gray-900 tabular-nums">
                                {{ seg.count }}<span class="ml-2 font-normal text-gray-400">{{ seg.percent }}%</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </section>
        </div>

        <!-- NBH per cabang & kategori audit -->
        <div class="mt-6 grid grid-cols-1 gap-6 text-sm lg:grid-cols-12">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white lg:col-span-7 shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">NBH per cabang</h2>
                    <span class="text-xs text-gray-500">Top 5</span>
                </div>

                <div v-if="topLossStores.length > 0" class="space-y-4 p-5">
                    <div v-for="store in topLossStores" :key="store.store_code">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <span class="truncate font-medium text-gray-900">
                                {{ store.store_name }} <span class="font-normal text-gray-400 tabular-nums">{{ store.store_code }}</span>
                            </span>
                            <span class="shrink-0 font-medium text-gray-900 tabular-nums">{{ formatRupiah(store.total_loss) }}</span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full bg-rose-400 transition-[width] duration-500"
                                :style="{ width: `${Math.max(5, (store.total_loss / maxStoreLoss) * 100)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
                <div v-else class="py-10 text-center text-gray-400">
                    Belum ada data NBH
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white lg:col-span-5 shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Kategori</h2>
                </div>

                <div v-if="by_category.length > 0" class="space-y-2 p-5">
                    <button
                        v-for="cat in by_category"
                        :key="cat.id"
                        type="button"
                        @click="setCategory(cat.id)"
                        class="w-full cursor-pointer select-none rounded-lg border p-3 text-left transition-colors duration-150"
                        :class="selectedCategory === String(cat.id) ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:border-gray-300'"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium text-gray-900">{{ cat.name }}</span>
                            <span class="shrink-0 text-gray-500 tabular-nums"><span class="font-medium text-gray-900">{{ cat.count }}</span> temuan</span>
                        </div>
                        <div class="mt-2 h-1 w-full overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full bg-blue-500 transition-[width] duration-500"
                                :style="{ width: `${totalFindingsCount > 0 ? (cat.count / totalFindingsCount) * 100 : 0}%` }"
                            ></div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-4 text-xs text-gray-500 tabular-nums">
                            <span>NBH <span class="font-medium text-gray-800">{{ formatRupiah(cat.total_loss) }}</span></span>
                            <span>NBL <span class="font-medium text-gray-800">{{ formatRupiah(cat.total_surplus) }}</span></span>
                        </div>
                    </button>
                </div>
                <div v-else class="py-10 text-center text-gray-400">
                    Belum ada data kategori
                </div>
            </section>
        </div>

        <!-- Rekap per cabang -->
        <section class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white text-sm shadow-sm">
            <div class="flex flex-col gap-2 border-b border-gray-200 px-5 py-4 sm:h-14 sm:flex-row sm:items-center sm:justify-between sm:py-0">
                <h2 class="text-base font-semibold text-gray-900">Rekap per cabang</h2>
                <div class="flex flex-wrap items-center gap-x-4 text-xs text-gray-500 tabular-nums">
                    <span>NBH <span class="font-medium text-gray-900">{{ formatRupiah(total_loss) }}</span></span>
                    <span>NBL <span class="font-medium text-gray-900">{{ formatRupiah(total_surplus) }}</span></span>
                </div>
            </div>

            <!-- Mobile -->
            <div class="block divide-y divide-gray-100 md:hidden">
                <div v-if="storeLossList.length === 0" class="p-6 text-center text-gray-400">
                    Belum ada data cabang
                </div>
                <div v-for="s in paginatedStoreLosses" :key="'m-sl-' + currentPage + '-' + s.store_code" class="page-fade p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-medium text-gray-900">{{ s.store_name }}</div>
                            <div class="mt-0.5 text-xs text-gray-400 tabular-nums">{{ s.store_code }} · {{ s.area || '-' }}</div>
                        </div>
                        <div class="shrink-0 text-right tabular-nums">
                            <div class="font-medium text-gray-900">{{ formatRupiah(s.total_loss) }}</div>
                            <div class="text-xs text-gray-500">NBL {{ formatRupiah(s.total_surplus) }}</div>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-gray-500 tabular-nums">{{ s.total_audits }} audit · {{ s.total_findings }} temuan</div>
                </div>
            </div>

            <!-- Desktop -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full table-fixed text-left text-sm">
                    <!-- Lebar kolom dikunci supaya tidak bergeser antar halaman -->
                    <colgroup>
                        <col />
                        <col class="w-40" />
                        <col class="w-24" />
                        <col class="w-24" />
                        <col class="w-44" />
                        <col class="w-40" />
                    </colgroup>
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs text-gray-500">
                        <tr>
                            <th class="h-10 px-5 font-medium">Toko</th>
                            <th class="h-10 px-5 font-medium">Area</th>
                            <th class="h-10 px-5 text-right font-medium">Audit</th>
                            <th class="h-10 px-5 text-right font-medium">Temuan</th>
                            <th class="h-10 px-5 text-right font-medium">NBH</th>
                            <th class="h-10 px-5 text-right font-medium">NBL</th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-100">
                        <tr v-for="s in paginatedStoreLosses" :key="s.store_code" class="transition-colors duration-150 hover:bg-gray-50/70">
                            <td class="h-14 truncate px-5 font-medium text-gray-900" :title="s.store_name + ' · ' + s.store_code">
                                {{ s.store_name }} <span class="font-normal text-gray-400 tabular-nums">{{ s.store_code }}</span>
                            </td>
                            <td class="h-14 truncate px-5 text-gray-600">{{ s.area || '-' }}</td>
                            <td class="h-14 px-5 text-right text-gray-600 tabular-nums">{{ s.total_audits }}</td>
                            <td class="h-14 px-5 text-right font-medium text-gray-900 tabular-nums">{{ s.total_findings }}</td>
                            <td class="h-14 whitespace-nowrap px-5 text-right font-medium text-gray-900 tabular-nums">{{ formatRupiah(s.total_loss) }}</td>
                            <td class="h-14 whitespace-nowrap px-5 text-right font-medium text-gray-900 tabular-nums">{{ formatRupiah(s.total_surplus) }}</td>
                        </tr>
                        <!-- Satu ruang kosong (tanpa garis) setinggi baris yang tidak terisi -->
                        <tr v-if="fillerRows" aria-hidden="true">
                            <td colspan="6" :style="{ height: fillerRows * 56 + 'px' }"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination
                :current-page="currentPage"
                :per-page="perPage"
                :total-items="storeLossList.length"
                @update:current-page="currentPage = $event"
            />
        </section>
    </AppLayout>
</template>
