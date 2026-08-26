<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recent_audits: {
        type: Array,
        default: () => [],
    },
    recent_findings: {
        type: Array,
        default: () => [],
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
    <AppLayout title="Koordinator Dashboard">
        <Head title="Dashboard — Koordinator" />

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Enterprise Audit Dashboard</h1>
                <p class="text-xs text-gray-500 mt-1">Ringkasan operasional dan pengawasan seluruh audit retail</p>
            </div>

            <div class="flex items-center gap-3">
                <Link
                    :href="route('coordinator.audits.create')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
                >
                    + Buat Audit Baru
                </Link>
            </div>
        </div>

        <!-- Metrics Overview Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-8">
            <div class="bg-white p-4 rounded border border-gray-200">
                <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Total Audits</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_audits }}</div>
                <div class="text-[10px] text-gray-400 mt-1">{{ stats.active_audits }} aktif</div>
            </div>

            <div class="bg-white p-4 rounded border border-gray-200">
                <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Total Findings</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_findings }}</div>
            </div>

            <div class="bg-white p-4 rounded border border-red-200 bg-red-50/20">
                <div class="text-[11px] font-medium text-red-600 uppercase tracking-wider">Open</div>
                <div class="text-2xl font-bold text-red-600 mt-1">{{ stats.open_findings }}</div>
            </div>

            <div class="bg-white p-4 rounded border border-amber-200 bg-amber-50/20">
                <div class="text-[11px] font-medium text-amber-600 uppercase tracking-wider">In Progress</div>
                <div class="text-2xl font-bold text-amber-600 mt-1">{{ stats.in_progress_findings }}</div>
            </div>

            <div class="bg-white p-4 rounded border border-blue-200 bg-blue-50/20">
                <div class="text-[11px] font-medium text-blue-700 uppercase tracking-wider">Waiting Verify</div>
                <div class="text-2xl font-bold text-blue-700 mt-1">{{ stats.waiting_verification }}</div>
            </div>

            <div class="bg-white p-4 rounded border border-emerald-200 bg-emerald-50/20">
                <div class="text-[11px] font-medium text-emerald-700 uppercase tracking-wider">Closed</div>
                <div class="text-2xl font-bold text-emerald-700 mt-1">{{ stats.closed_findings }}</div>
            </div>

            <div class="bg-white p-4 rounded border border-gray-200">
                <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Total Kerugian</div>
                <div class="text-sm font-bold text-gray-900 mt-2 truncate">{{ formatRupiah(stats.total_loss_amount) }}</div>
            </div>
        </div>

        <!-- 2 Column Tables: Recent Audits & Recent Findings -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Audits Table -->
            <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Recent Audits</h2>
                        <p class="text-[11px] text-gray-500">Penugasan audit terbaru</p>
                    </div>
                    <Link :href="route('coordinator.audits.index')" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        Semua Audit →
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2.5">No. Audit</th>
                                <th class="px-4 py-2.5">Toko</th>
                                <th class="px-4 py-2.5">Auditor</th>
                                <th class="px-4 py-2.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="recent_audits.length === 0">
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada data audit.</td>
                            </tr>
                            <tr v-for="a in recent_audits" :key="a.id" class="hover:bg-gray-50/70">
                                <td class="px-4 py-2.5 font-mono font-medium text-blue-600 hover:underline">
                                    <Link :href="route('coordinator.audits.show', a.id)">{{ a.audit_number }}</Link>
                                </td>
                                <td class="px-4 py-2.5 font-medium text-gray-900">{{ a.store }}</td>
                                <td class="px-4 py-2.5 text-gray-600">{{ a.auditor }}</td>
                                <td class="px-4 py-2.5"><StatusBadge :status="a.status" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Findings Table -->
            <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Recent Findings</h2>
                        <p class="text-[11px] text-gray-500">Temuan audit yang baru dicatat</p>
                    </div>
                    <Link :href="route('coordinator.findings.index')" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        Semua Finding →
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2.5">Toko & Kategori</th>
                                <th class="px-4 py-2.5">Severity</th>
                                <th class="px-4 py-2.5">Kerugian</th>
                                <th class="px-4 py-2.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="recent_findings.length === 0">
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada temuan audit.</td>
                            </tr>
                            <tr v-for="f in recent_findings" :key="f.id" class="hover:bg-gray-50/70">
                                <td class="px-4 py-2.5">
                                    <Link :href="route('coordinator.findings.show', f.id)" class="font-medium text-gray-900 hover:text-blue-600 hover:underline">
                                        {{ f.store }}
                                    </Link>
                                    <div class="text-[10px] text-gray-500">{{ f.category }}</div>
                                </td>
                                <td class="px-4 py-2.5"><SeverityBadge :severity="f.severity" /></td>
                                <td class="px-4 py-2.5 font-medium text-gray-800">{{ formatRupiah(f.loss_amount) }}</td>
                                <td class="px-4 py-2.5"><StatusBadge :status="f.status" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
