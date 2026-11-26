<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

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

const searchQuery = ref('');
const statusFilter = ref('');
const severityFilter = ref('');
const categoryFilter = ref('');
const storeFilter = ref('');
const periodFilter = ref('');
const currentPage = ref(1);

// Excel mengikuti pilihan toko dan bulan
const exportUrl = computed(() => {
    const params = {};
    if (storeFilter.value) params.store_id = storeFilter.value;
    if (periodFilter.value) params.period = periodFilter.value;
    return route('admin.findings.export', params);
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
            (f.audit_number && f.audit_number.toLowerCase().includes(query));

        const matchesStatus = !statusFilter.value || f.status === statusFilter.value;
        const matchesSeverity = !severityFilter.value || f.severity === severityFilter.value;
        const matchesCategory = !categoryFilter.value || f.category === categoryFilter.value;

        const matchesStore = !storeFilter.value || String(f.store_id) === storeFilter.value;
        const matchesPeriod = !periodFilter.value || f.period === periodFilter.value;

        return matchesSearch && matchesStatus && matchesSeverity && matchesCategory && matchesStore && matchesPeriod;
    });
});

// Reset pagination when filter changes
watch([searchQuery, statusFilter, severityFilter, categoryFilter, storeFilter, periodFilter], () => {
    currentPage.value = 1;
});

const paginatedFindings = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return filteredFindings.value.slice(start, start + 10);
});

const confirmModal = ref({
    show: false,
    title: '',
    message: '',
    confirmText: '',
    type: 'primary',
    action: null,
});

const openConfirm = (config) => {
    confirmModal.value = {
        show: true,
        title: config.title || 'Konfirmasi tindakan',
        message: config.message || 'Apakah Anda yakin ingin melanjutkan?',
        confirmText: config.confirmText || 'Ya, lanjutkan',
        type: config.type || 'primary',
        action: config.action,
    };
};

const handleConfirm = () => {
    if (confirmModal.value.action) {
        confirmModal.value.action();
    }
    confirmModal.value.show = false;
};

const deleteFinding = (finding) => {
    openConfirm({
        title: 'Hapus temuan',
        message: `Hapus temuan "${finding.finding.substring(0, 50)}…"?`,
        confirmText: 'Hapus',
        type: 'danger',
        action: () => router.delete(route('admin.findings.destroy', finding.id)),
    });
};

const storeOptions = computed(() => [
    { value: '', label: 'Semua toko' },
    ...props.stores.map((s) => ({ value: String(s.id), label: s.name, hint: s.code })),
]);
const periodOptions = computed(() => [{ value: '', label: 'Semua bulan' }, ...props.periods]);
</script>

<template>
    <AppLayout title="Temuan">
        <Head title="Temuan" />

        <!-- Header: judul + jumlah -->
        <div class="mb-6 flex flex-wrap items-center gap-3">
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Temuan</h1>
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
                        aria-label="Cari temuan, toko, no. audit"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-9 pr-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                </div>
                <div class="grid w-full grid-cols-2 gap-3 sm:ml-auto sm:flex sm:w-auto">
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
                    <select
                        v-model="statusFilter"
                        aria-label="Status"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 sm:w-auto shadow-sm"
                    >
                        <option value="">Semua status</option>
                        <option value="OPEN">Terbuka</option>
                        <option value="IN_PROGRESS">Dalam proses</option>
                        <option value="WAITING_VERIFICATION">Menunggu verifikasi</option>
                        <option value="VERIFIED">Terverifikasi</option>
                        <option value="CLOSED">Ditutup</option>
                    </select>
                    <select
                        v-model="categoryFilter"
                        aria-label="Kategori"
                        class="col-span-2 h-9 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 sm:w-auto shadow-sm"
                    >
                        <option value="">Semua kategori</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Mobile -->
            <div class="block divide-y divide-gray-200 md:hidden">
                <div v-if="filteredFindings.length === 0" class="p-8 text-center text-sm text-gray-400">
                    Tidak ada temuan
                </div>
                <div
                    v-for="f in paginatedFindings"
                    :key="'m-finding-' + f.id"
                >
                    <Link
                        :href="route('admin.findings.show', f.id)"
                        class="block space-y-3 p-4 pb-2 transition-colors duration-150 hover:bg-slate-50/70"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900">{{ f.store }}</div>
                                <div class="mt-0.5 text-xs text-gray-400 tabular-nums">{{ f.audit_number }}</div>
                            </div>
                            <StatusBadge :status="f.status" />
                        </div>

                        <div class="line-clamp-2 text-sm leading-relaxed text-gray-800">
                            {{ f.finding }}
                        </div>

                        <div v-if="f.action_plan" class="space-y-1 rounded-lg border border-gray-100 bg-slate-50 p-3 text-xs">
                            <div class="line-clamp-1 text-gray-700">{{ f.action_plan }}</div>
                            <div class="text-gray-400 tabular-nums">PIC {{ f.pic || '—' }} · {{ f.deadline || '—' }}</div>
                        </div>
                    </Link>

                    <div class="flex items-center gap-3 px-4 pb-4">
                        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                            <span class="rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                {{ f.category }}
                            </span>
                            <SeverityBadge :severity="f.severity" />
                            <span v-if="f.loss_amount > 0 || f.surplus_amount > 0" class="text-xs font-medium tabular-nums">
                                <span v-if="f.loss_amount > 0" class="text-red-700">NBH {{ formatRupiah(f.loss_amount) }}</span>
                                <span v-if="f.loss_amount > 0 && f.surplus_amount > 0" class="text-gray-300"> · </span>
                                <span v-if="f.surplus_amount > 0" class="text-blue-700">NBL {{ formatRupiah(f.surplus_amount) }}</span>
                            </span>
                        </div>
                        <button
                            @click="deleteFinding(f)"
                            title="Hapus"
                            aria-label="Hapus"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-700 transition-colors duration-150 hover:bg-red-100"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop -->
            <div v-stable-table="filteredFindings.length" class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-slate-50 text-xs text-slate-700">
                        <tr>
                            <th class="h-10 w-44 whitespace-nowrap px-4 font-medium">Audit</th>
                            <th class="h-10 min-w-[240px] px-4 font-medium">Temuan</th>
                            <th class="h-10 w-28 whitespace-nowrap px-4 font-medium">Severity</th>
                            <th class="h-10 w-32 whitespace-nowrap px-4 text-right font-medium">NBH</th>
                            <th class="h-10 w-32 whitespace-nowrap px-4 text-right font-medium">NBL</th>
                            <th class="h-10 min-w-[220px] px-4 font-medium">Tindak lanjut</th>
                            <th class="h-10 w-28 whitespace-nowrap px-4 font-medium">Status</th>
                            <th class="h-10 w-24 px-4"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredFindings.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada temuan
                            </td>
                        </tr>
                        <tr
                            v-for="f in paginatedFindings"
                            :key="f.id"
                            class="transition-colors duration-150 hover:bg-slate-50/70"
                        >
                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <Link
                                    :href="route('admin.audits.show', f.audit_id)"
                                    class="font-medium text-slate-800 tabular-nums transition-colors duration-150 hover:text-blue-600"
                                >
                                    {{ f.audit_number }}
                                </Link>
                                <div class="mt-0.5 text-sm text-gray-900">{{ f.store }}</div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="mb-1 text-xs font-medium text-slate-500">
                                    {{ f.category }}
                                </div>
                                <div class="line-clamp-3 leading-relaxed text-gray-900">{{ f.finding }}</div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <SeverityBadge :severity="f.severity" />
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-right align-top text-gray-900 tabular-nums">
                                {{ formatRupiah(f.loss_amount) }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-right align-top text-gray-900 tabular-nums">
                                {{ formatRupiah(f.surplus_amount) }}
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div v-if="f.action_plan" class="space-y-1">
                                    <div class="line-clamp-2 text-gray-900">{{ f.action_plan }}</div>
                                    <div class="text-xs text-gray-500 tabular-nums">PIC {{ f.pic || '—' }} · {{ f.deadline || '—' }}</div>
                                </div>
                                <div v-else class="text-gray-400">—</div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <StatusBadge :status="f.status" />
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-right align-top">
                                <div class="inline-flex items-center gap-1">
                                    <Link
                                        :href="route('admin.findings.show', f.id)"
                                        title="Detail"
                                        aria-label="Detail"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors duration-150 hover:text-slate-900"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                                    </Link>
                                    <button
                                        @click="deleteFinding(f)"
                                        title="Hapus"
                                        aria-label="Hapus"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition-colors duration-150 hover:text-red-800"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                                    </button>
                                </div>
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

        <!-- Konfirmasi -->
        <ConfirmModal
            :show="confirmModal.show"
            :title="confirmModal.title"
            :message="confirmModal.message"
            :confirm-text="confirmModal.confirmText"
            :type="confirmModal.type"
            @confirm="handleConfirm"
            @close="confirmModal.show = false"
        />
    </AppLayout>
</template>
