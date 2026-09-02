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

const form = useForm({
    code: '',
    title: '',
    category: '',
    effective_date: '',
    document: null,
});

const openCreateModal = () => {
    editingSop.value = null;
    form.reset();
    if (fileInputRef.value) fileInputRef.value.value = '';
    isModalOpen.value = true;
};

const openEditModal = (sop) => {
    editingSop.value = sop;
    form.code = sop.code;
    form.title = sop.title;
    form.category = sop.category;
    form.effective_date = sop.effective_date_raw || '';
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
        form.post(route('admin.sops.update', editingSop.value.id), {
            _method: 'put',
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.sops.store'), {
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

const deleteSop = (sop) => {
    openConfirm({
        title: 'Hapus Master SOP / SE',
        message: `Apakah Anda yakin ingin menghapus dokumen "${sop.code} - ${sop.title}"?`,
        confirmText: 'Ya, Hapus Dokumen',
        type: 'danger',
        action: () => router.delete(route('admin.sops.destroy', sop.id)),
    });
};
</script>

<template>
    <AppLayout title="SOP & Surat Edaran">
        <Head title="SOP & Surat Edaran" />

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">SOP & Surat Edaran (SE)</h1>
                <p class="text-xs text-gray-500 mt-1">Master regulasi internal dan panduan operasional sebagai standar acuan audit</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 font-mono">
                    {{ sops.length }} SOP/SE terdaftar
                </span>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-2xs"
                >
                    + Tambah SOP / SE
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap">Kode</th>
                            <th class="px-4 py-3 min-w-[200px]">Judul Regulasi</th>
                            <th class="px-4 py-3 min-w-[180px]">Deskripsi</th>
                            <th class="px-4 py-3 whitespace-nowrap">Dokumen Acuan</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center">Temuan Terkait</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center">Status</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="sops.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">Belum ada master SOP/SE.</td>
                        </tr>
                        <tr v-for="sop in paginatedSops" :key="sop.id" class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 font-mono font-bold text-gray-900">{{ sop.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ sop.title }}</td>
                            <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ sop.description || '—' }}</td>
                            <td class="px-4 py-3">
                                <a
                                    v-if="sop.document"
                                    :href="sop.document_url"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-slate-700 hover:text-blue-600 font-medium hover:underline"
                                >
                                    Lihat File
                                </a>
                                <span v-else class="text-gray-400">Tidak ada file</span>
                            </td>
                            <td class="px-4 py-3 text-center font-medium text-gray-800">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ sop.findings_count }} temuan
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium border"
                                    :class="sop.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="sop.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                    {{ sop.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        @click="openEditModal(sop)"
                                        class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs"
                                    >
                                        Edit
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        @click="deleteSop(sop)"
                                        class="text-red-600 hover:text-red-800 font-medium hover:underline text-xs"
                                    >
                                        Hapus
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
        <div v-if="isModalOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-lg w-full p-6 shadow-xl text-xs space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">
                    {{ editingSop ? 'Edit SOP / Surat Edaran' : 'Tambah SOP / Surat Edaran' }}
                </h3>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Kode <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.code"
                                type="text"
                                required
                                placeholder="SOP-004"
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                            />
                            <div v-if="form.errors.code" class="text-red-600 text-[11px] mt-1">{{ form.errors.code }}</div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-medium text-gray-700 mb-1">Judul SOP / SE <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="Contoh: Prosedur Penerimaan Barang"
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            />
                            <div v-if="form.errors.title" class="text-red-600 text-[11px] mt-1">{{ form.errors.title }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Deskripsi Ringkas</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Penjelasan pokok isi SOP/SE..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">File Dokumen SOP (PDF/DOCX)</label>
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept=".pdf,.docx,.xlsx"
                            @change="handleFileChange"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border file:border-gray-300 file:text-xs file:font-medium file:bg-white file:text-gray-700 hover:file:bg-gray-50"
                        />
                        <div v-if="form.errors.document" class="text-red-600 text-[11px] mt-1">{{ form.errors.document }}</div>
                    </div>

                    <div>
                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="font-medium text-gray-700">Status Aktif</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-3.5 py-1.5 rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-medium"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 font-medium disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
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
