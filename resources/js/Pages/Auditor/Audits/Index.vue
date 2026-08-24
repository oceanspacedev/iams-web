<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
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
            (audit.store_area && audit.store_area.toLowerCase().includes(searchQuery.value.toLowerCase()));

        const matchesStatus = !statusFilter.value || audit.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});
</script>

<template>
    <AppLayout title="Penugasan Audit">
        <Head title="My Audits" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Daftar Penugasan Audit</h1>
                <p class="text-xs text-gray-500 mt-1">Seluruh jadwal dan riwayat audit yang ditugaskan kepada Anda</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded border border-gray-200 mb-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:w-80">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nomor audit atau nama toko..."
                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
            <div class="w-full sm:w-auto flex items-center gap-3">
                <select
                    v-model="statusFilter"
                    class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-1.5"
                >
                    <option value="">Semua Status</option>
                    <option value="PLANNED">Planned</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CLOSED">Closed</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3.5">No. Audit</th>
                            <th class="px-5 py-3.5">Toko / Lokasi</th>
                            <th class="px-5 py-3.5">Tanggal Audit</th>
                            <th class="px-5 py-3.5">Findings</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="filteredAudits.length === 0">
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500">
                                Tidak ada data audit yang sesuai kriteria pencarian.
                            </td>
                        </tr>
                        <tr
                            v-for="audit in filteredAudits"
                            :key="audit.id"
                            class="hover:bg-gray-50/70 transition-colors"
                        >
                            <td class="px-5 py-3.5 font-mono font-medium text-gray-900">
                                {{ audit.audit_number }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-gray-900">{{ audit.store }}</div>
                                <div class="text-[11px] text-gray-500">{{ audit.store_area || '-' }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">
                                {{ audit.audit_date }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ audit.findings_count }} temuan
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <StatusBadge :status="audit.status" />
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <Link
                                    :href="route('auditor.audits.show', audit.id)"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
                                >
                                    Buka Audit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
