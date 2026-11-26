<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    findings: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    stores: {
        type: Array,
        default: () => [],
    },
    periods: {
        type: Array,
        default: () => [],
    },
});

// Label review severity yang tampil (nilai di database tetap kode)
const reviewLabels = { APPROVED: 'Disetujui', ADJUSTED: 'Disesuaikan', PENDING_REVIEW: 'Menunggu review', LOCKED: 'Terkunci' };
const reviewLabel = (status) => reviewLabels[status] || 'Terkunci';

const searchQuery = ref('');
const lockFilter = ref('');
const severityFilter = ref('');
const storeFilter = ref('');
const periodFilter = ref('');
const currentPage = ref(1);

// Excel mengikuti pilihan toko dan bulan
const exportUrl = computed(() => {
    const params = {};
    if (storeFilter.value) params.store_id = storeFilter.value;
    if (periodFilter.value) params.period = periodFilter.value;
    return route('coordinator.findings.export', params);
});

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};

const filteredFindings = computed(() => {
    return props.findings.filter((f) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (f.finding && f.finding.toLowerCase().includes(query)) ||
            (f.store && f.store.toLowerCase().includes(query)) ||
            (f.auditor && f.auditor.toLowerCase().includes(query)) ||
            (f.audit_number && f.audit_number.toLowerCase().includes(query));

        const matchesLock =
            lockFilter.value === ''
                ? true
                : lockFilter.value === 'LOCKED'
                ? f.is_severity_locked
                : !f.is_severity_locked;

        const matchesSeverity = !severityFilter.value || f.severity === severityFilter.value;

        const matchesStore = !storeFilter.value || String(f.store_id) === storeFilter.value;
        const matchesPeriod = !periodFilter.value || f.period === periodFilter.value;

        return matchesSearch && matchesLock && matchesSeverity && matchesStore && matchesPeriod;
    });
});

watch([searchQuery, lockFilter, severityFilter, storeFilter, periodFilter], () => {
    currentPage.value = 1;
});

const paginatedFindings = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return filteredFindings.value.slice(start, start + 10);
});

const storeOptions = computed(() => [
    { value: '', label: 'Semua toko' },
    ...props.stores.map((s) => ({ value: String(s.id), label: s.name, hint: s.code })),
]);
const periodOptions = computed(() => [{ value: '', label: 'Semua bulan' }, ...props.periods]);
</script>

<template>
    <AppLayout title="Review severity">
        <Head title="Review severity" />

        <!-- Header: judul + jumlah -->
        <div class="mb-6 flex flex-wrap items-center gap-3">
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Review severity</h1>
                <span class="text-2xl leading-8 text-gray-400 tabular-nums">{{ filteredFindings.length }}</span>
            </div>
            <div class="flex w-full flex-wrap items-center gap-3 sm:ml-auto sm:w-auto">
                <SearchableSelect
                    v-model="storeFilter"
                    :options="storeOptions"
                    size="sm"
                    aria-label="Toko"
                    class="min-w-0 flex-1 sm:w-52 sm:flex-none"
                />
                <SearchableSelect
                    v-model="periodFilter"
                    :options="periodOptions"
                    size="sm"
                    aria-label="Bulan"
                    class="min-w-0 flex-1 sm:w-44 sm:flex-none"
                />
                <a
                    :href="exportUrl"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-emerald-800 px-4 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-emerald-900"
                    title="Unduh rekap temuan sesuai toko dan bulan"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v12m0 0-4-4m4 4 4-4M4 20h16" /></svg>
                    <span>Excel</span>
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <!-- Filter di dalam kartu tabel -->
            <div class="flex flex-col gap-3 border-b border-gray-200 p-3 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-80">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari"
                        aria-label="Cari temuan, toko, auditor, atau no. audit"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-9 pr-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                </div>
                <div class="flex w-full gap-3 sm:ml-auto sm:w-auto">
                    <select
                        v-model="lockFilter"
                        aria-label="Status review"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 sm:w-auto shadow-sm"
                    >
                        <option value="">Semua review</option>
                        <option value="PENDING">Menunggu review</option>
                        <option value="LOCKED">Terkunci</option>
                    </select>
                    <select
                        v-model="severityFilter"
                        aria-label="Severity"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 sm:w-auto shadow-sm"
                    >
                        <option value="">Semua severity</option>
                        <option value="MINOR">Minor</option>
                        <option value="MEDIUM">Medium</option>
                        <option value="MAJOR">Major</option>
                        <option value="CONTINUOUS">Continuous</option>
                    </select>
                </div>
            </div>

            <!-- Mobile -->
            <div class="block divide-y divide-gray-200 md:hidden">
                <div v-if="filteredFindings.length === 0" class="p-8 text-center text-sm text-gray-400">
                    Tidak ada temuan
                </div>
                <Link
                    v-for="f in paginatedFindings"
                    :key="'m-finding-' + f.id"
                    :href="route('coordinator.findings.show', f.id)"
                    class="block space-y-3 p-4 transition-colors duration-150 hover:bg-slate-50/70"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900">{{ f.store }}</div>
                            <div class="mt-0.5 text-xs text-gray-400 tabular-nums">{{ f.audit_number }}</div>
                        </div>
                        <StatusBadge :status="f.status" />
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                            {{ f.category }}
                        </span>
                        <SeverityBadge :severity="f.severity" />
                        <span
                            v-if="f.is_severity_locked"
                            class="ml-auto inline-block rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700"
                        >
                            {{ reviewLabel(f.severity_status) }}
                        </span>
                        <span
                            v-else
                            class="ml-auto inline-block rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800"
                        >
                            Menunggu review
                        </span>
                    </div>

                    <div class="line-clamp-2 text-sm leading-relaxed text-gray-800">
                        {{ f.finding }}
                    </div>

                    <div class="flex items-center justify-between gap-3 text-xs text-gray-600">
                        <span class="font-medium text-gray-800">{{ f.auditor }}</span>
                        <span v-if="f.loss_amount > 0 || f.surplus_amount > 0" class="text-right text-xs font-medium text-slate-700 tabular-nums">
                            <span v-if="f.loss_amount > 0">NBH {{ formatRupiah(f.loss_amount) }}</span>
                            <span v-if="f.loss_amount > 0 && f.surplus_amount > 0" class="text-gray-300"> · </span>
                            <span v-if="f.surplus_amount > 0">NBL {{ formatRupiah(f.surplus_amount) }}</span>
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Desktop -->
            <div v-stable-table="filteredFindings.length" class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-slate-50 text-xs text-slate-700">
                        <tr>
                            <th class="h-10 w-44 whitespace-nowrap px-4 font-medium">Audit</th>
                            <th class="h-10 w-36 whitespace-nowrap px-4 font-medium">Auditor</th>
                            <th class="h-10 min-w-[240px] px-4 font-medium">Temuan</th>
                            <th class="h-10 w-28 whitespace-nowrap px-4 font-medium">Severity</th>
                            <th class="h-10 w-36 whitespace-nowrap px-4 font-medium">Review</th>
                            <th class="h-10 w-28 whitespace-nowrap px-4 font-medium">Status</th>
                            <th class="h-10 w-12 px-4"><span class="sr-only">Buka</span></th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredFindings.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada temuan
                            </td>
                        </tr>
                        <tr
                            v-for="f in paginatedFindings"
                            :key="f.id"
                            class="group cursor-pointer transition-colors duration-150 hover:bg-slate-50/70"
                            @click="router.visit(route('coordinator.findings.show', f.id))"
                        >
                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <div class="font-medium text-slate-800 tabular-nums">{{ f.audit_number }}</div>
                                <div class="mt-0.5 text-sm text-gray-900">{{ f.store }}</div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-top text-gray-700">
                                {{ f.auditor }}
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="mb-1 text-xs font-medium text-slate-500">
                                    {{ f.category }}
                                </div>
                                <div class="line-clamp-3 leading-relaxed text-gray-900">{{ f.finding }}</div>
                                <div v-if="f.loss_amount > 0 || f.surplus_amount > 0" class="mt-1 text-xs font-medium text-slate-600 tabular-nums">
                                    <span v-if="f.loss_amount > 0">NBH {{ formatRupiah(f.loss_amount) }}</span>
                                    <span v-if="f.loss_amount > 0 && f.surplus_amount > 0" class="text-gray-300"> · </span>
                                    <span v-if="f.surplus_amount > 0">NBL {{ formatRupiah(f.surplus_amount) }}</span>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <SeverityBadge :severity="f.severity" />
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <span
                                    v-if="f.is_severity_locked"
                                    class="inline-block rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700"
                                >
                                    {{ reviewLabel(f.severity_status) }}
                                </span>
                                <span
                                    v-else
                                    class="inline-block rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800"
                                >
                                    Menunggu review
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <StatusBadge :status="f.status" />
                            </td>

                            <td class="w-12 whitespace-nowrap px-4 py-3 text-right align-top">
                                <Link
                                    :href="route('coordinator.findings.show', f.id)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors duration-150 group-hover:text-gray-900"
                                    :aria-label="f.is_severity_locked ? 'Detail' : 'Review'"
                                    @click.stop
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination
                :current-page="currentPage"
                :per-page="10"
                :total-items="filteredFindings.length"
                @update:current-page="currentPage = $event"
            />
        </div>
    </AppLayout>
</template>
