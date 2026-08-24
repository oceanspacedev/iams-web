<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
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
            audit.auditor.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesStatus = !statusFilter.value || audit.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});

const deleteAudit = (audit) => {
    if (confirm(`Hapus audit ${audit.audit_number}? Seluruh temuan dan data tindak lanjut terkait akan terhapus.`)) {
        router.delete(route('admin.audits.destroy', audit.id));
    }
};
</script>

<template>
    <AppLayout title="Semua Pelaksanaan Audit">
        <Head title="Manajemen Audit" />

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Manajemen Pelaksanaan Audit</h1>
                <p class="text-xs text-gray-500 mt-1">Daftar penugasan audit toko, penetapan auditor, dan status pelaksanaan</p>
            </div>

            <Link
                :href="route('admin.audits.create')"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
            >
                + Jadwalkan Audit Baru
            </Link>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded border border-gray-200 mb-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:w-80">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nomor audit, toko, atau auditor..."
                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
            <div class="w-full sm:w-auto">
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
                            <th class="px-5 py-3.5">Unit Toko</th>
                            <th class="px-5 py-3.5">Auditor</th>
                            <th class="px-5 py-3.5">Tanggal Audit</th>
                            <th class="px-5 py-3.5">Jumlah Temuan</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="filteredAudits.length === 0">
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500">Tidak ada data audit yang sesuai.</td>
                        </tr>
                        <tr v-for="audit in filteredAudits" :key="audit.id" class="hover:bg-gray-50/70">
                            <td class="px-5 py-3.5 font-mono font-medium text-gray-900">
                                {{ audit.audit_number }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-gray-900">{{ audit.store }}</div>
                                <div class="text-[11px] text-gray-500 font-mono">{{ audit.store_code }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-700 font-medium">{{ audit.auditor }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ audit.audit_date }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ audit.findings_count }} temuan
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <StatusBadge :status="audit.status" />
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-2">
                                <Link
                                    :href="route('admin.audits.show', audit.id)"
                                    class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                                >
                                    Detail & Findings
                                </Link>
                                <Link
                                    :href="route('admin.audits.edit', audit.id)"
                                    class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="deleteAudit(audit)"
                                    class="px-2.5 py-1 text-xs rounded border border-red-200 text-red-600 hover:bg-red-50 font-medium"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
