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
    return props.audits.filter((a) => {
        const matchesSearch =
            a.audit_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            a.store.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            a.auditor.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesStatus = !statusFilter.value || a.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});
</script>

<template>
    <AppLayout title="Monitoring Seluruh Audit">
        <Head title="Monitoring Audits — Koordinator" />

        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Monitoring Seluruh Audit Lapangan</h1>
            <p class="text-xs text-gray-500 mt-1">Pemantauan progres pelaksanaan audit di seluruh cabang retail dan auditor yang bertugas</p>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded border border-gray-200 mb-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:w-80">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari no. audit, toko, atau auditor..."
                    class="w-full text-xs rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
            <div class="w-full sm:w-auto">
                <select
                    v-model="statusFilter"
                    class="text-xs rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5"
                >
                    <option value="">Semua Status Audit</option>
                    <option value="PLANNED">Planned</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CLOSED">Closed</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm text-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3.5">Nomor Audit</th>
                            <th class="px-5 py-3.5">Toko Sasaran</th>
                            <th class="px-5 py-3.5">Auditor</th>
                            <th class="px-5 py-3.5">Tanggal Audit</th>
                            <th class="px-5 py-3.5">Total Temuan</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="filteredAudits.length === 0">
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500">Tidak ada audit ditemukan.</td>
                        </tr>
                        <tr v-for="a in filteredAudits" :key="a.id" class="hover:bg-gray-50/70">
                            <td class="px-5 py-3.5 font-mono font-medium text-indigo-600">{{ a.audit_number }}</td>
                            <td class="px-5 py-3.5 font-semibold text-gray-900">{{ a.store }} ({{ a.store_code }})</td>
                            <td class="px-5 py-3.5 text-gray-800">{{ a.auditor }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ a.audit_date }}</td>
                            <td class="px-5 py-3.5 font-medium text-gray-900">{{ a.findings_count }} temuan</td>
                            <td class="px-5 py-3.5"><StatusBadge :status="a.status" /></td>
                            <td class="px-5 py-3.5 text-right">
                                <Link
                                    :href="route('coordinator.audits.show', a.id)"
                                    class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                                >
                                    Buka Detail
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
