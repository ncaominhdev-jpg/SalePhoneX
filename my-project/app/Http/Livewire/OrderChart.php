<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderChart extends Component
{
    public $orderData = [];

    public function mount()
    {
        // Get order count grouped by day for the last 7 days
        $orders = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', now()->subDays(6)->startOfDay())
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Prepare data for chart
        $dates = [];

        // Fill dates for last 7 days with zero counts initially
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[$date] = 0;
        }

        foreach ($orders as $order) {
            $dates[$order->date] = $order->count;
        }

        $formattedLabels = [];
        foreach (array_keys($dates) as $date) {
            $formattedLabels[] = date('d/m/Y', strtotime($date));
        }

        $this->orderData = [
            'labels' => $formattedLabels,
            'counts' => array_values($dates),
        ];
    }

    public function render()
    {
        return view('livewire.order-chart');
    }
}
