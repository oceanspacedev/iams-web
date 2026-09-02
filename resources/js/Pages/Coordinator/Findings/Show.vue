<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import WorkflowTracker from '@/Components/WorkflowTracker.vue';

const props = defineProps({
    finding: {
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

// Form Review Severity (Koordinator)
const severityForm = useForm({
    severity: props.finding.severity || 'MINOR',
    severity_notes: props.finding.severity_notes || '',
});

const submitSeverityReview = () => {
    severityForm.patch(route('coordinator.findings.review-severity', props.finding.id));
};
</script>

<template>
    <AppLayout :title="`Review Finding #${finding.id}`">
        <Head :title="`Review Finding #${finding.id} — Koordinator`" />

        <!-- Breadcrumbs -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <Link :href="route('coordinator.findings.index')" class="hover:text-blue-600">Review Severity</Link>
                    <span>/</span>
                    <Link :href="route('coordinator.audits.show', finding.audit.id)" class="hover:text-blue-600 font-mono">{{ finding.audit.audit_number }}</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-medium font-mono">Finding #{{ String(finding.id).padStart(4, '0') }}</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-3">
                    Finding #{{ String(finding.id).padStart(4, '0') }}
                    <SeverityBadge :severity="finding.severity" :show-timeline="true" />
                    <StatusBadge :status="finding.status" />
                </h1>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 text-xs">
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Detail Finding -->
                <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        1. Temuan Audit (Finding)
                    </h2>

                    <div class="grid grid-cols-2 gap-4 bg-gray-50/70 p-3 rounded border border-gray-200/60">
                        <div>
                            <span class="text-gray-500 font-medium">Kategori:</span>
                            <div class="font-semibold text-gray-900 mt-0.5">{{ finding.category }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500 font-medium">SOP / SE Acuan:</span>
                            <div class="font-semibold text-gray-900 mt-0.5">
                                {{ finding.sop ? `${finding.sop.code} - ${finding.sop.title}` : '—' }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500 font-medium mb-1">Uraian Temuan:</div>
                        <p class="text-gray-900 leading-relaxed bg-gray-50 p-3 rounded border border-gray-200 whitespace-pre-line">{{ finding.finding }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="finding.auditor_opinion">
                            <div class="text-gray-500 font-medium mb-1">Opini Auditor:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50 p-2.5 rounded border border-gray-200">{{ finding.auditor_opinion }}</p>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium mb-1">Rekomendasi Perbaikan:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50 p-2.5 rounded border border-gray-200">{{ finding.recommendation }}</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Komitmen Tindak Lanjut Toko -->
                <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100 flex items-center justify-between">
                        <span>2. Komitmen Tindak Lanjut Toko (Hasil Klarifikasi Lapangan)</span>
                        <StatusBadge v-if="finding.action_plan" :status="finding.action_plan.status" />
                    </h2>

                    <div v-if="finding.action_plan?.action_plan" class="space-y-3">
                        <div class="bg-gray-50 p-3.5 rounded border border-gray-200">
                            <div class="text-gray-500 font-medium mb-1">Komitmen Tindakan Perbaikan:</div>
                            <p class="text-gray-900 leading-relaxed font-medium whitespace-pre-line">{{ finding.action_plan.action_plan }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 bg-gray-50/70 p-3 rounded border border-gray-200/60">
                            <div>
                                <span class="text-gray-500 font-medium">PIC:</span>
                                <div class="font-semibold text-gray-900 mt-0.5">{{ finding.action_plan.pic || '—' }}</div>
                            </div>
                            <div>
                                <span class="text-gray-500 font-medium">Target Deadline:</span>
                                <div class="font-mono font-semibold text-gray-900 mt-0.5">{{ finding.action_plan.deadline || '—' }}</div>
                            </div>
                        </div>

                        <div v-if="finding.action_plan.response" class="text-gray-600 bg-gray-50 p-2.5 rounded border border-gray-200">
                            <span class="font-medium text-gray-700">Catatan: </span> {{ finding.action_plan.response }}
                        </div>
                    </div>

                    <div v-else class="text-center py-6 text-gray-400 bg-gray-50 rounded border border-dashed border-gray-200">
                        Belum ada komitmen tindak lanjut yang dicatat untuk temuan ini.
                    </div>
                </div>
            </div>

            <!-- Right Column: Severity Review & Audit Info -->
            <div class="space-y-6">
                <!-- Severity Review Card (Koordinator Form) -->
                <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-800">
                            Review Severity
                        </h3>
                        <span
                            v-if="finding.is_severity_locked"
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-800 border border-gray-300"
                        >
                            Terkunci
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-300"
                        >
                            Pending Review
                        </span>
                    </div>

                    <div v-if="finding.is_severity_locked" class="bg-gray-50 p-3 rounded border border-gray-200 space-y-2">
                        <div class="text-gray-900 font-medium">
                            Status: <span class="font-bold">{{ finding.severity_status }}</span>
                        </div>
                        <div class="text-[11px] text-gray-600">
                            Direview oleh: <strong class="text-gray-900">{{ finding.severity_reviewed_by || 'Koordinator' }}</strong>
                            <span v-if="finding.severity_reviewed_at"> • {{ finding.severity_reviewed_at }}</span>
                        </div>
                        <div v-if="finding.severity_notes" class="text-[11px] text-gray-700 bg-white p-2 rounded border border-gray-200">
                            "{{ finding.severity_notes }}"
                        </div>
                    </div>

                    <form @submit.prevent="submitSeverityReview" class="space-y-3 pt-1">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">
                                {{ finding.is_severity_locked ? 'Ubah Tingkat Severity' : 'Tentukan / Setujui Severity' }} <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="severityForm.severity"
                                required
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-medium"
                            >
                                <option value="MINOR">Minor (Timeline SLA: 3 - 7 hari)</option>
                                <option value="MEDIUM">Medium (Timeline SLA: 8 - 14 hari)</option>
                                <option value="MAJOR">Major (Timeline SLA: 15 - 30 hari)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Catatan Koordinator (Opsional)</label>
                            <textarea
                                v-model="severityForm.severity_notes"
                                rows="2"
                                placeholder="Alasan penyesuaian atau catatan severity..."
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            :disabled="severityForm.processing"
                            class="w-full py-2 px-3 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold text-xs shadow-xs disabled:opacity-50 flex items-center justify-center gap-1.5 transition-colors"
                        >
                            <span>{{ severityForm.processing ? 'Menyimpan...' : 'Simpan & Kunci Severity' }}</span>
                        </button>
                    </form>
                </div>

                <!-- Audit Info Card -->
                <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-xs space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-700 pb-2 border-b border-gray-100">
                        Informasi Audit
                    </h3>

                    <div>
                        <div class="text-gray-400">Nomor Audit:</div>
                        <div class="font-mono font-semibold text-gray-900 mt-0.5">{{ finding.audit.audit_number }}</div>
                    </div>

                    <div>
                        <div class="text-gray-400">Unit / Toko:</div>
                        <div class="font-semibold text-gray-900 mt-0.5">{{ finding.store.name }} ({{ finding.store.code }})</div>
                    </div>

                    <div>
                        <div class="text-gray-400">Auditor Pelapor:</div>
                        <div class="font-semibold text-gray-900 mt-0.5">{{ finding.auditor.name }}</div>
                        <div v-if="finding.auditor.phone" class="text-[11px] text-gray-500 font-mono">{{ finding.auditor.phone }}</div>
                    </div>

                    <div>
                        <div class="text-gray-400">Nominal Kerugian:</div>
                        <div class="text-sm font-bold text-gray-900 font-mono mt-0.5">{{ formatRupiah(finding.loss_amount) }}</div>
                    </div>

                    <div>
                        <div class="text-gray-400">Status Finding:</div>
                        <div class="mt-1"><StatusBadge :status="finding.status" /></div>
                    </div>
                </div>

                <!-- Workflow Step Tracker -->
                <WorkflowTracker
                    :status="finding.status"
                    :has-documents="finding.has_documents || (finding.audit && finding.audit.documents_count > 0)"
                    :documents-count="finding.documents_count || (finding.audit ? finding.audit.documents_count : 0)"
                    :has-action-plan="finding.has_action_plan || !!finding.action_plan?.action_plan"
                />
            </div>
        </div>
    </AppLayout>
</template>
