import './bootstrap';
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

window.initDashboardCharts = function () {
    const trendEl = document.getElementById('trend-chart');
    if (trendEl) {
        if (trendEl._chart) {
            trendEl._chart.destroy();
            trendEl._chart = null;
        }
        trendEl._chart = new ApexCharts(trendEl, {
            chart: { type: 'area', height: 256, toolbar: { show: false }, sparkline: { enabled: false }, fontFamily: 'inherit' },
            series: [{ name: 'Spend', data: JSON.parse(trendEl.dataset.series || '[]') }],
            xaxis: { categories: JSON.parse(trendEl.dataset.labels || '[]'), labels: { style: { colors: '#94a3b8', fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { formatter: (v) => '₹' + Math.round(v).toLocaleString('en-IN'), style: { colors: '#94a3b8', fontSize: '11px' } } },
            stroke: { curve: 'smooth', width: 2.5 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 100] } },
            colors: ['#10b981'],
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: (v) => '₹' + v.toLocaleString('en-IN', { minimumFractionDigits: 2 }) } },
        });
        trendEl._chart.render();
    }

    const catEl = document.getElementById('category-chart');
    if (catEl) {
        if (catEl._chart) {
            catEl._chart.destroy();
            catEl._chart = null;
        }
        catEl._chart = new ApexCharts(catEl, {
            chart: { type: 'donut', height: 192, fontFamily: 'inherit' },
            series: JSON.parse(catEl.dataset.series || '[]'),
            labels: JSON.parse(catEl.dataset.labels || '[]'),
            colors: JSON.parse(catEl.dataset.colors || '[]'),
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '65%' } } },
            tooltip: { y: { formatter: (v) => '₹' + v.toLocaleString('en-IN', { minimumFractionDigits: 2 }) } },
        });
        catEl._chart.render();
    }
};

document.addEventListener('DOMContentLoaded', () => window.initDashboardCharts?.());
document.addEventListener('livewire:navigated', () => window.initDashboardCharts?.());
