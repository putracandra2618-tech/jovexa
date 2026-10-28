/**
 * Pencarian admin: satu kotak di topbar yang mengikuti halaman yang sedang dibuka.
 *
 * Tanpa JavaScript form tetap bekerja sebagai GET biasa, hanya tanpa pencarian
 * langsung. Dengan JavaScript, mengetik menunggu sebentar lalu query dijalankan
 * ulang di server, sehingga hasil tetap benar lintas halaman dan ikut memakai
 * filter status/peran yang sedang aktif.
 */

import { debounce } from './debounce';

const DELAY = 400;

/**
 * Susun URL yang akan dikirim form, dengan membuang kontrol yang kosong supaya
 * mengosongkan kotak pencarian menghapus `?q=` alih-alih meninggalkan sisa.
 *
 * @param {HTMLFormElement} form
 * @returns {string}
 */
const buildUrl = (form) => {
    const params = new URLSearchParams();

    [...form.elements].forEach((field) => {
        if (!field.name || field.disabled || field.type === 'submit') {
            return;
        }

        // form.elements juga memuat tag seperti fieldset yang tidak punya value.
        const value = typeof field.value === 'string' ? field.value.trim() : '';

        if (value !== '') {
            params.set(field.name, value);
        }
    });

    const query = params.toString();

    return `${form.action}${query === '' ? '' : `?${query}`}`;
};

/**
 * Tandai daftar sedang dimuat ulang supaya perpindahan tidak terasa diam saja.
 *
 * @param {boolean} isBusy
 */
const setBusy = (isBusy) => {
    const results = document.querySelector('[data-admin-search-results]');

    if (!results) {
        return;
    }

    results.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    results.style.opacity = isBusy ? '0.55' : '';
};

/**
 * Pindah halaman hanya kalau URL-nya memang berubah.
 *
 * `replace` dipakai, bukan `assign`, supaya setiap ketikan tidak menumpuk menjadi
 * entri baru di riwayat browser dan tombol "kembali" tetap masuk ke halaman
 * sebelum administrator mulai mengetik.
 *
 * @param {HTMLFormElement} form
 */
const navigate = (form) => {
    const url = buildUrl(form);

    if (url === window.location.href) {
        return;
    }

    setBusy(true);
    window.location.replace(url);
};

const initAdminSearch = () => {
    document.querySelectorAll('[data-admin-search]').forEach((form) => {
        const input = form.querySelector('[data-admin-search-input]');

        if (!input) {
            return;
        }

        input.addEventListener('input', debounce(() => navigate(form), DELAY));

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            navigate(form);
        });
    });

    document.querySelectorAll('[data-admin-search-select]').forEach((select) => {
        select.addEventListener('change', () => {
            if (select.form) {
                navigate(select.form);
            }
        });
    });
};

export { initAdminSearch };