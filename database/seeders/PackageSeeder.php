<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Package;
use App\Models\WeddingOrganizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = WeddingOrganizer::first();
        if (!$organizer) return; 

        $categoryTradisional = Category::where('slug', 'akad-resepsi-tradisional')->first();
        $categoryModern = Category::where('slug', 'modern-international-look')->first();
        $categoryWisuda = Category::where('slug', 'make-up-wisuda-pesta')->first();
        
        $packages = [
            [
                'name' => 'Paket Silver Traditional',
                'category_id' => $categoryTradisional?->id,
                'description' => 'Layanan make up akad & resepsi di hari yang sama untuk gaya tradisional.',
                'price' => 5500000,
                'discount_price' => 4750000,
                'features' => ['Make Up Akad', 'Make Up Resepsi', 'Retouch 1x', 'Aksesoris Dasar', 'Hijab/Hair Do'],
                'theme' => 'Tradisional Elegan',
                'color' => '#be123c',
                'min_capacity' => 1,
                'max_capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1594463750939-ebb28c3f7f05',
            ],
            [
                'name' => 'Paket Gold Modern International',
                'category_id' => $categoryModern?->id,
                'description' => 'Riasan premium dengan produk High-End untuk gaya modern internasional.',
                'price' => 9500000,
                'discount_price' => null,
                'features' => ['High-End Products Only', 'Airbrush Make Up', 'Unlimited Retouch', 'Crown & Veil Premium', 'Trial Make Up 1x'],
                'theme' => 'Modern Luxury',
                'color' => '#1d4ed8',
                'min_capacity' => 1,
                'max_capacity' => 5,
                'is_featured' => true,
                'image' => 'https://images.unsplash.com/photo-1510076857177-74700760f497',
            ],
            [
                'name' => 'Paket Platinum All-in-One',
                'category_id' => $categoryTradisional?->id,
                'description' => 'Solusi lengkap riasan panggung, orang tua pempelai, dan pager ayu.',
                'price' => 15000000,
                'discount_price' => 13500000,
                'features' => ['Make Up Pengantin', 'Make Up 2 Ibu', 'Make Up 4 Pager Ayu', 'Sewa Busana Pengantin 1x', 'Hand Bouquet'],
                'theme' => 'Royal Wedding',
                'color' => '#a16207',
                'min_capacity' => 1,
                'max_capacity' => 10,
                'is_featured' => true,
                'image' => 'https://images.unsplash.com/photo-1591391516011-df6ad824f36f',
            ],
            [
                'name' => 'Private Make Up Wisuda',
                'category_id' => $categoryWisuda?->id,
                'description' => 'Make up privat di studio kami untuk hari kelulusan Anda.',
                'price' => 750000,
                'discount_price' => 600000,
                'features' => ['Flawless Look', 'Fake Lashes', 'Contouring', 'Hijab/Hair Do Simple', 'Free Retouch di Lokasi'],
                'theme' => 'Fresh & Glowy',
                'color' => '#047857',
                'min_capacity' => 0,
                'max_capacity' => 1,
                'image' => 'https://images.unsplash.com/photo-1512413316925-fd255f7c6ef1',
            ],
        ];

        foreach ($packages as $p) {
            $image = $p['image'];
            unset($p['image']);

            $package = Package::updateOrCreate(
                ['slug' => Str::slug($p['name'])],
                array_merge($p, ['wedding_organizer_id' => $organizer->id])
            );

            // Tambahkan gambar produk dengan Try-Catch agar tidak error jika internet lambat
            if ($package->getMedia('package')->isEmpty()) {
                try {
                    $package->addMediaFromUrl($image . '?q=80&w=600&auto=format&fit=crop')
                        ->toMediaCollection('package');
                } catch (\Exception $e) {
                    // Lewati jika koneksi ke unsplash gagal
                }
            }
        }
    }
}
