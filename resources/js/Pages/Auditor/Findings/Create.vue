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
    sops: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    category_id: props.audit.category_id || (props.categories.length > 0 ? props.categories[0].id : ''),
    sop_id: '',
    finding: '',
    loss_amount: '',
    auditor_opinion: '',
    recommendation: '',
    severity: 'MINOR',
});

const submit = () => {
    form.post(route('auditor.findings.store', props.audit.id));
};
</script>

<template>
    <AppLayout title="Buat Finding Baru">
        <Head :title="`Tambah Finding - ${audit.audit_number}`" />

        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('auditor.audits.index')" class="hover:text-blue-600">My Audits</Link>
                <span>/</span>
                <Link :href="route('auditor.audits.show', audit.id)" class="hover:text-blue-600">{{ audit.audit_number }}</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">Buat Finding</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Tambah Temuan Audit (Finding)</h1>
            <p class="text-xs text-gray-500 mt-1">Audit No: {{ audit.audit_number }} • Toko: {{ audit.store.name }}</p>
        </div>

        <div class="bg-white rounded border border-gray-200 p-6 shadow-xs max-w-4xl">
            <form @submit.prevent="submit" class="space-y-6 text-xs">
                <!-- Row 1: Category, SOP, Severity -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1.5">
                            Kategori Audit <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.category_id"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="" disabled>Pilih Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.category_id }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1.5">
                            SOP / Surat Edaran Terkait
                        </label>
                        <select
                            v-model="form.sop_id"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Pilih SOP/SE (Opsional)</option>
                            <option v-for="sop in sops" :key="sop.id" :value="sop.id">
                                {{ sop.code }} - {{ sop.title }}
                            </option>
                        </select>
                        <div v-if="form.errors.sop_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.sop_id }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1.5">
                            Severity / Dampak <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.severity"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-medium"
                        >
                            <option value="MINOR">Minor (Timeline SLA: 3 - 7 hari)</option>
                            <option value="MEDIUM">Medium (Timeline SLA: 8 - 14 hari)</option>
                            <option value="MAJOR">Major (Timeline SLA: 15 - 30 hari)</option>
                        </select>
                        <div v-if="form.errors.severity" class="text-red-600 text-[11px] mt-1">{{ form.errors.severity }}</div>
                    </div>
                </div>

                <!-- Severity SLA Info Guide -->
                <div class="bg-gray-50 border border-gray-200 rounded-md p-3 grid grid-cols-3 gap-3 text-center text-xs">
                    <div class="p-1.5 rounded bg-white border border-gray-200" :class="form.severity === 'MINOR' ? 'border-blue-500 bg-blue-50/40 text-blue-900 font-semibold' : 'text-gray-700'">
                        <div class="text-xs">Minor</div>
                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">3 - 7 hari</div>
                    </div>
                    <div class="p-1.5 rounded bg-white border border-gray-200" :class="form.severity === 'MEDIUM' ? 'border-blue-500 bg-blue-50/40 text-blue-900 font-semibold' : 'text-gray-700'">
                        <div class="text-xs">Medium</div>
                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">8 - 14 hari</div>
                    </div>
                    <div class="p-1.5 rounded bg-white border border-gray-200" :class="form.severity === 'MAJOR' ? 'border-blue-500 bg-blue-50/40 text-blue-900 font-semibold' : 'text-gray-700'">
                        <div class="text-xs">Major</div>
                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">15 - 30 hari</div>
                    </div>
                </div>

                <!-- Row 2: Loss amount -->
                <div class="max-w-xs">
                    <label class="block font-medium text-gray-700 mb-1.5">
                        Nominal Kerugian (Rp)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-xs">Rp</span>
                        <input
                            v-model="form.loss_amount"
                            type="number"
                            min="0"
                            placeholder="0"
                            class="w-full pl-9 text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <div v-if="form.errors.loss_amount" class="text-red-600 text-[11px] mt-1">{{ form.errors.loss_amount }}</div>
                </div>

                <!-- Row 3: Finding Description -->
                <div>
                    <label class="block font-medium text-gray-700 mb-1.5">
                        Uraian Temuan (Finding) <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="form.finding"
                        rows="4"
                        required
                        placeholder="Jelaskan kondisi temuan audit secara objektif dan faktual..."
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                    <div v-if="form.errors.finding" class="text-red-600 text-[11px] mt-1">{{ form.errors.finding }}</div>
                </div>

                <!-- Row 4: Auditor Opinion & Recommendation -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1.5">
                            Opini Auditor
                        </label>
                        <textarea
                            v-model="form.auditor_opinion"
                            rows="3"
                            placeholder="Analisis penyebab atau penilaian auditor terhadap dampak temuan..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                        <div v-if="form.errors.auditor_opinion" class="text-red-600 text-[11px] mt-1">{{ form.errors.auditor_opinion }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1.5">
                            Rekomendasi Perbaikan <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="form.recommendation"
                            rows="3"
                            required
                            placeholder="Langkah-langkah perbaikan yang disarankan untuk unit / auditee..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                        <div v-if="form.errors.recommendation" class="text-red-600 text-[11px] mt-1">{{ form.errors.recommendation }}</div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pt-4 border-t border-gray-200 flex items-center justify-end gap-3">
                    <Link
                        :href="route('auditor.audits.show', audit.id)"
                        class="px-4 py-2 text-xs font-medium rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 transition-colors shadow-xs"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Finding' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
