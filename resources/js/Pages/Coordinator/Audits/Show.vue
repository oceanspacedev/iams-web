<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';

const props = defineProps({
    audit: {
        type: Object,
        required: true,
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
    <AppLayout :title="`Detail Audit ${audit.audit_number}`">
        <Head :title="`Detail Audit ${audit.audit_number} — Koordinator`" />

        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('coordinator.audits.index')" class="hover:text-blue-600">Audits</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">{{ audit.audit_number }}</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-3">
                {{ audit.audit_number }}
                <StatusBadge :status="audit.status" />
            </h1>
        </div>

        <!-- Audit Information -->
        <div class="bg-white rounded border border-gray-200 p-5 mb-8 text-xs">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4 pb-2 border-b border-gray-100">
                Informasi Audit
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <div class="text-gray-500 font-medium">Toko Sasaran</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.store.name }}</div>
                    <div class="text-[11px] text-gray-500 font-mono">{{ audit.store.code }} ({{ audit.store.area || '-' }})</div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Tanggal Audit</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.audit_date }}</div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Auditor Ditugaskan</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.auditor.name }}</div>
                    <div class="text-[11px] text-gray-500">{{ audit.auditor.email }}</div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Total Temuan</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.findings.length }} Finding(s)</div>
                </div>
            </div>

            <div v-if="audit.notes" class="mt-4 pt-3 border-t border-gray-100">
                <span class="text-gray-500 font-medium">Catatan: </span>
                <span class="text-gray-700">{{ audit.notes }}</span>
            </div>
        </div>

        <!-- Findings List -->
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">
                Daftar Temuan Audit & Status Review Severity ({{ audit.findings.length }})
            </h2>
        </div>

        <div v-if="audit.findings.length === 0" class="bg-white rounded border border-gray-200 p-8 text-center text-xs text-gray-500">
            Belum ada temuan yang dicatat untuk audit ini.
        </div>

        <div v-else class="space-y-4 text-xs">
            <div
                v-for="(finding, index) in audit.findings"
                :key="finding.id"
                class="bg-white rounded border border-gray-200 p-5 shadow-xs space-y-3"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="font-mono font-bold text-gray-400">#{{ String(index + 1).padStart(3, '0') }}</span>
                        <span class="font-semibold text-gray-900">{{ finding.category }}</span>
                        <SeverityBadge :severity="finding.severity" />
                        <span
                            v-if="finding.is_severity_locked"
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-800 border border-gray-300"
                        >
                            {{ finding.severity_status || 'APPROVED' }}
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-300"
                        >
                            Pending Review
                        </span>
                    </div>

                    <Link
                        :href="route('coordinator.findings.show', finding.id)"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded font-medium transition-colors"
                        :class="finding.is_severity_locked ? 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' : 'bg-blue-600 text-white hover:bg-blue-700 shadow-xs'"
                    >
                        {{ finding.is_severity_locked ? 'Detail Temuan' : 'Review Severity' }}
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 space-y-2">
                        <div>
                            <div class="text-gray-500 font-medium mb-1">Temuan:</div>
                            <p class="text-gray-900 leading-relaxed bg-gray-50 p-2.5 rounded border border-gray-100">{{ finding.finding }}</p>
                        </div>
                        <div v-if="finding.recommendation">
                            <div class="text-gray-500 font-medium mb-1">Rekomendasi:</div>
                            <p class="text-gray-800 leading-relaxed">{{ finding.recommendation }}</p>
                        </div>
                    </div>

                    <div class="border-t md:border-t-0 md:border-l md:border-gray-100 md:pl-6 space-y-2.5">
                        <div>
                            <div class="text-gray-500 font-medium">Nominal Kerugian</div>
                            <div class="text-sm font-semibold text-gray-900 mt-0.5">{{ formatRupiah(finding.loss_amount) }}</div>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium">SOP / SE Acuan</div>
                            <div class="text-gray-800 mt-0.5">{{ finding.sop ? `${finding.sop.code} - ${finding.sop.title}` : '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
