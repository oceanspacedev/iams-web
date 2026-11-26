<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    audits: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const statusFilter = ref('');
const categoryFilter = ref('');
const currentPage = ref(1);

const filteredAudits = computed(() => {
    return props.audits.filter((audit) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (audit.audit_number && audit.audit_number.toLowerCase().includes(query)) ||
            (audit.store && audit.store.toLowerCase().includes(query)) ||
            (audit.auditor && audit.auditor.toLowerCase().includes(query)) ||
            (audit.category && audit.category.toLowerCase().includes(query));

        const matchesStatus = !statusFilter.value || audit.status === statusFilter.value;
        const matchesCategory = !categoryFilter.value || audit.category_id == categoryFilter.value || audit.category === categoryFilter.value;

        return matchesSearch && matchesStatus && matchesCategory;
    });
});

watch([searchQuery, statusFilter, categoryFilter], () => {
    currentPage.value = 1;
});

const paginatedAudits = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return filteredAudits.value.slice(start, start + 10);
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

const deleteAudit = (audit) => {
    openConfirm({
        title: 'Hapus audit',
        message: `Hapus audit ${audit.audit_number} (${audit.store})?`,
        confirmText: 'Hapus',
        type: 'danger',
        action: () => router.delete(route('admin.audits.destroy', audit.id)),
    });
};
</script>

<template>
    <AppLayout title="Jadwal audit">
        <Head title="Jadwal audit" />

        <!-- Header: judul + jumlah, tombol utama di kanan -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Jadwal audit</h1>
                <span class="text-2xl leading-8 text-gray-400 tabular-nums">{{ filteredAudits.length }}</span>
            </div>
            <Link
                :href="route('admin.audits.create')"
                class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 shadow-sm"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Jadwalkan audit
            </Link>
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
                        aria-label="Cari no. audit, nama toko, auditor, kategori"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-9 pr-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                </div>
                <div class="flex w-full gap-3 sm:ml-auto sm:w-auto">
                    <select
                        v-model="categoryFilter"
                        aria-label="Kategori audit"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 sm:w-auto shadow-sm"
                    >
                        <option value="">Semua kategori</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>
                    <select
                        v-model="statusFilter"
                        aria-label="Status audit"
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
                <div
                    v-for="audit in paginatedAudits"
                    :key="'m-audit-' + audit.id"
                >
                    <Link
                        :href="route('admin.audits.show', audit.id)"
                        class="block space-y-3 p-4 pb-2 transition-colors duration-150 hover:bg-slate-50/70"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900">{{ audit.store }}</div>
                                <div class="mt-0.5 text-xs text-gray-400 tabular-nums">{{ audit.store_code }}</div>
                            </div>
                            <StatusBadge :status="audit.status" />
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium text-blue-600 tabular-nums">{{ audit.audit_number }}</span>
                            <span
                                class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium"
                                :class="{
                                    'bg-emerald-50 text-emerald-800 border-emerald-200': audit.category && audit.category.includes('Retail'),
                                    'bg-amber-50 text-amber-800 border-amber-200': audit.category && audit.category.includes('Finance'),
                                    'bg-indigo-50 text-indigo-800 border-indigo-200': audit.category && audit.category.includes('Distribusi'),
                                    'bg-slate-100 text-slate-700 border-slate-200': !audit.category || audit.category === '—'
                                }"
                            >
                                {{ audit.category || '—' }}
                            </span>
                        </div>
                    </Link>

                    <div class="flex items-center justify-between gap-3 px-4 pb-4 text-xs text-gray-500">
                        <span class="min-w-0 truncate">
                            <span class="font-medium text-gray-800">{{ audit.auditor }}</span>
                            · <span class="tabular-nums">{{ audit.audit_date }}</span>
                            · <span class="tabular-nums">{{ audit.findings_count }} temuan</span>
                        </span>
                        <div class="flex shrink-0 items-center gap-2">
                            <Link
                                :href="route('admin.audits.edit', audit.id)"
                                title="Edit"
                                aria-label="Edit"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition-colors duration-150 hover:bg-blue-100"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                            </Link>
                            <button
                                @click="deleteAudit(audit)"
                                title="Hapus"
                                aria-label="Hapus"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-700 transition-colors duration-150 hover:bg-red-100"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop -->
            <div v-stable-table="filteredAudits.length" class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-slate-50 text-xs text-slate-700">
                        <tr>
                            <th class="h-10 w-44 whitespace-nowrap px-4 font-medium">No. surat tugas</th>
                            <th class="h-10 w-44 whitespace-nowrap px-4 font-medium">Kategori</th>
                            <th class="h-10 min-w-[200px] px-4 font-medium">Toko</th>
                            <th class="h-10 w-40 whitespace-nowrap px-4 font-medium">Auditor</th>
                            <th class="h-10 w-32 whitespace-nowrap px-4 font-medium">Tanggal</th>
                            <th class="h-10 w-24 whitespace-nowrap px-4 text-right font-medium">Temuan</th>
                            <th class="h-10 w-28 whitespace-nowrap px-4 font-medium">Status</th>
                            <th class="h-10 w-32 px-4"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredAudits.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada audit
                            </td>
                        </tr>
                        <tr
                            v-for="audit in paginatedAudits"
                            :key="audit.id"
                            class="transition-colors duration-150 hover:bg-slate-50/70"
                        >
                            <td class="h-14 whitespace-nowrap px-4 align-middle">
                                <Link
                                    :href="route('admin.audits.show', audit.id)"
                                    class="font-medium text-slate-800 tabular-nums transition-colors duration-150 hover:text-blue-600"
                                >
                                    {{ audit.audit_number }}
                                </Link>
                                <div v-if="audit.title" class="max-w-[180px] truncate text-xs text-gray-500">{{ audit.title }}</div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-middle">
                                <span
                                    class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-emerald-50 text-emerald-800 border-emerald-200': audit.category && audit.category.includes('Retail'),
                                        'bg-amber-50 text-amber-800 border-amber-200': audit.category && audit.category.includes('Finance'),
                                        'bg-indigo-50 text-indigo-800 border-indigo-200': audit.category && audit.category.includes('Distribusi'),
                                        'bg-slate-100 text-slate-700 border-slate-200': !audit.category || audit.category === '—'
                                    }"
                                >
                                    {{ audit.category || '—' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 align-middle">
                                <div class="font-medium text-gray-900">{{ audit.store }}</div>
                                <div class="text-xs text-gray-500 tabular-nums">{{ audit.store_code }}</div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-middle text-gray-800">
                                {{ audit.auditor }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-middle text-gray-600 tabular-nums">
                                {{ audit.audit_date }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-right align-middle text-gray-800 tabular-nums">
                                {{ audit.findings_count }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-middle">
                                <StatusBadge :status="audit.status" />
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-right align-middle">
                                <div class="inline-flex items-center gap-1">
                                    <Link
                                        :href="route('admin.audits.show', audit.id)"
                                        title="Detail"
                                        aria-label="Detail"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors duration-150 hover:text-slate-900"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                                    </Link>
                                    <Link
                                        :href="route('admin.audits.edit', audit.id)"
                                        title="Edit"
                                        aria-label="Edit"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors duration-150 hover:text-slate-900"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                                    </Link>
                                    <button
                                        @click="deleteAudit(audit)"
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
                :total-items="filteredAudits.length"
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
