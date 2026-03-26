<?php

namespace App\Filament\User\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class LatestBookings extends BaseWidget
{
    protected function getTableHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return __('Pesanan Terakhir');
    }

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::where('user_id', Auth::id())->latest()->limit(6)
            )
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('package.image_url')
                        ->label('')
                        ->height('12rem')
                        ->width('100%')
                        ->extraImgAttributes([
                            'class' => 'rounded-xl object-cover shadow-sm',
                        ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('package.name')
                                ->weight('black')
                                ->color('primary')
                                ->size('lg')
                                ->grow(false),
                            Tables\Columns\TextColumn::make('status')
                                ->badge()
                                ->alignEnd(),
                        ]),
                        Tables\Columns\TextColumn::make('order_number')
                            ->weight('bold')
                            ->size('sm')
                            ->icon('heroicon-m-hashtag')
                            ->color('gray'),
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('total_text')
                                ->state(fn() => __('Total:'))
                                ->size('sm')
                                ->color('gray')
                                ->alignEnd(),
                            Tables\Columns\TextColumn::make('total_price')
                                ->money('idr')
                                ->weight('black')
                                ->color('success')
                                ->grow(false)
                                ->size('md'),
                        ])->extraAttributes(['class' => 'mt-2 pt-2 border-t border-gray-100 dark:border-gray-800']),
                    ])->space(1)->extraAttributes(['class' => 'p-3 bg-gray-50 dark:bg-gray-900 rounded-xl mt-3']),
                ])->extraAttributes(['class' => 'p-4 bg-white dark:bg-gray-950 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 transition-all hover:shadow-md']),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('Belum ada pesanan'))
            ->emptyStateDescription(__('Mulai rencanakan pernikahan Anda sekarang.'))
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}
