<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

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
    description: '',
    is_active: true,
});

const openCreateModal = () => {
    editingCategory.value = null;
    form.reset();
    form.is_active = true;
    isModalOpen.value = true;
};

const openEditModal = (cat) => {
    editingCategory.value = cat;
    form.name = cat.name;
    form.description = cat.description || '';
    form.is_active = cat.is_active;
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

const deleteCategory = (cat) => {
    if (confirm(`Hapus kategori ${cat.name}?`)) {
        router.delete(route('admin.audit-categories.destroy', cat.id));
    }
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

            <button
                @click="openCreateModal"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
            >
                + Tambah Kategori
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3.5">Nama Kategori</th>
                            <th class="px-5 py-3.5">Deskripsi</th>
                            <th class="px-5 py-3.5">Jumlah Temuan</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="categories.length === 0">
                            <td colspan="5" class="px-5 py-8 text-center text-gray-500">Belum ada data kategori audit.</td>
                        </tr>
                        <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50/70">
                            <td class="px-5 py-3.5 font-semibold text-gray-900">{{ cat.name }}</td>
                            <td class="px-5 py-3.5 text-gray-600 max-w-md">{{ cat.description || '—' }}</td>
                            <td class="px-5 py-3.5 font-medium text-gray-800">{{ cat.findings_count }} finding(s)</td>
                            <td class="px-5 py-3.5">
                                <span
                                    class="inline-block px-2 py-0.5 rounded text-[11px] font-medium border"
                                    :class="cat.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-600 border-gray-200'"
                                >
                                    {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-2">
                                <button
                                    @click="openEditModal(cat)"
                                    class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="deleteCategory(cat)"
                                    class="px-2.5 py-1 text-xs rounded border border-red-200 text-red-600 hover:bg-red-50 font-medium"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
    </AppLayout>
</template>
