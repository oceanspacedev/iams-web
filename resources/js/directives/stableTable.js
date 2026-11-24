// v-stable-table="totalItems"
// Dipasang di pembungkus tabel yang ber-pagination supaya tabel tidak "loncat" saat ganti halaman:
// - lebar kolom diukur dari tampilan pertama lalu dikunci (table-layout: fixed)
// - tinggi pembungkus tidak menyusut di halaman yang barisnya lebih sedikit
// Saat jumlah data berubah (filter/pencarian) atau lebar layar berubah, ukuran dihitung ulang.

const freezeColumns = (el) => {
    const table = el.querySelector('table');
    if (!table || el.offsetWidth === 0) return; // tersembunyi (mis. tampilan HP)

    const headers = [...table.querySelectorAll('thead th')];
    table.style.tableLayout = 'auto';
    headers.forEach((th) => (th.style.width = ''));

    const widths = headers.map((th) => th.getBoundingClientRect().width);
    headers.forEach((th, i) => (th.style.width = `${widths[i]}px`));
    table.style.tableLayout = 'fixed';

    el._stableWidth = el.offsetWidth;
};

const resetHeight = (el) => {
    el._maxHeight = 0;
    el.style.minHeight = '';
};

const keepHeight = (el) => {
    if (el.offsetWidth === 0) return;
    const height = el.offsetHeight;
    if (height > (el._maxHeight || 0)) {
        el._maxHeight = height;
        el.style.minHeight = `${height}px`;
    }
};

const remeasure = (el) => {
    resetHeight(el);
    freezeColumns(el);
    keepHeight(el);
};

// setTimeout (bukan requestAnimationFrame): tetap jalan walau browser menahan frame animasi
const later = (fn) => setTimeout(fn, 0);

export default {
    mounted(el, binding) {
        el._total = binding.value;
        later(() => remeasure(el));

        // Lebar berubah (resize jendela, tampil dari keadaan tersembunyi) -> ukur ulang
        el._onResize = () => {
            if (el.offsetWidth && el.offsetWidth !== el._stableWidth) remeasure(el);
        };
        el._observer = new ResizeObserver(el._onResize);
        el._observer.observe(el);
        window.addEventListener('resize', el._onResize);
    },

    updated(el, binding) {
        if (binding.value !== el._total) {
            el._total = binding.value;
            later(() => remeasure(el));
            return;
        }
        // Lebar belum pernah terkunci (mis. tadinya tersembunyi) -> coba lagi
        later(() => (el._stableWidth ? keepHeight(el) : remeasure(el)));
    },

    unmounted(el) {
        el._observer?.disconnect();
        window.removeEventListener('resize', el._onResize);
    },
};
