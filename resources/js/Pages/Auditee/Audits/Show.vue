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
    <AppLayout :title="`Audit Toko - ${audit.audit_number}`">
        <Head :title="`Detail Audit ${audit.audit_number}`" />

        <!-- Breadcrumb -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <Link :href="route('auditee.audits.index')" class="hover:text-blue-600">Audit Toko</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-medium">{{ audit.audit_number }}</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-3">
                    {{ audit.audit_number }}
                    <StatusBadge :status="audit.status" />
                </h1>
            </div>
        </div>

        <!-- Audit Information -->
        <div class="bg-white rounded border border-gray-200 p-5 mb-8 text-xs">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4 pb-2 border-b border-gray-100">
                Informasi Pelaksanaan Audit
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <div class="text-gray-500 font-medium">Toko</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.store.name }}</div>
                    <div class="text-[11px] text-gray-500 font-mono">{{ audit.store.code }} ({{ audit.store.area || '-' }})</div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Tanggal Audit</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.audit_date }}</div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Auditor Bertugas</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.auditor.name }}</div>
                    <div class="text-[11px] text-gray-500">{{ audit.auditor.email }}</div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Jumlah Temuan</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.findings.length }} Finding(s)</div>
                </div>
            </div>

            <div v-if="audit.notes" class="mt-4 pt-3 border-t border-gray-100">
                <span class="text-gray-500 font-medium">Catatan Audit: </span>
                <span class="text-gray-700">{{ audit.notes }}</span>
            </div>
        </div>

        <!-- Findings List -->
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">
                Temuan Audit yang Perlu Ditindaklanjuti ({{ audit.findings.length }})
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Silakan lengkapi action plan dan unggah bukti perbaikan (evidence) untuk setiap temuan</p>
        </div>

        <div v-if="audit.findings.length === 0" class="bg-white rounded border border-gray-200 p-8 text-center text-xs text-gray-500">
            Tidak ada temuan pada audit ini.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="(finding, index) in audit.findings"
                :key="finding.id"
                class="bg-white rounded border border-gray-200 p-5 shadow-xs transition-colors hover:border-gray-300"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 mb-3 border-b border-gray-100 text-xs">
                    <div class="flex items-center gap-3">
                        <span class="font-mono font-bold text-gray-400">#{{ String(index + 1).padStart(3, '0') }}</span>
                        <span class="font-semibold text-gray-900">{{ finding.category }}</span>
                        <SeverityBadge :severity="finding.severity" />
                        <StatusBadge :status="finding.status" />
                    </div>

                    <Link
                        :href="route('auditee.findings.show', finding.id)"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
                    >
                        Isi Tindak Lanjut & Upload Evidence →
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
                    <div class="md:col-span-2 space-y-3">
                        <div>
                            <div class="text-gray-500 font-medium mb-1">Temuan Auditor:</div>
                            <p class="text-gray-900 leading-relaxed font-normal bg-gray-50/50 p-2.5 rounded border border-gray-100">{{ finding.finding }}</p>
                        </div>

                        <div v-if="finding.recommendation">
                            <div class="text-gray-500 font-medium mb-1">Rekomendasi Perbaikan:</div>
                            <p class="text-gray-800 leading-relaxed bg-blue-50/30 p-2.5 rounded border border-blue-100 text-blue-900">{{ finding.recommendation }}</p>
                        </div>
                    </div>

                    <div class="border-t md:border-t-0 md:border-l md:border-gray-100 md:pl-6 space-y-3">
                        <div>
                            <div class="text-gray-500 font-medium">Nominal Kerugian</div>
                            <div class="text-sm font-semibold text-gray-900 mt-0.5">{{ formatRupiah(finding.loss_amount) }}</div>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium">SOP / SE Terkait</div>
                            <div class="text-gray-800 mt-0.5">{{ finding.sop ? `${finding.sop.code} - ${finding.sop.title}` : '—' }}</div>
                        </div>

                        <div class="pt-2 border-t border-gray-100">
                            <div class="text-gray-500 font-medium mb-1">Status Action Plan</div>
                            <div v-if="finding.action_plan?.action_plan" class="text-gray-800">
                                <span class="font-medium text-emerald-700">Sudah diisi</span>
                                <div class="text-[11px] text-gray-500 mt-0.5">PIC: {{ finding.action_plan.pic || '-' }} | Deadline: {{ finding.action_plan.deadline || '-' }}</div>
                            </div>
                            <div v-else class="text-amber-700 font-medium">
                                Belum diisi
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
