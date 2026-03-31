<?php

namespace App\Observers;

use App\Models\Topup;
use App\Models\History;
use Filament\Notifications\Notification;

class TopupObserver
{
    /**
     * Handle the Topup "created" event.
     */
    public function created(Topup $topup): void
    {
        History::create([
            'user_id' => $topup->user_id,
            'type' => 'topup',
            'transaction_id' => $topup->id,
            'reference_number' => $topup->reference_number,
            'amount' => $topup->amount,
            'info' => $topup->payment_method_name ?? $topup->payment_method,
            'status' => $topup->status instanceof \BackedEnum ? $topup->status->value : $topup->status,
            'notes' => $topup->notes,
            'created_at' => $topup->created_at,
        ]);

        // 🎯 OTOMATISASI INSTANT TOPUP
        if ($topup->status === \App\Enums\TopupStatus::SUCCESS || $topup->status === 'success') {
            $user = $topup->user;
            if ($user) {
                $user->increment('balance', $topup->amount);
                Notification::make()
                    ->title(__('Topup Berhasil'))
                    ->body(__('Saldo sebesar Rp ') . number_format($topup->amount, 2, ',', '.') . __(' telah masuk ke akun Anda.'))
                    ->success()
                    ->icon('heroicon-o-banknotes')
                    ->sendToDatabase($user);
            }
        }
    }

    /**
     * Handle the Topup "updated" event.
     */
    public function updated(Topup $topup): void
    {
        History::updateOrCreate(
            ['type' => 'topup', 'transaction_id' => $topup->id],
            [
                'user_id' => $topup->user_id,
                'reference_number' => $topup->reference_number,
                'status' => $topup->status instanceof \BackedEnum ? $topup->status->value : $topup->status,
                'amount' => $topup->amount,
                'notes' => $topup->notes,
                'info' => $topup->payment_method_name ?? $topup->payment_method,
            ]
        );

        // Otomatis tambah saldo jika status SUCCESS
        if ($topup->status === \App\Enums\TopupStatus::SUCCESS && $topup->getOriginal('status') !== \App\Enums\TopupStatus::SUCCESS) {
            $user = $topup->user;
            if ($user) {
                $user->increment('balance', $topup->amount);

                // 🔔 Notify User: Topup Success
                Notification::make()
                    ->title(__('Topup Berhasil'))
                    ->body(__('Saldo sebesar Rp ') . number_format($topup->amount, 2, ',', '.') . __(' telah masuk ke akun Anda.'))
                    ->success()
                    ->icon('heroicon-o-banknotes')
                    ->sendToDatabase($user);
            }
        }
    }

    /**
     * Handle the Topup "deleted" event.
     */
    public function deleted(Topup $topup): void
    {
        History::where('type', 'topup')
               ->where('transaction_id', $topup->id)
               ->delete();
    }

    /**
     * Handle the Topup "restored" event.
     */
    public function restored(Topup $topup): void
    {
        History::withTrashed()
               ->where('type', 'topup')
               ->where('transaction_id', $topup->id)
               ->restore();
    }

    /**
     * Handle the Topup "force deleted" event.
     */
    public function forceDeleted(Topup $topup): void
    {
        History::withTrashed()
               ->where('type', 'topup')
               ->where('transaction_id', $topup->id)
               ->forceDelete();
    }
}
