<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
        default: 'OPEN',
    },
    hasDocuments: {
        type: Boolean,
        default: false,
    },
    hasActionPlan: {
        type: Boolean,
        default: false,
    },
    documentsCount: {
        type: Number,
        default: 0,
    },
    showNote: {
        type: Boolean,
        default: true,
    },
});

const isClosed = computed(() => props.status === 'CLOSED');
const isInProgress = computed(() => ['IN_PROGRESS', 'WAITING_VERIFICATION', 'VERIFIED'].includes(props.status));

// Tahap 2 dianggap selesai (ceklis hijau) jika:
// - Status sudah CLOSED, ATAU
// - Dokumen LHP/BAP sudah diunggah (hasDocuments atau documentsCount > 0), ATAU
// - Komitmen perbaikan toko sudah diisi (hasActionPlan), ATAU
// - Status finding sudah IN_PROGRESS / WAITING_VERIFICATION / VERIFIED
const isStep2Complete = computed(() => {
    return isClosed.value || props.hasDocuments || props.documentsCount > 0 || props.hasActionPlan || isInProgress.value;
});
</script>

<template>
    <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-xs text-xs">
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                Status Alur Pemeriksaan
            </h3>
            <span
                class="px-2 py-0.5 rounded text-[11px] font-semibold"
                :class="isClosed 
                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' 
                    : (isStep2Complete 
                        ? 'bg-blue-50 text-blue-700 border border-blue-200' 
                        : 'bg-amber-50 text-amber-700 border border-amber-200')"
            >
                {{ isClosed ? 'Selesai / Closed' : (isStep2Complete ? 'Dalam Tindak Lanjut' : 'Temuan Terbuka (Open)') }}
            </span>
        </div>

        <!-- 3-Step Clean Enterprise Timeline -->
        <div class="space-y-3 pt-1">
            <!-- Step 1: Temuan Dicatat -->
            <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 font-semibold text-[11px] border bg-emerald-600 text-white border-emerald-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="font-semibold text-gray-900">1. Temuan Dicatat</div>
                    <div class="text-[11px] text-gray-500">Temuan & rekomendasi auditor telah didaftarkan</div>
                </div>
            </div>

            <!-- Step 2: Tindak Lanjut & Dokumen LHP/BAP -->
            <div class="flex items-start gap-3">
                <div
                    class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 font-semibold text-[11px] border transition-colors"
                    :class="isStep2Complete ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-blue-50 text-blue-600 border-blue-300'"
                >
                    <svg v-if="isStep2Complete" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span v-else>2</span>
                </div>
                <div class="flex-1">
                    <div class="font-semibold" :class="isStep2Complete ? 'text-gray-900' : 'text-gray-700'">
                        2. Tindak Lanjut & Dokumen LHP/BAP
                    </div>
                    <div class="text-[11px]" :class="isStep2Complete ? 'text-emerald-700 font-medium' : 'text-gray-500'">
                        <span v-if="hasDocuments || documentsCount > 0">
                            Dokumen LHP / BAP bertanda tangan telah diunggah {{ documentsCount > 0 ? `(${documentsCount} file)` : '' }}
                        </span>
                        <span v-else-if="hasActionPlan">
                            Komitmen perbaikan toko telah didaftarkan
                        </span>
                        <span v-else-if="isStep2Complete">
                            Komitmen perbaikan toko & dokumen dalam proses
                        </span>
                        <span v-else>
                            Menunggu unggah dokumen LHP/BAP atau komitmen perbaikan
                        </span>
                    </div>
                </div>
            </div>

            <!-- Step 3: Closed -->
            <div class="flex items-start gap-3">
                <div
                    class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 font-semibold text-[11px] border transition-colors"
                    :class="isClosed 
                        ? 'bg-emerald-600 text-white border-emerald-600' 
                        : (isStep2Complete 
                            ? 'bg-amber-50 text-amber-700 border-amber-300 font-semibold' 
                            : 'bg-gray-100 text-gray-400 border-gray-200')"
                >
                    <svg v-if="isClosed" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span v-else>3</span>
                </div>
                <div class="flex-1">
                    <div class="font-semibold" :class="isClosed ? 'text-gray-900' : (isStep2Complete ? 'text-gray-800' : 'text-gray-400')">
                        3. Temuan Ditutup (Closed)
                    </div>
                    <div class="text-[11px]" :class="isClosed ? 'text-emerald-700 font-medium' : (isStep2Complete ? 'text-amber-700' : 'text-gray-400')">
                        <span v-if="isClosed">Temuan tuntas dan dinyatakan selesai oleh auditor</span>
                        <span v-else-if="isStep2Complete">Dokumen dan tindak lanjut terpenuhi, siap ditinjau & ditutup</span>
                        <span v-else>Temuan tuntas dan dinyatakan selesai oleh auditor</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showNote" class="mt-4 pt-3 border-t border-gray-100 text-[11px]">
            <span v-if="isClosed" class="text-emerald-700 font-medium">Temuan ini telah selesai dan ditutup secara resmi.</span>
            <span v-else-if="isStep2Complete" class="text-blue-700 font-medium">Dokumen LHP/BAP telah terpenuhi. Auditor dapat menutup temuan ini jika perbaikan lapangan telah tuntas.</span>
            <span v-else class="text-gray-500">Auditor dapat mengunggah LHP/BAP serta melengkapi tindak lanjut perbaikan toko.</span>
        </div>
    </div>
</template>
