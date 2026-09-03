<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';

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
const currentPage = ref(1);

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

watch([searchQuery, lockFilter, severityFilter], () => {
    currentPage.value = 1;
});

const paginatedFindings = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return filteredFindings.value.slice(start, start + 10);
});
</script>

<template>
    <AppLayout title="Review Severity Temuan">
        <Head title="Review Severity — Koordinator" />

        <!-- Header -->
        <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
                <h1 class="text-lg sm:text-xl font-semibold text-gray-900 tracking-tight">Review & Standardisasi Severity Temuan</h1>
                <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Koordinator meninjau usulan severity dari auditor, menyesuaikan level risiko, dan mengunci severity</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 font-mono">
                    {{ filteredFindings.length }} Temuan terdaftar
                </span>
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
                        placeholder="Cari temuan, toko, auditor, atau no. audit..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    />
                </div>

                <div class="w-full sm:w-56 shrink-0">
                    <select
                        v-model="lockFilter"
                        class="w-full py-2 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white font-medium"
                    >
                        <option value="">Semua Status Review</option>
                        <option value="PENDING">Menunggu Review (Pending)</option>
                        <option value="LOCKED">Terkunci (Reviewed)</option>
                    </select>
                </div>

                <div class="w-full sm:w-44 shrink-0">
                    <select
                        v-model="severityFilter"
                        class="w-full py-2 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    >
                        <option value="">Semua Severity</option>
                        <option value="MINOR">Minor</option>
                        <option value="MEDIUM">Medium</option>
                        <option value="MAJOR">Major</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Clean Dual View (Mobile Cards vs Desktop Table) -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            
            <!-- 1. MOBILE CARD VIEW (Visible on mobile screens) -->
            <div class="block md:hidden divide-y divide-gray-200">
                <div v-if="filteredFindings.length === 0" class="p-8 text-center text-gray-400 text-xs">
                    Tidak ada temuan yang sesuai filter.
                </div>
                <div
                    v-for="f in paginatedFindings"
                    :key="'m-finding-' + f.id"
                    class="p-4 space-y-2.5"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900 text-xs">{{ f.store }}</div>
                            <div class="font-mono text-[10px] text-gray-400 mt-0.5">{{ f.audit_number }}</div>
                        </div>
                        <StatusBadge :status="f.status" />
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-medium text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                            {{ f.category }}
                        </span>
                        <SeverityBadge :severity="f.severity" />
                        <span
                            v-if="f.is_severity_locked"
                            class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200 ml-auto"
                        >
                            {{ f.severity_status || 'LOCKED' }}
                        </span>
                        <span
                            v-else
                            class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-800 border border-amber-200 ml-auto"
                        >
                            Review Pending
                        </span>
                    </div>

                    <div class="text-xs text-gray-800 leading-relaxed line-clamp-2">
                        {{ f.finding }}
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-gray-600 pt-1.5 border-t border-gray-100">
                        <span>Auditor: <strong class="text-gray-800 font-medium">{{ f.auditor }}</strong></span>
                        <span v-if="f.loss_amount" class="font-mono text-[11px] font-semibold text-slate-700">
                            {{ formatRupiah(f.loss_amount) }}
                        </span>
                    </div>

                    <div class="pt-2 flex justify-end border-t border-gray-100">
                        <Link
                            :href="route('coordinator.findings.show', f.id)"
                            class="px-3 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-medium text-xs"
                        >
                            {{ f.is_severity_locked ? 'Detail' : 'Review Severity →' }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 2. DESKTOP TABLE VIEW (Visible on tablet & desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap w-44">Audit & Toko</th>
                            <th class="px-4 py-3 whitespace-nowrap w-36">Auditor</th>
                            <th class="px-4 py-3 min-w-[240px]">Uraian Temuan</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Severity</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-36">Status Review</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Status Audit</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredFindings.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada temuan yang sesuai filter.
                            </td>
                        </tr>
                        <tr
                            v-for="f in paginatedFindings"
                            :key="f.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-4 py-3 align-top whitespace-nowrap">
                                <div class="font-mono font-semibold text-slate-800">{{ f.audit_number }}</div>
                                <div class="text-xs text-gray-900 font-medium mt-0.5">{{ f.store }}</div>
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-gray-700 font-medium">
                                {{ f.auditor }}
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="text-[11px] font-medium text-slate-500 mb-0.5">
                                    {{ f.category }}
                                </div>
                                <div class="text-gray-900 leading-relaxed font-normal line-clamp-3">{{ f.finding }}</div>
                                <div v-if="f.loss_amount" class="text-[11px] text-slate-600 font-mono mt-1 font-medium">
                                    Loss: {{ formatRupiah(f.loss_amount) }}
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-center">
                                <SeverityBadge :severity="f.severity" />
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-center">
                                <span
                                    v-if="f.is_severity_locked"
                                    class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200"
                                >
                                    {{ f.severity_status || 'APPROVED' }}
                                </span>
                                <span
                                    v-else
                                    class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200"
                                >
                                    Menunggu Review
                                </span>
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-center">
                                <StatusBadge :status="f.status" />
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-right">
                                <Link
                                    :href="route('coordinator.findings.show', f.id)"
                                    class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs"
                                >
                                    {{ f.is_severity_locked ? 'Detail' : 'Review' }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <Pagination
                :current-page="currentPage"
                :per-page="10"
                :total-items="filteredFindings.length"
                @update:current-page="currentPage = $event"
            />
        </div>
    </AppLayout>
</template>
