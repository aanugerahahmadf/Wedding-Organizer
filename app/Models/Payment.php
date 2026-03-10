<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property string $payment_number
 * @property string $payment_method
 * @property string $status
 * @property numeric $amount
 * @property numeric $admin_fee
 * @property numeric $total_amount
 * @property string|null $payment_proof
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $expired_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $notes
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $payment_method_label
 * @property-read string $status_color
 * @property-read string $status_label
 * @property-read \App\Models\PaymentMethod|null $methodDetails
 * @property-read \App\Models\Order $order
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAdminFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentProof($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_number',
        'payment_method',
        'status',
        'amount',
        'admin_fee',
        'total_amount',
        'payment_proof',
        'paid_at',
        'expired_at',
        'cancelled_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Payment method labels
     */
    public static function paymentMethodLabels(): array
    {
        return [
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
            'gopay' => 'GoPay',
            'ovo' => 'OVO',
            'dana' => 'DANA',
            'shopeepay' => 'ShopeePay',
            'qris' => 'QRIS',
            'alfamart' => 'Alfamart',
            'indomaret' => 'Indomaret',
        ];
    }

    /**
     * Payment status labels
     */
    public static function statusLabels(): array
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'success' => 'Success',
            'failed' => 'Failed',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
        ];
    }

    /**
     * Payment status colors for UI
     */
    public static function statusColors(): array
    {
        return [
            'pending' => 'warning',
            'processing' => 'info',
            'success' => 'success',
            'failed' => 'danger',
            'expired' => 'gray',
            'cancelled' => 'gray',
            'refunded' => 'warning',
        ];
    }

    /**
     * Get the order that owns the payment
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if payment is successful
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'expired', 'cancelled']);
    }

    /**
     * Mark payment as success
     */
    public function markAsSuccess(): void
    {
        $this->update([
            'status' => 'success',
            'paid_at' => now(),
        ]);

        // Update order payment status
        $this->order->update([
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }

    /**
     * Get payment method label
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return self::paymentMethodLabels()[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return self::statusColors()[$this->status] ?? 'gray';
    }
    /**
     * Get the payment method details
     */
    public function methodDetails(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'code');
    }
}
