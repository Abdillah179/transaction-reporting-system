const progressElement = document.getElementById('import-progress');
const progressUrl = progressElement.dataset.progressUrl;
const initialProgress = JSON.parse(progressElement.dataset.initial);
const badgeClass = {
    pending: 'secondary',
    processing: 'primary',
    completed: 'success',
    failed: 'danger'
};

function formatNumber(value) {
    return Number(value).toLocaleString('id-ID');
}

function formatDuration(seconds) {
    if (seconds === null || seconds === undefined) {
        return '-';
    }

    const minutes = Math.floor(seconds / 60);
    const remainder = seconds % 60;

    return minutes > 0 ? `${minutes}m ${remainder}d` : `${remainder}d`;
}

function render(progress) {
    const isRunning = ['pending', 'processing'].includes(progress.status);
    const badge = document.getElementById('status-badge');
    const bar = document.getElementById('progress-bar');

    badge.className = `badge text-bg-${badgeClass[progress.status] ?? 'secondary'}`;
    badge.innerText = progress.status.charAt(0).toUpperCase() + progress.status.slice(1);

    bar.style.width = `${progress.percent}%`;
    bar.classList.toggle('progress-bar-striped', isRunning);
    bar.classList.toggle('progress-bar-animated', isRunning);

    document.getElementById('percent-text').innerText = `${progress.percent.toLocaleString('id-ID')}%`;
    document.getElementById('processed-rows').innerText = formatNumber(progress.processed_rows);
    document.getElementById('total-rows').innerText = formatNumber(progress.total_rows);
    document.getElementById('failed-rows').innerText = formatNumber(progress.failed_rows);
    document.getElementById('speed').innerText = formatNumber(progress.rows_per_second);

    document.getElementById('time-label').innerText = progress.status === 'processing' ? 'Estimasi Selesai' : 'Durasi';
    document.getElementById('time-value').innerText = progress.status === 'processing' ?
        formatDuration(progress.eta_seconds) :
        formatDuration(progress.elapsed_seconds);

    document.getElementById('reading-info').classList.toggle('d-none', !(progress.status === 'processing' &&
        progress.total_rows === 0));
}

render(initialProgress);

if (['pending', 'processing'].includes(initialProgress.status)) {
    const timer = setInterval(async () => {
        try {
            const response = await fetch(progressUrl, {
                headers: {
                    Accept: 'application/json'
                }
            });
            const progress = await response.json();

            render(progress);

            if (!['pending', 'processing'].includes(progress.status)) {
                clearInterval(timer);
                setTimeout(() => window.location.reload(), 1000);
            }
        } catch (error) {
            console.error(error);
        }
    }, 2000);
}
