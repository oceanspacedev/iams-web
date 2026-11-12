<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stores: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    auditors: {
        type: Array,
        default: () => [],
    },
    suggested_number: {
        type: String,
        default: '',
    },
    upcoming_audits: {
        type: Array,
        default: () => [],
    },
});

const today = new Date().toISOString().split('T')[0];

const useCustomStore = ref(false);

const form = useForm({
    audit_number: props.suggested_number,
    title: '',
    category_id: props.categories.length > 0 ? props.categories[0].id : '',
    store_id: '',
    custom_store_name: '',
    auditor_ids: [],
    audit_date: today,
    audit_time: '09:00',
    location: '',
    status: 'PLANNED',
    notes: '',
});

const selectedCategoryName = computed(() => {
    return props.categories.find(c => c.id === form.category_id)?.name || 'Belum dipilih';
});

const selectedStore = computed(() => {
    if (useCustomStore.value || !form.store_id) return null;
    return props.stores.find(s => s.id === form.store_id);
});

const selectedStoreName = computed(() => {
    if (useCustomStore.value) {
        return form.custom_store_name ? `${form.custom_store_name} (Manual)` : 'Ketik nama toko/unit baru';
    }
    const store = selectedStore.value;
    return store ? `[${store.code}] ${store.name}` : 'Belum dipilih';
});

const selectedAuditorsList = computed(() => {
    return props.auditors.filter(a => form.auditor_ids.includes(a.id));
});

const sameDateAudits = computed(() => {
    if (!form.audit_date) return [];
    return props.upcoming_audits.filter(a => a.audit_date === form.audit_date);
});

const sameStoreAudits = computed(() => {
    if (useCustomStore.value || !form.store_id) return [];
    return props.upcoming_audits.filter(a => a.store_id === form.store_id);
});

const availableAuditors = computed(() => {
    return props.auditors.filter(a => !form.auditor_ids.includes(a.id));
});

const addAuditor = (event) => {
    const val = Number(event.target.value);
    if (!val) return;
    if (form.auditor_ids.length >= 5) {
        alert('Maksimal penugasan 5 orang auditor per surat tugas audit.');
        event.target.value = '';
        return;
    }
    if (!form.auditor_ids.includes(val)) {
        form.auditor_ids.push(val);
    }
    event.target.value = '';
};

const removeAuditor = (auditorId) => {
    const index = form.auditor_ids.indexOf(auditorId);
    if (index > -1) {
        form.auditor_ids.splice(index, 1);
    }
};

const getAuditor = (auditorId) => {
    return props.auditors.find(a => a.id === auditorId);
};

const toggleAuditor = (auditorId) => {
    const index = form.auditor_ids.indexOf(auditorId);
    if (index > -1) {
        form.auditor_ids.splice(index, 1);
    } else {
        if (form.auditor_ids.length >= 5) {
            alert('Maksimal penugasan 5 orang auditor per surat tugas audit.');
            return;
        }
        form.auditor_ids.push(auditorId);
    }
};

const submit = () => {
    if (useCustomStore.value) {
        if (!form.custom_store_name || !form.custom_store_name.trim()) {
            alert('Silakan ketik nama toko atau badan usaha yang dicek.');
            return;
        }
        form.store_id = '';
    } else {
        if (!form.store_id) {
            alert('Silakan pilih unit toko atau gudang sasaran.');
            return;
        }
        form.custom_store_name = '';
    }

    if (form.auditor_ids.length === 0) {
        alert('Silakan pilih minimal 1 auditor yang bertugas.');
        return;
    }
    form.post(route('coordinator.audits.store'));
};
</script>

<template>
    <AppLayout title="Jadwalkan Audit Baru">
        <Head title="Jadwalkan Audit Baru — Koordinator" />

        <div class="max-w-5xl">
            <!-- Page Header -->
            <div class="mb-5">
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <Link :href="route('coordinator.audits.index')" class="hover:text-blue-600">Audits</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-medium">Jadwalkan Audit</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Jadwalkan Penugasan Audit Baru</h1>
                <p class="text-xs text-gray-500 mt-1">Lengkapi informasi audit, jadwal, lokasi, dan tim auditor yang bertugas.</p>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg border border-gray-200 shadow-xs text-xs">
                <div class="px-5 sm:px-6 divide-y divide-gray-100">
                    <!-- 1. Informasi Audit -->
                    <section class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4 py-6">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">Informasi Audit</h2>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">Nomor surat tugas, kategori, dan judul rencana audit.</p>
                        </div>

                        <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="audit_number" class="block font-medium text-gray-700 mb-1.5">Nomor Surat Tugas Audit <span class="text-red-500">*</span></label>
                                <input
                                    id="audit_number"
                                    v-model="form.audit_number"
                                    type="text"
                                    required
                                    class="w-full text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 tabular-nums"
                                />
                                <div v-if="form.errors.audit_number" class="text-red-600 text-[11px] mt-1">{{ form.errors.audit_number }}</div>
                            </div>

                            <div>
                                <label for="category_id" class="block font-medium text-gray-700 mb-1.5">Kategori Audit <span class="text-red-500">*</span></label>
                                <select
                                    id="category_id"
                                    v-model="form.category_id"
                                    required
                                    class="w-full text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="" disabled>Pilih kategori audit</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.category_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.category_id }}</div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="title" class="block font-medium text-gray-700 mb-1.5">Judul / Uraian Rencana Audit</label>
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Contoh: Audit Stock Opname & Cash Opname Cabang Retail"
                                    class="w-full text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="form.errors.title" class="text-red-600 text-[11px] mt-1">{{ form.errors.title }}</div>
                            </div>
                        </div>
                    </section>

                    <!-- 2. Jadwal Pelaksanaan -->
                    <section class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4 py-6">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">Jadwal Pelaksanaan</h2>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">Tanggal, jam mulai, dan status awal audit.</p>
                        </div>

                        <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="audit_date" class="block font-medium text-gray-700 mb-1.5">Tanggal <span class="text-red-500">*</span></label>
                                <input
                                    id="audit_date"
                                    v-model="form.audit_date"
                                    type="date"
                                    required
                                    class="w-full text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 tabular-nums"
                                />
                                <div v-if="form.errors.audit_date" class="text-red-600 text-[11px] mt-1">{{ form.errors.audit_date }}</div>
                            </div>

                            <div>
                                <label for="audit_time" class="block font-medium text-gray-700 mb-1.5">Jam Mulai</label>
                                <input
                                    id="audit_time"
                                    v-model="form.audit_time"
                                    type="time"
                                    class="w-full text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 tabular-nums"
                                />
                                <div v-if="form.errors.audit_time" class="text-red-600 text-[11px] mt-1">{{ form.errors.audit_time }}</div>
                            </div>

                            <div>
                                <label for="status" class="block font-medium text-gray-700 mb-1.5">Status</label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="w-full text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="PLANNED">Planned (Terencana)</option>
                                    <option value="IN_PROGRESS">In Progress (Berjalan)</option>
                                    <option value="COMPLETED">Completed (Selesai)</option>
                                    <option value="CLOSED">Closed (Ditutup)</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- 3. Lokasi Audit -->
                    <section class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4 py-6">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">Lokasi Audit</h2>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">Pilih unit dari master data CSA, atau ketik manual jika belum terdaftar.</p>
                        </div>

                        <div class="md:col-span-2 space-y-4">
                            <div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-1.5">
                                    <label class="block font-medium text-gray-700">
                                        Unit Toko / Badan Usaha <span class="text-red-500">*</span>
                                    </label>
                                    <div class="inline-flex self-start rounded-md bg-gray-100 p-0.5 text-[11px]">
                                        <button
                                            type="button"
                                            @click="useCustomStore = false"
                                            :class="!useCustomStore ? 'bg-white text-gray-900 font-medium shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                                            class="px-2.5 py-1 rounded transition-colors cursor-pointer"
                                        >
                                            Master Data
                                        </button>
                                        <button
                                            type="button"
                                            @click="useCustomStore = true"
                                            :class="useCustomStore ? 'bg-white text-gray-900 font-medium shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                                            class="px-2.5 py-1 rounded transition-colors cursor-pointer"
                                        >
                                            Input Manual
                                        </button>
                                    </div>
                                </div>

                                <select
                                    v-if="!useCustomStore"
                                    v-model="form.store_id"
                                    class="w-full text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="" disabled>Pilih unit toko / cabang CSA</option>
                                    <option v-for="s in stores" :key="s.id" :value="s.id">
                                        [{{ s.code }}] {{ s.name }} {{ s.area ? `• ${s.area}` : '' }}
                                    </option>
                                </select>
                                <input
                                    v-else
                                    v-model="form.custom_store_name"
                                    type="text"
                                    placeholder="Ketik nama toko atau badan usaha yang diperiksa"
                                    class="w-full text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                />

                                <p v-if="!useCustomStore && selectedStore && selectedStore.address" class="mt-1.5 flex gap-1 text-[11px] text-gray-500">
                                    <span class="shrink-0">📍</span>
                                    <span>{{ selectedStore.address }}</span>
                                </p>
                                <div v-if="form.errors.store_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.store_id }}</div>
                                <div v-if="form.errors.custom_store_name" class="text-red-600 text-[11px] mt-1">{{ form.errors.custom_store_name }}</div>
                            </div>

                            <div>
                                <label for="location" class="block font-medium text-gray-700 mb-1.5">Lokasi Khusus <span class="font-normal text-gray-400">(opsional)</span></label>
                                <input
                                    id="location"
                                    v-model="form.location"
                                    type="text"
                                    placeholder="Kosongkan untuk memakai alamat toko"
                                    class="w-full text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- 4. Tim Auditor -->
                    <section class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4 py-6">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">Tim Auditor</h2>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">Maksimal 5 auditor. Auditor pertama yang dipilih menjadi Lead Auditor.</p>
                        </div>

                        <div class="md:col-span-2">
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="auditor_select" class="block font-medium text-gray-700">
                                    Auditor Bertugas <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[11px] text-gray-500 tabular-nums">{{ form.auditor_ids.length }} / 5 dipilih</span>
                            </div>

                            <select
                                id="auditor_select"
                                @change="addAuditor"
                                class="w-full text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                :disabled="form.auditor_ids.length >= 5"
                            >
                                <option value="" selected>
                                    {{ form.auditor_ids.length === 0 ? 'Pilih Lead Auditor' : (form.auditor_ids.length >= 5 ? 'Maksimal 5 auditor tercapai' : '+ Tambah anggota tim') }}
                                </option>
                                <option
                                    v-for="auditor in availableAuditors"
                                    :key="auditor.id"
                                    :value="auditor.id"
                                >
                                    {{ auditor.name }} ({{ auditor.email }})
                                </option>
                            </select>

                            <div v-if="form.auditor_ids.length > 0" class="flex flex-wrap gap-2 mt-2.5">
                                <div
                                    v-for="(auditorId, index) in form.auditor_ids"
                                    :key="auditorId"
                                    class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 rounded-md text-xs border"
                                    :class="index === 0 ? 'bg-blue-50 border-blue-200 text-blue-900 font-medium' : 'bg-gray-50 border-gray-200 text-gray-700'"
                                >
                                    <span v-if="index === 0" class="px-1.5 py-0.5 rounded bg-blue-600 text-white font-semibold text-[10px]">Lead</span>
                                    <span>{{ getAuditor(auditorId)?.name || auditorId }}</span>
                                    <button
                                        type="button"
                                        @click="removeAuditor(auditorId)"
                                        class="w-4 h-4 inline-flex items-center justify-center rounded text-gray-400 hover:text-red-600 hover:bg-red-50 cursor-pointer text-sm leading-none"
                                        title="Hapus dari tim"
                                    >
                                        &times;
                                    </button>
                                </div>
                            </div>
                            <div v-if="form.errors.auditor_ids" class="text-red-600 text-[11px] mt-1">{{ form.errors.auditor_ids }}</div>
                        </div>
                    </section>

                    <!-- 5. Catatan -->
                    <section class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4 py-6">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">Catatan</h2>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">Instruksi khusus atau ruang lingkup audit (opsional).</p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="notes" class="sr-only">Catatan Tambahan</label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                placeholder="Catatan khusus, instruksi, atau ruang lingkup audit..."
                                class="w-full text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                        </div>
                    </section>
                </div>

                <!-- Footer Actions -->
                <div class="px-5 sm:px-6 py-4 bg-gray-50/70 border-t border-gray-200 rounded-b-lg flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3">
                    <Link
                        :href="route('coordinator.audits.index')"
                        class="w-full sm:w-auto text-center px-4 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium cursor-pointer"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-5 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 font-semibold disabled:opacity-50 shadow-xs cursor-pointer"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Jadwalkan Audit' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
