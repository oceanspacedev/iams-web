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
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (f.finding && f.finding.toLowerCase().includes(query)) ||
            (f.store && f.store.toLowerCase().includes(query)) ||
            (f.auditor && f.auditor.toLowerCase().includes(query)) ||
            (f.audit_number && f.audit_number.toLowerCase().includes(query));

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

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Review & Standardisasi Severity Temuan</h1>
                <p class="text-xs text-gray-500 mt-1">Koordinator meninjau usulan severity dari auditor, menyesuaikan level risiko, dan mengunci severity</p>
            </div>
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                    Total: {{ filteredFindings.length }} Temuan
                </span>
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
                        placeholder="Cari temuan, toko, auditor, atau no. audit..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-gray-50/50 focus:bg-white transition-colors"
                    />
                </div>

                <div class="w-full sm:w-56 shrink-0">
                    <select
                        v-model="lockFilter"
                        class="w-full py-2 px-3 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white"
                    >
                        <option value="">Semua Status Review</option>
                        <option value="PENDING">Menunggu Review (Pending)</option>
                        <option value="LOCKED">Terkunci (Reviewed)</option>
                    </select>
                </div>

                <div class="w-full sm:w-44 shrink-0">
                    <select
                        v-model="severityFilter"
                        class="w-full py-2 px-3 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white"
                    >
                        <option value="">Semua Severity</option>
                        <option value="CRITICAL">CRITICAL</option>
                        <option value="MAJOR">MAJOR</option>
                        <option value="MINOR">MINOR</option>
                        <option value="OBSERVATION">OBSERVATION</option>
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
                            <th class="px-4 py-3.5 whitespace-nowrap w-44">Audit & Toko</th>
                            <th class="px-4 py-3.5 whitespace-nowrap w-36">Auditor</th>
                            <th class="px-4 py-3.5 min-w-[240px]">Uraian Temuan</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center w-28">Severity</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center w-36">Status Review</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center w-28">Status Audit</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-right w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredFindings.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span class="text-xs font-medium">Tidak ada temuan yang sesuai filter.</span>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="f in filteredFindings"
                            :key="f.id"
                            class="hover:bg-slate-50/80 transition-colors"
                        >
                            <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                <div class="font-mono font-semibold text-blue-600">{{ f.audit_number }}</div>
                                <div class="text-[11px] text-gray-800 font-medium mt-0.5">{{ f.store }}</div>
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap font-medium text-gray-800">
                                {{ f.auditor }}
                            </td>

                            <td class="px-4 py-3.5 align-top">
                                <span class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded bg-slate-100 text-slate-700 mb-1 border border-slate-200">
                                    {{ f.category }}
                                </span>
                                <div class="text-gray-900 leading-relaxed font-medium line-clamp-3">{{ f.finding }}</div>
                                <div v-if="f.loss_amount" class="text-[11px] text-emerald-700 mt-1 font-semibold">
                                    Loss: {{ formatRupiah(f.loss_amount) }}
                                </div>
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap text-center">
                                <SeverityBadge :severity="f.severity" />
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap text-center">
                                <span
                                    v-if="f.is_severity_locked"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-800 border border-gray-300"
                                >
                                    {{ f.severity_status || 'APPROVED' }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-300"
                                >
                                    PENDING REVIEW
                                </span>
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap text-center">
                                <StatusBadge :status="f.status" />
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap text-right">
                                <Link
                                    :href="route('coordinator.findings.show', f.id)"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors shadow-xs whitespace-nowrap"
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
