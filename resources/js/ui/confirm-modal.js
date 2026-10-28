/**
 * Modal konfirmasi bergaya playful untuk form yang punya atribut data-confirm.
 *
 * Hanya aktif di halaman playful dan ketika elemen modal tersedia. Bila salah
 * satu syarat tidak terpenuhi, pemanggil tetap memakai window.confirm() bawaan
 * (lihat app.js) sehingga aksi admin tidak berubah.
 */

const initConfirmModal = () => {
    const modalElement = document.getElementById('jx-confirm-modal');

    if (!modalElement || !window.bootstrap) {
        return false;
    }

    const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
    const messageElement = modalElement.querySelector('[data-confirm-message]');
    const acceptButton = modalElement.querySelector('[data-confirm-accept]');

    let pendingForm = null;

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmBypass === 'true') {
                delete form.dataset.confirmBypass;

                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            pendingForm = form;

            if (messageElement) {
                messageElement.textContent = form.dataset.confirm || 'Lanjutkan tindakan ini?';
            }

            modal.show();
        });
    });

    acceptButton?.addEventListener('click', () => {
        const form = pendingForm;
        pendingForm = null;

        modal.hide();

        if (!form) {
            return;
        }

        form.dataset.confirmBypass = 'true';

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });

    return true;
};

export { initConfirmModal };
