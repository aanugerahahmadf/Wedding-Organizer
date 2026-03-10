<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $code
 * @property string|null $icon
 * @property string|null $account_number
 * @property string|null $account_holder
 * @property string|null $qris_image
 * @property numeric $fee
 * @property string|null $instructions
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $icon_url
 * @property-read mixed $qris_image_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereAccountHolder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereQrisImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'code',
        'icon',
        'account_number',
        'account_holder',
        'qris_image',
        'fee',
        'instructions',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fee' => 'decimal:2',
    ];

    /**
     * Get the full URL for the icon.
     */
    public function getIconUrlAttribute()
    {
        if ($this->icon) {
            return asset('storage/' . $this->icon);
        }
        return null;
    }

    /**
     * Get the full URL for the QRIS image.
     */
    public function getQrisImageUrlAttribute()
    {
        if ($this->qris_image) {
            return asset('storage/' . $this->qris_image);
        }
        return null;
    }
}
