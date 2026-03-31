<?php

namespace App\Filament\User\Resources;

use App\Models\Topup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\DB;

class HistoryResource extends Resource
{
    protected static ?string $model = \App\Models\History::class;

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['reference_number', 'type'];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Transaksi & Aktivitas');
    }

    public static function getNavigationLabel(): string
    {
        return __('Riwayat Aktivitas');
    }

    public static function getModelLabel(): string
    {
        return __('Riwayat Aktivitas');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->latest();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('Belum ada riwayat aktivitas'))
            ->emptyStateDescription(__('Temukan layanan pernikahan impianmu dan mulai transaksi pertama hari ini!'))
            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateActions([
                Tables\Actions\Action::make('explore')
                    ->label(__('Cari Layanan'))
                    ->url(PackageResource::getUrl())
                    ->button()
                    ->color('primary')
                    ->size('lg')
                    ->icon('heroicon-m-sparkles'),
            ])
            ->actionsAlignment('center')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Waktu'))
                    ->description(fn($record) => match($record->type) {
                        'topup' => __('Diterima'),
                        'withdrawal' => __('Penarikan'),
                        'order' => __('Pembayaran'),
                        default => ucfirst($record->type)
                    }, position: 'above')
                    ->dateTime('d M Y, H:i')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label(__('ID Transaksi'))
                    ->searchable()
                    ->copyable()
                    ->weight(FontWeight::Bold)
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('info')
                    ->label(__('Keterangan'))
                    ->limit(40)
                    ->tooltip(fn($record) => $record->info)
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Nominal'))
                    ->formatStateUsing(fn ($state, $record) => ($record->type === 'topup' ? '+' : '-') . ' Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->weight(FontWeight::Black)
                    ->color(fn($record) => $record->type === 'topup' ? 'success' : 'danger')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => __('Menunggu'),
                        'success', 'completed', 'approved', 'paid', 'confirmed' => __('Berhasil'),
                        'failed', 'rejected', 'cancelled', 'expired' => __('Gagal'),
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'success', 'completed', 'approved' => 'success',
                        'failed', 'rejected', 'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->searchable()
                    ->options([
                        'topup' => __('Deposit'),
                        'withdrawal' => __('Penarikan'),
                        'order' => __('Pembelian'),
                    ])
                    ->label(__('Filter Tipe'))
                    ->native(false)
                    ->preload(),
                Tables\Filters\Filter::make('id')
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label(__('ID')),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $id) => $q->where('id', $id)))
                    ->hidden(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('Lihat Detail'))
                    ->button()
                    ->color('primary')
                    ->icon('heroicon-m-magnifying-glass')
                    ->slideOver()
                    ->modalWidth('xl')
                    ->modalHeading(__('Rincian Aktivitas')),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make(__('Data Transaksi'))
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(2)->schema([
                            \Filament\Infolists\Components\TextEntry::make('reference_number')
                                ->label(__('ID Transaksi'))
                                ->weight(FontWeight::Bold)
                                ->copyable(),
                            \Filament\Infolists\Components\TextEntry::make('type')
                                ->label(__('Jenis'))
                                ->badge()
                                ->formatStateUsing(fn($state) => match($state) {
                                    'topup' => __('Deposit'),
                                    'withdrawal' => __('Penarikan'),
                                    'order' => __('Pembelian Paket'),
                                    default => ucfirst($state),
                                }),
                            \Filament\Infolists\Components\TextEntry::make('status')
                                ->label(__('Status'))
                                ->badge()
                                ->formatStateUsing(fn ($state) => match($state) {
                                    'pending' => __('Menunggu Konfirmasi'),
                                    'success', 'completed', 'approved', 'paid' => __('Selesai/Berhasil'),
                                    'failed', 'rejected', 'cancelled' => __('Gagal/Ditolak'),
                                    default => ucfirst($state),
                                })
                                ->color(fn ($state) => match($state) {
                                    'pending' => 'warning',
                                    'success', 'completed', 'approved' => 'success',
                                    'failed', 'rejected', 'cancelled' => 'danger',
                                    default => 'gray',
                                }),
                            \Filament\Infolists\Components\TextEntry::make('created_at')
                                ->label(__('Waktu Transaksi'))
                                ->dateTime('d F Y, H:i'),
                        ]),
                        \Filament\Infolists\Components\TextEntry::make('amount')
                            ->label(__('Nominal'))
                            ->formatStateUsing(fn ($state, $record) => ($record->type === 'topup' ? '+' : '-') . ' Rp ' . number_format($state, 0, ',', '.'))
                            ->weight(FontWeight::Black)
                            ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->color(fn($record) => $record->type === 'topup' ? 'success' : 'danger'),
                        \Filament\Infolists\Components\TextEntry::make('info')
                            ->label(__('Keterangan'))
                            ->color('gray'),
                        \Filament\Infolists\Components\TextEntry::make('notes')
                            ->label(__('Catatan'))
                            ->placeholder(__('Tidak ada catatan'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\User\Resources\HistoryResource\Pages\ListHistories::route('/'),
        ];
    }
}
