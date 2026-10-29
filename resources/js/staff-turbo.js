import '@hotwired/turbo';

const resetAdminSearchBusy = () => {
    document.querySelectorAll('[data-admin-search-results]').forEach((element) => {
        element.removeAttribute('aria-busy');
        element.style.opacity = '';
    });
};

/**
 * Tutup state sementara sebelum halaman disimpan ke cache Turbo.
 *
 * Tanpa ini, dropdown, modal, overlay sidebar, atau indikator pencarian yang
 * sedang terbuka bisa ikut tersimpan dan muncul kembali saat tombol kembali
 * ditekan.
 */
const resetTransientUi = () => {
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((toggle) => {
        window.bootstrap?.Dropdown?.getInstance(toggle)?.hide();
    });

    document.querySelectorAll('.modal.show').forEach((modal) => {
        window.bootstrap?.Modal?.getInstance(modal)?.hide();
    });

    document.querySelectorAll('.offcanvas.show').forEach((offcanvas) => {
        window.bootstrap?.Offcanvas?.getInstance(offcanvas)?.hide();
    });

    document.querySelector('.sidebar-wrapper')?.classList.remove('show');
    document.querySelector('.sidebar-overlay')?.classList.remove('show');

    resetAdminSearchBusy();
};

/**
 * Pasang listener siklus hidup Turbo untuk area staff.
 *
 * @param {() => void} initPage
 */
const setupTurbo = (initPage) => {
    document.addEventListener('turbo:before-cache', resetTransientUi);

    document.addEventListener('turbo:load', () => {
        initPage();
        resetAdminSearchBusy();
    });

    document.addEventListener('turbo:fetch-request-error', resetAdminSearchBusy);
};

export { setupTurbo };
