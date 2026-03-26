<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\PaymentResource\Pages;
use App\Filament\User\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getNavigationGroup(): ?string
    {
        return __('Transaksi & Aktivitas');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereHas('order', fn ($q) => $q->where('user_id', auth()->id()))->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('Konfirmasi Bayar');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('order', fn ($q) => $q->where('user_id', auth()->id()));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make(__('Data Tagihan'))
                        ->schema([
                            Forms\Components\Select::make('order_id')
                                ->label(__('Pilih Pesanan Anda'))
                                ->options(Order::where('user_id', auth()->id())->where('status', 'pending')->pluck('order_number', 'id'))
                                ->required()
                                ->searchable()
                                ->prefixIcon('heroicon-o-shopping-bag')
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('amount')
                                ->label(__('Jumlah Pembayaran'))
                                ->required()
                                ->rule('numeric')
                                ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*', 'class' => 'text-xl font-bold'])
                                ->prefix('Rp')
                                ->prefixIcon('heroicon-o-banknotes'),
                            Forms\Components\Select::make('payment_method')
                                ->label(__('Metode Pembayaran'))
                                ->options(\App\Models\PaymentMethod::where('is_active', true)->pluck('name', 'code'))
                                ->required()
                                ->native(false)
                                ->searchable()
                                ->live()
                                ->prefixIcon('heroicon-o-credit-card'),
                            Forms\Components\Placeholder::make('payment_info')
                                ->hiddenLabel()
                                ->visible(fn (Forms\Get $get) => filled($get('payment_method')))
                                ->content(function (Forms\Get $get) {
                                    $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                                    if (!$method) return new \Illuminate\Support\HtmlString('-');

                                    if ($method->type === \App\Enums\PaymentMethodType::QRIS) {
                                        $url = $method->qris_image_url;
                                        if (!$url) return new \Illuminate\Support\HtmlString('<span class="text-red-500">' . __('QRIS tidak tersedia.') . '</span>');
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="flex flex-col items-center gap-4 py-3">'
                                            . '<div class="p-3 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border-2 border-primary-200 dark:border-primary-700 inline-block">'
                                            . '<img src="' . $url . '" class="w-64 h-64 object-contain" alt="QRIS" />'
                                            . '</div>'
                                            . '<p class="text-[10px] text-center opacity-70 font-bold max-w-xs text-gray-600 dark:text-gray-400">'
                                            . __('Simpan QRIS ini atau scan langsung dari ponsel Anda.')
                                            . '</p></div>'
                                        );
                                    } else {
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="flex flex-col items-center gap-2 bg-primary-50 dark:bg-primary-950 p-4 rounded-xl border-2 border-primary-200 dark:border-primary-800">'
                                            . '<p class="text-center text-gray-950 dark:text-white">' . __('Tujuan Transfer:') . '<br>'
                                            . '<span class="font-mono text-2xl font-bold tracking-widest text-primary-600 dark:text-primary-400 select-all">' . ($method->account_number ?? '-') . '</span><br>'
                                            . '<span class="text-sm font-semibold opacity-80 mt-1 text-gray-600 dark:text-gray-400">' . __('a/n') . ' ' . ($method->account_holder ?? '-') . '</span></p>'
                                            . '</div>'
                                        );
                                    }
                                })
                                ->columnSpanFull(),
                        ])->columns(2),
                    Forms\Components\Wizard\Step::make(__('Konfirmasi Bayar'))
                        ->schema([
                            Forms\Components\FileUpload::make('evidence_url')
                                ->label(__('Unggah Foto Bukti Transfer'))
                                ->helperText(__('Pastikan nominal dan rekening tujuan terlihat jelas.'))
                                ->image()
                                ->imageEditor()
                                ->required()
                                ->directory('payment-evidence')
                                ->panelAspectRatio('2:1')
                                ->panelLayout('integrated')
                                ->columnSpanFull(),
                        ])
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('Belum ada riwayat bayar'))
            ->emptyStateIcon('heroicon-o-wallet')
            ->contentGrid([
                'md' => 1,
                'lg' => 2,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Header
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('payment_number')
                            ->default(fn($record) => 'PAY-' . str_pad($record->id, 4, '0', STR_PAD_LEFT))
                            ->weight(FontWeight::Bold)
                            ->icon('heroicon-s-wallet')
                            ->color('gray')
                            ->grow(false),
                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->alignEnd(),
                    ])->extraAttributes(['class' => 'mb-2 border-b border-gray-100 dark:border-gray-800 pb-2']),

                    // Middle Box
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('order.order_number')
                                ->label(__('Pesanan'))
                                ->formatStateUsing(fn($state) => __('Transaksi:') . ' ' . $state)
                                ->weight(FontWeight::Bold)
                                ->size('lg'),
                            Tables\Columns\TextColumn::make('payment_method')
                                ->badge()
                                ->color('info')
                                ->size('xs'),
                                Tables\Columns\TextColumn::make('created_at')
                                ->formatStateUsing(fn($state) => __('Tanggal:') . ' ' . \Carbon\Carbon::parse($state)->translatedFormat('d F Y, H:i'))
                                ->size('xs')
                                ->color('gray'),
                        ])->space(1),
                    ])->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-900 rounded-xl p-3']),

                    // Footer
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('total_text')
                            ->state(fn() => __('Total Bayar') . ':')
                            ->size('sm')
                            ->color('gray')
                            ->alignEnd()
                            ->extraAttributes(['class' => 'mt-1']),
                        Tables\Columns\TextColumn::make('amount')
                            ->money('idr')
                            ->weight(FontWeight::Bold)
                            ->color('success')
                            ->grow(false)
                            ->size('lg'),
                    ])->extraAttributes(['class' => 'mt-2 pt-2']),

                ])->space(3)->extraAttributes(['class' => 'p-4 bg-white dark:bg-gray-950 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_id')
                    ->label(__('Pesanan'))
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->native(false)
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(\App\Enums\PaymentStatus::class)
                    ->label(__('Status Bayar'))
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
                    ->modalHeading(__('Detail Pembayaran')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Konfirmasi Bayar'))
                    ->button()
                    ->size('lg')
                    ->color('primary')
                    ->icon('heroicon-m-plus-circle')
                    ->slideOver()
                    ->modalWidth('xl')
                    ->modalHeading(__('Konfirmasi Pembayaran'))
                    ->createAnother(false)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['payment_number'] = 'PAY-' . strtoupper(str()->random(8));
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
                            \Filament\Infolists\Components\TextEntry::make('payment_number')
                                ->label(__('ID Pembayaran'))
                                ->default(fn($record) => 'PAY-' . str_pad($record->id, 4, '0', STR_PAD_LEFT))
                                ->weight(FontWeight::Bold)
                                ->copyable(),
                            \Filament\Infolists\Components\TextEntry::make('status')
                                ->label(__('Status'))
                                ->badge()
                                ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),
                        ]),
                    ])
                    ->extraAttributes(['class' => 'bg-gray-50 dark:bg-white/5 border-0 shadow-none rounded-2xl mb-4']),

                \Filament\Infolists\Components\Section::make(__('Data Transaksi'))
                    ->icon('heroicon-o-document-text')
                    ->compact()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('order.order_number')
                            ->label(__('Nomor Pesanan'))
                            ->weight(FontWeight::Bold)
                            ->color('primary'),
                        \Filament\Infolists\Components\TextEntry::make('payment_method')
                            ->label(__('Metode Pembayaran'))
                            ->badge(),
                        \Filament\Infolists\Components\TextEntry::make('amount')
                            ->label(__('Nominal Bayar'))
                            ->money('idr')
                            ->weight(FontWeight::Bold)
                            ->color('success')
                            ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label(__('Tanggal Upload'))
                            ->dateTime('d F Y, H:i')
                            ->color('gray'),
                    ])->columns(2),

                \Filament\Infolists\Components\Section::make(__('Bukti Transfer'))
                    ->icon('heroicon-o-photo')
                    ->compact()
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('evidence_url')
                            ->hiddenLabel()
                            ->height('20rem')
                            ->width('100%')
                            ->extraImgAttributes(['class' => 'object-contain rounded-xl'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePayments::route('/'),
        ];
    }
}
