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

const initSuccessLoading = ({
    overlaySelector,
    formSelector,
    messageSelector,
    loadingMessage,
    successMessage,
    rootClass,
}) => {
    const overlay = document.querySelector(overlaySelector);

    if (!overlay) {
        return;
    }

    const message = overlay.querySelector(messageSelector);
    const forms = document.querySelectorAll(formSelector);
    const timers = [];

    const reset = () => {
        timers.splice(0).forEach((timer) => window.clearTimeout(timer));
        overlay.hidden = true;
        delete overlay.dataset.state;
        overlay.setAttribute('aria-busy', 'true');
        document.documentElement.classList.remove(rootClass);

        forms.forEach((form) => {
            delete form.dataset.successLoading;
            form.querySelector('[type="submit"]')?.removeAttribute('disabled');
        });
    };

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented || form.dataset.successLoading === 'true') {
                return;
            }

            event.preventDefault();
            form.dataset.successLoading = 'true';
            form.querySelector('[type="submit"]')?.setAttribute('disabled', 'disabled');

            if (message) {
                message.textContent = loadingMessage(form);
            }

            overlay.hidden = false;
            overlay.dataset.state = 'loading';
            document.documentElement.classList.add(rootClass);

            timers.push(window.setTimeout(() => {
                overlay.dataset.state = 'success';
                overlay.setAttribute('aria-busy', 'false');

                if (message) {
                    message.textContent = successMessage;
                }

                timers.push(window.setTimeout(() => form.submit(), 350));
            }, 550));
        });
    });

    window.addEventListener('pageshow', reset);
};

const initImpersonationLoading = () => {
    initSuccessLoading({
        overlaySelector: '[data-impersonation-loading-overlay]',
        formSelector: 'form[data-impersonation-loading]',
        messageSelector: '[data-impersonation-loading-message]',
        loadingMessage: (form) => `Masuk sebagai ${form.dataset.impersonationTarget}...`,
        successMessage: 'Akses siap. Mengalihkan dashboard...',
        rootClass: 'is-impersonating',
    });
};

const initLoginLoading = () => {
    const overlay = document.querySelector('[data-login-loading-overlay]');
    const form = document.querySelector('form[data-login-loading]');

    if (!overlay || !form) {
        return;
    }

    const title = overlay.querySelector('[data-login-loading-title]');
    const message = overlay.querySelector('[data-login-loading-message]');
    const note = overlay.querySelector('[data-login-loading-note]');
    const feedback = form.querySelector('[data-login-request-error]');
    const feedbackMessage = form.querySelector('[data-login-request-error-message]');
    const submitButton = form.querySelector('[type="submit"]');
    const defaultTitle = title?.textContent ?? '';
    const defaultMessage = message?.textContent ?? '';
    const defaultNote = note?.textContent ?? '';

    const delay = (milliseconds) => new Promise((resolve) => window.setTimeout(resolve, milliseconds));

    const reset = () => {
        overlay.hidden = true;
        delete overlay.dataset.state;
        overlay.setAttribute('aria-busy', 'true');
        document.documentElement.classList.remove('is-logging-in');
        delete form.dataset.loginLoading;
        submitButton?.removeAttribute('disabled');

        if (title) {
            title.textContent = defaultTitle;
        }

        if (message) {
            message.textContent = defaultMessage;
        }

        if (note) {
            note.textContent = defaultNote;
        }
    };

    const showFailure = async (errorMessage) => {
        overlay.dataset.state = 'error';
        overlay.setAttribute('aria-busy', 'false');

        if (title) {
            title.textContent = 'Login gagal';
        }

        if (message) {
            message.textContent = errorMessage;
        }

        if (note) {
            note.textContent = 'Periksa kembali email dan password Anda.';
        }

        if (feedback && feedbackMessage) {
            feedbackMessage.textContent = errorMessage;
            feedback.hidden = false;
        }

        await delay(1000);
        reset();
    };

    form.addEventListener('submit', async (event) => {
        if (event.defaultPrevented || form.dataset.loginLoading === 'true') {
            return;
        }

        event.preventDefault();
        form.dataset.loginLoading = 'true';
        feedback && (feedback.hidden = true);
        submitButton?.setAttribute('disabled', 'disabled');
        overlay.hidden = false;
        overlay.dataset.state = 'loading';
        document.documentElement.classList.add('is-logging-in');

        try {
            const response = await fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => null);
                const errors = payload?.errors ? Object.values(payload.errors).flat() : [];
                const errorMessage = errors[0] || payload?.message || 'Login tidak dapat diproses. Coba lagi.';

                await showFailure(errorMessage);

                return;
            }

            overlay.dataset.state = 'success';
            overlay.setAttribute('aria-busy', 'false');

            if (title) {
                title.textContent = 'Login berhasil';
            }

            if (message) {
                message.textContent = 'Mengalihkan dashboard...';
            }

            await delay(550);
            window.location.assign(response.url);
        } catch {
            await showFailure('Koneksi bermasalah. Periksa internetmu lalu coba lagi.');
        }
    });

    window.addEventListener('pageshow', reset);
};

const initLogoutLoading = () => {
    const isPlayful = document.body.classList.contains('playful');

    initSuccessLoading({
        overlaySelector: isPlayful ? '[data-jx-logout-loading-overlay]' : '[data-logout-loading-overlay]',
        formSelector: 'form[data-logout-loading]',
        messageSelector: isPlayful ? '[data-jx-logout-loading-message]' : '[data-logout-loading-message]',
        loadingMessage: () => 'Menutup sesi...',
        successMessage: 'Sampai jumpa!',
        rootClass: 'is-logging-out',
    });
};

const initMobileSidebar = () => {
    const sidebar = document.querySelector('.sidebar-wrapper');
    const toggle = document.querySelector('.sidebar-toggle-btn');

    if (!sidebar || !toggle) {
        return;
    }

    const overlay = document.querySelector('.sidebar-overlay') ?? document.createElement('div');
    overlay.className = 'sidebar-overlay';

    if (!overlay.isConnected) {
        document.body.appendChild(overlay);
    }

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

let fullscreenListenerBound = false;

const syncFullscreenIcon = () => {
    const icon = document.querySelector('#btn-fullscreen i');

    if (icon) {
        icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
    }
};

const initFullscreen = () => {
    const button = document.querySelector('#btn-fullscreen');

    if (!button) {
        return;
    }

    syncFullscreenIcon();

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

    if (!fullscreenListenerBound) {
        fullscreenListenerBound = true;
        document.addEventListener('fullscreenchange', syncFullscreenIcon);
    }
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

let initializedBody = null;

export const initPage = () => {
    if (initializedBody === document.body) {
        return;
    }

    initializedBody = document.body;

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
    initImpersonationLoading();
    initLoginLoading();
    initLogoutLoading();

    // Kalau initAnimate() gagal di tengah jalan, lepas penanda siapnya supaya
    // CSS tidak lagi menyembunyikan elemen yang observer tidak sempat tangani.
    try {
        initAnimate();
    } catch (error) {
        revealAllAnimatedContent();
        console.error('Animasi playful gagal dimuat.', error);
    }

    initInteractiveEnhancements();
};

onDomReady(initPage);

if (document.querySelector('meta[name="staff-turbo"]')) {
    import('./staff-turbo')
        .then(({ setupTurbo }) => setupTurbo(initPage))
        .catch((error) => console.error('Turbo admin gagal dimuat.', error));
}
