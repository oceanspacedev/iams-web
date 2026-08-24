<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    findings: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const lockFilter = ref('');
const severityFilter = ref('');

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
        const matchesSearch =
            f.finding.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            f.store.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            f.auditor.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            f.audit_number.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesLock =
            lockFilter.value === ''
                ? true
                : lockFilter.value === 'LOCKED'
                ? f.is_severity_locked
                : !f.is_severity_locked;

        const matchesSeverity = !severityFilter.value || f.severity === severityFilter.value;

        return matchesSearch && matchesLock && matchesSeverity;
    });
});
</script>

<template>
    <AppLayout title="Review Severity Temuan">
        <Head title="Review Severity — Koordinator" />

        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Review & Standardisasi Severity Temuan</h1>
            <p class="text-xs text-gray-500 mt-1">Koordinator meninjau usulan severity dari auditor, menyesuaikan level risiko, dan mengunci severity</p>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded border border-gray-200 mb-6 flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="w-full md:w-80">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari temuan, toko, auditor, atau no. audit..."
                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>

            <div class="w-full md:w-auto flex flex-wrap gap-2.5">
                <select
                    v-model="lockFilter"
                    class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-1.5"
                >
                    <option value="">Semua Status Review</option>
                    <option value="PENDING">Menunggu Review (Pending)</option>
                    <option value="LOCKED">Terkunci (Reviewed)</option>
                </select>

                <select
                    v-model="severityFilter"
                    class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-1.5"
                >
                    <option value="">Semua Severity</option>
                    <option value="CRITICAL">CRITICAL</option>
                    <option value="MAJOR">MAJOR</option>
                    <option value="MINOR">MINOR</option>
                    <option value="OBSERVATION">OBSERVATION</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm text-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3.5">Audit & Toko</th>
                            <th class="px-4 py-3.5">Auditor</th>
                            <th class="px-4 py-3.5">Uraian Temuan</th>
                            <th class="px-4 py-3.5">Severity</th>
                            <th class="px-4 py-3.5">Status Review</th>
                            <th class="px-4 py-3.5">Status Audit</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="filteredFindings.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada temuan yang sesuai filter.</td>
                        </tr>
                        <tr v-for="f in filteredFindings" :key="f.id" class="hover:bg-gray-50/70">
                            <td class="px-4 py-3.5">
                                <div class="font-mono font-medium text-gray-900">{{ f.audit_number }}</div>
                                <div class="text-[11px] text-gray-600 font-semibold">{{ f.store }}</div>
                            </td>
                            <td class="px-4 py-3.5 font-medium text-gray-900">{{ f.auditor }}</td>
                            <td class="px-4 py-3.5 max-w-sm">
                                <div class="text-[11px] font-semibold text-gray-500 mb-0.5">{{ f.category }}</div>
                                <div class="text-gray-900 line-clamp-2 leading-relaxed">{{ f.finding }}</div>
                                <div v-if="f.loss_amount" class="text-[11px] text-gray-500 mt-0.5 font-medium">
                                    Loss: {{ formatRupiah(f.loss_amount) }}
                                </div>
                            </td>
                            <td class="px-4 py-3.5"><SeverityBadge :severity="f.severity" /></td>
                            <td class="px-4 py-3.5">
                                <span
                                    v-if="f.is_severity_locked"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-800 border border-gray-300"
                                >
                                    {{ f.severity_status || 'APPROVED' }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-300"
                                >
                                    PENDING REVIEW
                                </span>
                            </td>
                            <td class="px-4 py-3.5"><StatusBadge :status="f.status" /></td>
                            <td class="px-4 py-3.5 text-right">
                                <Link
                                    :href="route('coordinator.findings.show', f.id)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded font-medium transition-colors"
                                    :class="f.is_severity_locked ? 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' : 'bg-blue-600 text-white hover:bg-blue-700'"
                                >
                                    {{ f.is_severity_locked ? 'Detail' : 'Review Severity' }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
