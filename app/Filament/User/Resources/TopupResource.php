<?php

namespace App\Filament\User\Resources;

use App\Models\Topup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Models\PaymentMethod;
use App\Enums\PaymentMethodType;
use App\Models\Withdrawal;
use App\Enums\WithdrawalStatus;
use Illuminate\Support\Str;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class TopupResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference_number', 'payment_method'];
    }


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


    public static function getNavigationLabel(): string
    {
        return __('Top Up Saldo');
    }

    public static function getModelLabel(): string
    {
        return __('Top Up Saldo');
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
                                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : null)
                                    ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $state)) : null)
                                    ->extraInputAttributes(['class' => 'text-2xl font-bold text-primary-600 dark:text-primary-400'])
                                    ->prefix('Rp')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $val = $state ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', (string) $state)) : 0;
                                        $set('total_amount', $val + 2500);
                                    }),
                                 Forms\Components\Select::make('payment_method')
                                    ->searchable()
                                    ->label(__('Metode Pembayaran'))
                                    ->options(\App\Models\PaymentMethod::where('is_active', true)->pluck('name', 'code'))
                                    ->required()
                                    ->native(false)
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
                            ->visible(fn (Forms\Get $get) => filled($get('payment_method')))
                            ->content(function (\Filament\Forms\Get $get) {
                                $amtStr = (string) $get('amount');
                                $amount = $amtStr ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $amtStr)) : 0;
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
                            ->alignEnd()
,
                    ])->extraAttributes(['class' => 'mb-2 border-b border-gray-100 dark:border-gray-800 pb-2']),

                    // Middle Box
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('total_amount_label')
                            ->state(fn() => __('Nominal Top Up'))
                            ->size('xs')
                            ->color('gray'),
                        Tables\Columns\TextColumn::make('amount')
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                            ->weight(FontWeight::Bold)
                            ->size('lg'),
                        Tables\Columns\TextColumn::make('payment_method')
                            ->badge()
                            ->color('info')
                            ->size('xs')
,
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
                            ->alignEnd()
,
                    ])->extraAttributes(['class' => 'mt-2 pt-2']),

                ])->space(3)->extraAttributes(['class' => 'p-4 bg-white dark:bg-gray-950 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->searchable()
                    ->options(\App\Enums\TopupStatus::class)
                    ->label(__('Status Topup'))

                    ->native(false),
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
                Tables\Actions\Action::make('withdraw')
                    ->label(__('Tarik Saldo'))
                    ->icon('heroicon-m-banknotes')
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->slideOver()
                    ->modalWidth('xl')
                    ->modalHeading(__('Tarik Saldo'))
                    ->form([
                        Forms\Components\Section::make(__('Rekening Tujuan'))
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label(__('Jumlah Penarikan'))
                                    ->required()
                                    ->prefix('Rp')
                                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : null)
                                    ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $state)) : null)
                                    ->helperText(fn () => __('Saldo Anda: ') . 'Rp ' . number_format(auth()->user()->balance, 2, ',', '.'))
                                    ->rules([
                                        fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                                            $cleanValue = (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $value));
                                            if ($cleanValue > auth()->user()->balance) {
                                                $fail(__('Saldo tidak mencukupi untuk melakukan penarikan.'));
                                            }
                                        },
                                    ]),
                                 Forms\Components\Select::make('bank_id')
                                    ->searchable()
                                    ->label(__('Nama Bank / E-Wallet'))
                                    ->required()
                                    ->options(\App\Models\Bank::where('is_active', true)->pluck('name', 'id'))
                                    ->native(false)
                                    ->preload()
                                    ->prefixIcon('heroicon-o-building-library'),
                                Forms\Components\TextInput::make('account_number')
                                    ->label(__('Nomor Rekening'))
                                    ->required(),
                                Forms\Components\TextInput::make('account_holder')
                                    ->label(__('Nama Pemilik Rekening'))
                                    ->required(),
                                Forms\Components\Textarea::make('notes')
                                    ->label(__('Catatan (Opsional)')),
                            ])
                    ])
                    ->action(function (array $data): void {
                        /** @var \App\Models\User $user */
                        $user = auth()->user();
                        
                        if ($user->balance < $data['amount']) {
                            Notification::make()
                                ->title(__('Saldo tidak cukup'))
                                ->body(__('Saldo Anda saat ini adalah ') . 'Rp ' . number_format($user->balance, 2, ',', '.'))
                                ->danger()
                                ->send();
                            return;
                        }

                        $user->decrement('balance', $data['amount']);
                        
                        $bank = \App\Models\Bank::find($data['bank_id']);
                        
                        Withdrawal::create([
                            'user_id' => $user->id,
                            'bank_id' => $data['bank_id'],
                            'reference_number' => 'WD-' . strtoupper(Str::random(10)),
                            'amount' => $data['amount'],
                            'bank_name' => $bank?->name,
                            'account_number' => $data['account_number'],
                            'account_holder' => $data['account_holder'],
                            'status' => WithdrawalStatus::PENDING,
                            'notes' => $data['notes'],
                        ]);

                        Notification::make()
                            ->title(__('Permintaan penarikan berhasil diajukan'))
                            ->body(__('Permintaan Anda akan segera diproses oleh admin.'))
                            ->success()
                            ->send();
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
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                            ->weight(FontWeight::Bold),
                        \Filament\Infolists\Components\TextEntry::make('admin_fee')
                            ->label(__('Biaya Admin'))
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                            ->color('gray'),
                        \Filament\Infolists\Components\TextEntry::make('total_amount')
                            ->label(__('Total Pembayaran'))
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
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
