const form = document.getElementById('upload-form');
const button = document.getElementById('upload-button');
const errorBox = document.getElementById('upload-error');
const maxSize = 1024 * 1024 * 1024;

function formatMb(bytes) {
    return (bytes / 1024 / 1024).toLocaleString('id-ID', {
        maximumFractionDigits: 1
    }) + ' MB';
}

function showProgress(uploaded, total) {
    const percent = total > 0 ? uploaded / total * 100 : 0;

    document.getElementById('upload-progress').classList.remove('d-none');
    document.getElementById('upload-bar').style.width = `${percent}%`;
    document.getElementById('upload-text').innerText =
        `Mengupload ${formatMb(uploaded)} / ${formatMb(total)} (${percent.toLocaleString('id-ID', { maximumFractionDigits: 1 })}%)`;
}

function showError(message) {
    errorBox.innerText = message;
    errorBox.classList.remove('d-none');
    button.disabled = false;
    button.innerText = 'Upload & Import';
}

async function send(url, body) {
    let lastError;

    for (let attempt = 1; attempt <= 3; attempt++) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: body,
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('input[name=_token]').value,
                    Accept: 'application/json',
                },
            });
            const data = await response.json();

            if (response.ok) {
                return data;
            }

            lastError = new Error(data.message ?? 'Upload gagal.');

            if (response.status < 500) {
                throw lastError;
            }
        } catch (error) {
            lastError = error;

            if (attempt === 3 || error === lastError && error.message !== 'Failed to fetch' && !(error instanceof TypeError)) {
                throw lastError;
            }
        }

        await new Promise(resolve => setTimeout(resolve, 1000 * attempt));
    }

    throw lastError;
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    errorBox.classList.add('d-none');

    const file = document.getElementById('file').files[0];

    if (!file || file.size === 0) {
        return showError('Silakan pilih file CSV yang berisi data.');
    }

    if (file.size > maxSize) {
        return showError('Ukuran file maksimal 1 GB.');
    }

    button.disabled = true;
    button.innerText = 'Mengupload...';

    const uploadId = form.dataset.uploadId;
    const chunkSize = Number(form.dataset.chunkSize);
    let offset = 0;

    try {
        showProgress(0, file.size);

        while (offset < file.size) {
            const body = new FormData();
            body.append('upload_id', uploadId);
            body.append('offset', offset);
            body.append('chunk', file.slice(offset, offset + chunkSize), file.name);

            const result = await send('/postimportchunk', body);
            offset = result.uploaded;

            showProgress(offset, file.size);
        }

        button.innerText = 'Memeriksa file...';

        const body = new FormData();
        body.append('upload_id', uploadId);
        body.append('file_name', file.name);

        const result = await send('/postimportcomplete', body);

        window.location.href = result.redirect;
    } catch (error) {
        showError(error.message);
    }
});
