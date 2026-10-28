/**
 * Filter client-side untuk katalog jasa dan daftar pesanan.
 *
 * Keduanya bersifat progressive enhancement: tanpa JavaScript, pencarian tetap
 * berjalan lewat submit form (?q=) dan daftar pesanan tampil lengkap.
 */

import { debounce } from './debounce';

const initLiveSearch = () => {
    document.querySelectorAll('[data-live-search]').forEach((form) => {
        const input = form.querySelector('[data-live-search-input]');
        const countTarget = form.querySelector('[data-live-search-count]');
        const originalCount = countTarget?.textContent.trim();

        document.querySelectorAll('[data-live-search-target]').forEach((target) => {
            const items = [...target.querySelectorAll('[data-service-item]')];
            const empty = document.querySelector('[data-live-search-empty]');

            if (!input || items.length === 0) {
                return;
            }

            const apply = () => {
                const query = input.value.trim().toLowerCase();
                let visible = 0;

                items.forEach((item) => {
                    const haystack = (
                        item.dataset.serviceName ||
                        item.querySelector('[data-service-name]')?.dataset.serviceName ||
                        ''
                    ).toLowerCase();

                    const description = (
                        item.dataset.serviceDescription ||
                        item.querySelector('[data-service-description]')?.dataset.serviceDescription ||
                        ''
                    ).toLowerCase();

                    const matches = query === '' || haystack.includes(query) || description.includes(query);

                    item.classList.toggle('d-none', !matches);

                    if (matches) {
                        visible += 1;
                    }
                });

                if (empty) {
                    empty.classList.toggle('d-none', visible !== 0);
                }

                if (countTarget) {
                    countTarget.textContent = query === ''
                        ? (originalCount || countTarget.textContent)
                        : `${visible} hasil untuk "${input.value.trim()}"`;
                }
            };

            input.addEventListener('input', debounce(apply));
            form.addEventListener('reset', () => window.setTimeout(apply, 0));

            apply();
        });
    });
};

const initStatusFilter = () => {
    document.querySelectorAll('[data-status-filter-group]').forEach((group) => {
        const buttons = [...group.querySelectorAll('[data-status-filter]')];
        const targetSelector = group.dataset.statusFilterTarget;
        const target = targetSelector ? document.querySelector(targetSelector) : null;

        if (!target) {
            return;
        }

        const rows = [...target.querySelectorAll('[data-order-status]')];
        const empty = document.querySelector('[data-status-filter-empty]');

        const apply = (filter) => {
            let visible = 0;

            rows.forEach((row) => {
                const matches = filter === 'all' || row.dataset.orderStatus === filter;

                row.classList.toggle('d-none', !matches);

                if (matches) {
                    visible += 1;
                }
            });

            if (empty) {
                empty.classList.toggle('d-none', visible !== 0);
            }
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                buttons.forEach((other) => {
                    const isActive = other === button;
                    other.classList.toggle('is-active', isActive);
                    other.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                apply(button.dataset.statusFilter);
            });
        });
    });
};

export { initLiveSearch, initStatusFilter };
