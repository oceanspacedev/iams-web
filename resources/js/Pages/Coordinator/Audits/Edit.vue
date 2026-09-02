<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
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

const form = useForm({
    audit_number: props.audit.audit_number,
    title: props.audit.title || '',
    category_id: props.audit.category_id || (props.categories.length > 0 ? props.categories[0].id : ''),
    store_id: props.audit.store_id,
    custom_store_name: '',
    auditor_ids: props.audit.auditor_ids || [props.audit.auditor_id],
    audit_date: props.audit.audit_date,
    audit_time: props.audit.audit_time || '09:00',
    location: props.audit.location || '',
    status: props.audit.status,
    notes: props.audit.notes || '',
});

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
    form.put(route('coordinator.audits.update', props.audit.id));
};
</script>

<template>
    <AppLayout :title="`Edit Audit - ${audit.audit_number}`">
        <Head :title="`Edit Audit - ${audit.audit_number} — Koordinator`" />

        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('coordinator.audits.index')" class="hover:text-blue-600">Audits</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">{{ audit.audit_number }}</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Edit Pelaksanaan Audit</h1>
            <p class="text-xs text-gray-500 mt-1">Perbarui kategori audit, data unit toko/badan usaha, dan tim auditor bertugas.</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-xs max-w-3xl text-xs">
            <form @submit.prevent="submit" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Nomor Audit <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.audit_number"
                            type="text"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                        />
                        <div v-if="form.errors.audit_number" class="text-red-600 text-[11px] mt-1">{{ form.errors.audit_number }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Kategori Audit <span class="text-red-500">*</span></label>
                        <select
                            v-model="form.category_id"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-semibold text-blue-900 bg-blue-50/40"
                        >
                            <option value="" disabled>-- Pilih Kategori Audit --</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.category_id }}</div>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Judul / Uraian Rencana Audit</label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="Contoh: Audit Stock Opname Bulanan"
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                    <div v-if="form.errors.title" class="text-red-600 text-[11px] mt-1">{{ form.errors.title }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Tanggal Pelaksanaan <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.audit_date"
                            type="date"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.audit_date" class="text-red-600 text-[11px] mt-1">{{ form.errors.audit_date }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Waktu / Jam Mulai</label>
                        <input
                            v-model="form.audit_time"
                            type="text"
                            placeholder="09:00"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                        />
                        <div v-if="form.errors.audit_time" class="text-red-600 text-[11px] mt-1">{{ form.errors.audit_time }}</div>
                    </div>
                </div>

                <!-- Unit Toko / Badan Usaha -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block font-medium text-gray-700">
                            Unit Toko / Badan Usaha <span class="text-red-500">*</span>
                        </label>
                        <div class="inline-flex rounded border border-gray-300 bg-gray-100 p-0.5 text-[11px]">
                            <button
                                type="button"
                                @click="useCustomStore = false"
                                :class="!useCustomStore ? 'bg-white text-gray-900 font-medium shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                                class="px-2.5 py-1 rounded transition-colors cursor-pointer"
                            >
                                Pilih dari Master Data
                            </button>
                            <button
                                type="button"
                                @click="useCustomStore = true"
                                :class="useCustomStore ? 'bg-white text-gray-900 font-medium shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                                class="px-2.5 py-1 rounded transition-colors cursor-pointer"
                            >
                                Input Manual (Free Text)
                            </button>
                        </div>
                    </div>

                    <!-- Dropdown selection mode -->
                    <div v-if="!useCustomStore">
                        <select
                            v-model="form.store_id"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option v-for="s in stores" :key="s.id" :value="s.id">
                                {{ s.name }} ({{ s.code }}) {{ s.type ? `[${s.type.toUpperCase()}]` : '' }} {{ s.business_entity ? `- ${s.business_entity}` : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Free text input mode -->
                    <div v-else>
                        <input
                            v-model="form.custom_store_name"
                            type="text"
                            placeholder="Ketik nama toko atau badan usaha baru..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div v-if="form.errors.store_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.store_id }}</div>
                    <div v-if="form.errors.custom_store_name" class="text-red-600 text-[11px] mt-1">{{ form.errors.custom_store_name }}</div>
                </div>

                <!-- TIM AUDITOR: MULTI-SELECT UP TO 5 PERSONS -->
                <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block font-bold text-gray-800 text-xs">
                            Tim Auditor Aktif Bertugas <span class="text-red-500">*</span>
                        </label>
                        <span
                            class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                            :class="form.auditor_ids.length > 0 ? 'bg-blue-100 text-blue-800' : 'bg-rose-100 text-rose-800'"
                        >
                            {{ form.auditor_ids.length }} / 5 Dipilih
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-500 mb-3">
                        Pilih minimal 1 dan maksimal 5 auditor aktif. Auditor urutan pertama bertindak sebagai <strong>Lead Auditor</strong>.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-48 overflow-y-auto p-1 bg-white rounded border border-gray-200">
                        <div
                            v-for="auditor in auditors"
                            :key="auditor.id"
                            @click="toggleAuditor(auditor.id)"
                            class="p-2.5 rounded border transition-all cursor-pointer flex items-center justify-between gap-2"
                            :class="form.auditor_ids.includes(auditor.id) ? 'border-blue-500 bg-blue-50/60 font-semibold text-blue-950' : 'border-gray-200 text-gray-700 hover:bg-gray-50'"
                        >
                            <div class="truncate">
                                <div class="truncate text-xs">{{ auditor.name }}</div>
                                <div class="text-[10px] text-gray-400 font-normal truncate">{{ auditor.email }}</div>
                            </div>
                            <div class="shrink-0 flex items-center">
                                <span
                                    v-if="form.auditor_ids.indexOf(auditor.id) === 0"
                                    class="text-[9px] px-1.5 py-0.5 rounded bg-blue-600 text-white font-bold mr-1"
                                >
                                    Lead
                                </span>
                                <input
                                    type="checkbox"
                                    :checked="form.auditor_ids.includes(auditor.id)"
                                    class="rounded text-blue-600 focus:ring-blue-500"
                                    @click.stop="toggleAuditor(auditor.id)"
                                />
                            </div>
                        </div>
                    </div>
                    <div v-if="form.errors.auditor_ids" class="text-red-600 text-[11px] mt-1">{{ form.errors.auditor_ids }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Lokasi Khusus (Opsional)</label>
                        <input
                            v-model="form.location"
                            type="text"
                            placeholder="Biarkan kosong untuk default alamat toko"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Status Audit</label>
                        <select
                            v-model="form.status"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-medium"
                        >
                            <option value="PLANNED">Planned (Terencana)</option>
                            <option value="IN_PROGRESS">In Progress (Sedang Berjalan)</option>
                            <option value="COMPLETED">Completed (Selesai)</option>
                            <option value="CLOSED">Closed (Ditutup)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Catatan Tambahan</label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <div class="pt-4 border-t border-gray-200 flex items-center justify-end gap-3">
                    <Link
                        :href="route('coordinator.audits.index')"
                        class="px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold disabled:opacity-50 shadow-xs"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Perbarui Audit' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
