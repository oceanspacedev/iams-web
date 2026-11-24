<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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

const formatDate = (dateString, fallback = '—') => {
    if (!dateString) return fallback;
    if (typeof dateString === 'string') {
        const match = dateString.trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (match) {
            const [, year, month, day] = match;
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const mIdx = parseInt(month, 10) - 1;
            return `${parseInt(day, 10)} ${months[mIdx] || month} ${year}`;
        }
    }
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
};

// Delete finding state
const deleteModalOpen = ref(false);
const selectedFindingToDelete = ref(null);

const confirmDeleteFinding = (finding) => {
    selectedFindingToDelete.value = finding;
    deleteModalOpen.value = true;
};

const executeDeleteFinding = () => {
    if (!selectedFindingToDelete.value) return;

    router.delete(route('auditor.findings.destroy', selectedFindingToDelete.value.id), {
        onSuccess: () => {
            deleteModalOpen.value = false;
            selectedFindingToDelete.value = null;
        },
    });
};

// Upload Document State (LHP & BAP Bertanda Tangan)
const uploadDocModalOpen = ref(false);
const docForm = useForm({
    document_type: 'LHP',
    title: '',
    file: null,
    notes: '',
});

const submitDoc = () => {
    docForm.post(route('auditor.audits.documents.store', props.audit.id), {
        onSuccess: () => {
            uploadDocModalOpen.value = false;
            docForm.reset();
        },
    });
};

const deleteDoc = (docId) => {
    if (confirm('Yakin ingin menghapus dokumen ini?')) {
        router.delete(route('auditor.documents.destroy', docId));
    }
};
</script>

<template>
    <AppLayout :title="`Audit ${audit.audit_number}`">
        <Head :title="`Audit ${audit.audit_number}`" />

        <!-- Header: breadcrumb + judul + badge + aksi dalam satu baris -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 flex-wrap items-center gap-3">
                <Link :href="route('auditor.audits.index')" class="text-2xl leading-8 text-gray-400 transition-colors duration-150 hover:text-blue-600">Audit saya</Link>
                <span class="text-2xl leading-8 text-gray-400" aria-hidden="true">/</span>
                <h1 class="text-2xl font-semibold leading-8 text-gray-900 tabular-nums">{{ audit.audit_number }}</h1>
                <span
                    v-if="audit.category"
                    class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium"
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
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="uploadDocModalOpen = true"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                >
                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Upload LHP / BAP
                </button>

                <Link
                    :href="route('auditor.findings.create', audit.id)"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 shadow-sm"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah temuan
                </Link>
            </div>
        </div>

        <!-- Fakta surat tugas -->
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-4 md:grid-cols-4">
                <div>
                    <dt class="text-xs text-gray-500">Kategori</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ audit.category || 'Audit umum' }}</dd>
                    <dd class="text-xs text-gray-500 tabular-nums">{{ audit.title || 'Surat tugas audit' }} · #{{ audit.id }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Toko</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ audit.store.name }}</dd>
                    <dd class="text-xs text-gray-500 tabular-nums">{{ audit.store.code }} · {{ audit.store.business_entity || 'Unit ritel' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Tanggal</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900 tabular-nums">{{ audit.audit_date }}</dd>
                    <dd class="text-xs text-gray-500">Area {{ audit.store.area || '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Tim auditor</dt>
                    <dd class="mt-1 flex items-center gap-1 text-sm font-medium text-gray-900">
                        <span>{{ audit.auditor.name }}</span>
                        <span class="rounded-md bg-blue-100 px-2 py-0.5 text-xs font-normal text-blue-800">Lead</span>
                    </dd>
                    <dd v-if="audit.auditors && audit.auditors.length > 1" class="mt-1 flex flex-wrap gap-1 text-xs text-gray-500">
                        <span v-for="team in audit.auditors.filter(a => a.id !== audit.auditor.id)" :key="team.id" class="rounded-md bg-gray-100 px-2 py-0.5 text-gray-700">
                            {{ team.name }}
                        </span>
                    </dd>
                </div>
                <div v-if="audit.notes" class="col-span-2 md:col-span-4">
                    <dt class="text-xs text-gray-500">Catatan</dt>
                    <dd class="mt-1 text-sm text-gray-700">{{ audit.notes }}</dd>
                </div>
            </dl>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Temuan -->
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white lg:col-span-2 shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <div class="flex items-baseline gap-3">
                        <h2 class="text-base font-semibold text-gray-900">Temuan</h2>
                        <span class="text-base text-gray-400 tabular-nums">{{ audit.findings.length }}</span>
                    </div>

                    <Link
                        :href="route('auditor.finding-qualities.create', { audit_id: audit.id })"
                        class="inline-flex h-8 shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-slate-100 px-3 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-200"
                    >
                        <svg class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Lapor finding quality</span>
                    </Link>
                </div>

                <div v-if="audit.findings.length === 0" class="p-8 text-center text-sm text-gray-500">
                    Belum ada temuan
                </div>

                <div v-else class="divide-y divide-gray-200">
                    <div
                        v-for="(finding, index) in audit.findings"
                        :key="finding.id"
                        class="px-5 py-4 text-sm"
                    >
                        <div class="flex items-center gap-3">
                            <span class="font-medium text-gray-400 tabular-nums">#{{ String(index + 1).padStart(3, '0') }}</span>
                            <span class="min-w-0 truncate font-medium text-gray-900">{{ finding.category }}</span>
                            <SeverityBadge :severity="finding.severity" :show-timeline="true" />
                            <StatusBadge :status="finding.status" />

                            <div class="ml-auto flex shrink-0 items-center gap-2">
                                <button
                                    v-if="['OPEN', 'IN_PROGRESS'].includes(finding.status)"
                                    @click="confirmDeleteFinding(finding)"
                                    title="Hapus temuan"
                                    aria-label="Hapus temuan"
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-300 bg-red-50 text-red-700 transition-colors duration-150 hover:border-red-600 hover:bg-red-600 hover:text-white"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                                </button>
                                <Link
                                    :href="route('auditor.findings.show', finding.id)"
                                    title="Detail temuan"
                                    aria-label="Detail temuan"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                                </Link>
                            </div>
                        </div>

                        <p class="mt-3 leading-relaxed text-gray-900">{{ finding.finding }}</p>

                        <div v-if="finding.opinion || finding.recommendation" class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div v-if="finding.opinion">
                                <div class="text-xs text-gray-500">Opini auditor</div>
                                <p class="mt-1 leading-relaxed text-gray-700">{{ finding.opinion }}</p>
                            </div>
                            <div v-if="finding.recommendation">
                                <div class="text-xs text-gray-500">Rekomendasi</div>
                                <p class="mt-1 leading-relaxed text-gray-700">{{ finding.recommendation }}</p>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 border-t border-gray-100 pt-3 md:grid-cols-4">
                            <div>
                                <dt class="text-xs text-gray-500">NBH</dt>
                                <dd class="mt-1 font-medium text-gray-900 tabular-nums">{{ formatRupiah(finding.loss_amount) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">NBL</dt>
                                <dd class="mt-1 font-medium text-gray-900 tabular-nums">{{ formatRupiah(finding.surplus_amount) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">SOP / SE</dt>
                                <dd class="mt-1 text-gray-800">{{ finding.sop ? `${finding.sop.code} - ${finding.sop.title}` : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Tindak lanjut</dt>
                                <template v-if="finding.action_plan?.action_plan">
                                    <dd class="mt-1 font-medium text-gray-900">{{ finding.action_plan.action_plan }}</dd>
                                    <dd class="text-xs text-gray-500">
                                        <span class="font-medium text-gray-700">{{ finding.action_plan.pic || '-' }}</span> ·
                                        <span class="font-medium text-gray-700 tabular-nums">{{ formatDate(finding.action_plan.deadline, finding.severity === 'CONTINUOUS' ? 'Dalam monitoring' : '—') }}</span>
                                    </dd>
                                </template>
                                <dd v-else class="mt-1 text-gray-400">Belum diisi</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <!-- Dokumen LHP & BAP -->
            <section class="self-start overflow-hidden rounded-xl border border-gray-200 bg-white text-sm shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <div class="flex items-baseline gap-3">
                        <h2 class="text-base font-semibold text-gray-900">LHP & BAP</h2>
                        <span class="text-base text-gray-400 tabular-nums">{{ audit.documents?.length || 0 }}</span>
                    </div>

                    <button
                        @click="uploadDocModalOpen = true"
                        title="Unggah LHP / BAP"
                        aria-label="Unggah LHP / BAP"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white transition-colors duration-150 hover:bg-blue-700 shadow-sm"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>

                <div v-if="!audit.documents || audit.documents.length === 0" class="p-6 text-center text-gray-400">
                    Belum ada dokumen
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div
                        v-for="doc in audit.documents"
                        :key="doc.id"
                        class="flex items-start gap-3 px-5 py-3"
                    >
                        <span
                            class="mt-0.5 inline-block shrink-0 rounded-md px-2 py-0.5 text-xs font-medium"
                            :class="doc.document_type === 'LHP' ? 'bg-blue-50 text-blue-700 border border-blue-200' : (doc.document_type === 'BAP' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-700 border border-gray-200')"
                        >
                            {{ doc.document_type }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-gray-900">{{ doc.title }}</div>
                            <div class="mt-0.5 break-all text-xs text-gray-500 tabular-nums">{{ doc.file_name }} ({{ doc.file_size }})</div>
                            <div class="mt-0.5 text-xs text-gray-400 tabular-nums">{{ doc.created_at }}</div>
                            <p v-if="doc.notes" class="mt-1 text-gray-600">{{ doc.notes }}</p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <a
                                :href="doc.file_url"
                                target="_blank"
                                title="Lihat file"
                                aria-label="Lihat file"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                            </a>
                            <button
                                @click="deleteDoc(doc.id)"
                                title="Hapus"
                                aria-label="Hapus dokumen"
                                class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 transition-colors duration-150 hover:bg-red-50"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Modal: konfirmasi hapus temuan -->
        <div v-if="deleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="animate-in fade-in zoom-in-95 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 text-sm shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Hapus temuan?</h3>
                        <p class="mt-0.5 text-sm text-gray-500">Action plan dan bukti terkait ikut terhapus.</p>
                    </div>
                </div>

                <div v-if="selectedFindingToDelete" class="mb-5 line-clamp-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-gray-800">
                    "{{ selectedFindingToDelete.finding }}"
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        @click="deleteModalOpen = false"
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDeleteFinding"
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-red-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-red-700"
                    >
                        Ya, hapus temuan
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal: upload LHP / BAP -->
        <div v-if="uploadDocModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="animate-in fade-in zoom-in-95 w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white text-sm shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                <div class="flex h-14 items-center justify-between border-b border-gray-200 px-5">
                    <h3 class="text-base font-semibold text-gray-900">Unggah LHP / BAP</h3>
                    <button
                        @click="uploadDocModalOpen = false"
                        aria-label="Tutup"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors duration-150 hover:text-gray-600"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitDoc">
                    <div class="space-y-4 p-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Jenis dokumen <span class="text-red-600">*</span></label>
                            <select
                                v-model="docForm.document_type"
                                required
                                class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            >
                                <option value="LHP">LHP (Laporan Hasil Pemeriksaan)</option>
                                <option value="BAP">BAP (Berita Acara Pemeriksaan) bertanda tangan auditee</option>
                                <option value="OTHER">Dokumen berita acara lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Judul <span class="text-red-600">*</span></label>
                            <input
                                v-model="docForm.title"
                                type="text"
                                required
                                placeholder="Contoh: BAP stock opname"
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">File <span class="text-red-600">*</span></label>
                            <input
                                type="file"
                                required
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
                                @change="e => docForm.file = e.target.files[0]"
                                class="w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <p class="mt-1 text-xs text-gray-400">PDF, Word, Excel, PNG, JPG · maks 10 MB</p>
                            <div v-if="docForm.errors.file" class="mt-1 text-xs text-red-600">{{ docForm.errors.file }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Catatan (opsional)</label>
                            <textarea
                                v-model="docForm.notes"
                                rows="2"
                                class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-5 py-4">
                        <button
                            @click="uploadDocModalOpen = false"
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="docForm.processing"
                            class="inline-flex h-9 items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-emerald-700 disabled:opacity-50"
                        >
                            {{ docForm.processing ? 'Mengunggah…' : 'Unggah dokumen' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
