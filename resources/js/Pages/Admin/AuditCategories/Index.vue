<script setup>































































import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

const isModalOpen = ref(false);
const editingCategory = ref(null);

const form = useForm({
    name: '',
    code: '',
    description: '',
});

const openCreateModal = () => {
    editingCategory.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (category) => {
    editingCategory.value = category;
    form.name = category.name;
    form.code = category.code;
    form.description = category.description || '';
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingCategory.value = null;
    form.reset();
};

const submit = () => {
    if (editingCategory.value) {
        form.put(route('admin.audit-categories.update', editingCategory.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.audit-categories.store'), {
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

const paginatedCategories = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return props.categories.slice(start, start + 10);
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

const deleteCategory = (cat) => {
    openConfirm({
        title: 'Hapus Kategori Audit',
        message: `Apakah Anda yakin ingin menghapus kategori "${cat.name}"?`,
        confirmText: 'Ya, Hapus Kategori',
        type: 'danger',
        action: () => router.delete(route('admin.audit-categories.destroy', cat.id)),
    });
};
</script>

<template>
    <AppLayout title="Kategori Audit">
        <Head title="Kategori Audit" />

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Kategori Audit</h1>
                <p class="text-xs text-gray-500 mt-1">Klasifikasi ruang lingkup pemeriksaan internal retail</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 font-mono">
                    {{ categories.length }} Kategori terdaftar
                </span>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-2xs"
                >
                    + Tambah Kategori
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap">Nama Kategori</th>
                            <th class="px-4 py-3 min-w-[200px]">Deskripsi</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center">Jumlah Temuan</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center">Status</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="categories.length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-gray-500">Belum ada data kategori audit.</td>
                        </tr>
                        <tr v-for="cat in paginatedCategories" :key="cat.id" class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ cat.name }}</td>
                            <td class="px-4 py-3 text-gray-600 max-w-md">{{ cat.description || '—' }}</td>
                            <td class="px-4 py-3 text-center font-medium text-gray-800">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ cat.findings_count }} temuan
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium border"
                                    :class="cat.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="cat.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                    {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        @click="openEditModal(cat)"
                                        class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs"
                                    >
                                        Edit
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        @click="deleteCategory(cat)"
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
                :total-items="categories.length"
                @update:current-page="currentPage = $event"
            />
        </div>

        <!-- Modal Create/Edit -->
        <div v-if="isModalOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl text-xs space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">
                    {{ editingCategory ? 'Edit Kategori Audit' : 'Tambah Kategori Audit' }}
                </h3>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Nama Kategori <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Contoh: Stock Opname & Selisih"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.name" class="text-red-600 text-[11px] mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Deskripsi Ruang Lingkup</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Penjelasan cakupan pemeriksaan kategori ini..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div>
                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="font-medium text-gray-700">Kategori Aktif</span>
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
