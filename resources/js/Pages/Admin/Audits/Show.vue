<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
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
        action: () => router.delete(route('admin.audits.destroy', props.audit.id)),
    });
};

const sendNotificationNow = (notif) => {
    openConfirm({
        title: `Kirim Notifikasi ${notif.rule_name}`,
        message: `Kirim notifikasi WhatsApp sekarang untuk aturan "${notif.rule_name}" ke penerima terkait?`,
        confirmText: 'Kirim Sekarang',
        type: 'primary',
        action: () => router.post(route('admin.audits.notifications.send-now', notif.id), {}, { preserveScroll: true }),
    });
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

        <!-- Grid Audit Info & Jadwal Notifikasi -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Audit Information (2 cols) -->
            <div class="lg:col-span-2 bg-white rounded border border-gray-200 p-5 text-xs">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4 pb-2 border-b border-gray-100">
                    Informasi Pelaksanaan Audit
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-6">
                    <div>
                        <div class="text-gray-500 font-medium">Toko Sasaran</div>
                        <div class="text-gray-900 font-semibold mt-1">{{ audit.store.name }}</div>
                        <div class="text-[11px] text-gray-500 font-mono">{{ audit.store.code }} ({{ audit.store.area || '-' }})</div>
                    </div>

                    <div>
                        <div class="text-gray-500 font-medium">Tanggal Audit</div>
                        <div class="text-gray-900 font-semibold mt-1">{{ audit.audit_date }}</div>
                        <div class="text-[11px] text-gray-500">Pukul {{ audit.audit_time }} WIB</div>
                    </div>

                    <div>
                        <div class="text-gray-500 font-medium">Auditor Ditugaskan</div>
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
                                <div class="text-[11px] text-gray-500 font-mono mt-0.5">
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
                        class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium self-start sm:self-auto"
                    >
                        Detail Finding & Bukti
                    </Link>
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

                <div v-if="finding.loss_amount > 0" class="pt-1 text-[11px] text-red-600 font-semibold">
                    Estimasi Kerugian: {{ formatRupiah(finding.loss_amount) }}
                </div>
            </div>
        </div>

        <!-- Modal Create Finding -->
        <div
            v-if="isFindingModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
        >
            <div class="bg-white rounded-lg max-w-lg w-full p-6 shadow-xl border border-gray-200">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Tambah Finding Baru</h3>
                    <button @click="closeFindingModal" class="text-gray-400 hover:text-gray-600 text-lg leading-none">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitFinding" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Kategori Audit</label>
                        <select
                            v-model="findingForm.category_id"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Pilih Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">SOP / SE Acuan (Opsional)</label>
                        <select
                            v-model="findingForm.sop_id"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Pilih SOP / SE</option>
                            <option v-for="s in sops" :key="s.id" :value="s.id">
                                {{ s.code }} - {{ s.title }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Uraian Temuan (Finding)</label>
                        <textarea
                            v-model="findingForm.finding"
                            rows="3"
                            required
                            placeholder="Jelaskan ketidaksesuaian yang ditemukan..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Rekomendasi Perbaikan</label>
                        <textarea
                            v-model="findingForm.recommendation"
                            rows="2"
                            required
                            placeholder="Langkah perbaikan yang disarankan..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Tingkat Severity</label>
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

                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Estimasi Kerugian (Rp)</label>
                            <input
                                v-model.number="findingForm.loss_amount"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                        <button
                            @click="closeFindingModal"
                            type="button"
                            class="px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="findingForm.processing"
                            class="px-3.5 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 font-medium disabled:opacity-50"
                        >
                            Simpan Finding
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
