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
    by_status: {
        type: Object,
        required: true,
    },
    store_losses: {
        type: Array,
        default: () => [],
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
    <AppLayout title="Laporan & Rekapitulasi Audit">
        <Head title="Laporan & Rekapitulasi — Koordinator" />

        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Laporan & Rekapitulasi Audit Retail</h1>
                <p class="text-xs text-gray-500 mt-1">Distribusi severity risiko temuan, efektivitas tindak lanjut, dan ekspor data Excel</p>
            </div>

            <!-- Export Buttons Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <a
                    :href="route('coordinator.reports.export-findings')"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded bg-emerald-700 hover:bg-emerald-800 text-white shadow-xs transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download Excel: Seluruh Temuan (.xls)
                </a>

                <a
                    :href="route('coordinator.reports.export-stores')"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-xs transition-colors"
                >
                    <svg class="w-4 h-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download Excel: Rekap Toko (.xls)
                </a>

                <a
                    :href="route('coordinator.reports.export-summary')"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-xs transition-colors"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Ringkasan Eksekutif (.xls)
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 text-xs">
            <!-- Severity Summary -->
            <div class="bg-white rounded border border-gray-200 p-5 shadow-xs">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4 pb-2 border-b border-gray-100">
                    Distribusi Severity Temuan
                </h2>
                <div class="space-y-3">
                    <div v-for="(count, sev) in by_severity" :key="sev" class="flex items-center justify-between p-2 rounded bg-gray-50/70 border border-gray-100">
                        <div class="flex items-center gap-2">
                            <SeverityBadge :severity="sev" />
                        </div>
                        <span class="font-bold text-gray-900 text-sm">{{ count }} temuan</span>
                    </div>
                </div>
            </div>

            <!-- Status Summary -->
            <div class="bg-white rounded border border-gray-200 p-5 shadow-xs">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4 pb-2 border-b border-gray-100">
                    Distribusi Status Penyelesaian
                </h2>
                <div class="space-y-3">
                    <div v-for="(count, st) in by_status" :key="st" class="flex items-center justify-between p-2 rounded bg-gray-50/70 border border-gray-100">
                        <div class="flex items-center gap-2">
                            <StatusBadge :status="st" />
                        </div>
                        <span class="font-bold text-gray-900 text-sm">{{ count }} temuan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Store Loss Ranking -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-xs text-xs">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-gray-50/50">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Rekapitulasi Temuan & Kerugian per Cabang</h2>
                    <p class="text-[11px] text-gray-500">Peringkat kerugian retail berdasarkan hasil temuan audit</p>
                </div>
                <div class="font-semibold text-gray-900">
                    Total Loss Nasional: <span class="text-gray-900 font-bold text-sm">{{ formatRupiah(total_loss) }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3">Kode & Nama Toko</th>
                            <th class="px-5 py-3">Wilayah / Area</th>
                            <th class="px-5 py-3">Total Audit</th>
                            <th class="px-5 py-3">Total Temuan</th>
                            <th class="px-5 py-3 text-right">Akumulasi Kerugian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="s in store_losses" :key="s.store_code" class="hover:bg-gray-50/70">
                            <td class="px-5 py-3.5 font-medium text-gray-900">
                                {{ s.store_name }} <span class="text-gray-500 font-mono">({{ s.store_code }})</span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-700">{{ s.area || '-' }}</td>
                            <td class="px-5 py-3.5 text-gray-700">{{ s.total_audits }} kali</td>
                            <td class="px-5 py-3.5 font-semibold text-gray-900">{{ s.total_findings }} temuan</td>
                            <td class="px-5 py-3.5 text-right font-bold font-mono text-gray-900">
                                {{ formatRupiah(s.total_loss) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
