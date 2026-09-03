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
            audit.auditor.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesStatus = !statusFilter.value || audit.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});
</script>

<template>
    <AppLayout title="Audit Toko">
        <Head title="Audit Toko" />

        <!-- Header -->
        <div class="mb-4 sm:mb-6">
            <h1 class="text-lg sm:text-xl font-semibold text-gray-900 tracking-tight">Daftar Audit Toko</h1>
            <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Seluruh riwayat pelaksanaan audit pada toko Anda</p>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-3 sm:p-3.5 rounded-lg border border-gray-200 shadow-2xs mb-4 sm:mb-5 text-xs flex flex-col sm:flex-row gap-2.5 sm:gap-3 items-center justify-between">
            <div class="w-full sm:w-80">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nomor audit atau auditor..."
                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2 px-3"
                />
            </div>
            <div class="w-full sm:w-auto">
                <select
                    v-model="statusFilter"
                    class="w-full sm:w-auto text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2 px-3"
                >
                    <option value="">Semua Status</option>
                    <option value="PLANNED">Planned</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CLOSED">Closed</option>
                </select>
            </div>
        </div>

        <!-- Clean Dual View (Mobile Cards vs Desktop Table) -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            
            <!-- 1. MOBILE CARD VIEW (Visible on mobile screens) -->
            <div class="block md:hidden divide-y divide-gray-200">
                <div v-if="filteredAudits.length === 0" class="p-8 text-center text-gray-400 text-xs">
                    Tidak ada data audit yang ditemukan.
                </div>
                <div
                    v-for="audit in filteredAudits"
                    :key="'m-auditee-audit-' + audit.id"
                    class="p-4 space-y-2.5"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900 text-xs">{{ audit.store }}</div>
                            <div class="font-mono text-[10px] text-gray-500 mt-0.5">{{ audit.store_code }}</div>
                        </div>
                        <StatusBadge :status="audit.status" />
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-xs font-semibold text-blue-600">
                            {{ audit.audit_number }}
                        </span>
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                            {{ audit.findings_count }} temuan
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-gray-600 pt-1.5 border-t border-gray-100">
                        <span>Auditor: <strong class="text-gray-800 font-medium">{{ audit.auditor }}</strong></span>
                        <span class="font-mono text-gray-500 text-[10px]">{{ audit.audit_date }}</span>
                    </div>

                    <div class="pt-2 flex justify-end border-t border-gray-100">
                        <Link
                            :href="route('auditee.audits.show', audit.id)"
                            class="px-3 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-medium text-xs"
                        >
                            Buka Temuan →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 2. DESKTOP TABLE VIEW (Visible on tablet & desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3.5">No. Audit</th>
                            <th class="px-5 py-3.5">Toko</th>
                            <th class="px-5 py-3.5">Auditor</th>
                            <th class="px-5 py-3.5">Tanggal Audit</th>
                            <th class="px-5 py-3.5">Findings</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="filteredAudits.length === 0">
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500">
                                Tidak ada data audit yang ditemukan.
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
                                <div class="text-[11px] text-gray-500 font-mono">{{ audit.store_code }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-700">
                                {{ audit.auditor }}
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
                                    :href="route('auditee.audits.show', audit.id)"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
                                >
                                    Buka Temuan
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
