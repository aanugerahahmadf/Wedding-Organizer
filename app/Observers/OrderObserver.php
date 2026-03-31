<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\History;
use Filament\Notifications\Notification;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        History::create([
            'user_id' => $order->user_id,
            'type' => 'order',
            'transaction_id' => $order->id,
            'reference_number' => $order->order_number,
            'amount' => $order->total_price,
            'info' => $order->package?->name ?? __('Pemesanan Paket'),
            'status' => $order->status instanceof \BackedEnum ? $order->status->value : $order->status,
            'notes' => $order->notes,
            'created_at' => $order->created_at,
        ]);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Fitur Otomatis: Auto-Refund jika Order Dibatalkan tapi sudah Dibayar
        if ($order->isDirty('status') && $order->status === \App\Enums\OrderStatus::CANCELLED) {
            if (in_array($order->payment_status, [\App\Enums\OrderPaymentStatus::PAID, \App\Enums\OrderPaymentStatus::PARTIAL])) {
                $user = $order->user;
                if ($user) {
                    $user->increment('balance', $order->total_price);
                    
                    // Update status pembayaran jadi Refunded secara otomatis
                    $order->updateQuietly(['payment_status' => \App\Enums\OrderPaymentStatus::REFUNDED]);

                    // Catat Log Refund Otomatis
                    \App\Models\History::create([
                        'user_id' => $order->user_id,
                        'type' => 'balance',
                        'transaction_id' => $order->id,
                        'reference_number' => 'REF-' . $order->order_number,
                        'amount' => $order->total_price,
                        'info' => __('Refund Otomatis (Pembatalan Order #') . $order->order_number . ')',
                        'status' => 'success',
                    ]);

                    // 🔔 Notify User: Refund
                    Notification::make()
                        ->title(__('Refund Berhasil'))
                        ->body(__('Dana sebesar Rp ') . number_format($order->total_price, 2, ',', '.') . __(' telah dikembalikan ke saldo Anda karena pembatalan Order #') . $order->order_number)
                        ->success()
                        ->sendToDatabase($user);
                }
            }
        }

        // 🔔 Notify User: Status Change (Hanya jika status berubah)
        if ($order->isDirty('status')) {
             $user = $order->user;
             if ($user) {
                 // Pastikan status adalah Enum object sebelum panggil getLabel()
                 $statusLabel = $order->status instanceof \App\Enums\OrderStatus 
                     ? $order->status->getLabel() 
                     : (is_string($order->status) ? $order->status : __('Tidak Diketahui'));

                 $statusIcon = $order->status instanceof \App\Enums\OrderStatus 
                     ? $order->status->getIcon() 
                     : 'heroicon-o-information-circle';

                 Notification::make()
                     ->title(__('Update Pesanan #') . $order->order_number)
                     ->body(__('Status pesanan Anda kini: ') . $statusLabel)
                     ->info()
                     ->icon($statusIcon)
                     ->sendToDatabase($user);
             }
        }

        History::updateOrCreate(
            ['type' => 'order', 'transaction_id' => $order->id],
            [
                'status' => $order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status,
                'amount' => $order->total_price,
                'info' => $order->package?->name ?? __('Pemesanan Paket'),
                'notes' => $order->notes,
            ]
        );
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        History::where('type', 'order')
               ->where('transaction_id', $order->id)
               ->delete();
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        History::withTrashed()
               ->where('type', 'order')
               ->where('transaction_id', $order->id)
               ->restore();
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        History::withTrashed()
               ->where('type', 'order')
               ->where('transaction_id', $order->id)
               ->forceDelete();
    }
}
