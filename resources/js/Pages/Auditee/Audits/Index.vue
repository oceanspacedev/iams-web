<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    audits: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const statusFilter = ref('');

const filteredAudits = computed(() => {
    return props.audits.filter((audit) => {
        const matchesSearch =
            audit.audit_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            audit.store.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            audit.auditor.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesStatus = !statusFilter.value || audit.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});

// Pagination 10 baris per halaman; kembali ke halaman 1 saat filter berubah
const currentPage = ref(1);
const perPage = 10;
watch([searchQuery, statusFilter], () => {
    currentPage.value = 1;
});
watch(() => filteredAudits.value.length, (n) => {
    const last = Math.max(1, Math.ceil(n / perPage));
    if (currentPage.value > last) currentPage.value = last;
});
const paginatedAudits = computed(() => filteredAudits.value.slice((currentPage.value - 1) * perPage, currentPage.value * perPage));
</script>

<template>
    <AppLayout title="Audit toko">
        <Head title="Audit toko" />

        <!-- Header: judul + jumlah -->
        <div class="mb-6 flex items-baseline gap-3">
            <h1 class="text-2xl font-semibold leading-8 text-gray-900">Audit toko</h1>
            <span class="text-2xl leading-8 text-gray-400 tabular-nums">{{ filteredAudits.length }}</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <!-- Filter di dalam kartu tabel -->
            <div class="flex flex-col gap-3 border-b border-gray-200 p-3 text-sm sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-80">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari"
                        aria-label="Cari nomor audit, toko, atau auditor"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-9 pr-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                </div>
                <div class="w-full sm:ml-auto sm:w-auto">
                    <select
                        v-model="statusFilter"
                        aria-label="Status"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 sm:w-auto shadow-sm"
                    >
                        <option value="">Semua status</option>
                        <option value="PLANNED">Terjadwal</option>
                        <option value="IN_PROGRESS">Dalam proses</option>
                        <option value="COMPLETED">Selesai</option>
                        <option value="CLOSED">Ditutup</option>
                    </select>
                </div>
            </div>

            <!-- Mobile -->
            <div class="block divide-y divide-gray-200 md:hidden">
                <div v-if="filteredAudits.length === 0" class="p-8 text-center text-sm text-gray-400">
                    Tidak ada audit
                </div>
                <Link
                    v-for="audit in paginatedAudits"
                    :key="'m-auditee-audit-' + audit.id"
                    :href="route('auditee.audits.show', audit.id)"
                    class="block space-y-3 p-4 transition-colors duration-150 hover:bg-gray-50/70"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-gray-900">{{ audit.store }}</div>
                            <div class="mt-0.5 text-xs text-gray-500 tabular-nums">{{ audit.store_code }}</div>
                        </div>
                        <StatusBadge :status="audit.status" />
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-blue-600 tabular-nums">{{ audit.audit_number }}</span>
                        <span class="text-xs text-gray-500 tabular-nums">{{ audit.findings_count }} temuan</span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span class="font-medium text-gray-800">{{ audit.auditor }}</span>
                        <span class="tabular-nums">{{ audit.audit_date }}</span>
                    </div>
                </Link>
            </div>

            <!-- Desktop -->
            <div v-stable-table="filteredAudits.length" class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs text-gray-600">
                        <tr>
                            <th class="h-10 px-5 font-medium">No. audit</th>
                            <th class="h-10 px-5 font-medium">Toko</th>
                            <th class="h-10 px-5 font-medium">Auditor</th>
                            <th class="h-10 px-5 font-medium">Tanggal</th>
                            <th class="h-10 px-5 text-right font-medium">Temuan</th>
                            <th class="h-10 px-5 font-medium">Status</th>
                            <th class="h-10 w-12 px-5"><span class="sr-only">Buka</span></th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-200">
                        <tr v-if="filteredAudits.length === 0">
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500">
                                Tidak ada audit
                            </td>
                        </tr>
                        <tr
                            v-for="audit in paginatedAudits"
                            :key="audit.id"
                            class="group cursor-pointer transition-colors duration-150 hover:bg-gray-50/70"
                            @click="router.visit(route('auditee.audits.show', audit.id))"
                        >
                            <td class="h-14 whitespace-nowrap px-5">
                                <Link
                                    :href="route('auditee.audits.show', audit.id)"
                                    class="font-medium text-gray-900 tabular-nums"
                                    @click.stop
                                >
                                    {{ audit.audit_number }}
                                </Link>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-gray-900">{{ audit.store }}</div>
                                <div class="mt-0.5 text-xs text-gray-500 tabular-nums">{{ audit.store_code }}</div>
                            </td>
                            <td class="px-5 py-3 text-gray-700">
                                {{ audit.auditor }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-gray-600 tabular-nums">
                                {{ audit.audit_date }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right text-gray-800 tabular-nums">
                                {{ audit.findings_count }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <StatusBadge :status="audit.status" />
                            </td>
                            <td class="w-12 whitespace-nowrap px-5 py-3 text-right text-gray-400 transition-colors duration-150 group-hover:text-gray-900">
                                <svg class="ml-auto h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination
                :current-page="currentPage"
                :per-page="perPage"
                :total-items="filteredAudits.length"
                @update:current-page="currentPage = $event"
            />
        </div>
    </AppLayout>
</template>
