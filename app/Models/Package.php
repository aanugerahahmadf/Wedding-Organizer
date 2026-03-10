<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $wedding_organizer_id
 * @property int|null $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property numeric $price
 * @property numeric|null $discount_price
 * @property bool $is_featured
 * @property array<array-key, mixed>|null $features
 * @property string|null $theme
 * @property string|null $color
 * @property int|null $min_capacity
 * @property int|null $max_capacity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category|null $category
 * @property-read mixed $image_url
 * @property-read bool $is_wishlisted
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \App\Models\WeddingOrganizer $weddingOrganizer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wishlist> $wishlists
 * @property-read int|null $wishlists_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereDiscountPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereMaxCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereMinCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereTheme($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereWeddingOrganizerId($value)
 * @mixin \Eloquent
 */
class Package extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('package')->singleFile();
        $this->addMediaCollection('videos');
    }

    protected $fillable = [
        'wedding_organizer_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'is_featured',
        'features',
        'theme',
        'color',
        'min_capacity',
        'max_capacity',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_featured' => 'boolean',
    ];

    protected $appends = [
        'image_url',
        'is_wishlisted',
        'video_url',
    ];

    public function getImageUrlAttribute()
    {
        return $this->getFirstMediaUrl('package')
            ?: $this->weddingOrganizer?->getFirstMediaUrl('gallery')
            ?: 'https://via.placeholder.com/400x300?text=Package';
    }

    public function getVideoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('videos') ?: null;
    }

    public function getIsWishlistedAttribute(): bool
    {
        if (!auth('sanctum')->check()) {
            return false;
        }

        return $this->wishlists()->where('user_id', auth('sanctum')->id())->exists();
    }

    public function weddingOrganizer()
    {
        return $this->belongsTo(WeddingOrganizer::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
}
