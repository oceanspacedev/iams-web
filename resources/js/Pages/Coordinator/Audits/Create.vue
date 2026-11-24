<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
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
    // Pilihan pertama = lead, jadi hanya tampilkan yang boleh jadi lead
    return props.auditors
        .filter(a => !form.auditor_ids.includes(a.id))
        .filter(a => form.auditor_ids.length > 0 || a.can_lead !== false);
});

// Lead (urutan pertama) wajib ber-role auditor (can_lead dari controller).
// Kalau lead dihapus dan urutan pertama jadi non-auditor, naikkan auditor pertama yang memenuhi syarat.
const ensureLeadFirst = () => {
    const ids = form.auditor_ids;
    if (!ids.length) return;
    const canLead = (id) => props.auditors.find(a => Number(a.id) === Number(id))?.can_lead !== false;
    if (!canLead(ids[0])) {
        const idx = ids.findIndex(canLead);
        if (idx > 0) {
            const [lead] = ids.splice(idx, 1);
            ids.unshift(lead);
        }
    }
};
watch(() => form.auditor_ids.slice(), ensureLeadFirst);

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
    <AppLayout title="Jadwalkan audit">
        <Head title="Jadwalkan audit" />

        <!-- Header -->
        <div class="mb-6">
            <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                <Link :href="route('coordinator.audits.index')" class="transition-colors duration-150 hover:text-blue-600">Audit</Link>
                <span>/</span>
                <span class="font-medium text-gray-900">Baru</span>
            </div>
            <h1 class="text-2xl font-semibold leading-8 text-gray-900">Jadwalkan audit</h1>
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 items-start gap-6 text-sm lg:grid-cols-3">
            <!-- Kolom utama -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Informasi audit -->
                <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Informasi audit</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                        <div>
                            <label for="audit_number" class="mb-2 block text-sm font-medium text-gray-900">No. surat tugas <span class="text-red-600">*</span></label>
                            <input
                                id="audit_number"
                                v-model="form.audit_number"
                                type="text"
                                required
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                            <div v-if="form.errors.audit_number" class="mt-1 text-xs text-red-600">{{ form.errors.audit_number }}</div>
                        </div>

                        <div>
                            <label for="category_id" class="mb-2 block text-sm font-medium text-gray-900">Kategori <span class="text-red-600">*</span></label>
                            <select
                                id="category_id"
                                v-model="form.category_id"
                                required
                                class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            >
                                <option value="" disabled>Pilih kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">{{ form.errors.category_id }}</div>
                        </div>

                        <div class="md:col-span-2">
                            <label for="title" class="mb-2 block text-sm font-medium text-gray-900">Judul</label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                placeholder="Audit Stock Opname Bulanan"
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                            <div v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</div>
                        </div>
                    </div>
                </section>

                <!-- Lokasi audit -->
                <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Lokasi</h2>
                        <div class="inline-flex rounded-lg bg-gray-100 p-0.5 text-xs">
                            <button
                                type="button"
                                @click="useCustomStore = false"
                                :class="!useCustomStore ? 'bg-white text-gray-900 font-medium shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                                class="cursor-pointer rounded-md px-3 py-1 transition-colors duration-150"
                            >
                                Master data
                            </button>
                            <button
                                type="button"
                                @click="useCustomStore = true"
                                :class="useCustomStore ? 'bg-white text-gray-900 font-medium shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                                class="cursor-pointer rounded-md px-3 py-1 transition-colors duration-150"
                            >
                                Manual
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-900">
                                Unit toko <span class="text-red-600">*</span>
                            </label>
                            <select
                                v-if="!useCustomStore"
                                v-model="form.store_id"
                                aria-label="Unit toko / badan usaha"
                                class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            >
                                <option value="" disabled>Pilih toko</option>
                                <option v-for="s in stores" :key="s.id" :value="s.id">
                                    [{{ s.code }}] {{ s.name }} {{ s.area ? `• ${s.area}` : '' }}
                                </option>
                            </select>
                            <input
                                v-else
                                v-model="form.custom_store_name"
                                type="text"
                                placeholder="Nama toko / badan usaha"
                                aria-label="Nama toko atau badan usaha yang diperiksa"
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />

                            <p v-if="!useCustomStore && selectedStore && selectedStore.address" class="mt-2 text-xs text-gray-500">
                                {{ selectedStore.address }}
                            </p>
                            <div v-if="form.errors.store_id" class="mt-1 text-xs text-red-600">{{ form.errors.store_id }}</div>
                            <div v-if="form.errors.custom_store_name" class="mt-1 text-xs text-red-600">{{ form.errors.custom_store_name }}</div>
                        </div>

                        <div class="md:col-span-2">
                            <label for="location" class="mb-2 block text-sm font-medium text-gray-900">Lokasi khusus <span class="font-normal text-gray-400">opsional</span></label>
                            <input
                                id="location"
                                v-model="form.location"
                                type="text"
                                placeholder="Kosong = alamat toko"
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                        </div>
                    </div>
                </section>

                <!-- Tim auditor -->
                <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Tim auditor <span class="text-red-600">*</span></h2>
                        <span class="text-sm text-gray-500 tabular-nums" title="Maksimal 5 auditor">{{ form.auditor_ids.length }}/5</span>
                    </div>
                    <div class="p-5">
                        <label for="auditor_select" class="sr-only">Auditor bertugas</label>
                        <select
                            id="auditor_select"
                            @change="addAuditor"
                            class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            :disabled="form.auditor_ids.length >= 5"
                        >
                            <option value="" selected>
                                {{ form.auditor_ids.length === 0 ? 'Pilih Lead Auditor' : (form.auditor_ids.length >= 5 ? 'Maksimal 5 auditor' : 'Tambah anggota') }}
                            </option>
                            <option
                                v-for="auditor in availableAuditors"
                                :key="auditor.id"
                                :value="auditor.id"
                            >
                                {{ auditor.name }} ({{ auditor.email }})
                            </option>
                        </select>

                        <div v-if="form.auditor_ids.length > 0" class="mt-3 flex flex-wrap gap-2">
                            <div
                                v-for="(auditorId, index) in form.auditor_ids"
                                :key="auditorId"
                                class="inline-flex items-center gap-2 rounded-md border py-1 pl-2 pr-1 text-xs"
                                :class="index === 0 ? 'bg-blue-50 border-blue-200 text-blue-900 font-medium' : 'bg-gray-50 border-gray-200 text-gray-700'"
                            >
                                <span v-if="index === 0" class="rounded-md bg-blue-600 px-2 py-0.5 text-xs font-medium text-white">Lead</span>
                                <span>{{ getAuditor(auditorId)?.name || auditorId }}</span>
                                <button
                                    type="button"
                                    @click="removeAuditor(auditorId)"
                                    class="inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded-md text-sm leading-none text-gray-400 transition-colors duration-150 hover:bg-red-50 hover:text-red-600"
                                    title="Hapus dari tim"
                                    aria-label="Hapus dari tim"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>
                        <div v-if="form.errors.auditor_ids" class="mt-1 text-xs text-red-600">{{ form.errors.auditor_ids }}</div>
                    </div>
                </section>
            </div>

            <!-- Kolom samping -->
            <div class="space-y-6 lg:sticky lg:top-6">
                <!-- Jadwal -->
                <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Jadwal</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-4 p-5">
                        <div>
                            <label for="audit_date" class="mb-2 block text-sm font-medium text-gray-900">Tanggal <span class="text-red-600">*</span></label>
                            <input
                                id="audit_date"
                                v-model="form.audit_date"
                                type="date"
                                required
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                            <div v-if="form.errors.audit_date" class="mt-1 text-xs text-red-600">{{ form.errors.audit_date }}</div>
                        </div>

                        <div>
                            <label for="audit_time" class="mb-2 block text-sm font-medium text-gray-900">Jam</label>
                            <input
                                id="audit_time"
                                v-model="form.audit_time"
                                type="time"
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                            <div v-if="form.errors.audit_time" class="mt-1 text-xs text-red-600">{{ form.errors.audit_time }}</div>
                        </div>

                        <div class="col-span-2">
                            <label for="status" class="mb-2 block text-sm font-medium text-gray-900">Status</label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            >
                                <option value="PLANNED">Terjadwal</option>
                                <option value="IN_PROGRESS">Dalam proses</option>
                                <option value="COMPLETED">Selesai</option>
                                <option value="CLOSED">Ditutup</option>
                            </select>
                        </div>

                        <div class="col-span-2">
                            <label for="notes" class="mb-2 block text-sm font-medium text-gray-900">Catatan</label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                placeholder="Instruksi atau ruang lingkup"
                                class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 rounded-b-xl border-t border-gray-200 bg-gray-50/70 px-5 py-4">
                        <Link
                            :href="route('coordinator.audits.index')"
                            class="inline-flex h-9 cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex h-9 flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Menyimpan…' : 'Jadwalkan audit' }}</span>
                        </button>
                    </div>
                </section>
            </div>
        </form>
    </AppLayout>
</template>
