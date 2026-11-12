// One color per Finding Quality category: flat tinted card (same style as the report cards),
// an outline when selected, and a dot for compact labels.
const colors = {
    impact_50m: {
        card: 'border-emerald-100 bg-emerald-50/70 hover:border-emerald-200',
        selected: 'border-emerald-500 ring-1 ring-emerald-500 bg-emerald-50/70',
        label: 'text-emerald-700',
        desc: 'text-emerald-700/70',
        dot: 'bg-emerald-500',
        checkbox: 'text-emerald-600 focus:ring-emerald-500',
    },
    fraud_risk: {
        card: 'border-rose-100 bg-rose-50/70 hover:border-rose-200',
        selected: 'border-rose-500 ring-1 ring-rose-500 bg-rose-50/70',
        label: 'text-rose-700',
        desc: 'text-rose-700/70',
        dot: 'bg-rose-500',
        checkbox: 'text-rose-600 focus:ring-rose-500',
    },
    system_control: {
        card: 'border-indigo-100 bg-indigo-50/70 hover:border-indigo-200',
        selected: 'border-indigo-500 ring-1 ring-indigo-500 bg-indigo-50/70',
        label: 'text-indigo-700',
        desc: 'text-indigo-700/70',
        dot: 'bg-indigo-500',
        checkbox: 'text-indigo-600 focus:ring-indigo-500',
    },
    org_structure: {
        card: 'border-amber-100 bg-amber-50/70 hover:border-amber-200',
        selected: 'border-amber-500 ring-1 ring-amber-500 bg-amber-50/70',
        label: 'text-amber-700',
        desc: 'text-amber-700/70',
        dot: 'bg-amber-500',
        checkbox: 'text-amber-600 focus:ring-amber-500',
    },
};

const fallback = {
    card: 'border-gray-200 bg-white hover:border-gray-300',
    selected: 'border-slate-500 ring-1 ring-slate-500 bg-white',
    label: 'text-gray-700',
    desc: 'text-gray-500',
    dot: 'bg-slate-400',
    checkbox: 'text-blue-600 focus:ring-blue-500',
};

export const qualityCategoryColor = (key) => colors[key] || fallback;
