/**
 * Drawer navigasi untuk halaman playful (Bootstrap offcanvas).
 *
 * Menutup drawer ketika pengguna mengikuti tautan di dalamnya atau ketika
 * layar kembali ke lebar desktop. Penutupan tautan ditangani di sini, bukan
 * dengan data-bs-dismiss, karena atribut tersebut membatalkan navigasi
 * tautan anchor (event.preventDefault()) dan hanya menutup elemen miliknya.
 */

const DESKTOP_BREAKPOINT = window.matchMedia('(min-width: 992px)');

const initNavDrawer = () => {
    const drawers = document.querySelectorAll('.jx-drawer');

    if (drawers.length === 0 || !window.bootstrap) {
        return;
    }

    const hide = (drawer) => {
        window.bootstrap.Offcanvas.getInstance(drawer)?.hide();
    };

    drawers.forEach((drawer) => {
        drawer.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => hide(drawer));
        });

        const toggle = document.querySelector(`[data-bs-target="#${drawer.id}"]`);

        drawer.addEventListener('show.bs.offcanvas', () => {
            toggle?.setAttribute('aria-expanded', 'true');
        });

        drawer.addEventListener('hidden.bs.offcanvas', () => {
            toggle?.setAttribute('aria-expanded', 'false');
        });
    });

    DESKTOP_BREAKPOINT.addEventListener('change', (event) => {
        if (event.matches) {
            drawers.forEach(hide);
        }
    });
};

export { initNavDrawer };
