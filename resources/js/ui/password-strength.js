/**
 * Indikator kekuatan password dan kecocokan konfirmasi pada form registrasi.
 */

const scorePassword = (value) => {
    let score = 0;

    if (value.length >= 8) {
        score += 1;
    }

    if (value.length >= 12) {
        score += 1;
    }

    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) {
        score += 1;
    }

    if (/\d/.test(value)) {
        score += 1;
    }

    if (/[^A-Za-z0-9]/.test(value)) {
        score += 1;
    }

    return Math.min(score, 4);
};

const initPasswordStrength = () => {
    document.querySelectorAll('[data-password-strength]').forEach((wrapper) => {
        const password = document.getElementById(wrapper.dataset.passwordStrength);
        const bar = wrapper.querySelector('[data-strength-bar]');
        const label = wrapper.querySelector('[data-strength-label]');

        if (!password || !bar || !label) {
            return;
        }

        const labels = ['Sangat lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'];
        const confirmationId = wrapper.dataset.passwordConfirm;
        const confirmation = confirmationId ? document.getElementById(confirmationId) : null;
        const matchTarget = wrapper.querySelector('[data-password-match]');

        const update = () => {
            const score = scorePassword(password.value);

            bar.dataset.level = password.value === '' ? '0' : String(score);
            label.textContent = password.value === '' ? '' : labels[score];
        };

        const updateMatch = () => {
            if (!confirmation || !matchTarget) {
                return;
            }

            if (confirmation.value === '' || password.value === '') {
                matchTarget.textContent = '';
                matchTarget.dataset.state = '';

                return;
            }

            const matches = confirmation.value === password.value;

            matchTarget.textContent = matches ? 'Password cocok.' : 'Password belum cocok.';
            matchTarget.dataset.state = matches ? 'ok' : 'no';
        };

        password.addEventListener('input', () => {
            update();
            updateMatch();
        });

        confirmation?.addEventListener('input', updateMatch);

        update();
    });
};

export { initPasswordStrength };
