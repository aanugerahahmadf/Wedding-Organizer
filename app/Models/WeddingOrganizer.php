<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property numeric $rating
 * @property int $is_verified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $business_name
 * @property-read mixed $city
 * @property-read mixed $cover_image_url
 * @property-read mixed $email
 * @property-read mixed $logo_url
 * @property-read mixed $phone
 * @property-read mixed $total_reviews
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Package> $packages
 * @property-read int|null $packages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class WeddingOrganizer extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('videos');
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'latitude',
        'longitude',
        'rating',
        'is_verified',
    ];

    protected $appends = [
        'business_name',
        'city',
        'logo_url',
        'cover_image_url',
        'phone',
        'total_reviews',
        'video_url',
    ];

    /**
     * Get the superadmin user (Devi Make Up owner)
     */
    public static function getOwner()
    {
        return \App\Models\User::whereHas('roles', function ($query): void {
            $query->where('name', 'super_admin');
        })->first();
    }

    public function getBusinessNameAttribute()
    {
        return $this->name;
    }

    public function getCityAttribute()
    {
        if (!$this->address) return 'Unknown';
        $parts = explode(',', $this->address);

        return trim(end($parts)) ?: 'Unknown';
    }

    public function getPhoneAttribute()
    {
        $owner = self::getOwner();
        return $owner?->phone ?? '';
    }

    public function getEmailAttribute()
    {
        $owner = self::getOwner();
        return $owner?->email ?? '';
    }

    public function getLogoUrlAttribute()
    {
        return $this->getFirstMediaUrl('logo') ?: 'https://via.placeholder.com/150';
    }

    public function getCoverImageUrlAttribute()
    {
        return $this->getFirstMediaUrl('gallery') ?: 'https://via.placeholder.com/800x400';
    }

    public function getVideoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('videos') ?: null;
    }

    public function getTotalReviewsAttribute()
    {
        return $this->reviews_count ?? $this->reviews()->count();
    }


    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
