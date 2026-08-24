<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    by_severity: {
        type: Object,
        required: true,
    },
    by_category: {
        type: Array,
        default: () => [],
    },
    store_losses: {
        type: Array,
        default: () => [],
    },
    by_status: {
        type: Object,
        required: true,
    },
    total_loss: {
        type: Number,
        default: 0,
    },
});

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};
</script>

<template>
    <AppLayout title="Laporan & Rekap Audit">
        <Head title="Laporan & Rekap Audit — Administrator" />

        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Laporan & Rekapitulasi Audit Retail</h1>
                <p class="text-xs text-gray-500 mt-1">Analisis agregat temuan, estimasi kerugian cabang, dan ekspor data Excel</p>
            </div>

            <!-- Export Buttons Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <a
                    :href="route('admin.reports.export-findings')"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded bg-emerald-700 hover:bg-emerald-800 text-white shadow-xs transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download Excel: Seluruh Temuan (.xls)
                </a>

                <a
                    :href="route('admin.reports.export-stores')"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-xs transition-colors"
                >
                    <svg class="w-4 h-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download Excel: Rekap Toko (.xls)
                </a>

                <a
                    :href="route('admin.reports.export-summary')"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-xs transition-colors"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Ringkasan Eksekutif (.xls)
                </a>
            </div>
        </div>

        <!-- Total Loss Highlight Card -->
        <div class="bg-white rounded border border-gray-200 p-6 mb-6 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Akumulasi Nominal Kerugian (Loss Amount)</div>
                <div class="text-3xl font-bold text-gray-900 mt-1">{{ formatRupiah(total_loss) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Dari seluruh temuan audit yang tercatat di sistem</div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    onclick="window.print()"
                    class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-xs transition-colors"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Halaman / PDF
                </button>
            </div>
        </div>

        <!-- 2 Column Breakdown Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 text-xs">
            <!-- Findings by Severity -->
            <div class="bg-white rounded border border-gray-200 p-5 shadow-xs">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4 pb-2 border-b border-gray-100">
                    Distribusi Berdasarkan Severity
                </h2>
                <div class="space-y-3">
                    <div
                        v-for="(count, severity) in by_severity"
                        :key="severity"
                        class="flex items-center justify-between p-2.5 bg-gray-50/70 rounded border border-gray-100"
                    >
                        <div class="flex items-center gap-2">
                            <SeverityBadge :severity="severity" />
                        </div>
                        <div class="font-bold text-gray-900 text-sm">{{ count }} temuan</div>
                    </div>
                </div>
            </div>

            <!-- Findings by Workflow Status -->
            <div class="bg-white rounded border border-gray-200 p-5 shadow-xs">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4 pb-2 border-b border-gray-100">
                    Distribusi Status Workflow
                </h2>
                <div class="space-y-3">
                    <div
                        v-for="(count, status) in by_status"
                        :key="status"
                        class="flex items-center justify-between p-2.5 bg-gray-50/70 rounded border border-gray-100"
                    >
                        <StatusBadge :status="status" />
                        <div class="font-bold text-gray-900 text-sm">{{ count }} temuan</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Store Loss & Finding Ranking Table -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm text-xs mb-8">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Rekapitulasi Kerugian & Temuan per Toko Retail</h2>
                    <p class="text-[11px] text-gray-500">Peringkat akumulasi temuan dan kerugian per unit cabang</p>
                </div>
                <a
                    :href="route('admin.reports.export-stores')"
                    class="text-xs font-medium text-emerald-700 hover:text-emerald-900"
                >
                    Unduh Data Toko (Excel) →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3.5">Kode</th>
                            <th class="px-5 py-3.5">Nama Toko</th>
                            <th class="px-5 py-3.5">Area</th>
                            <th class="px-5 py-3.5">Total Audit</th>
                            <th class="px-5 py-3.5">Total Temuan</th>
                            <th class="px-5 py-3.5 text-right">Akumulasi Kerugian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="store_losses.length === 0">
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500">Tidak ada data rekap toko.</td>
                        </tr>
                        <tr v-for="st in store_losses" :key="st.store_code" class="hover:bg-gray-50/70">
                            <td class="px-5 py-3.5 font-mono font-medium text-gray-900">{{ st.store_code }}</td>
                            <td class="px-5 py-3.5 font-semibold text-gray-900">{{ st.store_name }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ st.area || '-' }}</td>
                            <td class="px-5 py-3.5 text-gray-800">{{ st.total_audits }} audit</td>
                            <td class="px-5 py-3.5 font-medium text-gray-800">{{ st.total_findings }} temuan</td>
                            <td class="px-5 py-3.5 text-right font-bold" :class="st.total_loss > 0 ? 'text-red-700' : 'text-gray-900'">
                                {{ formatRupiah(st.total_loss) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
