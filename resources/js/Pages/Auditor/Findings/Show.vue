<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import WorkflowTracker from '@/Components/WorkflowTracker.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

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

// Evidence rejection modal state
const rejectingEvidenceId = ref(null);
const rejectForm = useForm({
    rejection_reason: '',
});

const openRejectModal = (evidenceId) => {
    rejectingEvidenceId.value = evidenceId;
    rejectForm.rejection_reason = '';
};

const closeRejectModal = () => {
    rejectingEvidenceId.value = null;
    rejectForm.reset();
};

const submitReject = () => {
    rejectForm.patch(route('auditor.evidences.reject', rejectingEvidenceId.value), {
        onSuccess: () => closeRejectModal(),
    });
};

const confirmModal = ref({
    show: false,
    title: '',
    message: '',
    confirmText: '',
    type: 'primary',
    action: null,
});

const openConfirm = (config) => {
    confirmModal.value = {
        show: true,
        title: config.title || 'Konfirmasi Tindakan',
        message: config.message || 'Apakah Anda yakin ingin melanjutkan?',
        confirmText: config.confirmText || 'Ya, Lanjutkan',
        type: config.type || 'primary',
        action: config.action,
    };
};

const handleConfirm = () => {
    if (confirmModal.value.action) {
        confirmModal.value.action();
    }
    confirmModal.value.show = false;
};

const approveEvidence = (evidenceId) => {
    openConfirm({
        title: 'Setujui Bukti Perbaikan (Evidence)',
        message: 'Apakah Anda yakin bukti fisik foto perbaikan toko ini sudah sesuai dengan standar dan siap disetujui?',
        confirmText: 'Ya, Setujui Bukti',
        type: 'success',
        action: () => router.patch(route('auditor.evidences.approve', evidenceId)),
    });
};

const closeFinding = () => {
    openConfirm({
        title: 'Tutup Temuan Audit (Close Finding)',
        message: 'Temuan yang sudah ditutup dinyatakan tuntas 100% dan seluruh tindak lanjut selesai. Lanjutkan penutupan?',
        confirmText: 'Ya, Tutup Temuan',
        type: 'success',
        action: () => router.patch(route('auditor.findings.close', props.finding.id)),
    });
};
</script>

<template>
    <AppLayout :title="`Finding Detail - ${finding.audit.audit_number}`">
        <Head :title="`Finding Detail - ${finding.audit.audit_number}`" />

        <!-- Top Breadcrumb & Actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <Link :href="route('auditor.audits.index')" class="hover:text-blue-600">My Audits</Link>
                    <span>/</span>
                    <Link :href="route('auditor.audits.show', finding.audit.id)" class="hover:text-blue-600">{{ finding.audit.audit_number }}</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-medium">Finding Detail</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-3">
                    Finding #{{ String(finding.id).padStart(4, '0') }}
                    <SeverityBadge :severity="finding.severity" />
                    <StatusBadge :status="finding.status" />
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <button
                    v-if="finding.can_close"
                    @click="closeFinding"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-medium rounded bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Tutup Finding (Close)
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Finding Info & Action Plan -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Section 1: Finding Details -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        1. Temuan Audit (Finding Information)
                    </h2>

                    <div class="grid grid-cols-2 gap-4 bg-gray-50/60 p-3 rounded border border-gray-100">
                        <div>
                            <span class="text-gray-500 font-medium">Kategori Audit:</span>
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
                        <p class="text-gray-900 leading-relaxed bg-gray-50 p-3 rounded border border-gray-200/70">{{ finding.finding }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="finding.auditor_opinion">
                            <div class="text-gray-500 font-medium mb-1">Opini Auditor:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50/50 p-3 rounded border border-gray-100">{{ finding.auditor_opinion }}</p>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium mb-1">Rekomendasi Perbaikan:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50/50 p-3 rounded border border-gray-100">{{ finding.recommendation }}</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Action Plan & Response Toko -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100 flex items-center justify-between">
                        <span>2. Tindak Lanjut & Action Plan Toko</span>
                        <StatusBadge v-if="finding.action_plan" :status="finding.action_plan.status" />
                    </h2>

                    <div v-if="finding.action_plan?.action_plan" class="space-y-4">
                        <div>
                            <div class="text-gray-500 font-medium mb-1">Rencana Tindakan (Action Plan):</div>
                            <p class="text-gray-900 leading-relaxed bg-gray-50/50 p-3 rounded border border-gray-100 font-medium">{{ finding.action_plan.action_plan }}</p>
                        </div>

                        <div v-if="finding.action_plan.response">
                            <div class="text-gray-500 font-medium mb-1">Response Toko:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50/50 p-3 rounded border border-gray-100">{{ finding.action_plan.response }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 bg-gray-50 p-3 rounded border border-gray-100">
                            <div>
                                <span class="text-gray-500 font-medium">PIC Perbaikan:</span>
                                <div class="font-semibold text-gray-900 mt-0.5">{{ finding.action_plan.pic || '—' }}</div>
                            </div>
                            <div>
                                <span class="text-gray-500 font-medium">Target Deadline:</span>
                                <div class="font-semibold text-gray-900 mt-0.5">{{ finding.action_plan.deadline || '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-6 text-gray-400 italic bg-gray-50 rounded border border-dashed border-gray-200">
                        Pihak toko / auditee belum mengisi action plan untuk temuan ini.
                    </div>
                </div>

                <!-- Section 3: Evidences & Verifications -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        3. Bukti Perbaikan (Evidences) & Verifikasi
                    </h2>

                    <div v-if="finding.evidences.length === 0" class="text-center py-6 text-gray-400 italic bg-gray-50 rounded border border-dashed border-gray-200">
                        Belum ada evidence yang diunggah oleh pihak toko.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="evidence in finding.evidences"
                            :key="evidence.id"
                            class="p-4 rounded border border-gray-200 bg-gray-50/30 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900">{{ evidence.description || 'Bukti Perbaikan' }}</span>
                                    <StatusBadge :status="evidence.verification_status" />
                                </div>
                                <div class="text-[11px] text-gray-500">
                                    Diunggah oleh: <span class="font-medium text-gray-700">{{ evidence.uploaded_by }}</span> • {{ evidence.uploaded_at }}
                                </div>
                                <div v-if="evidence.rejection_reason" class="text-[11px] text-red-600 font-medium mt-1">
                                    Alasan Penolakan: {{ evidence.rejection_reason }}
                                </div>
                                <div v-if="evidence.verified_by" class="text-[11px] text-gray-500">
                                    Diverifikasi oleh: {{ evidence.verified_by }}
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <a
                                    :href="evidence.file_url"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Lihat File
                                </a>

                                <template v-if="evidence.can_verify">
                                    <button
                                        @click="approveEvidence(evidence.id)"
                                        class="px-2.5 py-1.5 rounded bg-emerald-600 text-white hover:bg-emerald-700 font-medium transition-colors"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        @click="openRejectModal(evidence.id)"
                                        class="px-2.5 py-1.5 rounded bg-red-600 text-white hover:bg-red-700 font-medium transition-colors"
                                    >
                                        Reject
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Metadata Card & Workflow Indicator -->
            <div class="space-y-6">
                <!-- Severity Review Card (Auditor view) -->
                <div class="bg-white rounded border p-5 shadow-xs text-xs space-y-3" :class="finding.is_severity_locked ? 'border-emerald-200 bg-emerald-50/20' : 'border-amber-200 bg-amber-50/20'">
                    <div class="flex items-center justify-between pb-2 border-b" :class="finding.is_severity_locked ? 'border-emerald-100' : 'border-amber-100'">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800">
                            Review Severity
                        </h3>
                        <span
                            v-if="finding.is_severity_locked"
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-800 border border-gray-300"
                        >
                            Terkunci
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-300"
                        >
                            Pending Review
                        </span>
                    </div>

                    <div v-if="finding.is_severity_locked" class="space-y-1.5">
                        <div class="font-semibold text-emerald-900">
                            Severity Resmi: <span class="font-bold text-gray-900">{{ finding.severity }}</span> ({{ finding.severity_status }})
                        </div>
                        <div class="text-[11px] text-gray-600">
                            Direview oleh: <strong>{{ finding.severity_reviewed_by || 'Koordinator' }}</strong>
                            <span v-if="finding.severity_reviewed_at"> • {{ finding.severity_reviewed_at }}</span>
                        </div>
                        <div v-if="finding.severity_notes" class="text-[11px] text-gray-700 bg-white p-2 rounded border border-emerald-200/60 italic">
                            "{{ finding.severity_notes }}"
                        </div>
                        <div class="text-[10px] text-emerald-700 font-medium">
                            ✓ Severity telah disetujui & dikunci oleh Koordinator.
                        </div>
                    </div>

                    <div v-else class="space-y-1.5">
                        <div class="text-gray-800">
                            Usulan Severity: <strong>{{ finding.severity }}</strong>
                        </div>
                        <p class="text-[11px] text-gray-600">
                            Severity ini sedang menunggu peninjauan dan persetujuan dari Koordinator Audit.
                        </p>
                    </div>
                </div>

                <!-- Meta -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        Audit Meta
                    </h3>

                    <div>
                        <div class="text-gray-500">Nomor Audit</div>
                        <div class="font-mono font-semibold text-gray-900 mt-0.5">{{ finding.audit.audit_number }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Toko</div>
                        <div class="font-semibold text-gray-900 mt-0.5">{{ finding.store.name }}</div>
                        <div class="text-[11px] text-gray-500 font-mono">{{ finding.store.code }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Nominal Kerugian</div>
                        <div class="text-base font-bold text-gray-900 mt-0.5">{{ formatRupiah(finding.loss_amount) }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Status Finding</div>
                        <div class="mt-1"><StatusBadge :status="finding.status" /></div>
                    </div>
                </div>

                <!-- Workflow Step Tracker (Live Animated) -->
                <WorkflowTracker :status="finding.status" />
            </div>
        </div>

        <!-- Reject Evidence Modal -->
        <div
            v-if="rejectingEvidenceId"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl text-xs space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">Tolak Bukti Perbaikan (Evidence)</h3>
                <p class="text-gray-500">Jelaskan alasan penolakan agar pihak toko dapat melakukan perbaikan yang sesuai.</p>

                <form @submit.prevent="submitReject" class="space-y-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Alasan Penolakan <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="rejectForm.rejection_reason"
                            required
                            rows="4"
                            placeholder="Tuliskan catatan perbaikan atau alasan dokumen belum memenuhi syarat..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                        <div v-if="rejectForm.errors.rejection_reason" class="text-red-600 text-[11px] mt-1">{{ rejectForm.errors.rejection_reason }}</div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button
                            type="button"
                            @click="closeRejectModal"
                            class="px-3.5 py-1.5 rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-medium"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="rejectForm.processing"
                            class="px-4 py-1.5 rounded bg-red-600 text-white hover:bg-red-700 font-medium disabled:opacity-50"
                        >
                            {{ rejectForm.processing ? 'Menolak...' : 'Konfirmasi Tolak' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modern Action Confirmation Modal -->
        <ConfirmModal
            :show="confirmModal.show"
            :title="confirmModal.title"
            :message="confirmModal.message"
            :confirm-text="confirmModal.confirmText"
            :type="confirmModal.type"
            @confirm="handleConfirm"
            @close="confirmModal.show = false"
        />
    </AppLayout>
</template>
