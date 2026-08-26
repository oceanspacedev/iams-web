<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
        default: 'OPEN',
    },
    showNote: {
        type: Boolean,
        default: true,
    },
});

const steps = [
    {
        key: 'OPEN',
        label: 'Temuan Dibuka',
        desc: 'Temuan dicatat & didaftarkan ke sistem',
        role: 'Auditor',
    },
    {
        key: 'IN_PROGRESS',
        label: 'Action Plan Toko',
        desc: 'Toko menyusun rencana perbaikan & PIC',
        role: 'Toko / Auditee',
    },
    {
        key: 'WAITING_VERIFICATION',
        label: 'Verifikasi Bukti',
        desc: 'Foto bukti diunggah & menunggu validasi',
        role: 'Auditor',
    },
    {
        key: 'VERIFIED',
        label: 'Bukti Disetujui',
        desc: 'Auditor telah menyetujui bukti perbaikan',
        role: 'Auditor',
    },
    {
        key: 'CLOSED',
        label: 'Selesai / Ditutup',
        desc: 'Temuan tuntas & terarsip resmi',
        role: 'Auditor',
    },
];

const isClosed = computed(() => props.status === 'CLOSED');

// Determine step state: 'completed' | 'active' | 'pending'
const getStepState = (index) => {
    if (props.status === 'CLOSED') {
        return 'completed'; // Every single step is completed 100%
    }

    if (props.status === 'VERIFIED') {
        // Steps 0, 1, 2, 3 are completed; Step 4 (Closing) is active
        if (index <= 3) return 'completed';
        return 'active';
    }

    if (props.status === 'WAITING_VERIFICATION') {
        if (index < 2) return 'completed';
        if (index === 2) return 'active';
        return 'pending';
    }

    if (props.status === 'IN_PROGRESS') {
        if (index < 1) return 'completed';
        if (index === 1) return 'active';
        return 'pending';
    }

    // OPEN
    if (index === 0) return 'active';
    return 'pending';
};
</script>

<template>
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs text-xs font-sans">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                    Workflow Status
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Tracking proses penyelesaian temuan</p>
            </div>
            
            <!-- Live Tracker or Completed Badge -->
            <div
                v-if="isClosed"
                class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200"
            >
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span>✓ Selesai 100%</span>
            </div>

            <div
                v-else
                class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-semibold border border-blue-200"
            >
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
                </span>
                <span>Live Tracker</span>
            </div>
        </div>

        <!-- Vertical Step Flow (Gojek / Tokopedia Order Tracker Style) -->
        <div class="relative space-y-1">
            <div
                v-for="(step, index) in steps"
                :key="step.key"
                class="relative flex items-start gap-3.5 group"
            >
                <!-- Connecting Line (Vertical Bar between steps) -->
                <div
                    v-if="index < steps.length - 1"
                    class="absolute left-3.5 top-7 bottom-0 w-0.5 -ml-[1px]"
                    :class="[
                        getStepState(index) === 'completed' && getStepState(index + 1) === 'completed'
                            ? 'bg-emerald-500'
                            : (getStepState(index) === 'completed' && getStepState(index + 1) === 'active'
                                ? 'border-l-2 border-dashed border-blue-400'
                                : 'bg-slate-200')
                    ]"
                    style="height: calc(100% - 10px);"
                ></div>

                <!-- Step Circle Indicator -->
                <div class="relative z-10 shrink-0 mt-0.5">
                    <!-- 1. COMPLETED STEP (Green Solid Checkmark) -->
                    <div
                        v-if="getStepState(index) === 'completed'"
                        class="w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center shadow-xs transition-transform duration-200 group-hover:scale-105"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <!-- 2. CURRENT ACTIVE STEP (Gojek Style Pulsing live radar) -->
                    <div
                        v-else-if="getStepState(index) === 'active'"
                        class="relative flex items-center justify-center w-7 h-7"
                    >
                        <span class="animate-ping absolute inline-flex h-7 w-7 rounded-full bg-blue-400 opacity-60"></span>
                        <div class="relative w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center ring-4 ring-blue-100 shadow-md">
                            <!-- Spinning / active radar icon -->
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- 3. UPCOMING / PENDING STEP -->
                    <div
                        v-else
                        class="w-7 h-7 rounded-full bg-slate-100 text-slate-400 border border-slate-200 flex items-center justify-center font-bold text-[11px]"
                    >
                        {{ index + 1 }}
                    </div>
                </div>

                <!-- Step Content Description Card -->
                <div
                    class="flex-1 pb-4 transition-all duration-200"
                    :class="[
                        getStepState(index) === 'active' ? 'bg-blue-50/70 border border-blue-200/80 p-2.5 rounded-lg -mt-1 shadow-2xs' : 'py-0.5'
                    ]"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <span
                                class="font-bold text-xs"
                                :class="[
                                    getStepState(index) === 'completed'
                                        ? 'text-slate-800 font-semibold'
                                        : (getStepState(index) === 'active' ? 'text-blue-950 font-extrabold' : 'text-slate-400 font-medium')
                                ]"
                            >
                                {{ step.label }}
                            </span>
                        </div>

                        <!-- Badge Status -->
                        <span
                            v-if="getStepState(index) === 'completed'"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800"
                        >
                            ✓ Selesai
                        </span>
                        <span
                            v-else-if="getStepState(index) === 'active'"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-600 text-white animate-pulse"
                        >
                            ● Sedang Berjalan
                        </span>
                        <span
                            v-else
                            class="text-[10px] text-slate-400 font-medium"
                        >
                            Menunggu
                        </span>
                    </div>

                    <p
                        class="text-[11px] mt-0.5 leading-relaxed"
                        :class="[
                            getStepState(index) === 'completed'
                                ? 'text-slate-600'
                                : (getStepState(index) === 'active' ? 'text-blue-800 font-medium' : 'text-slate-400')
                        ]"
                    >
                        {{ step.desc }}
                    </p>

                    <!-- PIC / Role info -->
                    <div
                        v-if="getStepState(index) === 'active'"
                        class="mt-2 pt-1.5 border-t border-blue-200/60 flex items-center justify-between text-[10px] text-blue-900"
                    >
                        <span>Penanggung Jawab:</span>
                        <span class="font-bold bg-white/80 px-1.5 py-0.5 rounded border border-blue-200">{{ step.role }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Notice -->
        <div v-if="showNote" class="mt-3 pt-3 border-t border-slate-100 text-[10px] text-slate-500 leading-relaxed">
            <span v-if="isClosed" class="font-semibold text-emerald-700">✓ Temuan Tuntas: Seluruh alur perbaikan telah selesai diverifikasi dan ditutup resmi.</span>
            <span v-else><strong class="font-semibold text-slate-700">Catatan:</strong> Status otomatis bergerak maju seiring tindakan toko dan validasi auditor lapangan.</span>
        </div>
    </div>
</template>
