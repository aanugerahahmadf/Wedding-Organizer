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
                                Forms\Components\Select::make('bank_id')
                                    ->label(__('Nama Bank / E-Wallet'))
                                    ->relationship('bank', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('Nama Bank/E-Wallet'))
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->editOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('Nama Bank/E-Wallet'))
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            $bank = \App\Models\Bank::find($state);
                                            if ($bank) {
                                                // Sync display name if empty
                                                if (empty($get('name'))) {
                                                    $set('name', $bank->name);
                                                }
                                                // Sync code if empty
                                                if (empty($get('code'))) {
                                                    $set('code', strtolower(str_replace(' ', '', $bank->name)));
                                                }
                                                // Sync type if empty
                                                if ($bank->type && empty($get('type'))) {
                                                    $set('type', $bank->type);
                                                }
                                            }
                                        }
                                    })
                                    ->placeholder(__('Pilih dari Daftar Bank/E-Wallet...'))
                                    ->prefixIcon('heroicon-o-building-library')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('name')
                                    ->label(__('Nama Tampilan Metode'))
                                    ->required()
                                    ->placeholder(__('e.g., Transfer Bank BCA atau BCA Virtual Account'))
                                    ->prefixIcon('heroicon-m-identification')
                                    ->maxLength(255),

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
                                    ->inline(),
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
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.'))
                                    ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $state)) : 0)
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

                Forms\Components\Section::make(__('Data QRIS (Opsional)'))
                    ->description(__('Input payload/gambar QRIS jika bank/e-wallet ini mendukung pembayaran QR.'))
                    ->visible(fn (Forms\Get $get) => $get('type') === \App\Enums\PaymentMethodType::QRIS->value)
                    ->schema([
                        \JeffersonGoncalves\Filament\QrCodeField\Forms\Components\QrCodeInput::make('qris_payload')
                            ->label(__('QRIS Payload'))
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('qris_image')
                            ->label(__('Atau Upload Gambar QRIS'))
                            ->image()
                            ->directory('bank-qris')
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('qris_preview')
                            ->label(__('Preview QRIS'))
                            ->content(function ($record, Forms\Get $get) {
                                // If editing an existing record and we have no fresh state, show the record's URL
                                if ($record && empty($get('qris_payload')) && empty($get('qris_image'))) {
                                    $url = $record->qris_image_url;
                                } else {
                                    // Try to generate preview from current state
                                    $payload = $get('qris_payload');
                                    $image = $get('qris_image');
                                    
                                    if (!$payload && !$image) return new \Illuminate\Support\HtmlString('<span class="text-gray-500 italic">' . __('Belum ada data QRIS') . '</span>');

                                    // Create a temporary model to compute the URL
                                    $temp = new \App\Models\PaymentMethod([
                                        'qris_payload' => $payload,
                                        'qris_image' => is_string($image) ? $image : null, // Handle potential temporary file object
                                    ]);
                                    $url = $temp->qris_image_url;
                                }

                                if (!$url) return null;
                                return new \Illuminate\Support\HtmlString('<div class="p-2 bg-white rounded-lg border shadow-sm w-fit"><img src="' . $url . '" class="w-32 h-32" /></div>');
                            })
                            ->columnSpanFull(),
                    ])->columns(1),

                Forms\Components\Section::make(__('Ikon & Instruksi'))
                    ->schema([
                        Forms\Components\FileUpload::make('icon')
                            ->label(__('Ikon / Logo Kustom'))
                            ->helperText(__('Opsional. Jika dikosongkan, sistem akan menggunakan logo dari daftar Bank/E-Wallet yang dipilih.'))
                            ->image()
                            ->directory('payment-icons')
                            ->visibility('public')
                            ->maxWidth(200)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('instructions')
                            ->label(__('Instruksi Pembayaran'))
                            ->placeholder(__('Tuliskan langkah-langkah pembayaran di sini...'))
                            ->columnSpanFull(),
                    ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon_url')
                    ->label(__('Logo'))
                    ->circular()
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Metode Pembayaran'))
                    ->searchable()
                    ->description(fn($record) => $record->bank?->name)
                    ->alignment('left'),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Tipe'))
                    ->badge()
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('account_number')
                    ->label(__('Nomor Rekening/HP'))
                    ->searchable()
                    ->copyable()
                    ->alignment('center'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Aktif'))
                    ->boolean()
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('fee')
                    ->label(__('Biaya Admin'))
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                    ->searchable()
                    ->alignment('right'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->searchable()
                    
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
