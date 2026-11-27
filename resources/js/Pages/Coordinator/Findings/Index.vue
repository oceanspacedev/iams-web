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
    audits: {
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

const showCreateFindingModal = ref(false);
const selectedAuditId = ref(props.audits[0]?.id ? String(props.audits[0].id) : '');

const auditOptions = computed(() =>
    props.audits.map((a) => ({
        value: String(a.id),
        label: `${a.audit_number} — ${a.store}`,
        hint: a.category,
    }))
);

const goToCreateFinding = () => {
    if (!selectedAuditId.value) return;
    showCreateFindingModal.value = false;
    router.visit(route('coordinator.findings.create', selectedAuditId.value));
};

const searchQuery = ref('');
const lockFilter = ref('');
const severityFilter = ref('');
const storeFilter = ref('');
const periodFilter = ref('');
const currentPage = ref(1);

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
    { value: '', label: 'Semua Toko' },
    ...props.stores.map((s) => ({ value: String(s.id), label: s.name, hint: s.code })),
]);
const periodOptions = computed(() => [{ value: '', label: 'Semua Bulan' }, ...props.periods]);
</script>

<template>
    <AppLayout title="Review Severity Temuan">
        <Head title="Review Severity — Koordinator" />

        <!-- Header -->
        <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
                <h1 class="text-lg sm:text-xl font-semibold text-gray-900 tracking-tight">Review & Standardisasi Severity Temuan</h1>
                <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Koordinator meninjau usulan severity dari auditor, menyesuaikan level risiko, dan mengunci severity ({{ filteredFindings.length }} Temuan terdaftar)</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a
                    :href="exportUrl"
                    class="inline-flex h-8 items-center justify-center rounded border border-gray-300 bg-white px-3.5 text-xs font-medium text-gray-700 shadow-xs transition-colors hover:bg-gray-50 whitespace-nowrap"
                    title="Unduh file Excel rekap temuan sesuai pilihan toko dan bulan"
                >
                    Download Rekap Temuan (.xlsx)
                </a>
                <button
                    type="button"
                    @click="showCreateFindingModal = true"
                    class="inline-flex h-8 items-center justify-center rounded bg-blue-600 px-3.5 text-xs font-medium text-white shadow-xs transition-colors hover:bg-blue-700 whitespace-nowrap cursor-pointer"
                >
                    + Tambah Finding Baru
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-3 sm:p-3.5 rounded-lg border border-gray-200 shadow-2xs mb-4 sm:mb-5 text-xs">
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2.5">
                <div class="relative flex-1 min-w-0">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari temuan, toko, auditor, atau no. audit..."
                        class="w-full h-8 pl-9 pr-4 py-1.5 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    />
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 shrink-0">
                    <SearchableSelect
                        v-model="storeFilter"
                        :options="storeOptions"
                        size="sm"
                        aria-label="Toko"
                        class="w-full sm:w-44"
                    />
                    <SearchableSelect
                        v-model="periodFilter"
                        :options="periodOptions"
                        size="sm"
                        aria-label="Bulan"
                        class="w-full sm:w-36"
                    />
                    <select
                        v-model="lockFilter"
                        class="w-full sm:w-44 h-8 py-1.5 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white font-medium"
                    >
                        <option value="">Semua Status Review</option>
                        <option value="PENDING">Menunggu Review</option>
                        <option value="LOCKED">Terkunci (Reviewed)</option>
                    </select>
                    <select
                        v-model="severityFilter"
                        class="w-full sm:w-36 h-8 py-1.5 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    >
                        <option value="">Semua Severity</option>
                        <option value="MINOR">Minor</option>
                        <option value="MEDIUM">Medium</option>
                        <option value="MAJOR">Major</option>
                        <option value="CONTINUOUS">Continuous</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Clean Dual View (Mobile Cards vs Desktop Table) -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            
            <!-- 1. MOBILE CARD VIEW (Visible on mobile screens) -->
            <div class="block md:hidden divide-y divide-gray-200">
                <div v-if="filteredFindings.length === 0" class="p-8 text-center text-gray-400 text-xs">
                    Tidak ada temuan yang sesuai filter.
                </div>
                <div
                    v-for="f in paginatedFindings"
                    :key="'m-finding-' + f.id"
                    class="p-4 space-y-2.5"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900 text-xs">{{ f.store }}</div>
                            <div class="tabular-nums text-[10px] text-gray-400 mt-0.5">{{ f.audit_number }}</div>
                        </div>
                        <StatusBadge :status="f.status" />
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-medium text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                            {{ f.category }}
                        </span>
                        <SeverityBadge :severity="f.severity" />
                        <span
                            v-if="f.is_severity_locked"
                            class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200 ml-auto"
                        >
                            {{ f.severity_status || 'LOCKED' }}
                        </span>
                        <span
                            v-else
                            class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-800 border border-amber-200 ml-auto"
                        >
                            Review Pending
                        </span>
                    </div>

                    <div class="text-xs text-gray-800 leading-relaxed line-clamp-2">
                        {{ f.finding }}
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-gray-600 pt-1.5 border-t border-gray-100">
                        <span>Auditor: <strong class="text-gray-800 font-medium">{{ f.auditor }}</strong></span>
                        <span v-if="f.loss_amount > 0 || f.surplus_amount > 0" class="tabular-nums text-[11px] font-semibold text-slate-700 text-right">
                            <span v-if="f.loss_amount > 0">NBH {{ formatRupiah(f.loss_amount) }}</span>
                            <span v-if="f.loss_amount > 0 && f.surplus_amount > 0" class="text-gray-300"> · </span>
                            <span v-if="f.surplus_amount > 0">NBL {{ formatRupiah(f.surplus_amount) }}</span>
                        </span>
                    </div>

                    <div class="pt-2 flex justify-end border-t border-gray-100">
                        <Link
                            :href="route('coordinator.findings.show', f.id)"
                            class="px-3 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-medium text-xs"
                        >
                            {{ f.is_severity_locked ? 'Detail' : 'Review Severity →' }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 2. DESKTOP TABLE VIEW (Visible on tablet & desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap w-44">Audit & Toko</th>
                            <th class="px-4 py-3 whitespace-nowrap w-36">Auditor</th>
                            <th class="px-4 py-3 min-w-[240px]">Uraian Temuan</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Severity</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-36">Status Review</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Status Audit</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredFindings.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada temuan yang sesuai filter.
                            </td>
                        </tr>
                        <tr
                            v-for="f in paginatedFindings"
                            :key="f.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-4 py-3 align-top whitespace-nowrap">
                                <div class="tabular-nums font-semibold text-slate-800">{{ f.audit_number }}</div>
                                <div class="text-xs text-gray-900 font-medium mt-0.5">{{ f.store }}</div>
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-gray-700 font-medium">
                                {{ f.auditor }}
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="text-[11px] font-medium text-slate-500 mb-0.5">
                                    {{ f.category }}
                                </div>
                                <div class="text-gray-900 leading-relaxed font-normal line-clamp-3">{{ f.finding }}</div>
                                <div v-if="f.loss_amount > 0 || f.surplus_amount > 0" class="text-[11px] text-slate-600 tabular-nums mt-1 font-medium">
                                    <span v-if="f.loss_amount > 0">NBH: {{ formatRupiah(f.loss_amount) }}</span>
                                    <span v-if="f.loss_amount > 0 && f.surplus_amount > 0" class="text-gray-300"> · </span>
                                    <span v-if="f.surplus_amount > 0">NBL: {{ formatRupiah(f.surplus_amount) }}</span>
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-center">
                                <SeverityBadge :severity="f.severity" />
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-center">
                                <span
                                    v-if="f.is_severity_locked"
                                    class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200"
                                >
                                    {{ f.severity_status || 'APPROVED' }}
                                </span>
                                <span
                                    v-else
                                    class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200"
                                >
                                    Menunggu Review
                                </span>
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-center">
                                <StatusBadge :status="f.status" />
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-right">
                                <Link
                                    :href="route('coordinator.findings.show', f.id)"
                                    class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs"
                                >
                                    {{ f.is_severity_locked ? 'Detail' : 'Review' }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <Pagination
                :current-page="currentPage"
                :per-page="10"
                :total-items="filteredFindings.length"
                @update:current-page="currentPage = $event"
            />
        </div>

        <!-- Modal Pilih Surat Tugas Audit untuk Input Finding Baru -->
        <div
            v-if="showCreateFindingModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="showCreateFindingModal = false"
        >
            <div class="w-full max-w-md rounded-lg bg-white border border-gray-200 shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between bg-slate-50">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Tambah Finding Baru</h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Pilih Nomor Surat Tugas Audit yang akan ditambahkan temuannya</p>
                    </div>
                    <button
                        type="button"
                        @click="showCreateFindingModal = false"
                        class="text-gray-400 hover:text-gray-600 text-sm font-bold px-1.5"
                    >
                        ✕
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            Surat Tugas Audit / Toko <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect
                            v-model="selectedAuditId"
                            :options="auditOptions"
                            placeholder="Cari Nomor Audit atau Nama Toko..."
                            class="w-full"
                        />
                    </div>
                </div>
                <div class="px-5 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="showCreateFindingModal = false"
                        class="px-3.5 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="!selectedAuditId"
                        @click="goToCreateFinding"
                        class="px-3.5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 transition-colors cursor-pointer"
                    >
                        Lanjut Input Finding
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
