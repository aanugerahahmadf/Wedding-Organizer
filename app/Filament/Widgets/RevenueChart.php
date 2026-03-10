<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trend';

    protected static ?string $pollingInterval = null; // Disable mobile polling to avoid method-not-found crashes

    protected static ?int $sort = 2;


    protected function getData(): array
    {
        // Deteksi cerdas: Gunakan sintaks spesifik database
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';

        $data = Order::where('payment_status', 'paid')
            ->selectRaw("{$monthExpr} as month, SUM(total_price) as sum")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('sum', 'month')
            ->toArray();

        $labels = [];
        $revenue = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('m');
            $labels[] = now()->subMonths($i)->format('M');

            // Handle key mismatch (some DBs return '01', some return 1)
            $val = $data[$month] ?? $data[(int) $month] ?? 0;
            $revenue[] = (float) $val;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (IDR)',
                    'data' => $revenue,
                    'backgroundColor' => 'rgba(233, 30, 99, 0.1)',
                    'borderColor' => '#E91E63',
                    'fill' => 'start',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function updateChartData(): void
    {
        // Explicitly defined to fix "MethodNotFoundException" on some mobile environments
        $this->cachedData = null;
        $this->dispatch('updateChartData', [
            'data' => $this->getData(),
        ]);
    }
}
