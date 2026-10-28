/**
 * Interaksi & animasi sisi pengguna (playful).
 *
 * Semua fungsi di sini hanya aktif ketika <body> memakai class "playful" agar
 * dashboard admin (tema Spark) tidak ikut berubah. Saat pengguna memilih
 * "prefers-reduced-motion", animasi dilewati dan konten langsung tampil final.
 */

const BOUNCE = 'cubic-bezier(0.34, 1.56, 0.64, 1)';
const SMOOTH = 'cubic-bezier(0.22, 1, 0.36, 1)';

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Class yang hanya ditambahkan setelah reveal benar-benar siap dijalankan.
 *
 * CSS menyembunyikan .jx-reveal hanya di bawah class ini, jadi kalau bundle ini
 * gagal dimuat atau error sebelum initReveal() selesai, konten tetap terlihat
 * apa adanya. Menandainya dari inline <head> tidak akan cukup: skrip kecil itu
 * tetap jalan meski bundle-nya gagal, sehingga konten kembali tersembunyi tanpa
 * ada yang memunculkannya.
 */
const REVEAL_READY_CLASS = 'jx-reveal-ready';

const markRevealReady = () => {
    document.documentElement.classList.add(REVEAL_READY_CLASS);
};

const isPlayful = () => document.body.classList.contains('playful');

const easeOutBack = (t) => {
    const c1 = 1.70158;
    const c3 = c1 + 1;

    return 1 + c3 * (t - 1) ** 3 + c1 * (t - 1) ** 2;
};

/**
 * Reveal bertahap ketika elemen masuk viewport.
 */
const initReveal = () => {
    const items = document.querySelectorAll('.jx-reveal');

    if (items.length === 0) {
        return;
    }

    if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
        items.forEach((item) => item.classList.add('is-visible'));

        return;
    }

    // Disembunyikan hanya setelah observer siap, supaya elemen yang belum
    // teramati tidak pernah tertinggal tak terlihat.
    markRevealReady();

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const delay = Number(entry.target.dataset.revealDelay || 0);

                entry.target.style.transitionDelay = `${delay}ms`;
                entry.target.classList.add('is-visible');

                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -40px 0px' },
    );

    items.forEach((item) => {
        // Elemen yang sudah terlihat saat halaman dimuat tidak boleh menunggu
        // scroll: observer baru memberi tahu saat ada perubahan viewport.
        if (item.getBoundingClientRect().top < window.innerHeight) {
            item.classList.add('is-visible');

            return;
        }

        observer.observe(item);
    });
};

/**
 * Animasi angka dari 0 ke nilai target.
 */
const animateCounter = (element) => {
    const target = Number(element.dataset.counter || 0);

    if (!Number.isFinite(target)) {
        return;
    }

    if (prefersReducedMotion()) {
        element.textContent = new Intl.NumberFormat('id-ID').format(target);

        return;
    }

    const duration = 1200;
    const start = performance.now();
    const format = (value) => new Intl.NumberFormat('id-ID').format(Math.round(value));

    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = Math.max(0, easeOutBack(progress));

        element.textContent = format(target * eased);

        if (progress < 1) {
            requestAnimationFrame(tick);
        } else {
            element.textContent = format(target);
        }
    };

    requestAnimationFrame(tick);
};

const initCounters = () => {
    const counters = document.querySelectorAll('[data-counter]');

    if (counters.length === 0) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        counters.forEach(animateCounter);

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                animateCounter(entry.target);
                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.4 },
    );

    counters.forEach((counter) => observer.observe(counter));
};

/**
 * Animasi tinggi untuk satu elemen (dipakai FAQ accordion).
 */
const animateHeight = (element, from, to, duration, done) => {
    element.style.overflow = 'hidden';
    element.style.height = `${from}px`;

    requestAnimationFrame(() => {
        element.style.transition = `height ${duration}ms ${BOUNCE}`;
        element.style.height = `${to}px`;

        const finish = () => {
            element.style.transition = '';
            element.style.height = '';
            element.style.overflow = '';

            if (done) {
                done();
            }
        };

        element.addEventListener('transitionend', finish, { once: true });
    });
};

/**
 * FAQ accordion: hanya satu item terbuka, dengan animasi buka/tutup.
 */
const initAccordions = () => {
    document.querySelectorAll('.jx-faq[data-accordion]').forEach((group) => {
        const items = [...group.querySelectorAll('details')];

        items.forEach((details) => {
            const summary = details.querySelector('summary');
            const answer = details.querySelector('.jx-faq-answer');

            if (!summary || !answer) {
                return;
            }

            if (prefersReducedMotion()) {
                summary.addEventListener('click', () => {
                    if (!details.open) {
                        items.forEach((other) => {
                            if (other !== details) {
                                other.open = false;
                            }
                        });
                    }
                });

                return;
            }

            summary.addEventListener('click', (event) => {
                event.preventDefault();

                if (details.open) {
                    animateHeight(answer, answer.scrollHeight, 0, 260, () => {
                        details.open = false;
                    });

                    return;
                }

                items.forEach((other) => {
                    if (other === details || !other.open) {
                        return;
                    }

                    const otherAnswer = other.querySelector('.jx-faq-answer');

                    if (otherAnswer) {
                        animateHeight(otherAnswer, otherAnswer.scrollHeight, 0, 260, () => {
                            other.open = false;
                        });
                    } else {
                        other.open = false;
                    }
                });

                details.open = true;
                animateHeight(answer, 0, answer.scrollHeight, 420);
            });
        });
    });
};

/**
 * Isi progres stepper pesanan dianimasikan setelah halaman siap.
 */
const initSteppers = () => {
    document.querySelectorAll('.jx-stepper[data-stepper]').forEach((stepper) => {
        if (prefersReducedMotion()) {
            stepper.classList.add('is-active');

            return;
        }

        requestAnimationFrame(() => {
            requestAnimationFrame(() => stepper.classList.add('is-active'));
        });
    });
};

/**
 * Toast kecil untuk umpan balik (mis. salin nomor pesanan).
 */
const toastContainer = () => {
    let container = document.querySelector('.jx-toasts');

    if (!container) {
        container = document.createElement('div');
        container.className = 'jx-toasts';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);
    }

    return container;
};

const showToast = (message, variant = 'success') => {
    if (!isPlayful()) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = `jx-toast jx-toast-${variant}`;
    toast.setAttribute('role', 'status');
    toast.innerHTML = `<i class="bi bi-check-circle-fill"></i><span></span>`;
    toast.querySelector('span').textContent = message;

    toastContainer().appendChild(toast);

    window.setTimeout(() => {
        toast.classList.add('is-leaving');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, 3200);
};

/**
 * Auto-dismiss untuk alert flash (sukses/info) dengan animasi keluar.
 */
const initAlerts = () => {
    document.querySelectorAll('.alert-custom-dismissible').forEach((alert) => {
        alert.classList.add('jx-alert-enter');

        const isInfo =
            alert.classList.contains('alert-custom-success') ||
            alert.classList.contains('alert-custom-info');

        if (!isInfo) {
            return;
        }

        const close = () => {
            if (prefersReducedMotion()) {
                alert.remove();

                return;
            }

            alert.classList.add('is-leaving');
            alert.addEventListener('transitionend', () => alert.remove(), { once: true });
        };

        window.setTimeout(close, 6500);

        const button = alert.querySelector('.alert-custom-close');

        if (button) {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                close();
            });
        }
    });
};

/**
 * Beri umpan balik visual saat form dikirim (tombol jadi spinner).
 */
const initSubmitState = () => {
    document.querySelectorAll('form[data-loading]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) {
                return;
            }

            const button = form.querySelector('[type="submit"]');

            if (!button || button.disabled) {
                return;
            }

            button.classList.add('is-loading');
            button.disabled = true;
        });
    });
};

/**
 * Smooth scroll untuk tautan anchor ke section di halaman yang sama.
 */
const initNavigation = () => {
    document.querySelectorAll('a[href*="#"]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const url = new URL(link.href, window.location.origin);
            const id = url.hash.slice(1);

            if (!id || url.pathname !== window.location.pathname) {
                return;
            }

            const target = document.getElementById(id);

            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({
                behavior: prefersReducedMotion() ? 'auto' : 'smooth',
                block: 'start',
            });
        });
    });
};

/**
 * Salin teks ke clipboard lewat tombol [data-copy].
 */
const initCopy = () => {
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.dataset.copy || '';

            try {
                await navigator.clipboard.writeText(value);
            } catch (error) {
                return;
            }

            button.classList.add('is-copied');
            window.setTimeout(() => button.classList.remove('is-copied'), 1600);
            showToast(button.dataset.copyMessage || 'Tersalin ke clipboard.');
        });
    });
};

/**
 * Efek shake saat field form tidak valid.
 */
const initValidationFeedback = () => {
    document.querySelectorAll('.playful input, .playful textarea, .playful select').forEach((field) => {
        field.addEventListener('invalid', () => {
            const group = field.closest('.login-input-group, .login-form-group, .mb-3') || field;

            group.classList.remove('is-shaking');
            void group.offsetWidth;
            group.classList.add('is-shaking');
        });
    });
};

/**
 * Header kompak saat scroll, progress bar baca, dan tombol kembali ke atas.
 */
const initScrollPolish = () => {
    const header = document.querySelector('.jx-header');

    const progress = document.createElement('div');
    progress.className = 'jx-scroll-progress';
    progress.setAttribute('aria-hidden', 'true');
    progress.innerHTML = '<span></span>';
    document.body.appendChild(progress);

    const backToTop = document.createElement('button');
    backToTop.type = 'button';
    backToTop.className = 'jx-back-to-top';
    backToTop.setAttribute('aria-label', 'Kembali ke atas');
    backToTop.innerHTML = '<i class="bi bi-arrow-up"></i>';
    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
    });
    document.body.appendChild(backToTop);

    const bar = progress.querySelector('span');

    const onScroll = () => {
        const scrolled = window.scrollY;
        const max = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = max > 0 ? Math.min(scrolled / max, 1) : 0;

        bar.style.transform = `scaleX(${ratio})`;

        if (header) {
            header.classList.toggle('is-scrolled', scrolled > 8);
        }

        backToTop.classList.toggle('is-visible', scrolled > 480);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
};

export const initAnimate = () => {
    if (!isPlayful()) {
        return;
    }

    initReveal();
    initCounters();
    initAccordions();
    initSteppers();
    initAlerts();
    initNavigation();
    initValidationFeedback();
    initSubmitState();
    initCopy();
    initScrollPolish();
};

export { showToast };
