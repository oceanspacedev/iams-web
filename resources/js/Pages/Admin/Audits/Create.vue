<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';

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

const selectedStoreName = computed(() => {
    if (useCustomStore.value) {
        return form.custom_store_name ? `${form.custom_store_name} (Manual)` : 'Ketik nama toko/unit baru';
    }
    const store = props.stores.find(s => s.id === form.store_id);
    return store ? `${store.name} (${store.code})` : 'Belum dipilih';
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
    form.post(route('admin.audits.store'));
};
</script>

<template>
    <AppLayout title="Jadwalkan Audit Baru">
        <Head title="Jadwalkan Audit Baru — Admin" />

        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('admin.audits.index')" class="hover:text-blue-600">Audits</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">Jadwalkan Audit</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Jadwalkan Penugasan Audit Baru</h1>
            <p class="text-xs text-gray-500 mt-1">Pilih kategori audit, tentukan unit toko retail / gudang (CSA) atau ketik nama toko bebas, tanggal & waktu audit.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start text-xs">
            <!-- Kolom Kiri: Form Input Jadwal Audit -->
            <div class="lg:col-span-8 bg-white rounded-lg border border-gray-200 p-6 shadow-xs">
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
                            <option value="" disabled>Pilih Unit Toko / Cabang</option>
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
                            placeholder="Ketik nama toko atau badan usaha yang diperiksa"
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
                            Tim Auditor Bertugas <span class="text-red-500">*</span>
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
                        placeholder="Catatan khusus, instruksi, atau ruang lingkup audit..."
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <div class="pt-4 border-t border-gray-200 flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.audits.index')"
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
                        <span>{{ form.processing ? 'Menyimpan...' : 'Jadwalkan Audit' }}</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Ringkasan Penugasan & Kalender Jadwal Aktif CSA -->
        <div class="lg:col-span-4 space-y-4">
            <!-- Card 1: Pratinjau / Ringkasan Surat Tugas yang Sedang Dibuat -->
            <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-xs text-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800 uppercase tracking-wider text-[11px]">
                        Ringkasan Surat Tugas Baru
                    </h3>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                        Draft
                    </span>
                </div>

                <div class="space-y-2.5">
                    <div>
                        <span class="text-gray-400 font-medium">Nomor Surat Tugas:</span>
                        <div class="font-mono font-bold text-gray-900 mt-0.5">{{ form.audit_number || '—' }}</div>
                    </div>

                    <div>
                        <span class="text-gray-400 font-medium">Kategori Audit:</span>
                        <div class="font-semibold text-blue-700 mt-0.5">{{ selectedCategoryName }}</div>
                    </div>

                    <div>
                        <span class="text-gray-400 font-medium">Sasaran Toko / Badan Usaha:</span>
                        <div class="font-semibold text-gray-900 mt-0.5">{{ selectedStoreName }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-gray-400 font-medium">Tanggal:</span>
                            <div class="font-mono font-semibold text-gray-900 mt-0.5">{{ form.audit_date || '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-400 font-medium">Waktu:</span>
                            <div class="font-mono font-semibold text-gray-900 mt-0.5">{{ form.audit_time || '09:00' }} WIB</div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 font-medium">Tim Auditor Bertugas:</span>
                            <span class="text-[11px] font-mono" :class="selectedAuditorsList.length > 0 ? 'text-blue-600 font-semibold' : 'text-gray-400'">
                                {{ selectedAuditorsList.length }}/5 Dipilih
                            </span>
                        </div>
                        <div v-if="selectedAuditorsList.length > 0" class="mt-1.5 space-y-1">
                            <div
                                v-for="(aud, idx) in selectedAuditorsList"
                                :key="aud.id"
                                class="flex items-center justify-between bg-gray-50 px-2.5 py-1 rounded border border-gray-200 text-[11px]"
                            >
                                <span class="font-medium text-gray-800">{{ aud.name }}</span>
                                <span v-if="idx === 0" class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Lead Auditor</span>
                                <span v-else class="text-[10px] text-gray-500">Anggota</span>
                            </div>
                        </div>
                        <div v-else class="text-gray-400 italic mt-0.5">
                            Belum ada auditor dipilih
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert / Deteksi Jadwal Sama -->
            <div v-if="sameDateAudits.length > 0" class="p-3 bg-amber-50 rounded-lg border border-amber-200">
                <div class="font-semibold text-amber-800 text-[11px] mb-1">
                    Pemberitahuan Jadwal: {{ form.audit_date }}
                </div>
                <p class="text-amber-700 text-[11px] leading-relaxed">
                    Terdapat {{ sameDateAudits.length }} agenda audit lain pada tanggal ini. Mohon pastikan ketersediaan personil agar tidak terjadi bentrok penugasan.
                </p>
            </div>

            <!-- Alert / Toko Yang Sama Memiliki Audit Aktif -->
            <div v-if="sameStoreAudits.length > 0" class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                <div class="font-semibold text-blue-800 text-[11px] mb-1">
                    Informasi Unit Toko
                </div>
                <p class="text-blue-700 text-[11px] leading-relaxed">
                    Unit toko ini tercatat memiliki {{ sameStoreAudits.length }} jadwal pemeriksaan lain di sistem.
                </p>
            </div>

            <!-- Card 2: Jadwal Audit Aktif / Terdekat di CSA -->
            <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-xs text-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <div>
                        <h3 class="font-semibold text-gray-800 uppercase tracking-wider text-[11px]">
                            Jadwal Audit Aktif di CSA
                        </h3>
                        <p class="text-[10px] text-gray-400 mt-0.5">Penugasan audit yang sedang berjalan atau terdekat</p>
                    </div>
                    <span class="text-gray-500 font-mono text-[11px] bg-gray-100 px-1.5 py-0.5 rounded">
                        {{ upcoming_audits.length }}
                    </span>
                </div>

                <div v-if="upcoming_audits.length > 0" class="space-y-2 max-h-[420px] overflow-y-auto pr-0.5">
                    <div
                        v-for="item in upcoming_audits"
                        :key="item.id"
                        class="p-2.5 rounded border transition-colors text-[11px]"
                        :class="item.audit_date === form.audit_date ? 'bg-amber-50/70 border-amber-300' : 'bg-gray-50/70 border-gray-200 hover:bg-gray-50'"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-semibold text-gray-900">{{ item.store_name }}</div>
                                <div class="text-[10px] text-gray-500 font-mono">{{ item.audit_number }}</div>
                            </div>
                            <span
                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold shrink-0"
                                :class="item.status === 'IN_PROGRESS' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700'"
                            >
                                {{ item.status === 'IN_PROGRESS' ? 'Berjalan' : 'Terjadwal' }}
                            </span>
                        </div>

                        <div class="mt-2 flex items-center justify-between text-[11px] border-t border-gray-200/60 pt-1.5">
                            <span class="font-medium text-blue-700 truncate mr-2">{{ item.category }}</span>
                            <span class="font-mono text-gray-600 shrink-0">{{ item.audit_date_formatted }}</span>
                        </div>

                        <div class="mt-1 text-[10px] text-gray-500 flex items-center justify-between">
                            <span>Lead: <strong class="text-gray-700">{{ item.lead_auditor }}</strong></span>
                            <span v-if="item.audit_date === form.audit_date" class="text-amber-700 font-semibold">
                                Tanggal Sama
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-gray-400 italic text-center py-4 text-[11px]">
                    Tidak ada agenda audit aktif terdekat.
                </div>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
