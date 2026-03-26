<?php

namespace App\Filament\User\Resources;

use App\Models\Topup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;

class TopupResource extends Resource
{
    protected static ?string $model = Topup::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getNavigationGroup(): ?string
    {
        return __('Transaksi & Aktivitas');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('user_id', auth()->id())->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }

    protected static ?string $slug = 'isibalance';

    public static function getNavigationLabel(): string
    {
        return __('Top Up Saldo');
    }

    public static function getModelLabel(): string
    {
        return __('Top Up Saldo');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Riwayat Top Up');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Isi Saldo Dompet'))
                    ->description(__('Silahkan isi detail saldo yang ingin Anda tambahkan.'))
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label(__('Jumlah Saldo'))
                                    ->required()
                                    ->rule('numeric')
                                    ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*', 'class' => 'text-2xl font-bold text-primary-600 dark:text-primary-400'])
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('total_amount', (float)$state + 2500)),
                                 Forms\Components\Select::make('payment_method')
                                    ->label(__('Metode Pembayaran'))
                                    ->options(\App\Models\PaymentMethod::where('is_active', true)->pluck('name', 'code'))
                                    ->required()
                                    ->native(false)
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->prefixIcon('heroicon-o-credit-card'),
                                Forms\Components\Placeholder::make('payment_info')
                                    ->hiddenLabel()
                                    ->visible(fn (\Filament\Forms\Get $get) => filled($get('payment_method')))
                                    ->content(function (\Filament\Forms\Get $get) {
                                        $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                                        if (!$method) return new \Illuminate\Support\HtmlString('-');

                                        return view('filament.user.payment-info', ['method' => $method]);
                                    })
                                    ->columnSpanFull(),
                             ]),
                        Forms\Components\Placeholder::make('summary')
                            ->hiddenLabel()
                            ->content(function (\Filament\Forms\Get $get) {
                                $amount = (float)$get('amount');
                                $fee = 2500;
                                
                                return view('filament.user.topup-summary', [
                                    'amount' => $amount,
                                    'fee' => $fee,
                                    'total' => $amount + $fee,
                                ]);
                            })
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('admin_fee')->default(2500),
                        Forms\Components\Hidden::make('total_amount')->default(2500),
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Catatan (Opsional)'))
                            ->placeholder(__('Konfirmasi atau instruksi khusus untuk proses topup Anda.'))
                            ->rows(2),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('Saldo Anda Masih Kosong'))
            ->emptyStateDescription(__('Isi saldo sekarang untuk mulai membooking paket impian Anda!'))
            ->emptyStateIcon('heroicon-o-wallet')
            ->contentGrid([
                'md' => 1,
                'lg' => 2,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Header (Ref number & Status)
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('reference_number')
                            ->weight(FontWeight::Bold)
                            ->icon('heroicon-s-wallet')
                            ->color('gray')
                            ->grow(false),
                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->alignEnd(),
                    ])->extraAttributes(['class' => 'mb-2 border-b border-gray-100 dark:border-gray-800 pb-2']),

                    // Middle Box
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('total_amount_label')
                            ->state(fn() => __('Nominal Top Up'))
                            ->size('xs')
                            ->color('gray'),
                        Tables\Columns\TextColumn::make('amount')
                            ->money('idr')
                            ->weight(FontWeight::Bold)
                            ->size('lg'),
                        Tables\Columns\TextColumn::make('payment_method')
                            ->badge()
                            ->color('info')
                            ->size('xs'),
                    ])->space(1)->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-900 rounded-xl p-3']),

                    // Footer
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('created_at')
                            ->date('d M Y, H:i')
                            ->size('xs')
                            ->color('gray'),
                        Tables\Columns\TextColumn::make('status_text')
                            ->state(fn($record) => $record?->status?->getLabel() ?? '')
                            ->size('xs')
                            ->color(fn($record) => $record?->status?->getColor() ?? 'gray')
                            ->alignEnd(),
                    ])->extraAttributes(['class' => 'mt-2 pt-2']),

                ])->space(3)->extraAttributes(['class' => 'p-4 bg-white dark:bg-gray-950 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(\App\Enums\TopupStatus::class)
                    ->label(__('Status Topup'))
                    ->searchable()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('Rincian'))
                    ->button()
                    ->color('gray')
                    ->outlined()
                    ->size('lg')
                    ->icon('heroicon-m-eye')
                    ->slideOver()
                    ->modalWidth('xl')
                    ->modalHeading(__('Detail Top Up')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->slideOver()
                    ->modalWidth('xl')
                    ->label(__('Isi Saldo'))
                    ->button()
                    ->color('primary')
                    ->size('lg')
                    ->icon('heroicon-m-plus-circle')
                    ->modalHeading(__('Isi Saldo Dompet'))
                    ->modalSubmitActionLabel(__('Buat Top Up'))
                    ->createAnother(false)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make()
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(2)->schema([
                            \Filament\Infolists\Components\TextEntry::make('reference_number')
                                ->label(__('No. Referensi'))
                                ->weight(FontWeight::Bold)
                                ->copyable(),
                            \Filament\Infolists\Components\TextEntry::make('status')
                                ->label(__('Status'))
                                ->badge()
                                ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),
                        ]),
                    ])
                    ->extraAttributes(['class' => 'bg-gray-50 dark:bg-white/5 border-0 shadow-none rounded-2xl mb-4']),

                \Filament\Infolists\Components\Section::make(__('Rincian Transaksi'))
                    ->icon('heroicon-o-document-text')
                    ->compact()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('amount')
                            ->label(__('Nominal Top Up'))
                            ->money('idr')
                            ->weight(FontWeight::Bold),
                        \Filament\Infolists\Components\TextEntry::make('admin_fee')
                            ->label(__('Biaya Admin'))
                            ->money('idr')
                            ->color('gray'),
                        \Filament\Infolists\Components\TextEntry::make('total_amount')
                            ->label(__('Total Pembayaran'))
                            ->money('idr')
                            ->weight(FontWeight::Bold)
                            ->color('primary')
                            ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('payment_method')
                            ->label(__('Metode Pembayaran'))
                            ->badge(),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label(__('Tanggal Permintaan'))
                            ->dateTime('d F Y, H:i')
                            ->color('gray'),
                        \Filament\Infolists\Components\TextEntry::make('notes')
                            ->label(__('Catatan Khusus'))
                            ->columnSpanFull()
                            ->color('gray'),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\User\Resources\TopupResource\Pages\ManageTopups::route('/'),
        ];
    }
}
