const initNotifications = () => {
    document.querySelectorAll('[data-notification-bell]').forEach((bell) => {
        if (bell.dataset.notificationBound === 'true') {
            return;
        }

        bell.dataset.notificationBound = 'true';

        bell.addEventListener('shown.bs.dropdown', async () => {
            const badge = bell.querySelector('[data-notification-badge]');

            if (!badge || bell.dataset.notificationReading === 'true') {
                return;
            }

            bell.dataset.notificationReading = 'true';

            try {
                const response = await fetch(bell.dataset.notificationMarkAllUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': bell.dataset.notificationCsrf,
                    },
                });

                if (!response.ok) {
                    return;
                }

                badge.remove();
                bell.querySelector('[data-notification-unread-label]')?.remove();
                bell.querySelectorAll('[data-notification-read-form]').forEach((form) => form.remove());
            } catch (error) {
                console.error('Gagal menandai notifikasi sebagai dibaca.', error);
            } finally {
                delete bell.dataset.notificationReading;
            }
        });
    });
};

export { initNotifications };
