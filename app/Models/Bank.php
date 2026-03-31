<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'logo',
        'qris_payload',
        'qris_image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Bank $bank) {
            // Auto-generate code from name if empty
            if (empty($bank->code)) {
                $bank->code = \Illuminate\Support\Str::slug($bank->name, '_');
            }
            // Default type to 'bank' if empty
            if (empty($bank->type)) {
                $bank->type = 'bank';
            }
            // Ensure is_active is set
            if (!isset($bank->is_active)) {
                $bank->is_active = true;
            }
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo);
        }
        return null;
    }

    public function getQrisImageUrlAttribute(): ?string
    {
        if ($this->qris_image) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->qris_image);
        }

        if ($this->qris_payload) {
            try {
                $result = \Endroid\QrCode\Builder\Builder::create()
                    ->writer(new \Endroid\QrCode\Writer\PngWriter())
                    ->data($this->qris_payload)
                    ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                    ->errorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::Low)
                    ->size(300)
                    ->margin(10)
                    ->build();
                    
                return $result->getDataUri();
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }
}
