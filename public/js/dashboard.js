const dashboardElement = document.getElementById('dashboard-data');
const daily = JSON.parse(dashboardElement.dataset.daily);
const monthly = JSON.parse(dashboardElement.dataset.monthly);
const perCategory = JSON.parse(dashboardElement.dataset.categories);

new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: daily.map(row => row.date),
        datasets: [{
            label: 'Jumlah Transaksi',
            data: daily.map(row => Number(row.total_count)),
            tension: 0.3,
            pointRadius: 0,
        }],
    },
    options: {
        maintainAspectRatio: false,
    },
});

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: monthly.map(row => row.month),
        datasets: [{
            label: 'Total Amount (Rp)',
            data: monthly.map(row => Number(row.total_amount)),
        }],
    },
    options: {
        maintainAspectRatio: false,
    },
});

new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: perCategory.map(row => row.category.name),
        datasets: [{
            data: perCategory.map(row => Number(row.total_amount)),
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            },
        },
    },
});
