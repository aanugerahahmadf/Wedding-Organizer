<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * @mixin \Eloquent
 * @property-read \App\Models\PaymentMethod $record
 */
class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 7;

    public static function getModelLabel(): string
    {
        return __('Metode Pembayaran');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Metode Pembayaran');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Transaksi');
    }

    public static function getNavigationLabel(): string
    {
        return __('Metode Pembayaran');
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var Builder $query */
        $query = static::$model::query();

        return (string) $query->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Total Metode Pembayaran Aktif');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(['sm' => 3])
                    ->schema([
                        Forms\Components\Section::make(__('Konfigurasi Dasar'))
                            ->description(__('Tentukan identitas dan tipe metode pembayaran ini.'))
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Nama Metode'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('e.g., Bank BCA, GoPay, OVO'))
                                    ->prefixIcon('heroicon-m-identification')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('code', Str::slug($state)) : null),

                                Forms\Components\TextInput::make('code')
                                    ->label(__('Kode Unik'))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder(__('e.g., bca, gopay'))
                                    ->prefixIcon('heroicon-m-key'),

                                Forms\Components\ToggleButtons::make('type')
                                    ->label(__('Tipe Pembayaran'))
                                    ->options(\App\Enums\PaymentMethodType::class)
                                    ->default(\App\Enums\PaymentMethodType::BANK_TRANSFER)
                                    ->required()
                                    ->inline()
                                    ->live(),
                            ])
                            ->columnSpan(2),

                        Forms\Components\Section::make(__('Status & Biaya'))
                            ->description(__('Atur status aktif dan biaya admin.'))
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label(__('Status Aktif'))
                                    ->default(true)
                                    ->helperText(__('Hanya metode aktif yang muncul di aplikasi.'))
                                    ->onColor('success')
                                    ->offColor('danger'),

                                Forms\Components\TextInput::make('fee')
                                    ->label(__('Biaya Admin'))
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->placeholder('0'),
                            ])
                            ->columnSpan(1),
                    ]),

                Forms\Components\Section::make(__('Detail Konten & Instruksi'))
                    ->description(__('Lengkapi informasi rekening atau gambar QRIS.'))
                    ->schema([
                        Forms\Components\Grid::make(['sm' => 2])
                            ->schema([
                                Forms\Components\TextInput::make('account_number')
                                    ->label(fn (Forms\Get $get) => $get('type') === \App\Enums\PaymentMethodType::EWALLET->value ? __('Nomor E-Wallet / HP') : __('Nomor Rekening'))
                                    ->required(fn (Forms\Get $get) => in_array($get('type'), [\App\Enums\PaymentMethodType::BANK_TRANSFER->value, \App\Enums\PaymentMethodType::EWALLET->value]))
                                    ->placeholder(__('Masukkan nomor rekening atau HP...'))
                                    ->visible(fn (Forms\Get $get) => in_array($get('type'), [\App\Enums\PaymentMethodType::BANK_TRANSFER->value, \App\Enums\PaymentMethodType::EWALLET->value]))
                                    ->prefixIcon('heroicon-m-hashtag'),

                                Forms\Components\TextInput::make('account_holder')
                                    ->label(__('Nama Pemilik Rekening'))
                                    ->required(fn (Forms\Get $get) => $get('type') === \App\Enums\PaymentMethodType::BANK_TRANSFER->value)
                                    ->placeholder(__('e.g., PT Devi Make Up Wedding Organizer'))
                                    ->visible(fn (Forms\Get $get) => $get('type') === \App\Enums\PaymentMethodType::BANK_TRANSFER->value)
                                    ->prefixIcon('heroicon-m-user'),
                            ]),

                        \JeffersonGoncalves\Filament\QrCodeField\Forms\Components\QrCodeInput::make('qris_image')
                            ->label(__('Scan/Input QRIS Payload'))
                            ->visible(fn (Forms\Get $get) => $get('type') === \App\Enums\PaymentMethodType::QRIS->value)
                            ->required(fn (Forms\Get $get) => $get('type') === \App\Enums\PaymentMethodType::QRIS->value)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                            
                        Forms\Components\Placeholder::make('qris_preview')
                            ->label(__('Preview QRIS'))
                            ->content(function (Forms\Get $get) {
                                $payload = $get('qris_image');
                                if (!$payload) return new \Illuminate\Support\HtmlString('<span class="text-gray-500">Belum ada QR Code</span>');
                                
                                $temp = new \App\Models\PaymentMethod(['qris_image' => $payload]);
                                $url = $temp->qris_image_url;
                                
                                if (!$url) return new \Illuminate\Support\HtmlString('<span class="text-red-500">Gagal memuat QR Code atau Data Tidak Valid</span>');
                                
                                return new \Illuminate\Support\HtmlString('<img src="' . $url . '" class="w-48 h-48 border rounded-lg shadow-sm p-2 bg-white" />');
                            })
                            ->visible(fn (Forms\Get $get) => $get('type') === \App\Enums\PaymentMethodType::QRIS->value && $get('qris_image'))
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('icon')
                            ->label(__('Ikon / Logo'))
                            ->image()
                            ->directory('payment-icons')
                            ->visibility('public')
                            ->maxWidth(200)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('instructions')
                            ->label(__('Instruksi Pembayaran'))
                            ->placeholder(__('Tuliskan langkah-langkah pembayaran di sini...'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label(__('Logo'))
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Metode'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Tipe'))
                    ->badge()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('account_number')
                    ->label(__('Nomor Rekening/HP'))
                    ->searchable()
                    ->copyable()
                    ->alignment('center'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Aktif'))
                    ->boolean()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('fee')
                    ->label(__('Biaya Admin'))
                    ->money('IDR')
                    ->sortable()
                    ->alignment('right'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Tipe'))
                    ->options([
                        'bank_transfer' => __('Bank'),
                        'ewallet' => __('E-Wallet'),
                        'qris' => __('QRIS'),
                        'cod' => __('COD'),
                        'wallet' => __('Saldo'),
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Status Aktif')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->button()
                    ->color('info')
                    ->size('lg'),
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Metode Diperbarui'))
                            ->body(__('Metode pembayaran telah berhasil diperbarui.'))
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Metode Dihapus'))
                            ->body(__('Metode pembayaran telah berhasil dihapus.'))
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePaymentMethods::route('/'),
        ];
    }
}
