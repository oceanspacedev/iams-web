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
        title: 'Pulihkan Kategori Audit',
        message: `Apakah Anda yakin ingin memulihkan kategori "${cat.name}" dari arsip riwayat?`,
        confirmText: 'Ya, Pulihkan',
        type: 'primary',
        action: () => router.post(route('admin.audit-categories.restore', cat.id), {}, {
            preserveScroll: true,
        }),
    });
};

const deleteCategory = (cat) => {
    openConfirm({
        title: 'Hapus Kategori Audit',
        message: `Kategori "${cat.name}" akan dihapus dari daftar pilihan. Riwayat temuan dan audit yang sudah ada tetap tersimpan aman di sistem. Lanjutkan?`,
        confirmText: 'Ya, Hapus',
        type: 'danger',
        action: () => router.delete(route('admin.audit-categories.destroy', cat.id), {
            preserveScroll: true,
        }),
    });
};

const setStatusFilter = (status) => {
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

const currentPage = ref(1);

const paginatedCategories = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return props.categories.slice(start, start + 10);
});
</script>

<template>
    <AppLayout title="Kategori Audit">
        <Head title="Kategori Audit — Admin" />

        <!-- Header -->
        <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
                <h1 class="text-lg sm:text-xl font-semibold text-gray-900 tracking-tight">Kategori Audit</h1>
                <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Klasifikasi ruang lingkup pemeriksaan internal retail</p>
            </div>

            <div class="flex items-center">
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center w-full sm:w-auto gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-2xs cursor-pointer"
                >
                    + Tambah Kategori
                </button>
            </div>
        </div>

        <!-- Filter Status Tabs (Smooth horizontal scroll on mobile) -->
        <div class="mb-4 border-b border-gray-200 overflow-x-auto no-scrollbar">
            <nav class="flex space-x-4 sm:space-x-6 text-xs whitespace-nowrap min-w-max pb-0.5" aria-label="Tabs">
                <button
                    type="button"
                    @click="setStatusFilter('all')"
                    class="pb-3 px-1 border-b-2 font-medium cursor-pointer transition-colors"
                    :class="currentStatus === 'all' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                >
                    Semua
                    <span class="ml-1.5 py-0.5 px-1.5 rounded-full text-[10px]" :class="currentStatus === 'all' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'">
                        {{ stats.all }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="setStatusFilter('active')"
                    class="pb-3 px-1 border-b-2 font-medium cursor-pointer transition-colors"
                    :class="currentStatus === 'active' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                >
                    Aktif
                    <span class="ml-1.5 py-0.5 px-1.5 rounded-full text-[10px]" :class="currentStatus === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'">
                        {{ stats.active }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="setStatusFilter('inactive')"
                    class="pb-3 px-1 border-b-2 font-medium cursor-pointer transition-colors"
                    :class="currentStatus === 'inactive' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                >
                    Nonaktif
                    <span class="ml-1.5 py-0.5 px-1.5 rounded-full text-[10px]" :class="currentStatus === 'inactive' ? 'bg-gray-200 text-gray-800' : 'bg-gray-100 text-gray-600'">
                        {{ stats.inactive }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="setStatusFilter('trashed')"
                    class="pb-3 px-1 border-b-2 font-medium cursor-pointer transition-colors"
                    :class="currentStatus === 'trashed' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                >
                    Riwayat Terhapus
                    <span class="ml-1.5 py-0.5 px-1.5 rounded-full text-[10px]" :class="currentStatus === 'trashed' ? 'bg-rose-100 text-rose-800' : 'bg-gray-100 text-gray-600'">
                        {{ stats.trashed }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Clean Dual View (Mobile Cards vs Desktop Table) -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            
            <!-- 1. MOBILE CARD VIEW (Visible on mobile screens) -->
            <div class="block md:hidden divide-y divide-gray-200">
                <div v-if="categories.length === 0" class="p-8 text-center text-gray-400 text-xs">
                    Tidak ada data kategori audit pada filter ini.
                </div>
                <div
                    v-for="cat in paginatedCategories"
                    :key="'m-cat-' + cat.id"
                    class="p-4 space-y-2.5"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900 text-xs">{{ cat.name }}</div>
                            <div v-if="cat.is_deleted" class="text-[10px] text-red-500 mt-0.5">
                                Dihapus: {{ cat.deleted_at }}
                            </div>
                        </div>
                        <template v-if="cat.is_deleted">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium text-rose-700 bg-rose-50 border border-rose-200">
                                Terhapus
                            </span>
                        </template>
                        <template v-else>
                            <span
                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-medium border"
                                :class="cat.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="cat.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </template>
                    </div>

                    <div v-if="cat.description" class="text-xs text-gray-600 line-clamp-2">
                        {{ cat.description }}
                    </div>

                    <div class="pt-2 flex items-center justify-between border-t border-gray-100">
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                            {{ cat.findings_count }} temuan
                        </span>
                        
                        <div v-if="cat.is_deleted" class="flex items-center gap-2">
                            <button
                                @click="restoreCategory(cat)"
                                class="px-2.5 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium text-[11px] cursor-pointer"
                            >
                                Pulihkan
                            </button>
                        </div>
                        <div v-else class="flex items-center gap-2">
                            <button
                                @click="openEditModal(cat)"
                                class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-[11px] cursor-pointer"
                            >
                                Edit
                            </button>
                            <button
                                @click="toggleActive(cat)"
                                class="px-2.5 py-1 rounded border border-gray-200 bg-white hover:bg-gray-50 text-slate-700 font-medium text-[11px] cursor-pointer"
                            >
                                {{ cat.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                            <button
                                @click="deleteCategory(cat)"
                                class="px-2.5 py-1 rounded bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-medium text-[11px] cursor-pointer"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. DESKTOP TABLE VIEW (Visible on tablet & desktop) -->
            <div class="hidden md:block overflow-x-auto">
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
                            <td colspan="5" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada data kategori audit pada filter ini.
                            </td>
                        </tr>
                        <tr v-for="cat in paginatedCategories" :key="cat.id" class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900">{{ cat.name }}</div>
                                <div v-if="cat.is_deleted" class="text-[10px] text-red-500 mt-0.5">
                                    Dihapus pada {{ cat.deleted_at }} (Tersimpan di history)
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-md">{{ cat.description || '—' }}</td>
                            <td class="px-4 py-3 text-center font-medium text-gray-800">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ cat.findings_count }} temuan
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <template v-if="cat.is_deleted">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium text-rose-700 bg-rose-50 border border-rose-200">
                                        Terhapus
                                    </span>
                                </template>
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="toggleActive(cat)"
                                        title="Klik untuk mengubah status Aktif / Nonaktif"
                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium border cursor-pointer hover:opacity-80 transition"
                                        :class="cat.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="cat.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                        {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </template>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <!-- Aksi jika sudah terhapus -->
                                <div v-if="cat.is_deleted" class="inline-flex items-center gap-2">
                                    <button
                                        @click="restoreCategory(cat)"
                                        class="text-blue-600 hover:text-blue-800 font-medium hover:underline text-xs cursor-pointer"
                                    >
                                        Pulihkan
                                    </button>
                                </div>

                                <!-- Aksi jika belum terhapus -->
                                <div v-else class="inline-flex items-center gap-2">
                                    <button
                                        @click="openEditModal(cat)"
                                        class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs cursor-pointer"
                                    >
                                        Edit
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        @click="toggleActive(cat)"
                                        class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs cursor-pointer"
                                    >
                                        {{ cat.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        @click="deleteCategory(cat)"
                                        class="text-red-600 hover:text-red-800 font-medium hover:underline text-xs cursor-pointer"
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
                            placeholder="Contoh: Audit Operational Retail"
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
                        <div v-if="form.errors.description" class="text-red-600 text-[11px] mt-1">{{ form.errors.description }}</div>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="font-medium text-gray-700">Kategori Aktif (Bisa dipilih pada audit & temuan baru)</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-3.5 py-1.5 rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-medium cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 font-medium disabled:opacity-50 cursor-pointer"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
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
