<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

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
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (audit.audit_number && audit.audit_number.toLowerCase().includes(query)) ||
            (audit.store && audit.store.toLowerCase().includes(query)) ||
            (audit.auditor && audit.auditor.toLowerCase().includes(query));

        const matchesStatus = !statusFilter.value || audit.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
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
        title: 'Hapus Penugasan Audit',
        message: `Apakah Anda yakin ingin menghapus audit ${audit.audit_number}? Seluruh data temuan dan riwayat terkait akan terhapus.`,
        confirmText: 'Ya, Hapus Audit',
        type: 'danger',
        action: () => router.delete(route('admin.audits.destroy', audit.id)),
    });
};
</script>

<template>
    <AppLayout title="Semua Pelaksanaan Audit">
        <Head title="Manajemen Audit — Admin" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Manajemen Pelaksanaan Audit</h1>
                <p class="text-xs text-gray-500 mt-1">Daftar penugasan audit toko, penetapan auditor, dan status pelaksanaan</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                    Total: {{ filteredAudits.length }} Audit
                </span>
                <Link
                    :href="route('admin.audits.create')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
                >
                    + Jadwalkan Audit Baru
                </Link>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-xs mb-6">
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nomor audit, toko, atau auditor..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-gray-50/50 focus:bg-white transition-colors"
                    />
                </div>

                <div class="w-full sm:w-56 shrink-0">
                    <select
                        v-model="statusFilter"
                        class="w-full py-2 px-3 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white"
                    >
                        <option value="">Semua Status</option>
                        <option value="PLANNED">Planned</option>
                        <option value="IN_PROGRESS">In Progress</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="CLOSED">Closed</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3.5 whitespace-nowrap w-44">No. Audit</th>
                            <th class="px-4 py-3.5 min-w-[200px]">Unit Toko</th>
                            <th class="px-4 py-3.5 whitespace-nowrap w-40">Auditor</th>
                            <th class="px-4 py-3.5 whitespace-nowrap w-32">Tanggal Audit</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center w-28">Jumlah Temuan</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center w-28">Status</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-right w-44">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredAudits.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    <span class="text-xs font-medium">Tidak ada data audit yang sesuai.</span>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="audit in filteredAudits"
                            :key="audit.id"
                            class="hover:bg-slate-50/80 transition-colors"
                        >
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap">
                                <Link
                                    :href="route('admin.audits.show', audit.id)"
                                    class="font-mono font-semibold text-blue-600 hover:underline"
                                >
                                    {{ audit.audit_number }}
                                </Link>
                            </td>

                            <td class="px-4 py-3.5 align-middle">
                                <div class="font-medium text-gray-900">{{ audit.store }}</div>
                                <div class="text-[11px] text-gray-500 font-mono">{{ audit.store_code }}</div>
                            </td>

                            <td class="px-4 py-3.5 align-middle whitespace-nowrap font-medium text-gray-800">
                                {{ audit.auditor }}
                            </td>

                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-gray-600 font-mono">
                                {{ audit.audit_date }}
                            </td>

                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                    {{ audit.findings_count }} temuan
                                </span>
                            </td>

                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-center">
                                <StatusBadge :status="audit.status" />
                            </td>

                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-right space-x-1.5">
                                <Link
                                    :href="route('admin.audits.show', audit.id)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-xs transition-colors"
                                >
                                    Detail
                                </Link>
                                <Link
                                    :href="route('admin.audits.edit', audit.id)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-xs transition-colors"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="deleteAudit(audit)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md border border-red-200 text-red-600 bg-white hover:bg-red-50 shadow-xs transition-colors"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
