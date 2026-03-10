<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class OrdersChart extends ChartWidget
{
    protected static ?string $heading = 'Recent Orders Count';
    
    protected static ?string $pollingInterval = null; // Disable mobile polling
    
    protected static ?int $sort = 3;


    protected function getData(): array
    {
        // Deteksi cerdas: Gunakan sintaks spesifik database
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';

        $data = \App\Models\Order::selectRaw("{$monthExpr} as month, count(*) as count")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $labels = [];
        $counts = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('m');
            $labels[] = now()->subMonths($i)->format('M');

            // Handle key mismatch (some DBs return '01', some return 1)
            $val = $data[$month] ?? $data[(int) $month] ?? 0;
            $counts[] = $val;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $counts,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
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
        $this->cachedData = null;
        $this->dispatch('updateChartData', [
            'data' => $this->getData(),
        ]);
    }
}
