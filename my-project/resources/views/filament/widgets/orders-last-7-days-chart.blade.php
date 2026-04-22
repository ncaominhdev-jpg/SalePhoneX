<x-filament::widget>
    <div class="bg-white rounded-xl shadow-md p-4">
        <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-blue-700">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5V6a2 2 0 012-2h14a2 2 0 012 2v7.5M16 21H8a2 2 0 01-2-2v-5a2 2 0 012-2h8a2 2 0 012 2v5a2 2 0 01-2 2z"/></svg>
            Đơn hàng 7 ngày gần nhất
        </h3>
        <canvas id="orders7daysChart" height="150"></canvas>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('orders7daysChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        label: 'Số đơn hàng',
                        data: @json($data),
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
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
