<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';

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

// Action Plan Form
const actionPlanForm = useForm({
    action_plan: props.finding.action_plan?.action_plan || '',
    response: props.finding.action_plan?.response || '',
    pic: props.finding.action_plan?.pic || '',
    deadline: props.finding.action_plan?.deadline || '',
});

const submitActionPlan = () => {
    actionPlanForm.post(route('auditee.action-plans.store', props.finding.id), {
        preserveScroll: true,
    });
};

// Evidence Upload Form
const evidenceForm = useForm({
    file: null,
    description: '',
});

const fileInputRef = ref(null);

const handleFileChange = (e) => {
    evidenceForm.file = e.target.files[0];
};

const submitEvidence = () => {
    evidenceForm.post(route('auditee.evidences.store', props.finding.id), {
        preserveScroll: true,
        onSuccess: () => {
            evidenceForm.reset();
            if (fileInputRef.value) fileInputRef.value.value = '';
        },
    });
};

const deleteEvidence = (evidenceId) => {
    if (confirm('Hapus bukti perbaikan ini?')) {
        router.delete(route('auditee.evidences.destroy', evidenceId), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout :title="`Tindak Lanjut Finding #${finding.id}`">
        <Head :title="`Tindak Lanjut Finding #${finding.id}`" />

        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('auditee.audits.index')" class="hover:text-blue-600">Audit Toko</Link>
                <span>/</span>
                <Link :href="route('auditee.audits.show', finding.audit.id)" class="hover:text-blue-600">{{ finding.audit.audit_number }}</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">Finding Detail & Tindak Lanjut</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-3">
                Finding #{{ String(finding.id).padStart(4, '0') }}
                <SeverityBadge :severity="finding.severity" />
                <StatusBadge :status="finding.status" />
            </h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Finding Info (Read-Only) + Action Plan Form + Evidence Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Detail Temuan Auditor (Read Only) -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100 flex items-center justify-between">
                        <span>1. Informasi Temuan Auditor (Read-Only)</span>
                        <span class="text-[11px] text-gray-400 font-normal">Ditetapkan oleh Auditor</span>
                    </h2>

                    <div class="grid grid-cols-2 gap-4 bg-gray-50/70 p-3 rounded border border-gray-100">
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
                        <p class="text-gray-900 leading-relaxed bg-gray-50 p-3 rounded border border-gray-200/70 font-medium">{{ finding.finding }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="finding.auditor_opinion">
                            <div class="text-gray-500 font-medium mb-1">Opini Auditor:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50/50 p-2.5 rounded border border-gray-100">{{ finding.auditor_opinion }}</p>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium mb-1">Rekomendasi Perbaikan:</div>
                            <p class="text-blue-900 font-medium leading-relaxed bg-blue-50/40 p-2.5 rounded border border-blue-100">{{ finding.recommendation }}</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Formulir Action Plan & Response Toko -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100 flex items-center justify-between">
                        <span>2. Rencana Tindak Lanjut (Action Plan)</span>
                        <StatusBadge v-if="finding.action_plan" :status="finding.action_plan.status" />
                    </h2>

                    <form @submit.prevent="submitActionPlan" class="space-y-4">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1.5">
                                Rencana Perbaikan (Action Plan) <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="actionPlanForm.action_plan"
                                rows="3"
                                required
                                :disabled="!finding.can_edit_action_plan"
                                placeholder="Jelaskan langkah konkret perbaikan yang akan/sedang dilakukan toko..."
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                            ></textarea>
                            <div v-if="actionPlanForm.errors.action_plan" class="text-red-600 text-[11px] mt-1">{{ actionPlanForm.errors.action_plan }}</div>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 mb-1.5">
                                Tanggapan / Response Toko <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="actionPlanForm.response"
                                rows="3"
                                required
                                :disabled="!finding.can_edit_action_plan"
                                placeholder="Tanggapan toko mengenai temuan ini..."
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                            ></textarea>
                            <div v-if="actionPlanForm.errors.response" class="text-red-600 text-[11px] mt-1">{{ actionPlanForm.errors.response }}</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-gray-700 mb-1.5">
                                    PIC Perbaikan <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="actionPlanForm.pic"
                                    type="text"
                                    required
                                    :disabled="!finding.can_edit_action_plan"
                                    placeholder="Nama penanggung jawab toko..."
                                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                                />
                                <div v-if="actionPlanForm.errors.pic" class="text-red-600 text-[11px] mt-1">{{ actionPlanForm.errors.pic }}</div>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-700 mb-1.5">
                                    Target Deadline <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="actionPlanForm.deadline"
                                    type="date"
                                    required
                                    :disabled="!finding.can_edit_action_plan"
                                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                                />
                                <div v-if="actionPlanForm.errors.deadline" class="text-red-600 text-[11px] mt-1">{{ actionPlanForm.errors.deadline }}</div>
                            </div>
                        </div>

                        <div v-if="finding.can_edit_action_plan" class="pt-2 flex justify-end">
                            <button
                                type="submit"
                                :disabled="actionPlanForm.processing"
                                class="px-4 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 transition-colors shadow-xs"
                            >
                                {{ actionPlanForm.processing ? 'Menyimpan...' : 'Simpan Action Plan' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 3. Upload & Bukti Perbaikan (Evidences) -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        3. Bukti Perbaikan (Evidence)
                    </h2>

                    <!-- Upload Form (if open/in progress) -->
                    <div v-if="finding.can_upload_evidence" class="bg-gray-50/70 p-4 rounded border border-gray-200 space-y-3">
                        <div class="font-semibold text-gray-900">Unggah Bukti Baru (Foto / Dokumen)</div>
                        <form @submit.prevent="submitEvidence" class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-gray-700 mb-1">File Bukti <span class="text-red-500">*</span></label>
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        required
                                        accept=".jpg,.jpeg,.png,.pdf,.docx,.xlsx"
                                        @change="handleFileChange"
                                        class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border file:border-gray-300 file:text-xs file:font-medium file:bg-white file:text-gray-700 hover:file:bg-gray-50"
                                    />
                                    <div v-if="evidenceForm.errors.file" class="text-red-600 text-[11px] mt-1">{{ evidenceForm.errors.file }}</div>
                                </div>

                                <div>
                                    <label class="block font-medium text-gray-700 mb-1">Keterangan Bukti</label>
                                    <input
                                        v-model="evidenceForm.description"
                                        type="text"
                                        placeholder="Contoh: Foto fisik stok ulang, Berita Acara..."
                                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <div v-if="evidenceForm.errors.description" class="text-red-600 text-[11px] mt-1">{{ evidenceForm.errors.description }}</div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="evidenceForm.processing"
                                    class="px-4 py-1.5 text-xs font-medium rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 transition-colors shadow-xs"
                                >
                                    {{ evidenceForm.processing ? 'Mengunggah...' : 'Unggah Bukti' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Evidences List -->
                    <div v-if="finding.evidences.length === 0" class="text-center py-6 text-gray-400 italic bg-gray-50 rounded border border-dashed border-gray-200">
                        Belum ada bukti perbaikan yang diunggah.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="e in finding.evidences"
                            :key="e.id"
                            class="p-3.5 rounded border border-gray-200 bg-gray-50/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900">{{ e.description || 'Bukti Perbaikan' }}</span>
                                    <StatusBadge :status="e.verification_status" />
                                </div>
                                <div class="text-[11px] text-gray-500">
                                    Diupload pada: {{ e.uploaded_at }}
                                </div>
                                <div v-if="e.rejection_reason" class="text-[11px] text-red-600 font-medium">
                                    Catatan Penolakan Auditor: {{ e.rejection_reason }}
                                </div>
                                <div v-if="e.verified_by" class="text-[11px] text-gray-600">
                                    Diverifikasi oleh: {{ e.verified_by }}
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a
                                    :href="e.file_url"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Lihat File
                                </a>
                                <button
                                    v-if="e.can_delete"
                                    @click="deleteEvidence(e.id)"
                                    class="px-2.5 py-1 text-xs rounded border border-red-200 text-red-600 hover:bg-red-50 font-medium transition-colors"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Metadata & Workflow Status -->
            <div class="space-y-6">
                <!-- Meta -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        Informasi Toko & Audit
                    </h3>

                    <div>
                        <div class="text-gray-500">Nomor Audit</div>
                        <div class="font-mono font-semibold text-gray-900 mt-0.5">{{ finding.audit.audit_number }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Toko</div>
                        <div class="font-semibold text-gray-900 mt-0.5">{{ finding.store.name }} ({{ finding.store.code }})</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Nominal Kerugian</div>
                        <div class="text-base font-bold text-gray-900 mt-0.5">{{ formatRupiah(finding.loss_amount) }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Status Finding Saat Ini</div>
                        <div class="mt-1"><StatusBadge :status="finding.status" /></div>
                    </div>
                </div>

                <!-- Workflow Step Tracker -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100 mb-4">
                        Workflow Status
                    </h3>

                    <div class="space-y-3">
                        <div
                            v-for="step in ['OPEN', 'IN_PROGRESS', 'WAITING_VERIFICATION', 'VERIFIED', 'CLOSED']"
                            :key="step"
                            class="flex items-center gap-2.5"
                            :class="finding.status === step ? 'font-semibold text-blue-700' : 'text-gray-400'"
                        >
                            <div
                                class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]"
                                :class="finding.status === step ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500 border border-gray-200'"
                            >
                                ✓
                            </div>
                            <span>{{ step.replace('_', ' ') }}</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 text-[11px] text-gray-500">
                        * Catatan: Penutupan finding (CLOSED) hanya dapat dilakukan oleh Auditor setelah verifikasi disetujui.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
