<x-filament::widget>
    <div class="bg-white rounded-xl shadow-md p-4">
        <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-green-700">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H4v-2a3 3 0 015.356-1.857M15 11a4 4 0 10-8 0 4 4 0 008 0zm6 0a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Người dùng mới 7 ngày gần nhất
        </h3>
        <canvas id="newUsers7daysChart" height="150"></canvas>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('newUsers7daysChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        label: 'Người dùng mới',
                        data: @json($data),
                        backgroundColor: 'rgba(34,197,94,0.7)',
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