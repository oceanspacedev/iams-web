<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    pending_evidences: {
        type: Array,
        default: () => [],
    },
});

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
</script>

<template>
    <AppLayout title="Antrean Verifikasi Evidence">
        <Head title="Verification Queue" />

        <!-- Header -->
        <div class="mb-4 sm:mb-6">
            <h1 class="text-lg sm:text-xl font-semibold text-gray-900 tracking-tight">Antrean Verifikasi Evidence</h1>
            <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Daftar bukti perbaikan yang diunggah oleh toko dan menunggu verifikasi Anda</p>
        </div>

        <!-- Clean Dual View (Mobile Cards vs Desktop Table) -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            
            <!-- 1. MOBILE CARD VIEW (Visible on mobile screens) -->
            <div class="block md:hidden divide-y divide-gray-200">
                <div v-if="pending_evidences.length === 0" class="p-8 text-center text-gray-400 text-xs">
                    Tidak ada evidence yang menunggu verifikasi saat ini.
                </div>
                <div
                    v-for="e in pending_evidences"
                    :key="'m-ev-' + e.id"
                    class="p-4 space-y-2.5"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900 text-xs">{{ e.store }}</div>
                            <div class="font-mono text-[10px] text-gray-500 mt-0.5">{{ e.audit }}</div>
                        </div>
                    </div>

                    <div class="text-xs">
                        <Link
                            :href="route('auditor.findings.show', e.finding_id)"
                            class="font-medium text-gray-900 hover:text-blue-600 hover:underline line-clamp-2"
                        >
                            {{ e.finding }}
                        </Link>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded border border-gray-100 text-[11px] text-gray-700">
                        <span class="font-medium text-gray-500">Keterangan:</span> {{ e.description || 'Bukti perbaikan' }}
                    </div>

                    <div class="text-[10px] text-gray-500 flex items-center justify-between pt-1 border-t border-gray-100">
                        <span>Oleh: <strong class="text-gray-700 font-medium">{{ e.uploaded_by }}</strong></span>
                        <span>{{ e.uploaded_at }}</span>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                        <a
                            :href="e.file_url"
                            target="_blank"
                            class="px-2.5 py-1 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            Lihat File
                        </a>
                        <button
                            @click="approveEvidence(e.id)"
                            class="px-2.5 py-1 text-xs font-medium rounded bg-emerald-600 text-white hover:bg-emerald-700 transition-colors cursor-pointer"
                        >
                            Approve
                        </button>
                        <button
                            @click="openRejectModal(e.id)"
                            class="px-2.5 py-1 text-xs font-medium rounded bg-red-600 text-white hover:bg-red-700 transition-colors cursor-pointer"
                        >
                            Reject
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. DESKTOP TABLE VIEW (Visible on tablet & desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3.5">No. Audit & Toko</th>
                            <th class="px-5 py-3.5">Temuan Finding</th>
                            <th class="px-5 py-3.5">Keterangan Evidence</th>
                            <th class="px-5 py-3.5">Diunggah Oleh</th>
                            <th class="px-5 py-3.5 text-right">Aksi Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="pending_evidences.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-gray-500">
                                Tidak ada evidence yang menunggu verifikasi saat ini.
                            </td>
                        </tr>
                        <tr
                            v-for="e in pending_evidences"
                            :key="e.id"
                            class="hover:bg-gray-50/70 transition-colors"
                        >
                            <td class="px-5 py-3.5">
                                <div class="font-mono font-semibold text-gray-900">{{ e.audit }}</div>
                                <div class="text-[11px] text-gray-500 font-medium">{{ e.store }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <Link
                                    :href="route('auditor.findings.show', e.finding_id)"
                                    class="font-medium text-gray-900 hover:text-blue-600 hover:underline line-clamp-2"
                                >
                                    {{ e.finding }}
                                </Link>
                            </td>
                            <td class="px-5 py-3.5 text-gray-700">
                                {{ e.description || 'Bukti perbaikan' }}
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">
                                <div>{{ e.uploaded_by }}</div>
                                <div class="text-[10px] text-gray-400">{{ e.uploaded_at }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-2">
                                <a
                                    :href="e.file_url"
                                    target="_blank"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    Lihat File
                                </a>
                                <button
                                    @click="approveEvidence(e.id)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-emerald-600 text-white hover:bg-emerald-700 transition-colors cursor-pointer"
                                >
                                    Approve
                                </button>
                                <button
                                    @click="openRejectModal(e.id)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-red-600 text-white hover:bg-red-700 transition-colors cursor-pointer"
                                >
                                    Reject
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reject Modal -->
        <div
            v-if="rejectingEvidenceId"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl text-xs space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">Tolak Bukti Perbaikan (Evidence)</h3>
                <p class="text-gray-500">Jelaskan alasan penolakan agar pihak toko dapat memperbaiki bukti yang diunggah.</p>

                <form @submit.prevent="submitReject" class="space-y-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Alasan Penolakan <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="rejectForm.rejection_reason"
                            required
                            rows="4"
                            placeholder="Tuliskan catatan perbaikan atau alasan belum memenuhi syarat..."
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
