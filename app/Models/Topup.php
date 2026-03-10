<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $reference_number
 * @property numeric $amount
 * @property numeric $admin_fee
 * @property numeric $total_amount
 * @property string|null $payment_method
 * @property string $status
 * @property string|null $payment_url
 * @property string|null $snap_token
 * @property string|null $payment_proof
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereAdminFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup wherePaymentProof($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup wherePaymentUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereSnapToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereUserId($value)
 * @mixin \Eloquent
 */
class Topup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference_number',
        'amount',
        'admin_fee',
        'total_amount',
        'payment_method',
        'status',
        'payment_url',
        'snap_token',
        'payment_proof',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model): void {
            if (!$model->reference_number) {
                $model->reference_number = 'TOPUP-' . strtoupper(Str::random(10));
            }
        });
    }
}
