import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        let payload;

        try {
            payload = JSON.parse(canvas.getAttribute('data-chart') || '{}');
        } catch {
            return;
        }

        if (!Array.isArray(payload.labels) || !Array.isArray(payload.values)) {
            return;
        }

        const type = canvas.getAttribute('data-chart-type') || 'bar';

        new Chart(canvas, {
            type,
            data: {
                labels: payload.labels,
                datasets: [{
                    data: payload.values,
                    backgroundColor: payload.colors || ['#1e3a5f', '#0f766e', '#d97706', '#dc2626'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: type === 'doughnut', position: 'bottom' },
                },
                scales: type === 'bar'
                    ? { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    : {},
            },
        });
    });
});
