<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    findings: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
    preselectedFinding: {
        type: [String, Number],
        default: null,
    },
    preselectedAudit: {
        type: [String, Number],
        default: null,
    },
});

const form = useForm({
    finding_id: props.preselectedFinding || '',
    quality_category: 'impact_50m',
    title: '',
    impact_amount: '',
    root_cause: '',
    systemic_issue: '',
    recommendation: '',
    auditor_notes: '',
});

// Auto fill loss amount when finding is selected
watch(
    () => form.finding_id,
    (selectedId) => {
        const found = props.findings.find(f => f.id === Number(selectedId));
        if (found) {
            if (!form.title) {
                form.title = `Temuan Kritis: ${found.finding.substring(0, 70)}...`;
            }
            if (found.loss_amount && !form.impact_amount) {
                form.impact_amount = found.loss_amount;
            }
        }
    }
);

const selectedFindingObj = computed(() => {
    return props.findings.find(f => f.id === Number(form.finding_id));
});

const submit = () => {
    form.post(route('auditor.finding-qualities.store'));
};
</script>

<template>
    <AppLayout title="Buat Laporan Finding Quality">
        <Head title="Buat Laporan Finding Quality" />

        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('auditor.finding-qualities.index')" class="hover:text-blue-600">Finding Quality</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">Buat Laporan Baru</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Formulir Laporan Finding Quality</h1>
            <p class="text-xs text-gray-500 mt-1">
                Pilih salah satu dari 4 pilar kriteria temuan berkualitas tinggi untuk dilaporkan ke manajemen
            </p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-xs max-w-4xl text-xs">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- STEP 1: Pilih Finding Referensi -->
                <div>
                    <label class="block font-semibold text-gray-800 text-xs mb-1.5">
                        1. Referensi Temuan Audit (Nomor Audit & Uraian) <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.finding_id"
                        required
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                    >
                        <option value="" disabled>-- Pilih Nomor Temuan Audit --</option>
                        <option v-for="f in findings" :key="f.id" :value="f.id">
                            {{ f.display_label }}
                        </option>
                    </select>
                    <div v-if="form.errors.finding_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.finding_id }}</div>

                    <!-- Highlight box for selected finding -->
                    <div v-if="selectedFindingObj" class="mt-2.5 p-3 rounded bg-slate-50 border border-slate-200">
                        <div class="font-semibold text-gray-900 flex items-center gap-2">
                            <span>{{ selectedFindingObj.audit_number }}</span>
                            <span>• {{ selectedFindingObj.store_name }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 bg-slate-200 rounded font-normal">{{ selectedFindingObj.category }}</span>
                        </div>
                        <p class="text-gray-700 mt-1 font-normal">"{{ selectedFindingObj.finding }}"</p>
                    </div>
                </div>

                <!-- STEP 2: Pilih 1 dari 4 Kategori Target Temuan High Quality -->
                <div>
                    <label class="block font-semibold text-gray-800 text-xs mb-2">
                        2. Kategori Target Finding Quality <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="(cat, key) in categories"
                            :key="key"
                            @click="form.quality_category = key"
                            class="p-3.5 rounded-lg border cursor-pointer transition-colors flex items-start gap-3"
                            :class="form.quality_category === key ? 'border-blue-600 bg-blue-50/40 ring-1 ring-blue-600' : 'border-gray-200 hover:border-gray-300 bg-white'"
                        >
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-gray-900 text-xs">{{ cat.label }}</div>
                                    <input
                                        type="radio"
                                        name="quality_category"
                                        :value="key"
                                        v-model="form.quality_category"
                                        class="text-blue-600 focus:ring-blue-500"
                                    />
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1 leading-relaxed">{{ cat.description }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="form.errors.quality_category" class="text-red-600 text-[11px] mt-1">{{ form.errors.quality_category }}</div>
                </div>

                <!-- STEP 3: Judul & Dampak Finansial -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block font-medium text-gray-700 mb-1">
                            Judul Laporan Finding Quality <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="Contoh: Selisih Fisik Barang Dagangan Senilai Rp 65 Juta di DC Cikarang"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.title" class="text-red-600 text-[11px] mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Nilai Dampak / Kerugian (Rp)
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-xs">Rp</span>
                            <input
                                v-model="form.impact_amount"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="w-full pl-9 text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                            />
                        </div>
                        <div v-if="form.errors.impact_amount" class="text-red-600 text-[11px] mt-1">{{ form.errors.impact_amount }}</div>
                    </div>
                </div>

                <!-- STEP 4: Root Cause & Isu Sistemik -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Root Cause / Analisis Penyebab Utama <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="form.root_cause"
                            rows="4"
                            required
                            placeholder="Jelaskan akar penyebab terjadinya temuan, mengapa kontrol tidak berfungsi..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                        <div v-if="form.errors.root_cause" class="text-red-600 text-[11px] mt-1">{{ form.errors.root_cause }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Kelemahan Sistemik / Celah Prosedur
                        </label>
                        <textarea
                            v-model="form.systemic_issue"
                            rows="4"
                            placeholder="Potensi keterulangan di unit/cabang lain atau kelemahan validasi sistem..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                        <div v-if="form.errors.systemic_issue" class="text-red-600 text-[11px] mt-1">{{ form.errors.systemic_issue }}</div>
                    </div>
                </div>

                <!-- STEP 5: Rekomendasi Strategis & Catatan -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Rekomendasi Perbaikan Strategis <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="form.recommendation"
                            rows="3"
                            required
                            placeholder="Langkah perbaikan dan mitigasi yang direkomendasikan..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                        <div v-if="form.errors.recommendation" class="text-red-600 text-[11px] mt-1">{{ form.errors.recommendation }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Catatan Khusus Auditor
                        </label>
                        <textarea
                            v-model="form.auditor_notes"
                            rows="3"
                            placeholder="Catatan tambahan atau temuan pendukung..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="pt-4 border-t border-gray-200 flex items-center justify-end gap-3">
                    <Link
                        :href="route('auditor.finding-qualities.index')"
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
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Laporan' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
