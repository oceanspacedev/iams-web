<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';

const props = defineProps({
    audit: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    sops: {
        type: Array,
        default: () => [],
    },
});

const isFindingModalOpen = ref(false);

const findingForm = useForm({
    category_id: '',
    sop_id: '',
    finding: '',
    loss_amount: '',
    auditor_opinion: '',
    recommendation: '',
    severity: 'MINOR',
    status: 'OPEN',
});

const openFindingModal = () => {
    findingForm.reset();
    findingForm.severity = 'MINOR';
    findingForm.status = 'OPEN';
    isFindingModalOpen.value = true;
};

const closeFindingModal = () => {
    isFindingModalOpen.value = false;
    findingForm.reset();
};

const submitFinding = () => {
    findingForm.post(route('admin.audits.findings.store', props.audit.id), {
        onSuccess: () => closeFindingModal(),
    });
};

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};

const deleteAudit = () => {
    if (confirm(`Hapus audit ${props.audit.audit_number}?`)) {
        router.delete(route('admin.audits.destroy', props.audit.id));
    }
};
</script>

<template>
    <AppLayout :title="`Detail Audit ${audit.audit_number}`">
        <Head :title="`Detail Audit ${audit.audit_number}`" />

        <!-- Breadcrumb & Top Actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <Link :href="route('admin.audits.index')" class="hover:text-blue-600">Audits</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-medium">{{ audit.audit_number }}</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-3">
                    {{ audit.audit_number }}
                    <StatusBadge :status="audit.status" />
                </h1>
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="openFindingModal"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
                >
                    + Tambah Finding
                </button>
                <Link
                    :href="route('admin.audits.edit', audit.id)"
                    class="px-3 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors"
                >
                    Edit Audit
                </Link>
                <button
                    @click="deleteAudit"
                    class="px-3 py-2 text-xs font-medium rounded border border-red-200 text-red-600 hover:bg-red-50 transition-colors"
                >
                    Hapus
                </button>
            </div>
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
                <span class="text-gray-500 font-medium">Catatan Audit: </span>
                <span class="text-gray-700">{{ audit.notes }}</span>
            </div>
        </div>

        <!-- Findings Section -->
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">
                Daftar Temuan Audit ({{ audit.findings.length }})
            </h2>
        </div>

        <div v-if="audit.findings.length === 0" class="bg-white rounded border border-gray-200 p-8 text-center text-xs text-gray-500">
            Belum ada temuan untuk audit ini. Klik tombol "+ Tambah Finding" untuk mencatat temuan baru.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="(finding, index) in audit.findings"
                :key="finding.id"
                class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-3"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="font-mono font-bold text-gray-400">#{{ String(index + 1).padStart(3, '0') }}</span>
                        <span class="font-semibold text-gray-900">{{ finding.category }}</span>
                        <SeverityBadge :severity="finding.severity" />
                        <StatusBadge :status="finding.status" />
                    </div>

                    <Link
                        :href="route('admin.findings.show', finding.id)"
                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        Buka Detail Temuan →
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 space-y-2">
                        <div>
                            <div class="text-gray-500 font-medium mb-1">Temuan:</div>
                            <p class="text-gray-900 leading-relaxed bg-gray-50/60 p-2.5 rounded border border-gray-100">{{ finding.finding }}</p>
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
                            <div class="text-gray-500 font-medium">SOP / SE Terkait</div>
                            <div class="text-gray-800 mt-0.5">{{ finding.sop ? `${finding.sop.code} - ${finding.sop.title}` : '—' }}</div>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium">Action Plan Toko</div>
                            <div v-if="finding.action_plan?.action_plan" class="text-emerald-700 font-medium mt-0.5">
                                ✓ Sudah diisi (PIC: {{ finding.action_plan.pic || '-' }})
                            </div>
                            <div v-else class="text-red-600 italic mt-0.5">Belum diisi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Finding Modal -->
        <div v-if="isFindingModalOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full p-6 shadow-xl text-xs space-y-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-sm font-semibold text-gray-900">Tambah Finding ke Audit {{ audit.audit_number }}</h3>

                <form @submit.prevent="submitFinding" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                            <select
                                v-model="findingForm.category_id"
                                required
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="" disabled>Pilih Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 mb-1">SOP / SE Acuan</label>
                            <select
                                v-model="findingForm.sop_id"
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Pilih SOP/SE (Opsional)</option>
                                <option v-for="sop in sops" :key="sop.id" :value="sop.id">{{ sop.code }} - {{ sop.title }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Severity <span class="text-red-500">*</span></label>
                            <select
                                v-model="findingForm.severity"
                                required
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="CRITICAL">CRITICAL</option>
                                <option value="MAJOR">MAJOR</option>
                                <option value="MINOR">MINOR</option>
                                <option value="OBSERVATION">OBSERVATION</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Nominal Kerugian (Rp)</label>
                            <input
                                v-model="findingForm.loss_amount"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Status Awal</label>
                            <select
                                v-model="findingForm.status"
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="OPEN">Open</option>
                                <option value="IN_PROGRESS">In Progress</option>
                                <option value="WAITING_VERIFICATION">Waiting Verification</option>
                                <option value="VERIFIED">Verified</option>
                                <option value="CLOSED">Closed</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Uraian Temuan (Finding) <span class="text-red-500">*</span></label>
                        <textarea
                            v-model="findingForm.finding"
                            rows="3"
                            required
                            placeholder="Jelaskan kondisi temuan secara faktual..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Opini Auditor</label>
                            <textarea
                                v-model="findingForm.auditor_opinion"
                                rows="2"
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Rekomendasi Perbaikan <span class="text-red-500">*</span></label>
                            <textarea
                                v-model="findingForm.recommendation"
                                rows="2"
                                required
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button
                            type="button"
                            @click="closeFindingModal"
                            class="px-3.5 py-1.5 rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-medium"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="findingForm.processing"
                            class="px-4 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 font-medium disabled:opacity-50"
                        >
                            {{ findingForm.processing ? 'Menyimpan...' : 'Simpan Finding' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
