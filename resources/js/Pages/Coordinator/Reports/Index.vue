<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
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
    selected_category: {
        type: [String, Number],
        default: 'all',
    },
});

const exportDropdownOpen = ref(false);
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
    OPEN: 'Open',
    IN_PROGRESS: 'In Progress',
    WAITING_VERIFICATION: 'Waiting Verification',
    VERIFIED: 'Verified',
    CLOSED: 'Closed',
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
    <AppLayout title="Laporan & Rekapitulasi Audit">
        <Head title="Laporan & Rekapitulasi Audit" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Laporan & Rekapitulasi Audit Retail</h1>
                <p class="text-xs text-gray-500 mt-1">Ringkasan temuan, NBH/NBL, dan progres tindak lanjut audit.</p>
                <div v-if="selectedCategory !== 'all'" class="mt-2 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-800 border border-blue-200">
                        <span>Filter aktif: <strong>{{ categories.find(c => String(c.id) === selectedCategory)?.name || selectedCategory }}</strong></span>
                        <button type="button" @click="setCategory('all')" class="hover:text-blue-950 font-bold ml-1 cursor-pointer" title="Reset filter">✕</button>
                    </span>
                </div>
            </div>

            <!-- Action Controls: Category Filter Box & Download Button -->
            <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
                <!-- Kotak Navigasi Filter Kategori -->
                <div class="relative flex items-center bg-white border border-gray-300 rounded-md shadow-2xs hover:border-gray-400 transition-colors">
                    <div class="flex items-center gap-1.5 pl-3 pr-1 py-2 text-xs text-gray-500 pointer-events-none select-none">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span class="font-medium text-gray-600 hidden sm:inline">Kategori:</span>
                    </div>
                    <select
                        v-model="selectedCategory"
                        @change="handleCategoryChange"
                        class="text-xs font-semibold text-gray-800 bg-transparent py-2 pl-1 pr-8 border-0 focus:ring-0 focus:outline-hidden cursor-pointer"
                    >
                        <option value="all">Semua Kategori (All)</option>
                        <option
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="String(cat.id)"
                        >
                            {{ cat.name }}
                        </option>
                    </select>
                </div>

                <!-- Single Trigger Button & Smooth Dropdown Menu directly underneath -->
                <div class="relative">
                    <button
                        type="button"
                        @click="exportDropdownOpen = !exportDropdownOpen"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-md bg-emerald-800 hover:bg-emerald-900 text-white shadow-xs transition-colors cursor-pointer"
                    >
                        <span>Download Laporan Excel</span>
                        <svg
                            class="w-3.5 h-3.5 text-emerald-200 transition-transform duration-200"
                            :class="{ 'rotate-180': exportDropdownOpen }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Invisible overlay to close dropdown when clicking outside -->
                    <div
                        v-if="exportDropdownOpen"
                        class="fixed inset-0 z-20"
                        @click="exportDropdownOpen = false"
                    ></div>

                    <!-- Smooth Dropdown menu directly below the button -->
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
                            class="absolute right-0 mt-1.5 w-56 rounded-lg border border-gray-200 bg-white shadow-lg z-30 py-1 text-xs origin-top-right divide-y divide-gray-100"
                        >
                            <a
                                :href="route(`${currentPrefix}reports.export-findings`, selectedCategory !== 'all' ? { category_id: selectedCategory } : {})"
                                @click="exportDropdownOpen = false"
                                class="block px-4 py-2.5 text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors font-medium"
                            >
                                Rekap Seluruh Temuan (.xls)
                            </a>

                            <a
                                :href="route(`${currentPrefix}reports.export-stores`, selectedCategory !== 'all' ? { category_id: selectedCategory } : {})"
                                @click="exportDropdownOpen = false"
                                class="block px-4 py-2.5 text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors font-medium"
                            >
                                Rekapitulasi per Toko (.xls)
                            </a>

                            <a
                                :href="route(`${currentPrefix}reports.export-summary`, selectedCategory !== 'all' ? { category_id: selectedCategory } : {})"
                                @click="exportDropdownOpen = false"
                                class="block px-4 py-2.5 text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors font-medium"
                            >
                                Ringkasan Eksekutif (.xls)
                            </a>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- Key Metrics: flat tinted cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3 sm:gap-4 mb-6">
            <div class="rounded-lg border border-rose-100 bg-rose-50/70 p-4">
                <div class="text-xs font-medium text-rose-700">Total NBH</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 tabular-nums leading-tight">{{ formatRupiah(total_loss) }}</div>
                <div class="mt-1 text-[11px] text-rose-700/70">Nilai Barang Hilang</div>
            </div>

            <div class="rounded-lg border border-indigo-100 bg-indigo-50/70 p-4">
                <div class="text-xs font-medium text-indigo-700">Total NBL</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 tabular-nums leading-tight">{{ formatRupiah(total_surplus) }}</div>
                <div class="mt-1 text-[11px] text-indigo-700/70">Nilai Barang Lebih</div>
            </div>

            <div class="rounded-lg border border-sky-100 bg-sky-50/70 p-4">
                <div class="text-xs font-medium text-sky-700">Total Temuan</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 tabular-nums leading-tight">{{ totalFindingsCount }}</div>
                <div class="mt-1 text-[11px] text-sky-700/70 tabular-nums">{{ by_status.OPEN || 0 }} masih open</div>
            </div>

            <div class="rounded-lg border border-emerald-100 bg-emerald-50/70 p-4">
                <div class="text-xs font-medium text-emerald-700">Tingkat Closed</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 tabular-nums leading-tight">{{ calculatedCompletionRate }}%</div>
                <div class="mt-1 text-[11px] text-emerald-700/70 tabular-nums">{{ closed_on_time || 0 }} on time · {{ closed_overdue || 0 }} overdue</div>
            </div>

            <div class="rounded-lg border border-amber-100 bg-amber-50/70 p-4">
                <div class="text-xs font-medium text-amber-700">Risiko Tinggi</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 tabular-nums leading-tight">{{ highRiskCount }}</div>
                <div class="mt-1 text-[11px] text-amber-700/70">Temuan major</div>
            </div>
        </div>

        <!-- Severity & Status -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 text-xs">
            <section class="bg-white rounded-lg border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold text-gray-900">Severity Temuan</h2>
                    <span class="text-[11px] text-gray-500 tabular-nums">{{ totalFindingsCount }} total</span>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-8">
                    <div class="relative w-32 h-32 shrink-0">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90 transform">
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
                                class="transition-all duration-500"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-xl font-semibold text-gray-900 tabular-nums leading-none">{{ totalFindingsCount }}</span>
                            <span class="text-[10px] text-gray-400 mt-1">temuan</span>
                        </div>
                    </div>

                    <ul class="flex-1 w-full divide-y divide-gray-100">
                        <li v-for="seg in severitySegments" :key="seg.key" class="flex items-center justify-between py-2">
                            <span class="flex items-center gap-2 text-gray-700">
                                <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: seg.color }"></span>
                                <span class="capitalize">{{ seg.key.toLowerCase() }}</span>
                            </span>
                            <span class="tabular-nums font-medium text-gray-900">
                                {{ seg.count }}<span class="ml-2 font-normal text-gray-400">{{ seg.percent }}%</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="bg-white rounded-lg border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold text-gray-900">Status Penyelesaian</h2>
                    <span class="text-[11px] text-gray-500 tabular-nums">{{ calculatedCompletionRate }}% selesai</span>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-8">
                    <div class="relative w-32 h-32 shrink-0">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90 transform">
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
                                class="transition-all duration-500"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-xl font-semibold text-gray-900 tabular-nums leading-none">{{ calculatedCompletionRate }}%</span>
                            <span class="text-[10px] text-gray-400 mt-1">closed</span>
                        </div>
                    </div>

                    <ul class="flex-1 w-full divide-y divide-gray-100">
                        <li v-for="seg in statusSegments" :key="seg.key" class="flex items-center justify-between py-2">
                            <span class="flex items-center gap-2 text-gray-700">
                                <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: seg.color }"></span>
                                {{ statusLabels[seg.key] || seg.key }}
                            </span>
                            <span class="tabular-nums font-medium text-gray-900">
                                {{ seg.count }}<span class="ml-2 font-normal text-gray-400">{{ seg.percent }}%</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </section>
        </div>

        <!-- NBH per Cabang & Kategori Audit -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 mb-6 text-xs">
            <section class="lg:col-span-7 bg-white rounded-lg border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold text-gray-900">NBH per Cabang</h2>
                    <span class="text-[11px] text-gray-500">Top 5</span>
                </div>

                <div v-if="topLossStores.length > 0" class="space-y-4">
                    <div v-for="store in topLossStores" :key="store.store_code">
                        <div class="flex items-center justify-between gap-3 mb-1.5">
                            <span class="font-medium text-gray-900 truncate">
                                {{ store.store_name }} <span class="font-normal text-gray-400 tabular-nums">{{ store.store_code }}</span>
                            </span>
                            <span class="tabular-nums font-semibold text-gray-900 shrink-0">{{ formatRupiah(store.total_loss) }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div
                                class="h-full rounded-full bg-rose-400 transition-all duration-500"
                                :style="{ width: `${Math.max(5, (store.total_loss / maxStoreLoss) * 100)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
                <div v-else class="py-8 text-center text-gray-400">
                    Belum ada data NBH.
                </div>
            </section>

            <section class="lg:col-span-5 bg-white rounded-lg border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold text-gray-900">Kategori Audit</h2>
                </div>

                <div v-if="by_category.length > 0" class="space-y-2.5">
                    <button
                        v-for="cat in by_category"
                        :key="cat.id"
                        type="button"
                        @click="setCategory(cat.id)"
                        class="w-full text-left p-3 rounded-lg border transition-colors cursor-pointer select-none"
                        :class="selectedCategory === String(cat.id) ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:border-gray-300'"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium text-gray-900">{{ cat.name }}</span>
                            <span class="tabular-nums text-gray-500 shrink-0"><span class="font-semibold text-gray-900">{{ cat.count }}</span> temuan</span>
                        </div>
                        <div class="mt-2 w-full bg-gray-100 rounded-full h-1 overflow-hidden">
                            <div
                                class="h-full rounded-full bg-blue-500 transition-all duration-500"
                                :style="{ width: `${totalFindingsCount > 0 ? (cat.count / totalFindingsCount) * 100 : 0}%` }"
                            ></div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-4 text-[11px] text-gray-500 tabular-nums">
                            <span>NBH <span class="font-medium text-gray-800">{{ formatRupiah(cat.total_loss) }}</span></span>
                            <span>NBL <span class="font-medium text-gray-800">{{ formatRupiah(cat.total_surplus) }}</span></span>
                        </div>
                    </button>
                </div>
                <div v-else class="py-8 text-center text-gray-400">
                    Belum ada data kategori.
                </div>
            </section>
        </div>

        <!-- Rekap per Cabang -->
        <section class="bg-white rounded-lg border border-gray-200 overflow-hidden text-xs">
            <div class="px-5 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="text-sm font-semibold text-gray-900">Rekap per Cabang</h2>
                <div class="flex flex-wrap items-center gap-x-4 text-[11px] text-gray-500 tabular-nums">
                    <span>NBH <span class="font-semibold text-gray-900">{{ formatRupiah(total_loss) }}</span></span>
                    <span>NBL <span class="font-semibold text-gray-900">{{ formatRupiah(total_surplus) }}</span></span>
                </div>
            </div>

            <!-- Mobile list -->
            <div class="block md:hidden divide-y divide-gray-100">
                <div v-if="store_losses.length === 0" class="p-5 text-center text-gray-400">
                    Belum ada data cabang.
                </div>
                <div v-for="s in store_losses" :key="'m-sl-' + s.store_code" class="px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-medium text-gray-900">{{ s.store_name }}</div>
                            <div class="text-[11px] text-gray-400 tabular-nums mt-0.5">{{ s.store_code }} · {{ s.area || '-' }}</div>
                        </div>
                        <div class="text-right tabular-nums shrink-0">
                            <div class="font-semibold text-gray-900">{{ formatRupiah(s.total_loss) }}</div>
                            <div class="text-[11px] text-gray-500">NBL {{ formatRupiah(s.total_surplus) }}</div>
                        </div>
                    </div>
                    <div class="mt-1.5 text-[11px] text-gray-500 tabular-nums">{{ s.total_audits }} audit · {{ s.total_findings }} temuan</div>
                </div>
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3 font-medium">Toko</th>
                            <th class="px-5 py-3 font-medium">Area</th>
                            <th class="px-5 py-3 font-medium text-right">Audit</th>
                            <th class="px-5 py-3 font-medium text-right">Temuan</th>
                            <th class="px-5 py-3 font-medium text-right">NBH</th>
                            <th class="px-5 py-3 font-medium text-right">NBL</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="s in store_losses" :key="s.store_code" class="hover:bg-gray-50/70">
                            <td class="px-5 py-3 font-medium text-gray-900">
                                {{ s.store_name }} <span class="font-normal text-gray-400 tabular-nums">{{ s.store_code }}</span>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ s.area || '-' }}</td>
                            <td class="px-5 py-3 text-right tabular-nums text-gray-600">{{ s.total_audits }}</td>
                            <td class="px-5 py-3 text-right tabular-nums font-medium text-gray-900">{{ s.total_findings }}</td>
                            <td class="px-5 py-3 text-right tabular-nums font-semibold text-gray-900">{{ formatRupiah(s.total_loss) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums font-semibold text-gray-900">{{ formatRupiah(s.total_surplus) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppLayout>
</template>
