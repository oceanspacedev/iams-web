<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    stores: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const statusFilter = ref('');
const typeFilter = ref('');

const typeLabels = {
    toko: 'Toko',
    gudang: 'Gudang',
    head_office: 'Head office',
    hub: 'Hub',
};
const typeLabel = (type) => typeLabels[(type || 'toko').toLowerCase()] || type;
const importModalOpen = ref(false);
const currentPage = ref(1);

const importForm = useForm({
    file: null,
});

const submitImport = () => {
    importForm.post(route('admin.stores.import-csv'), {
        onSuccess: () => {
            importModalOpen.value = false;
            importForm.reset();
        },
    });
};

const filteredStores = computed(() => {
    return props.stores.filter((s) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (s.name && s.name.toLowerCase().includes(query)) ||
            (s.code && s.code.toLowerCase().includes(query)) ||
            (s.business_entity && s.business_entity.toLowerCase().includes(query)) ||
            (s.area && s.area.toLowerCase().includes(query)) ||
            (s.regional && s.regional.toLowerCase().includes(query));

        const matchesStatus = !statusFilter.value || s.status === statusFilter.value;
        const matchesType = !typeFilter.value || (s.type || 'toko').toLowerCase() === typeFilter.value;

        return matchesSearch && matchesStatus && matchesType;
    });
});

watch([searchQuery, statusFilter, typeFilter], () => {
    currentPage.value = 1;
});

const paginatedStores = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return filteredStores.value.slice(start, start + 10);
});

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

const deleteStore = (store) => {
    openConfirm({
        title: 'Hapus unit',
        message: `Hapus unit "${store.name} (${store.code})"?`,
        confirmText: 'Hapus',
        type: 'danger',
        action: () => router.delete(route('admin.stores.destroy', store.id)),
    });
};
</script>

<template>
    <AppLayout title="Toko & gudang">
        <Head title="Toko & gudang" />

        <!-- Header: judul + jumlah, aksi di kanan -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Toko & gudang</h1>
                <span class="text-2xl leading-8 text-gray-400 tabular-nums">{{ filteredStores.length }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="importModalOpen = true"
                    type="button"
                    class="inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                >
                    <svg class="h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4m0 0-4 4m4-4 4 4M4 20h16" /></svg>
                    Import CSV
                </button>

                <a
                    :href="route('admin.stores.download-template')"
                    class="inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-lg border border-emerald-300 bg-white px-4 text-sm font-medium text-emerald-700 transition-colors duration-150 hover:border-emerald-400 hover:bg-emerald-50"
                    title="Download seluruh master data toko ke format Excel"
                >
                    <svg class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v12m0 0-4-4m4 4 4-4M4 20h16" /></svg>
                    Download
                </a>

                <Link
                    :href="route('admin.stores.create')"
                    class="inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 shadow-sm"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Unit manual
                </Link>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <!-- Filter di dalam kartu tabel -->
            <div class="flex flex-col gap-3 border-b border-gray-200 p-3 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-80">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari"
                        aria-label="Cari kode, nama toko, badan usaha, area"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-9 pr-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                </div>
                <div class="flex w-full gap-3 sm:ml-auto sm:w-auto">
                    <select
                        v-model="typeFilter"
                        aria-label="Tipe unit"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 sm:w-auto shadow-sm"
                    >
                        <option value="">Semua tipe</option>
                        <option value="toko">Toko (cabang retail)</option>
                        <option value="gudang">Gudang (warehouse / DC)</option>
                        <option value="head_office">Head office</option>
                        <option value="hub">Hub</option>
                    </select>
                    <select
                        v-model="statusFilter"
                        aria-label="Status"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 sm:w-auto shadow-sm"
                    >
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- Mobile -->
            <div class="block divide-y divide-gray-200 md:hidden">
                <div v-if="filteredStores.length === 0" class="p-8 text-center text-sm text-gray-400">
                    Tidak ada unit
                </div>
                <div
                    v-for="s in paginatedStores"
                    :key="'m-store-' + s.id"
                    class="space-y-3 p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900">{{ s.name }}</div>
                            <div class="mt-0.5 text-xs text-gray-500">
                                <span class="font-medium text-gray-800 tabular-nums">{{ s.code }}</span>
                                <span v-if="s.business_entity"> · {{ s.business_entity }}</span>
                            </div>
                            <div v-if="s.address" class="mt-1 flex gap-1 text-xs text-gray-500">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ s.address }}</span>
                            </div>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-2 rounded-md border px-2 py-0.5 text-xs font-medium"
                            :class="s.status === 'active' ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :class="s.status === 'active' ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                            {{ s.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2 text-xs text-gray-500">
                            <span class="inline-block rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                {{ typeLabel(s.type) }}
                            </span>
                            <span>{{ s.area || '—' }} <span v-if="s.regional">({{ s.regional }})</span></span>
                            <span class="tabular-nums">· {{ s.audits_count }} audit</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Link
                                :href="route('admin.stores.edit', s.id)"
                                title="Edit"
                                aria-label="Edit"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition-colors duration-150 hover:bg-blue-100"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                            </Link>
                            <button
                                @click="deleteStore(s)"
                                title="Hapus"
                                aria-label="Hapus"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-700 transition-colors duration-150 hover:bg-red-100"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop -->
            <div v-stable-table="filteredStores.length" class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-slate-50 text-xs text-slate-700">
                        <tr>
                            <th class="h-10 w-24 whitespace-nowrap px-4 font-medium">Kode</th>
                            <th class="h-10 min-w-[280px] px-4 font-medium">Nama</th>
                            <th class="h-10 w-32 whitespace-nowrap px-4 font-medium">Tipe</th>
                            <th class="h-10 w-44 whitespace-nowrap px-4 font-medium">Area</th>
                            <th class="h-10 w-24 whitespace-nowrap px-4 text-right font-medium">Audit</th>
                            <th class="h-10 w-28 whitespace-nowrap px-4 font-medium">Status</th>
                            <th class="h-10 w-24 px-4"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredStores.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada unit
                            </td>
                        </tr>
                        <tr
                            v-for="s in paginatedStores"
                            :key="s.id"
                            class="transition-colors duration-150 hover:bg-slate-50/70"
                        >
                            <td class="h-14 whitespace-nowrap px-4 align-middle font-medium text-gray-900 tabular-nums">
                                {{ s.code }}
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <div class="font-medium text-gray-900">{{ s.name }}</div>
                                <div v-if="s.address" class="mt-0.5 flex gap-1 text-xs text-gray-500">
                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>{{ s.address }}</span>
                                </div>
                                <div v-if="s.business_entity" class="mt-0.5 text-xs text-slate-500">
                                    {{ s.business_entity }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-middle">
                                <span class="inline-block rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                    {{ typeLabel(s.type) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-middle text-gray-600">
                                {{ s.area || '—' }} <span v-if="s.regional" class="text-gray-400">({{ s.regional }})</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right align-middle text-slate-700 tabular-nums">
                                {{ s.audits_count }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-middle">
                                <span
                                    class="inline-flex items-center gap-2 rounded-md border px-2 py-0.5 text-xs font-medium"
                                    :class="s.status === 'active' ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="s.status === 'active' ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                    {{ s.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right align-middle">
                                <div class="inline-flex items-center gap-1">
                                    <Link
                                        :href="route('admin.stores.edit', s.id)"
                                        title="Edit"
                                        aria-label="Edit"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors duration-150 hover:text-slate-900"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                                    </Link>
                                    <button
                                        @click="deleteStore(s)"
                                        title="Hapus"
                                        aria-label="Hapus"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition-colors duration-150 hover:text-red-800"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination
                :current-page="currentPage"
                :per-page="10"
                :total-items="filteredStores.length"
                @update:current-page="currentPage = $event"
            />
        </div>

        <!-- Modal import CSV -->
        <div v-if="importModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white text-sm shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h3 class="text-base font-semibold text-gray-900">Import CSV</h3>
                    <button @click="importModalOpen = false" class="-mr-2 inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors duration-150 hover:text-gray-600" aria-label="Tutup">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                            <path d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitImport">
                    <div class="space-y-4 p-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">File CSV</label>
                            <input
                                type="file"
                                accept=".csv,text/csv"
                                required
                                @input="importForm.file = $event.target.files[0]"
                                class="w-full cursor-pointer text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
                            />
                            <div v-if="importForm.errors.file" class="mt-1 text-xs text-red-600">{{ importForm.errors.file }}</div>
                        </div>

                        <div class="space-y-1 rounded-lg border border-slate-200 bg-slate-50 p-3 text-slate-600">
                            <div class="text-xs text-slate-600">Kolom header</div>
                            <div class="font-mono text-xs text-slate-700">code, name, business_entity, type, area, regional, address</div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-5 py-4">
                        <button
                            type="button"
                            @click="importModalOpen = false"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="importForm.processing"
                            class="inline-flex h-9 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                        >
                            {{ importForm.processing ? 'Mengunggah…' : 'Import' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Konfirmasi -->
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
