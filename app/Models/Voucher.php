<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property numeric $discount_amount
 * @property string $discount_type
 * @property numeric $min_purchase
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereMinPurchase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Voucher extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_amount',
        'discount_type',
        'min_purchase',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'discount_amount' => 'decimal:2',
        'min_purchase' => 'decimal:2',
    ];
}
