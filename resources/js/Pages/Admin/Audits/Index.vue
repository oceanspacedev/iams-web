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
        title: config.title || 'Konfirmasi Tindakan',
        message: config.message || 'Apakah Anda yakin ingin melanjutkan?',
        confirmText: config.confirmText || 'Ya, Lanjutkan',
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
        title: 'Hapus Surat Tugas Audit',
        message: `Apakah Anda yakin ingin menghapus jadwal audit ${audit.audit_number} (${audit.store})?`,
        confirmText: 'Ya, Hapus Audit',
        type: 'danger',
        action: () => router.delete(route('admin.audits.destroy', audit.id)),
    });
};
</script>

<template>
    <AppLayout title="Jadwal & Penugasan Audit">
        <Head title="Manajemen Audits — Admin" />

        <!-- Header -->
        <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
                <h1 class="text-lg sm:text-xl font-semibold text-gray-900 tracking-tight">Jadwal & Penugasan Audit</h1>
                <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Kelola dan jadwalkan pemeriksaan audit untuk seluruh cabang dan badan usaha</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 font-mono hidden sm:inline">
                    {{ filteredAudits.length }} Audit terdaftar
                </span>
                <Link
                    :href="route('admin.audits.create')"
                    class="inline-flex items-center justify-center w-full sm:w-auto gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-2xs cursor-pointer"
                >
                    + Jadwalkan Audit Baru
                </Link>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-3 sm:p-3.5 rounded-lg border border-gray-200 shadow-2xs mb-4 sm:mb-5 text-xs">
            <div class="flex flex-col sm:flex-row items-center gap-2.5 sm:gap-3">
                <div class="relative flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari no. audit, nama toko, auditor, kategori..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    />
                </div>

                <div class="w-full sm:w-52 shrink-0">
                    <select
                        v-model="categoryFilter"
                        class="w-full py-2 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white font-medium"
                    >
                        <option value="">Semua Kategori Audit</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>
                </div>

                <div class="w-full sm:w-48 shrink-0">
                    <select
                        v-model="statusFilter"
                        class="w-full py-2 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    >
                        <option value="">Semua Status Audit</option>
                        <option value="PLANNED">Terjadwal (Planned)</option>
                        <option value="IN_PROGRESS">Berlangsung (In Progress)</option>
                        <option value="COMPLETED">Selesai (Completed)</option>
                        <option value="CLOSED">Ditutup (Closed)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Clean Dual View (Mobile Cards vs Desktop Table) -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            
            <!-- 1. MOBILE CARD VIEW (Visible on mobile screens) -->
            <div class="block md:hidden divide-y divide-gray-200">
                <div v-if="filteredAudits.length === 0" class="p-8 text-center text-gray-400 text-xs">
                    Tidak ada data audit yang sesuai filter.
                </div>
                <div
                    v-for="audit in paginatedAudits"
                    :key="'m-audit-' + audit.id"
                    class="p-4 space-y-2.5"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900 text-xs">{{ audit.store }}</div>
                            <div class="text-[10px] text-gray-400 font-mono mt-0.5">{{ audit.store_code }}</div>
                        </div>
                        <StatusBadge :status="audit.status" />
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <Link
                            :href="route('admin.audits.show', audit.id)"
                            class="font-mono text-xs font-semibold text-blue-600 hover:underline"
                        >
                            {{ audit.audit_number }}
                        </Link>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold border"
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

                    <div class="flex items-center justify-between text-[11px] text-gray-600 pt-1.5 border-t border-gray-100">
                        <span>Lead: <strong class="text-gray-800 font-medium">{{ audit.auditor }}</strong></span>
                        <span class="font-mono text-gray-500 text-[10px]">{{ audit.audit_date }}</span>
                    </div>

                    <div class="pt-2 flex items-center justify-between border-t border-gray-100">
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                            {{ audit.findings_count }} temuan
                        </span>
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('admin.audits.show', audit.id)"
                                class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-[11px]"
                            >
                                Detail
                            </Link>
                            <Link
                                :href="route('admin.audits.edit', audit.id)"
                                class="px-2.5 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-medium text-[11px]"
                            >
                                Edit
                            </Link>
                            <button
                                @click="deleteAudit(audit)"
                                class="px-2.5 py-1 rounded bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-medium text-[11px]"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. DESKTOP TABLE VIEW (Visible on tablet & desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap w-44">No. Surat Tugas</th>
                            <th class="px-4 py-3 whitespace-nowrap w-44">Kategori Audit</th>
                            <th class="px-4 py-3 min-w-[200px]">Toko & Lokasi</th>
                            <th class="px-4 py-3 whitespace-nowrap w-40">Auditor Lapangan</th>
                            <th class="px-4 py-3 whitespace-nowrap w-32">Tanggal</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-24">Temuan</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Status</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredAudits.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada data audit yang sesuai filter.
                            </td>
                        </tr>
                        <tr
                            v-for="audit in paginatedAudits"
                            :key="audit.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-4 py-3 align-middle whitespace-nowrap">
                                <Link
                                    :href="route('admin.audits.show', audit.id)"
                                    class="font-mono font-semibold text-slate-800 hover:text-blue-600 transition-colors"
                                >
                                    {{ audit.audit_number }}
                                </Link>
                                <div v-if="audit.title" class="text-[11px] text-gray-500 truncate max-w-[180px]">{{ audit.title }}</div>
                            </td>

                            <td class="px-4 py-3 align-middle whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold tracking-wide border"
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
                                <div class="text-[11px] text-gray-500 font-mono">{{ audit.store_code }}</div>
                            </td>

                            <td class="px-4 py-3 align-middle whitespace-nowrap font-medium text-gray-800">
                                {{ audit.auditor }}
                            </td>

                            <td class="px-4 py-3 align-middle whitespace-nowrap text-gray-600 font-mono text-[11px]">
                                {{ audit.audit_date }}
                            </td>

                            <td class="px-4 py-3 align-middle whitespace-nowrap text-center">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ audit.findings_count }} temuan
                                </span>
                            </td>

                            <td class="px-4 py-3 align-middle whitespace-nowrap text-center">
                                <StatusBadge :status="audit.status" />
                            </td>

                            <td class="px-4 py-3 align-middle whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <Link
                                        :href="route('admin.audits.show', audit.id)"
                                        class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs"
                                    >
                                        Detail
                                    </Link>
                                    <span class="text-slate-300">|</span>
                                    <Link
                                        :href="route('admin.audits.edit', audit.id)"
                                        class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs"
                                    >
                                        Edit
                                    </Link>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        @click="deleteAudit(audit)"
                                        class="text-red-600 hover:text-red-800 font-medium hover:underline text-xs"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <Pagination
                :current-page="currentPage"
                :per-page="10"
                :total-items="filteredAudits.length"
                @update:current-page="currentPage = $event"
            />
        </div>

        <!-- Modern Action Confirmation Modal -->
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
