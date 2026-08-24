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

const deleteFinding = () => {
    if (confirm('Hapus temuan audit ini?')) {
        router.delete(route('admin.findings.destroy', props.finding.id));
    }
};

// Form Review Severity (Koordinator)
const severityForm = useForm({
    severity: props.finding.severity,
    severity_notes: props.finding.severity_notes || '',
});

const submitSeverityReview = () => {
    severityForm.patch(route('admin.findings.review-severity', props.finding.id));
};
</script>

<template>
    <AppLayout :title="`Finding #${finding.id} - ${finding.audit.audit_number}`">
        <Head :title="`Finding #${finding.id} - ${finding.audit.audit_number}`" />

        <!-- Breadcrumbs & Actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <Link :href="route('admin.findings.index')" class="hover:text-blue-600">Findings</Link>
                    <span>/</span>
                    <Link :href="route('admin.audits.show', finding.audit.id)" class="hover:text-blue-600">{{ finding.audit.audit_number }}</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-medium">Finding #{{ finding.id }}</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-3">
                    Finding #{{ String(finding.id).padStart(4, '0') }}
                    <SeverityBadge :severity="finding.severity" />
                    <StatusBadge :status="finding.status" />
                </h1>
            </div>

            <button
                @click="deleteFinding"
                class="px-3.5 py-1.5 rounded border border-red-200 text-red-600 hover:bg-red-50 text-xs font-medium transition-colors"
            >
                Hapus Finding
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 text-xs">
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Detail Finding -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        1. Temuan Audit (Finding)
                    </h2>

                    <div class="grid grid-cols-2 gap-4 bg-gray-50 p-3 rounded border border-gray-100">
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
                        <p class="text-gray-900 leading-relaxed bg-gray-50 p-3 rounded border border-gray-200/70">{{ finding.finding }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="finding.auditor_opinion">
                            <div class="text-gray-500 font-medium mb-1">Opini Auditor:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50/50 p-2.5 rounded border border-gray-100">{{ finding.auditor_opinion }}</p>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium mb-1">Rekomendasi Perbaikan:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50/50 p-2.5 rounded border border-gray-100">{{ finding.recommendation }}</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Action Plan Toko -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100 flex items-center justify-between">
                        <span>2. Tindak Lanjut & Action Plan Toko</span>
                        <StatusBadge v-if="finding.action_plan" :status="finding.action_plan.status" />
                    </h2>

                    <div v-if="finding.action_plan?.action_plan" class="space-y-3">
                        <div>
                            <div class="text-gray-500 font-medium mb-1">Action Plan Toko:</div>
                            <p class="text-gray-900 leading-relaxed bg-gray-50 p-3 rounded border border-gray-100 font-medium">{{ finding.action_plan.action_plan }}</p>
                        </div>

                        <div v-if="finding.action_plan.response">
                            <div class="text-gray-500 font-medium mb-1">Response Toko:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50/50 p-2.5 rounded border border-gray-100">{{ finding.action_plan.response }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 bg-gray-50 p-3 rounded border border-gray-100">
                            <div>
                                <span class="text-gray-500 font-medium">PIC:</span>
                                <div class="font-semibold text-gray-900 mt-0.5">{{ finding.action_plan.pic || '-' }}</div>
                            </div>
                            <div>
                                <span class="text-gray-500 font-medium">Target Deadline:</span>
                                <div class="font-semibold text-gray-900 mt-0.5">{{ finding.action_plan.deadline || '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-6 text-gray-400 italic bg-gray-50 rounded border border-dashed border-gray-200">
                        Belum ada tindak lanjut yang diisi oleh toko.
                    </div>
                </div>

                <!-- 3. Evidences -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        3. Bukti Perbaikan (Evidences)
                    </h2>

                    <div v-if="finding.evidences.length === 0" class="text-center py-6 text-gray-400 italic bg-gray-50 rounded border border-dashed border-gray-200">
                        Belum ada bukti perbaikan yang diunggah.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="e in finding.evidences"
                            :key="e.id"
                            class="p-3.5 rounded border border-gray-200 bg-gray-50/40 flex items-center justify-between gap-3"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900">{{ e.description || 'Bukti Perbaikan' }}</span>
                                    <StatusBadge :status="e.verification_status" />
                                </div>
                                <div class="text-[11px] text-gray-500">
                                    Diunggah oleh: {{ e.uploaded_by }} • {{ e.uploaded_at }}
                                </div>
                                <div v-if="e.rejection_reason" class="text-[11px] text-red-600 font-medium">
                                    Alasan Penolakan: {{ e.rejection_reason }}
                                </div>
                            </div>

                            <a
                                :href="e.file_url"
                                target="_blank"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                            >
                                📄 Buka File
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Severity Review Card -->
                <div class="bg-white rounded border border-gray-300 p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
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

                    <div v-if="finding.is_severity_locked" class="bg-gray-50 p-3 rounded border border-gray-200 space-y-2">
                        <div class="text-gray-900 font-medium">
                            Status: <span class="font-bold">{{ finding.severity_status }}</span>
                        </div>
                        <div class="text-[11px] text-gray-600">
                            Direview oleh: <strong class="text-gray-900">{{ finding.severity_reviewed_by || 'Koordinator' }}</strong>
                            <span v-if="finding.severity_reviewed_at"> • {{ finding.severity_reviewed_at }}</span>
                        </div>
                        <div v-if="finding.severity_notes" class="text-[11px] text-gray-700 bg-white p-2 rounded border border-gray-200 italic">
                            "{{ finding.severity_notes }}"
                        </div>
                        <p class="text-[10px] text-gray-500 pt-1 border-t border-gray-200">
                            *Auditor tidak dapat lagi mengubah tingkat severity ini.
                        </p>
                    </div>

                    <form @submit.prevent="submitSeverityReview" class="space-y-3 pt-1">
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">
                                {{ finding.is_severity_locked ? 'Ubah Severity' : 'Tentukan / Setujui Severity' }} <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="severityForm.severity"
                                required
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-medium"
                            >
                                <option value="CRITICAL">CRITICAL (Kritis)</option>
                                <option value="MAJOR">MAJOR (Mayor / Berat)</option>
                                <option value="MINOR">MINOR (Minor / Ringan)</option>
                                <option value="OBSERVATION">OBSERVATION (Catatan Observasi)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Catatan Koordinator (Opsional)</label>
                            <textarea
                                v-model="severityForm.severity_notes"
                                rows="2"
                                placeholder="Alasan penyesuaian / persetujuan severity..."
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            :disabled="severityForm.processing"
                            class="w-full py-2 px-3 rounded bg-blue-600 text-white hover:bg-blue-700 font-medium text-xs shadow-xs disabled:opacity-50 flex items-center justify-center gap-1.5 transition-colors"
                        >
                            <span>{{ severityForm.processing ? 'Menyimpan...' : 'Simpan & Kunci Severity' }}</span>
                        </button>
                        <p class="text-[10px] text-gray-400 text-center">
                            Akan otomatis mengirim notifikasi WhatsApp ke Auditor bertugas.
                        </p>
                    </form>
                </div>

                <!-- Audit Info Card -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs space-y-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        Informasi Audit
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
                        <div class="text-gray-500">Auditor</div>
                        <div class="font-semibold text-gray-900 mt-0.5">{{ finding.auditor.name }}</div>
                        <div v-if="finding.auditor.phone" class="text-[11px] text-gray-500 font-mono">{{ finding.auditor.phone }}</div>
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
            </div>
        </div>
    </AppLayout>
</template>
