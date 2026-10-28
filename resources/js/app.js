import { initAnimate } from './ui/animate';
import { initConfirmModal } from './ui/confirm-modal';
import { initAdminSearch } from './ui/admin-search';
import { initLiveSearch, initStatusFilter } from './ui/filters';
import { initPasswordStrength } from './ui/password-strength';
import { initDropzone, initCharCounter } from './ui/upload';
import { initNavDrawer } from './ui/drawer';

const onDomReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback);
    } else {
        callback();
    }
};

const initPasswordToggles = () => {
    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const input = document.getElementById(toggle.dataset.passwordToggle);

        if (!input) {
            return;
        }

        toggle.addEventListener('click', () => {
            const isRevealed = input.getAttribute('type') === 'text';

            input.setAttribute('type', isRevealed ? 'password' : 'text');
            toggle.setAttribute('aria-label', isRevealed ? 'Show password' : 'Hide password');

            const icon = toggle.querySelector('i');
            icon?.classList.toggle('bi-eye', isRevealed);
            icon?.classList.toggle('bi-eye-slash', !isRevealed);
        });
    });
};

const initFormValidation = () => {
    document.querySelectorAll('form.needs-validation').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        });
    });
};

const initMobileSidebar = () => {
    const sidebar = document.querySelector('.sidebar-wrapper');
    const toggle = document.querySelector('.sidebar-toggle-btn');

    if (!sidebar || !toggle) {
        return;
    }

    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    const setOpen = (isOpen) => {
        sidebar.classList.toggle('show', isOpen);
        overlay.classList.toggle('show', isOpen);
    };

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(!sidebar.classList.contains('show'));
    });

    overlay.addEventListener('click', () => setOpen(false));
};

const initDesktopSidebar = () => {
    const toggle = document.querySelector('#desktop-sidebar-toggle');

    if (!toggle) {
        return;
    }

    const icon = toggle.querySelector('i');

    if (localStorage.getItem('sidebar-minimized') === 'true') {
        document.body.classList.add('sidebar-minimized');
        icon && (icon.className = 'bi bi-chevron-bar-right');
    }

    toggle.addEventListener('click', () => {
        const isMinimized = document.body.classList.toggle('sidebar-minimized');

        localStorage.setItem('sidebar-minimized', String(isMinimized));

        if (icon) {
            icon.className = isMinimized ? 'bi bi-chevron-bar-right' : 'bi bi-chevron-bar-left';
        }

        window.setTimeout(() => window.dispatchEvent(new Event('resize')), 300);
    });
};

const initFullscreen = () => {
    const button = document.querySelector('#btn-fullscreen');

    if (!button) {
        return;
    }

    const icon = button.querySelector('i');

    const syncIcon = (isFullscreen) => {
        if (icon) {
            icon.className = isFullscreen ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
        }
    };

    button.addEventListener('click', async () => {
        try {
            if (document.fullscreenElement) {
                await document.exitFullscreen();
            } else {
                await document.documentElement.requestFullscreen();
            }
        } catch (error) {
            console.error(`Gagal mengubah mode fullscreen: ${error.message}`);
        }
    });

    document.addEventListener('fullscreenchange', () => syncIcon(Boolean(document.fullscreenElement)));
};

const initConfirmations = (useNativeConfirm) => {
    if (!useNativeConfirm) {
        return;
    }

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
};

const initInteractiveEnhancements = () => {
    if (!document.body.classList.contains('playful')) {
        return;
    }

    initLiveSearch();
    initStatusFilter();
    initDropzone();
    initCharCounter();
    initPasswordStrength();
    initNavDrawer();
};

/**
 * Tampilkan semua elemen yang sedang menunggu animasi.
 *
 * Dipanggil dari catch pada initAnimate(). Class .jx-reveal-ready sengaja
 * dilepas supaya CSS berhenti menyembunyikan elemen yang observer tidak sempat
 * tangani.
 */
const revealAllAnimatedContent = () => {
    document.documentElement.classList.remove('jx-reveal-ready');

    document.querySelectorAll('.jx-reveal:not(.is-visible)').forEach((element) => {
        element.classList.add('is-visible');
    });
};

onDomReady(() => {
    initPasswordToggles();
    initFormValidation();
    initMobileSidebar();
    initDesktopSidebar();
    initFullscreen();

    // Admin memakai tema Spark, jadi pencarian topbar tidak ikut di balik
    // gerbang body.playful di bawah.
    initAdminSearch();

    // Modal playful dipasang lebih dulu agar submit-state melihat status
    // defaultPrevented ketika pengguna belum menekan tombol konfirmasi.
    const modalHandled = initConfirmModal();
    initConfirmations(!modalHandled);

    // Kalau initAnimate() gagal di tengah jalan, lepas penanda siapnya supaya
    // CSS tidak lagi menyembunyikan elemen yang observer tidak sempat tangani.
    try {
        initAnimate();
    } catch (error) {
        revealAllAnimatedContent();
        console.error('Animasi playful gagal dimuat.', error);
    }

    initInteractiveEnhancements();
});
