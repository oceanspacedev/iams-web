<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    sops: {
        type: Array,
        default: () => [],
    },
});

const isModalOpen = ref(false);
const editingSop = ref(null);
const fileInputRef = ref(null);

// Field sesuai tabel `sops` dan validasi SopController (code, title, description, document, is_active)
const form = useForm({
    code: '',
    title: '',
    description: '',
    document: null,
    is_active: true,
});

const openCreateModal = () => {
    editingSop.value = null;
    form.reset();
    form.clearErrors();
    if (fileInputRef.value) fileInputRef.value.value = '';
    isModalOpen.value = true;
};

const openEditModal = (sop) => {
    editingSop.value = sop;
    form.clearErrors();
    form.code = sop.code;
    form.title = sop.title;
    form.description = sop.description || '';
    form.is_active = Boolean(sop.is_active);
    form.document = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingSop.value = null;
    form.reset();
};

const handleFileChange = (e) => {
    form.document = e.target.files[0];
};

const submit = () => {
    if (editingSop.value) {
        // Upload file butuh multipart POST; method PUT dikirim lewat field _method (method spoofing Laravel)
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.sops.update', editingSop.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.transform((data) => data).post(route('admin.sops.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmModal = ref({
    show: false,
    title: '',
    message: '',
    confirmText: '',
    type: 'primary',
    action: null,
});

const currentPage = ref(1);

const paginatedSops = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return props.sops.slice(start, start + 10);
});

const openConfirm = (config) => {
    confirmModal.value = {
        show: true,
        title: config.title || 'Konfirmasi tindakan',
        message: config.message || 'Apakah Anda yakin ingin melanjutkan?',
        confirmText: config.confirmText || 'Ya, lanjutkan',
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

const deleteSop = (sop) => {
    openConfirm({
        title: 'Hapus master SOP / SE',
        message: `Apakah Anda yakin ingin menghapus dokumen "${sop.code} - ${sop.title}"?`,
        confirmText: 'Ya, hapus dokumen',
        type: 'danger',
        action: () => router.delete(route('admin.sops.destroy', sop.id)),
    });
};
</script>

<template>
    <AppLayout title="SOP / SE">
        <Head title="SOP / SE" />

        <!-- Header: judul + jumlah, aksi di kanan -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">SOP / SE</h1>
                <span class="text-2xl leading-8 text-gray-400 tabular-nums">{{ sops.length }}</span>
            </div>
            <button
                @click="openCreateModal"
                class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 shadow-sm"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah SOP / SE
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <!-- Mobile -->
            <div class="block divide-y divide-gray-200 md:hidden">
                <div v-if="sops.length === 0" class="p-8 text-center text-sm text-gray-400">
                    Belum ada SOP/SE
                </div>
                <div
                    v-for="sop in paginatedSops"
                    :key="'m-sop-' + sop.id"
                    class="space-y-3 p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900">{{ sop.title }}</div>
                            <div class="mt-0.5 text-xs font-medium text-gray-500 tabular-nums">{{ sop.code }}</div>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-2 rounded-md border px-2 py-0.5 text-xs font-medium"
                            :class="sop.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :class="sop.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                            {{ sop.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <div v-if="sop.description" class="line-clamp-2 text-sm text-gray-600">
                        {{ sop.description }}
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-500 tabular-nums">{{ sop.findings_count }} temuan</span>

                        <div class="flex items-center gap-2">
                            <a
                                v-if="sop.document"
                                :href="sop.document_url"
                                target="_blank"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-600 transition-colors duration-150 hover:underline"
                                title="Lihat dokumen"
                                aria-label="Lihat dokumen"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                            </a>
                            <button
                                @click="openEditModal(sop)"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition-colors duration-150 hover:bg-slate-200"
                                title="Edit"
                                aria-label="Edit"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                            </button>
                            <button
                                @click="deleteSop(sop)"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-700 transition-colors duration-150 hover:bg-red-100"
                                title="Hapus"
                                aria-label="Hapus"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop -->
            <div v-stable-table="sops.length" class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-slate-50 text-xs text-slate-700">
                        <tr>
                            <th class="h-10 whitespace-nowrap px-5 font-medium">Kode</th>
                            <th class="h-10 min-w-[240px] px-5 font-medium">Judul</th>
                            <th class="h-10 whitespace-nowrap px-5 text-center font-medium">File</th>
                            <th class="h-10 whitespace-nowrap px-5 text-right font-medium">Temuan</th>
                            <th class="h-10 whitespace-nowrap px-5 text-center font-medium">Status</th>
                            <th class="h-10 whitespace-nowrap px-5"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-200 bg-white">
                        <tr v-if="sops.length === 0">
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">Belum ada SOP/SE</td>
                        </tr>
                        <tr v-for="sop in paginatedSops" :key="sop.id" class="transition-colors duration-150 hover:bg-slate-50/70">
                            <td class="h-14 whitespace-nowrap px-5 py-3 font-medium text-gray-900 tabular-nums">{{ sop.code }}</td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-gray-900">{{ sop.title }}</div>
                                <div v-if="sop.description" class="mt-0.5 max-w-md truncate text-xs text-gray-500">{{ sop.description }}</div>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <a
                                    v-if="sop.document"
                                    :href="sop.document_url"
                                    target="_blank"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700 transition-colors duration-150 hover:text-blue-600"
                                    title="Lihat file"
                                    aria-label="Lihat file"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                                </a>
                                <span v-else class="text-gray-400" title="Tidak ada file">—</span>
                            </td>
                            <td class="px-5 py-3 text-right font-medium text-gray-800 tabular-nums">
                                {{ sop.findings_count }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span
                                    class="inline-flex items-center gap-2 rounded-md border px-2 py-0.5 text-xs font-medium"
                                    :class="sop.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="sop.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                    {{ sop.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button
                                        @click="openEditModal(sop)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors duration-150 hover:text-slate-900"
                                        title="Edit"
                                        aria-label="Edit"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                                    </button>
                                    <button
                                        @click="deleteSop(sop)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition-colors duration-150 hover:text-red-800"
                                        title="Hapus"
                                        aria-label="Hapus"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <Pagination
                :current-page="currentPage"
                :per-page="10"
                :total-items="sops.length"
                @update:current-page="currentPage = $event"
            />
        </div>

        <!-- Modal Create/Edit -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white text-sm shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h3 class="text-base font-semibold text-gray-900">
                        {{ editingSop ? 'Edit SOP / SE' : 'Tambah SOP / SE' }}
                    </h3>
                </div>

                <form @submit.prevent="submit">
                    <div class="space-y-4 p-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Kode <span class="text-red-600">*</span></label>
                                <input
                                    v-model="form.code"
                                    type="text"
                                    required
                                    placeholder="SOP-004"
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                />
                                <div v-if="form.errors.code" class="mt-1 text-xs text-red-600">{{ form.errors.code }}</div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-gray-900">Judul <span class="text-red-600">*</span></label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    required
                                    placeholder="Prosedur Penerimaan Barang"
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                />
                                <div v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</div>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Deskripsi</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            ></textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Dokumen <span class="font-normal text-gray-400">PDF / DOCX</span></label>
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept=".pdf,.docx,.xlsx"
                                @change="handleFileChange"
                                class="block w-full text-sm text-gray-500 file:mr-3 file:h-9 file:rounded-lg file:border file:border-gray-300 file:bg-white file:px-3 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-50"
                            />
                            <div v-if="form.errors.document" class="mt-1 text-xs text-red-600">{{ form.errors.document }}</div>
                        </div>

                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600 shadow-sm"
                            />
                            <span class="text-sm font-medium text-gray-700">Aktif</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex h-9 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                        >
                            {{ form.processing ? 'Menyimpan…' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Action Confirmation Modal -->
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
