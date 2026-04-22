<x-filament::widget>
    <div class="bg-white rounded-xl shadow-md p-4">
        <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-purple-700">
            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h18v18H3V3z"/></svg>
            Hàng tồn kho 7 ngày gần nhất
        </h3>
        <canvas id="stock7daysChart" height="150"></canvas>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('stock7daysChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        label: 'Tồn kho',
                        data: @json($data),
                        backgroundColor: 'rgba(139,92,246,0.7)',
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