<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    audit: {
        type: Object,
        required: true,
    },
});

const uploadDocModalOpen = ref(false);
const docForm = useForm({
    document_type: 'LHP',
    title: '',
    file: null,
    notes: '',
});

const submitDoc = () => {
    docForm.post(route('coordinator.audits.documents.store', props.audit.id), {
        onSuccess: () => {
            uploadDocModalOpen.value = false;
            docForm.reset();
        },
    });
};

const deleteDoc = (doc) => {
    openConfirm({
        title: 'Hapus Dokumen Audit',
        message: `Apakah Anda yakin ingin menghapus dokumen "${doc.title}"?`,
        confirmText: 'Ya, Hapus Dokumen',
        type: 'danger',
        action: () => router.delete(route('coordinator.documents.destroy', doc.id)),
    });
};

const deleteFinding = (finding) => {
    openConfirm({
        title: 'Hapus Temuan Audit (Finding)',
        message: 'Apakah Anda yakin ingin menghapus temuan audit ini?',
        confirmText: 'Ya, Hapus Finding',
        type: 'danger',
        action: () => router.delete(route('coordinator.findings.destroy', finding.id)),
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

const deleteAudit = () => {
    openConfirm({
        title: 'Hapus Penugasan Audit',
        message: `Apakah Anda yakin ingin menghapus audit ${props.audit.audit_number}? Seluruh temuan terkait akan ikut terhapus.`,
        confirmText: 'Ya, Hapus Audit',
        type: 'danger',
        action: () => router.delete(route('coordinator.audits.destroy', props.audit.id)),
    });
};

const sendNotificationNow = (notif) => {
    openConfirm({
        title: `Kirim Notifikasi ${notif.rule_name}`,
        message: `Kirim notifikasi WhatsApp sekarang untuk aturan "${notif.rule_name}" ke penerima terkait?`,
        confirmText: 'Kirim Sekarang',
        type: 'primary',
        action: () => router.post(route('coordinator.audits.notifications.send-now', notif.id), {}, { preserveScroll: true }),
    });
};
</script>

<template>
    <AppLayout :title="`Detail Audit ${audit.audit_number}`">
        <Head :title="`Detail Audit ${audit.audit_number} — Koordinator`" />

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <Link :href="route('coordinator.audits.index')" class="hover:text-blue-600">Audits</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-medium">{{ audit.audit_number }}</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-2.5 flex-wrap">
                    {{ audit.audit_number }}
                    <span
                        v-if="audit.category"
                        class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold tracking-wide border"
                        :class="{
                            'bg-emerald-50 text-emerald-800 border-emerald-200': audit.category.includes('Retail'),
                            'bg-amber-50 text-amber-800 border-amber-200': audit.category.includes('Finance'),
                            'bg-indigo-50 text-indigo-800 border-indigo-200': audit.category.includes('Distribusi'),
                            'bg-slate-100 text-slate-700 border-slate-200': !audit.category
                        }"
                    >
                        {{ audit.category }}
                    </span>
                    <StatusBadge :status="audit.status" />
                </h1>
            </div>

            <div class="flex items-center flex-wrap gap-2">
                <Link
                    :href="route('coordinator.findings.create', audit.id)"
                    class="inline-flex items-center px-3.5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
                >
                    + Tambah Finding Baru
                </Link>
                <button
                    @click="uploadDocModalOpen = true"
                    type="button"
                    class="px-3.5 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-xs cursor-pointer"
                >
                    Upload LHP / BAP
                </button>
                <Link
                    :href="route('coordinator.finding-qualities.create', { audit_id: audit.id })"
                    class="px-3.5 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-xs"
                >
                    + Lapor Finding Quality
                </Link>
                <Link
                    :href="route('coordinator.audits.edit', audit.id)"
                    class="px-3.5 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-xs"
                >
                    Edit Audit
                </Link>
                <button
                    @click="deleteAudit"
                    class="px-3.5 py-2 text-xs font-medium rounded border border-red-200 text-red-600 hover:bg-red-50 transition-colors shadow-xs cursor-pointer"
                >
                    Hapus
                </button>
            </div>
        </div>

        <!-- Grid Audit Info & Jadwal Notifikasi -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Audit Information (2 cols) -->
            <div class="lg:col-span-2 bg-white rounded border border-gray-200 p-5 text-xs">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4 pb-2 border-b border-gray-100">
                    Informasi Pelaksanaan Audit
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <div class="text-gray-500 font-medium">Kategori Audit</div>
                        <div class="text-gray-900 font-semibold mt-1">{{ audit.category || 'Belum Ditentukan' }}</div>
                        <div class="text-[11px] text-gray-500 tabular-nums">{{ audit.title || 'Audit Lapangan' }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500 font-medium">Toko Sasaran</div>
                        <div class="text-gray-900 font-semibold mt-1">{{ audit.store.name }}</div>
                        <div class="text-[11px] text-gray-500 tabular-nums">{{ audit.store.code }} ({{ audit.store.area || '-' }})</div>
                    </div>

                    <div>
                        <div class="text-gray-500 font-medium">Tanggal Audit</div>
                        <div class="text-gray-900 font-semibold mt-1">{{ audit.audit_date }}</div>
                        <div class="text-[11px] text-gray-500">Pukul {{ audit.audit_time }} WIB</div>
                    </div>

                    <div>
                        <div class="text-gray-500 font-medium">Lead Auditor</div>
                        <div class="text-gray-900 font-semibold mt-1">{{ audit.auditor.name }}</div>
                        <div class="text-[11px] text-gray-500">{{ audit.auditor.email }}</div>
                    </div>
                </div>

                <div v-if="audit.location" class="mt-4 pt-3 border-t border-gray-100">
                    <span class="text-gray-500 font-medium">Lokasi: </span>
                    <span class="text-gray-800 font-medium">{{ audit.location }}</span>
                </div>

                <div v-if="audit.notes" class="mt-2">
                    <span class="text-gray-500 font-medium">Catatan: </span>
                    <span class="text-gray-700">{{ audit.notes }}</span>
                </div>
            </div>

            <!-- Jadwal Notifikasi Otomatis (1 col) -->
            <div class="bg-white rounded border border-gray-200 p-5 text-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                            Jadwal Notifikasi
                        </h2>
                        <span class="text-[11px] text-gray-400">Otomatis (H-N)</span>
                    </div>

                    <div v-if="!audit.notifications || audit.notifications.length === 0" class="text-center py-6 text-gray-400">
                        Belum ada jadwal notifikasi.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="notif in audit.notifications"
                            :key="notif.id"
                            class="flex items-center justify-between p-2.5 rounded border border-gray-100 bg-gray-50/50 hover:bg-gray-50 transition-colors"
                        >
                            <div class="min-w-0 pr-2">
                                <div class="font-semibold text-gray-900 flex items-center gap-1.5">
                                    <span>{{ notif.rule_name }}</span>
                                    <span class="text-[10px] text-gray-400 font-normal uppercase">({{ notif.channel }})</span>
                                </div>
                                <div class="text-[11px] text-gray-500 tabular-nums mt-0.5">
                                    {{ notif.scheduled_at }}
                                </div>
                                <div v-if="notif.sent_at" class="text-[10px] text-emerald-600 mt-0.5">
                                    Terkirim: {{ notif.sent_at }}
                                </div>
                                <div v-if="notif.error_message" class="text-[10px] text-red-500 mt-0.5 truncate max-w-[180px]" :title="notif.error_message">
                                    Error: {{ notif.error_message }}
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <!-- Status Badge -->
                                <span
                                    class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold border"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 border-emerald-200': notif.status === 'SENT',
                                        'bg-amber-50 text-amber-700 border-amber-200': notif.status === 'PENDING',
                                        'bg-red-50 text-red-700 border-red-200': notif.status === 'FAILED',
                                        'bg-gray-100 text-gray-500 border-gray-200': notif.status === 'INACTIVE',
                                    }"
                                >
                                    <span v-if="notif.status === 'SENT'">Terkirim</span>
                                    <span v-else-if="notif.status === 'PENDING'">Menunggu</span>
                                    <span v-else-if="notif.status === 'FAILED'">Gagal</span>
                                    <span v-else-if="notif.status === 'INACTIVE'">Nonaktif</span>
                                    <span v-else>{{ notif.status }}</span>
                                </span>

                                <!-- Send Now Trigger Button -->
                                <button
                                    v-if="notif.status !== 'SENT' && notif.status !== 'INACTIVE'"
                                    @click="sendNotificationNow(notif)"
                                    type="button"
                                    class="p-1 rounded border border-gray-300 bg-white text-gray-600 hover:text-blue-600 hover:bg-blue-50"
                                    title="Kirim notifikasi sekarang"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-100 text-[10px] text-gray-400 text-center">
                    Jadwal dihitung otomatis dari Tanggal Audit.
                </div>
            </div>
        </div>

        <!-- Dokumen LHP & BAP Section -->
        <div class="bg-white rounded border border-gray-200 p-5 mb-8 text-xs">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                        Dokumen Bukti Lapangan (LHP &amp; BAP Bertanda Tangan)
                    </h2>
                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-gray-100 text-gray-700 border border-gray-200">
                        {{ audit.documents?.length || 0 }} File
                    </span>
                </div>

                <button
                    @click="uploadDocModalOpen = true"
                    type="button"
                    class="px-3 py-1.5 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs cursor-pointer"
                >
                    + Unggah LHP / BAP
                </button>
            </div>

            <div v-if="!audit.documents || audit.documents.length === 0" class="text-center py-6 text-gray-400 bg-gray-50/50 rounded border border-dashed border-gray-200">
                Belum ada dokumen LHP / BAP bertanda tangan yang diunggah untuk audit ini.
            </div>

            <div v-else class="overflow-x-auto border border-gray-200 rounded">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2.5">Tipe</th>
                            <th class="px-4 py-2.5">Judul Dokumen</th>
                            <th class="px-4 py-2.5">File</th>
                            <th class="px-4 py-2.5">Diunggah Oleh</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="doc in audit.documents" :key="doc.id" class="hover:bg-gray-50/70">
                            <td class="px-4 py-2.5">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-gray-100 text-gray-700 border border-gray-200">
                                    {{ doc.document_type }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 font-medium text-gray-900">
                                {{ doc.title }}
                                <div v-if="doc.notes" class="text-[11px] font-normal text-gray-500 mt-0.5">{{ doc.notes }}</div>
                            </td>
                            <td class="px-4 py-2.5 text-gray-600 tabular-nums">{{ doc.file_name }} ({{ doc.file_size }})</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ doc.uploaded_by }} · <span class="text-gray-400 tabular-nums">{{ doc.created_at }}</span></td>
                            <td class="px-4 py-2.5 text-right space-x-2 whitespace-nowrap">
                                <a :href="doc.file_url" target="_blank" class="text-blue-600 hover:underline font-medium">Lihat</a>
                                <button type="button" @click="deleteDoc(doc)" class="text-red-600 hover:underline font-medium cursor-pointer">Hapus</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Findings Monitoring -->
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">
                Monitoring Temuan &amp; Review Severity ({{ audit.findings.length }})
            </h2>
            <Link
                :href="route('coordinator.findings.create', audit.id)"
                class="px-3.5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
            >
                + Tambah Finding Baru
            </Link>
        </div>

        <div v-if="audit.findings.length === 0" class="bg-white rounded border border-gray-200 p-8 text-center text-xs text-gray-500">
            Belum ada temuan untuk audit ini. Klik tombol "+ Tambah Finding Baru" untuk mencatat temuan audit.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="(finding, index) in audit.findings"
                :key="finding.id"
                class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-3"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="tabular-nums font-bold text-gray-400">#{{ String(index + 1).padStart(3, '0') }}</span>
                        <span class="font-semibold text-gray-900">{{ finding.category }}</span>
                        <SeverityBadge :severity="finding.severity" />
                        <StatusBadge :status="finding.status" />
                    </div>

                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <Link
                            :href="route('coordinator.findings.show', finding.id)"
                            class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                        >
                            Review Severity &amp; Detail
                        </Link>
                        <button
                            type="button"
                            @click="deleteFinding(finding)"
                            class="px-2.5 py-1 text-xs rounded border border-red-200 text-red-600 hover:bg-red-50 font-medium cursor-pointer"
                        >
                            Hapus
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-gray-500 font-medium mb-1">Uraian Temuan:</div>
                        <p class="text-gray-900 leading-relaxed">{{ finding.finding }}</p>
                    </div>

                    <div>
                        <div class="text-gray-500 font-medium mb-1">Rekomendasi Perbaikan:</div>
                        <p class="text-gray-700 leading-relaxed">{{ finding.recommendation }}</p>
                    </div>
                </div>

                <div v-if="finding.sop" class="pt-2 text-[11px] text-gray-500">
                    <span class="font-medium">SOP Terkait: </span>
                    <span>{{ finding.sop.code }} - {{ finding.sop.title }}</span>
                </div>

                <div v-if="finding.loss_amount > 0 || finding.surplus_amount > 0" class="pt-1 flex flex-wrap gap-x-4 text-[11px] font-semibold">
                    <span v-if="finding.loss_amount > 0" class="text-red-600">NBH: {{ formatRupiah(finding.loss_amount) }}</span>
                    <span v-if="finding.surplus_amount > 0" class="text-blue-700">NBL: {{ formatRupiah(finding.surplus_amount) }}</span>
                </div>
            </div>
        </div>

        <!-- Modal Upload Dokumen LHP / BAP -->
        <div
            v-if="uploadDocModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
        >
            <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl border border-gray-200 text-xs">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Unggah Dokumen LHP / BAP</h3>
                    <button @click="uploadDocModalOpen = false" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
                </div>

                <form @submit.prevent="submitDoc" class="space-y-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Jenis Dokumen <span class="text-red-500">*</span></label>
                        <select v-model="docForm.document_type" required class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="LHP">LHP (Lembar Hasil Pemeriksaan)</option>
                            <option value="BAP">BAP (Berita Acara Pemeriksaan)</option>
                            <option value="OTHER">Dokumen Pendukung Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Judul Dokumen <span class="text-red-500">*</span></label>
                        <input
                            v-model="docForm.title"
                            type="text"
                            required
                            placeholder="Contoh: LHP Bertanda Tangan Kepala Toko"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Pilih File (PDF / Gambar / Excel, Maks. 10MB) <span class="text-red-500">*</span></label>
                        <input
                            type="file"
                            required
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                            @change="e => docForm.file = e.target.files[0]"
                            class="w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                        />
                        <div v-if="docForm.errors.file" class="text-red-600 text-[11px] mt-1">{{ docForm.errors.file }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                        <textarea
                            v-model="docForm.notes"
                            rows="2"
                            placeholder="Keterangan tambahan dokumen..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button
                            type="button"
                            @click="uploadDocModalOpen = false"
                            class="px-3.5 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="docForm.processing"
                            class="px-4 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 font-medium disabled:opacity-50"
                        >
                            {{ docForm.processing ? 'Mengunggah...' : 'Simpan Dokumen' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Confirm Modal -->
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
