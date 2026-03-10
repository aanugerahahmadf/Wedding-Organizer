<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrders extends BaseWidget
{
    protected static ?int $sort = 4;
    

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn () => Order::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('package.name')
                    ->label('Package')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('IDR')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Date')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
