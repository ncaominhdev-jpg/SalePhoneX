<x-filament::widget>
    <div class="bg-white rounded-xl shadow-md p-4">
        <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-orange-700">
            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Phiếu xuất kho 7 ngày gần nhất
        </h3>
        <canvas id="recentExports7daysChart" height="150"></canvas>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('recentExports7daysChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        label: 'Phiếu xuất kho',
                        data: @json($data),
                        backgroundColor: 'rgba(251,146,60,0.7)',
                        borderRadius: 8,
                        maxBarThickness: 40,
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#64748b', font: { weight: 'bold' } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#e5e7eb' },
                            ticks: { color: '#64748b' }
                        }
                    }
                }
            });
        });
    </script>
</x-filament::widget> 