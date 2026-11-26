<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    currentStatus: {
        type: String,
        default: 'all',
    },
    stats: {
        type: Object,
        default: () => ({
            all: 0,
            active: 0,
            inactive: 0,
            trashed: 0,
        }),
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

const openEditModal = (category) => {
    editingCategory.value = category;
    form.name = category.name;
    form.description = category.description || '';
    form.is_active = Boolean(category.is_active);
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

const toggleActive = (cat) => {
    router.patch(route('admin.audit-categories.toggle-active', cat.id), {}, {
        preserveScroll: true,
    });
};

const restoreCategory = (cat) => {
    openConfirm({
        title: 'Pulihkan kategori audit',
        message: `Pulihkan kategori "${cat.name}"?`,
        confirmText: 'Ya, pulihkan',
        type: 'primary',
        action: () => router.post(route('admin.audit-categories.restore', cat.id), {}, {
            preserveScroll: true,
        }),
    });
};

const deleteCategory = (cat) => {
    openConfirm({
        title: 'Hapus kategori audit',
        message: `Kategori "${cat.name}" dihapus dari pilihan. Riwayat audit dan temuan tetap tersimpan.`,
        confirmText: 'Ya, hapus',
        type: 'danger',
        action: () => router.delete(route('admin.audit-categories.destroy', cat.id), {
            preserveScroll: true,
        }),
    });
};

const setStatusFilter = (status) => {
    currentPage.value = 1;
    router.get(route('admin.audit-categories.index'), { status }, {
        preserveState: true,
        preserveScroll: true,
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

const currentPage = ref(1);

// Jaga halaman tetap valid saat jumlah data berkurang (hapus / ganti tab)
watch(() => props.categories.length, (total) => {
    const lastPage = Math.max(1, Math.ceil(total / 10));
    if (currentPage.value > lastPage) currentPage.value = lastPage;
});

const paginatedCategories = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return props.categories.slice(start, start + 10);
});
</script>

<template>
    <AppLayout title="Kategori audit">
        <Head title="Kategori audit" />

        <!-- Header: judul + jumlah, aksi di kanan -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Kategori audit</h1>
                <span class="text-2xl leading-8 text-gray-400 tabular-nums">{{ categories.length }}</span>
            </div>
            <button
                @click="openCreateModal"
                class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 shadow-sm"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah kategori
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <!-- Tab status di dalam kartu -->
            <div class="no-scrollbar overflow-x-auto border-b border-gray-200 px-5">
                <nav class="flex min-w-max gap-6 whitespace-nowrap text-sm" aria-label="Tabs">
                    <button
                        type="button"
                        @click="setStatusFilter('all')"
                        class="-mb-px flex h-12 items-center gap-2 border-b-2 px-1 font-medium transition-colors duration-150"
                        :class="currentStatus === 'all' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    >
                        Semua
                        <span class="rounded-md px-2 py-0.5 text-xs tabular-nums" :class="currentStatus === 'all' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'">
                            {{ stats.all }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="setStatusFilter('active')"
                        class="-mb-px flex h-12 items-center gap-2 border-b-2 px-1 font-medium transition-colors duration-150"
                        :class="currentStatus === 'active' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    >
                        Aktif
                        <span class="rounded-md px-2 py-0.5 text-xs tabular-nums" :class="currentStatus === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'">
                            {{ stats.active }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="setStatusFilter('inactive')"
                        class="-mb-px flex h-12 items-center gap-2 border-b-2 px-1 font-medium transition-colors duration-150"
                        :class="currentStatus === 'inactive' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    >
                        Nonaktif
                        <span class="rounded-md px-2 py-0.5 text-xs tabular-nums" :class="currentStatus === 'inactive' ? 'bg-gray-200 text-gray-800' : 'bg-gray-100 text-gray-600'">
                            {{ stats.inactive }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="setStatusFilter('trashed')"
                        class="-mb-px flex h-12 items-center gap-2 border-b-2 px-1 font-medium transition-colors duration-150"
                        :class="currentStatus === 'trashed' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    >
                        Terhapus
                        <span class="rounded-md px-2 py-0.5 text-xs tabular-nums" :class="currentStatus === 'trashed' ? 'bg-rose-100 text-rose-800' : 'bg-gray-100 text-gray-600'">
                            {{ stats.trashed }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Mobile -->
            <div class="block divide-y divide-gray-200 md:hidden">
                <div v-if="categories.length === 0" class="p-8 text-center text-sm text-gray-400">
                    Tidak ada kategori
                </div>
                <div
                    v-for="cat in paginatedCategories"
                    :key="'m-cat-' + cat.id"
                    class="space-y-3 p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900">{{ cat.name }}</div>
                            <div v-if="cat.is_deleted" class="mt-0.5 text-xs text-red-500 tabular-nums">
                                Dihapus {{ cat.deleted_at }}
                            </div>
                        </div>
                        <template v-if="cat.is_deleted">
                            <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">
                                Terhapus
                            </span>
                        </template>
                        <template v-else>
                            <span
                                class="inline-flex shrink-0 items-center gap-2 rounded-md border px-2 py-0.5 text-xs font-medium"
                                :class="cat.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="cat.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </template>
                    </div>

                    <div v-if="cat.description" class="line-clamp-2 text-sm text-gray-600">
                        {{ cat.description }}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 tabular-nums">{{ cat.findings_count }} temuan</span>

                        <div v-if="cat.is_deleted" class="flex items-center gap-2">
                            <button
                                @click="restoreCategory(cat)"
                                class="inline-flex h-8 items-center rounded-lg bg-blue-50 px-3 text-sm font-medium text-blue-700 transition-colors duration-150 hover:bg-blue-100"
                            >
                                Pulihkan
                            </button>
                        </div>
                        <div v-else class="flex items-center gap-2">
                            <button
                                @click="openEditModal(cat)"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition-colors duration-150 hover:bg-slate-200"
                                title="Edit"
                                aria-label="Edit"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                            </button>
                            <button
                                @click="toggleActive(cat)"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-slate-700 transition-colors duration-150 hover:bg-gray-50"
                                :title="cat.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                :aria-label="cat.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v9" /><path d="M6.4 6.4a8 8 0 1 0 11.2 0" /></svg>
                            </button>
                            <button
                                @click="deleteCategory(cat)"
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
            <div v-stable-table="categories.length" class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-slate-50 text-xs text-slate-700">
                        <tr>
                            <th class="h-10 whitespace-nowrap px-5 font-medium">Nama</th>
                            <th class="h-10 min-w-[200px] px-5 font-medium">Deskripsi</th>
                            <th class="h-10 whitespace-nowrap px-5 text-right font-medium">Temuan</th>
                            <th class="h-10 whitespace-nowrap px-5 text-center font-medium">Status</th>
                            <th class="h-10 whitespace-nowrap px-5"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-200 bg-white">
                        <tr v-if="categories.length === 0">
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">
                                Tidak ada kategori
                            </td>
                        </tr>
                        <tr v-for="cat in paginatedCategories" :key="cat.id" class="transition-colors duration-150 hover:bg-slate-50/70">
                            <td class="h-14 px-5 py-3">
                                <div class="font-medium text-gray-900">{{ cat.name }}</div>
                                <div v-if="cat.is_deleted" class="mt-0.5 text-xs text-red-500 tabular-nums">
                                    Dihapus {{ cat.deleted_at }}
                                </div>
                            </td>
                            <td class="max-w-md px-5 py-3 text-gray-600">{{ cat.description || '—' }}</td>
                            <td class="px-5 py-3 text-right font-medium text-gray-800 tabular-nums">
                                {{ cat.findings_count }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <template v-if="cat.is_deleted">
                                    <span class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">
                                        Terhapus
                                    </span>
                                </template>
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="toggleActive(cat)"
                                        title="Ubah status aktif"
                                        class="inline-flex items-center gap-2 rounded-md border px-2 py-0.5 text-xs font-medium transition-opacity duration-150 hover:opacity-80"
                                        :class="cat.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="cat.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                        {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </template>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <div v-if="cat.is_deleted" class="inline-flex items-center gap-2">
                                    <button
                                        @click="restoreCategory(cat)"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                    >
                                        Pulihkan
                                    </button>
                                </div>

                                <div v-else class="inline-flex items-center gap-1">
                                    <button
                                        @click="openEditModal(cat)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors duration-150 hover:text-slate-900"
                                        title="Edit"
                                        aria-label="Edit"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                                    </button>
                                    <button
                                        @click="toggleActive(cat)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors duration-150 hover:text-slate-900"
                                        :title="cat.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                        :aria-label="cat.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v9" /><path d="M6.4 6.4a8 8 0 1 0 11.2 0" /></svg>
                                    </button>
                                    <button
                                        @click="deleteCategory(cat)"
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
                :total-items="categories.length"
                @update:current-page="currentPage = $event"
            />
        </div>

        <!-- Modal Create/Edit -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white text-sm shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h3 class="text-base font-semibold text-gray-900">
                        {{ editingCategory ? 'Edit kategori' : 'Tambah kategori' }}
                    </h3>
                </div>

                <form @submit.prevent="submit">
                    <div class="space-y-4 p-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Nama <span class="text-red-600">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Audit Operational Retail"
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                            <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Deskripsi</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            ></textarea>
                            <div v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2">
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
