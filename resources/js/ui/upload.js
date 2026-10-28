/**
 * Unggah berkas interaktif: dropzone dengan pratinjau, dan penghitung karakter.
 *
 * Validasi di sini hanya umpan balik cepat; aturan sebenarnya tetap ditegakkan
 * di server (StoreOrderRequest).
 */

const MAX_FILES = 5;
const MAX_SIZE = 10 * 1024 * 1024;

const formatSize = (bytes) => {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(0)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const initDropzone = () => {
    document.querySelectorAll('[data-dropzone]').forEach((zone) => {
        const input = zone.querySelector('[data-dropzone-input]');
        const preview = document.querySelector(zone.dataset.dropzonePreview || '');

        if (!input) {
            return;
        }

        const list = preview || zone.parentElement.querySelector('[data-file-preview]');

        const syncPreview = () => {
            if (!list) {
                return;
            }

            list.innerHTML = '';

            [...input.files].forEach((file, index) => {
                const item = document.createElement('li');
                item.className = 'jx-file-preview-item';
                item.innerHTML =
                    '<span class="jx-file-preview-icon"><i class="bi bi-file-earmark-text"></i></span>' +
                    '<span class="jx-file-preview-info"><span class="jx-file-preview-name"></span>' +
                    `<span class="jx-file-preview-meta">${formatSize(file.size)}</span></span>` +
                    '<button type="button" class="jx-file-preview-remove" aria-label="Hapus berkas"><i class="bi bi-x-lg"></i></button>';

                item.querySelector('.jx-file-preview-name').textContent = file.name;

                item.querySelector('.jx-file-preview-remove').addEventListener('click', () => {
                    const data = new DataTransfer();

                    [...input.files].forEach((existing, position) => {
                        if (position !== index) {
                            data.items.add(existing);
                        }
                    });

                    input.files = data.files;
                    syncPreview();
                    validate(false);
                });

                list.appendChild(item);
            });

            zone.classList.toggle('has-files', input.files.length > 0);
        };

        const validate = (showMessage) => {
            const message = zone.parentElement.querySelector('[data-dropzone-message]');

            if (!message) {
                return true;
            }

            const files = [...input.files];
            const tooMany = files.length > MAX_FILES;
            const tooBig = files.some((file) => file.size > MAX_SIZE);
            const invalid = tooMany || tooBig;

            if (!showMessage) {
                message.classList.add('d-none');
                zone.classList.remove('is-invalid');

                return !invalid;
            }

            let text = '';

            if (tooMany) {
                text = `Maksimal ${MAX_FILES} berkas.`;
            } else if (tooBig) {
                text = 'Ada berkas yang melebihi 10 MB.';
            }

            message.textContent = text;
            message.classList.toggle('d-none', !invalid);
            zone.classList.toggle('is-invalid', invalid);

            return !invalid;
        };

        input.addEventListener('change', () => {
            syncPreview();
            validate(true);
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            zone.addEventListener(eventName, (event) => {
                event.preventDefault();
                zone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            zone.addEventListener(eventName, (event) => {
                event.preventDefault();
                zone.classList.remove('is-dragover');
            });
        });

        zone.addEventListener('drop', (event) => {
            const dropped = event.dataTransfer?.files;

            if (!dropped || dropped.length === 0) {
                return;
            }

            const data = new DataTransfer();

            [...dropped, ...input.files].slice(0, MAX_FILES + 1).forEach((file) => data.items.add(file));

            input.files = data.files;
            syncPreview();
            validate(true);
        });

        syncPreview();
    });
};

const initCharCounter = () => {
    document.querySelectorAll('[data-char-counter]').forEach((field) => {
        const max = Number(field.dataset.charCounter || 0);
        const output = field.parentElement.querySelector('[data-char-counter-output]');

        if (!output) {
            return;
        }

        const update = () => {
            const length = field.value.length;

            output.querySelector('[data-char-current]').textContent = String(length);
            output.classList.toggle('is-near', max > 0 && length > max * 0.9 && length <= max);
            output.classList.toggle('is-over', max > 0 && length > max);
        };

        field.addEventListener('input', update);
        update();
    });
};

export { initDropzone, initCharCounter };
