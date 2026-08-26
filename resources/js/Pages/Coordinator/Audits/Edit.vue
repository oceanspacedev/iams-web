<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    audit: {
        type: Object,
        required: true,
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

const form = useForm({
    audit_number: props.audit.audit_number,
    title: props.audit.title || '',
    store_id: props.audit.store_id,
    auditor_id: props.audit.auditor_id,
    audit_date: props.audit.audit_date,
    audit_time: props.audit.audit_time || '09:00',
    location: props.audit.location || '',
    status: props.audit.status,
    notes: props.audit.notes || '',
});

const submit = () => {
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
            <p class="text-xs text-gray-500 mt-1">Perbarui detail pelaksanaan audit. Jadwal notifikasi akan disinkronkan secara otomatis.</p>
        </div>

        <div class="bg-white rounded border border-gray-200 p-6 shadow-xs max-w-2xl text-xs">
            <form @submit.prevent="submit" class="space-y-4">
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
                        <label class="block font-medium text-gray-700 mb-1">Judul / Jenis Audit</label>
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="Contoh: Audit Stock Opname Bulanan"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.title" class="text-red-600 text-[11px] mt-1">{{ form.errors.title }}</div>
                    </div>
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Toko Sasaran <span class="text-red-500">*</span></label>
                        <select
                            v-model="form.store_id"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option v-for="s in stores" :key="s.id" :value="s.id">
                                {{ s.name }} ({{ s.code }})
                            </option>
                        </select>
                        <div v-if="form.errors.store_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.store_id }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Auditor Bertugas <span class="text-red-500">*</span></label>
                        <select
                            v-model="form.auditor_id"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option v-for="a in auditors" :key="a.id" :value="a.id">
                                {{ a.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.auditor_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.auditor_id }}</div>
                    </div>
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
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
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
                        class="px-3.5 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 font-medium disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Perbarui Audit' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
