<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    audit: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    stores: {
        type: Array,
        default: () => [],
    },
    auditors: {
        type: Array,
        default: () => [],
    },
});

const useCustomStore = ref(false);

const initialAuditorIds = Array.isArray(props.audit.auditor_ids) && props.audit.auditor_ids.length > 0
    ? [...props.audit.auditor_ids]
    : (props.audit.auditor_id ? [props.audit.auditor_id] : []);

const form = useForm({
    audit_number: props.audit.audit_number,
    title: props.audit.title || '',
    category_id: props.audit.category_id || (props.categories.length > 0 ? props.categories[0].id : ''),
    store_id: props.audit.store_id,
    custom_store_name: '',
    auditor_ids: initialAuditorIds,
    audit_date: props.audit.audit_date,
    audit_time: props.audit.audit_time || '09:00',
    location: props.audit.location || '',
    status: props.audit.status,
    notes: props.audit.notes || '',
});

const selectedStore = computed(() => {
    if (useCustomStore.value || !form.store_id) return null;
    return props.stores.find(s => s.id === form.store_id);
});

const availableAuditors = computed(() => {
    // Pilihan pertama = lead, jadi hanya tampilkan yang boleh jadi lead
    return props.auditors
        .filter(a => !form.auditor_ids.some(id => Number(id) === Number(a.id)))
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
    if (!form.auditor_ids.some(id => Number(id) === val)) {
        form.auditor_ids.push(val);
    }
    event.target.value = '';
};

const removeAuditor = (auditorId) => {
    const index = form.auditor_ids.findIndex(id => Number(id) === Number(auditorId));
    if (index > -1) {
        form.auditor_ids.splice(index, 1);
    }
};

const getAuditor = (auditorId) => {
    return props.auditors.find(a => Number(a.id) === Number(auditorId));
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
    form.put(route('admin.audits.update', props.audit.id));
};
</script>

<template>
    <AppLayout :title="`Edit audit ${audit.audit_number}`">
        <Head :title="`Edit audit ${audit.audit_number}`" />

        <!-- Header -->
        <div class="mb-6">
            <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                <Link :href="route('admin.audits.index')" class="transition-colors duration-150 hover:text-blue-600">Audit</Link>
                <span>/</span>
                <Link :href="route('admin.audits.show', audit.id)" class="tabular-nums transition-colors duration-150 hover:text-blue-600">{{ audit.audit_number }}</Link>
                <span>/</span>
                <span class="font-medium text-gray-900">Edit</span>
            </div>
            <h1 class="text-2xl font-semibold leading-8 text-gray-900">Edit audit</h1>
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
                            <label class="mb-2 block text-sm font-medium text-gray-900">No. surat tugas <span class="text-red-600">*</span></label>
                            <input
                                v-model="form.audit_number"
                                type="text"
                                required
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                            <div v-if="form.errors.audit_number" class="mt-1 text-xs text-red-600">{{ form.errors.audit_number }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Kategori <span class="text-red-600">*</span></label>
                            <select
                                v-model="form.category_id"
                                required
                                class="h-10 w-full rounded-lg border-gray-300 bg-blue-50/40 pl-3 pr-9 text-sm font-medium text-blue-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            >
                                <option value="" disabled>Pilih kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">{{ form.errors.category_id }}</div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-900">Judul</label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Audit Stock Opname Bulanan"
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                            <div v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</div>
                        </div>
                    </div>
                </section>

                <!-- Lokasi -->
                <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Lokasi</h2>
                        <div class="inline-flex rounded-lg border border-gray-300 bg-gray-100 p-0.5 text-xs">
                            <button
                                type="button"
                                @click="useCustomStore = false"
                                :class="!useCustomStore ? 'bg-white text-gray-900 font-medium shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                                class="cursor-pointer rounded-md px-3 py-1 transition-colors duration-150"
                            >
                                Master data
                            </button>
                            <button
                                type="button"
                                @click="useCustomStore = true"
                                :class="useCustomStore ? 'bg-white text-gray-900 font-medium shadow-xs' : 'text-gray-600 hover:text-gray-900'"
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

                            <!-- Pilih dari master data -->
                            <div v-if="!useCustomStore">
                                <select
                                    v-model="form.store_id"
                                    aria-label="Unit toko / badan usaha"
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                >
                                    <option value="" disabled>Pilih toko</option>
                                    <option v-for="s in stores" :key="s.id" :value="s.id">
                                        [{{ s.code }}] {{ s.name }} {{ s.area ? `• ${s.area}` : '' }}
                                    </option>
                                </select>
                                <div v-if="selectedStore && selectedStore.address" class="mt-2 flex items-start gap-2 rounded-lg border border-gray-100 bg-gray-50 p-2 text-xs text-gray-600">
                                    <span class="shrink-0 font-medium text-blue-600">Alamat</span>
                                    <span>{{ selectedStore.address }}</span>
                                </div>
                            </div>

                            <!-- Input manual -->
                            <div v-else>
                                <input
                                    v-model="form.custom_store_name"
                                    type="text"
                                    placeholder="Nama toko / badan usaha"
                                    aria-label="Nama toko atau badan usaha baru"
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                />
                            </div>

                            <div v-if="form.errors.store_id" class="mt-1 text-xs text-red-600">{{ form.errors.store_id }}</div>
                            <div v-if="form.errors.custom_store_name" class="mt-1 text-xs text-red-600">{{ form.errors.custom_store_name }}</div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-900">Lokasi khusus <span class="font-normal text-gray-400">opsional</span></label>
                            <input
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
                        <span class="text-sm text-gray-500 tabular-nums" title="Maksimal 5 auditor, urutan pertama Lead Auditor">{{ form.auditor_ids.length }}/5</span>
                    </div>
                    <div class="p-5">
                        <select
                            @change="addAuditor"
                            aria-label="Tambah auditor bertugas"
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

                        <!-- Auditor terpilih -->
                        <div v-if="form.auditor_ids.length > 0" class="mt-3 flex flex-wrap gap-2">
                            <div
                                v-for="(auditorId, index) in form.auditor_ids"
                                :key="auditorId"
                                class="inline-flex items-center gap-2 rounded-md border py-1 pl-2 pr-1 text-xs"
                                :class="index === 0 ? 'bg-blue-50 border-blue-200 text-blue-900 font-medium' : 'bg-gray-50 border-gray-200 text-gray-700'"
                            >
                                <span
                                    v-if="index === 0"
                                    class="rounded-md bg-blue-600 px-2 py-0.5 text-xs font-medium text-white"
                                >
                                    Lead
                                </span>
                                <span>{{ getAuditor(auditorId)?.name || auditorId }}</span>
                                <button
                                    type="button"
                                    @click="removeAuditor(auditorId)"
                                    class="inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded-md text-sm leading-none text-gray-400 transition-colors duration-150 hover:text-red-600"
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
                <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Jadwal</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-4 p-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Tanggal <span class="text-red-600">*</span></label>
                            <input
                                v-model="form.audit_date"
                                type="date"
                                required
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                            <div v-if="form.errors.audit_date" class="mt-1 text-xs text-red-600">{{ form.errors.audit_date }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Jam</label>
                            <input
                                v-model="form.audit_time"
                                type="text"
                                placeholder="09:00"
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                            <div v-if="form.errors.audit_time" class="mt-1 text-xs text-red-600">{{ form.errors.audit_time }}</div>
                        </div>

                        <div class="col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-900">Status</label>
                            <select
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
                            <label class="mb-2 block text-sm font-medium text-gray-900">Catatan</label>
                            <textarea
                                v-model="form.notes"
                                rows="4"
                                class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                        <Link
                            :href="route('admin.audits.show', audit.id)"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Menyimpan…' : 'Perbarui audit' }}</span>
                        </button>
                    </div>
                </section>
            </div>
        </form>
    </AppLayout>
</template>
