<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Package;
use App\Models\User;
use App\Models\WeddingOrganizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WeddingDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Categories
        $categories = [
            ['name' => 'Traditional', 'slug' => 'traditional'],
            ['name' => 'Modern', 'slug' => 'modern'],
            ['name' => 'Rustic', 'slug' => 'rustic'],
            ['name' => 'Luxury', 'slug' => 'luxury'],
            ['name' => 'Minimalist', 'slug' => 'minimalist'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        $categoryIds = Category::pluck('id')->toArray();

        // 2. Devi Make Up & Wedding — satu WO, semua paket milik Devi
        $woData = [
            [
                'name' => 'Devi Make Up & Wedding',
                'description' => 'Professional wedding organizer specializing in traditional and modern make-up.',
                'address' => 'Jakarta Selatan, DKI Jakarta',
                'user' => [
                    'full_name' => 'Super Admin',
                    'first_name' => 'Super',
                    'last_name' => 'Admin',
                    'username' => 'superadmin',
                    'email' => 'devimakeup.wo@gmail.com',
                    'password' => '@Admin123',
                ],
                'packages' => [
                    [
                        'name' => 'Traditional Gold Package',
                        'price' => 25000000,
                        'description' => 'Complete traditional wedding package with premium make-up.',
                        'theme' => 'Traditional',
                    ],
                    [
                        'name' => 'Modern Minimalist Package',
                        'price' => 15000000,
                        'description' => 'Simple and elegant package for modern couples.',
                        'theme' => 'Modern',
                    ],
                    [
                        'name' => 'Outdoor Rustic Party',
                        'price' => 35000000,
                        'description' => 'Beautiful rustic outdoor setup with floral decorations.',
                        'theme' => 'Rustic',
                    ],
                    [
                        'name' => 'Grand Ballroom Royal',
                        'price' => 150000000,
                        'description' => 'The ultimate luxury experience in a five-star ballroom.',
                        'theme' => 'Luxury',
                    ],
                ],
                'images' => [
                    'https://images.unsplash.com/photo-1595184153530-1545692aaea4?auto=format&fit=crop&w=800&q=80', // Bridal makeup artist applying foundation
                    'https://images.unsplash.com/photo-1522205408450-add114ad53fe?auto=format&fit=crop&w=800&q=80', // Close-up of bridal makeup
                    'https://images.unsplash.com/photo-1599599947980-0fbac7a77a5f?auto=format&fit=crop&w=800&q=80', // Bride getting makeup done
                    'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=800&q=80', // Professional makeup artist
                ],
            ],
        ];

        foreach ($woData as $data) {
            // Create/update User (sama dengan SuperAdminSeeder)
            $user = User::updateOrCreate(
                ['email' => $data['user']['email']],
                [
                    'full_name' => $data['user']['full_name'],
                    'first_name' => $data['user']['first_name'] ?? null,
                    'last_name' => $data['user']['last_name'] ?? null,
                    'username' => $data['user']['username'],
                    'password' => Hash::make($data['user']['password']),
                ]
            );

            // Create WO — pakai slug agar tidak duplicate
            $wo = WeddingOrganizer::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'address' => $data['address'],
                    'is_verified' => true,
                    'rating' => rand(40, 50) / 10,
                ]
            );

            // Create Packages — pakai slug (unique global) agar tidak duplicate
            $packageIndex = 0;
            foreach ($data['packages'] as $pkg) {
                $slug = Str::slug($pkg['name']);
                $package = Package::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'wedding_organizer_id' => $wo->id,
                        'name' => $pkg['name'],
                        'description' => $pkg['description'],
                        'price' => $pkg['price'],
                        'category_id' => $categoryIds[array_rand($categoryIds)],
                        'theme' => $pkg['theme'],
                        'is_featured' => true,
                    ]
                );
                // Gambar per paket
                if ($package->getMedia('package')->count() == 0) {
                    $imgUrl = $data['images'][$packageIndex % count($data['images'])] ?? $data['images'][0];
                    $this->addImageFromUrl($package, $imgUrl, 'package');
                }
                $packageIndex++;
            }

            // Gambar organizer (banner/cover)
            if ($wo->getMedia('gallery')->count() == 0) {
                foreach ($data['images'] as $url) {
                    $this->addImageFromUrl($wo, $url, 'gallery');
                }
            }
        }

        // 3. Banners — semua Devi Make Up
        $banners = [
            [
                'title' => 'Devi Make Up - Big Opening Sale!',
                'image_url' => 'https://images.unsplash.com/photo-1595184153530-1545692aaea4?auto=format&fit=crop&w=1200&q=80', // Bridal makeup artist
                'link_url' => '/packages',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Paket Wedding Devi Make Up',
                'image_url' => 'https://images.unsplash.com/photo-1522205408450-add114ad53fe?auto=format&fit=crop&w=1200&q=80', // Bridal makeup close-up
                'link_url' => '/organizers',
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($banners as $b) {
            \App\Models\Banner::updateOrCreate(['title' => $b['title']], $b);
        }

        // 4. Articles — dari Devi Make Up
        $deviUser = User::where('email', 'devimakeup.wo@gmail.com')->first();
        $authorId = $deviUser?->id ?? User::first()?->id;
        $articles = [
            [
                'title' => '5 Tips Wedding Perfect dari Devi Make Up',
                'content' => 'Devi Make Up berbagi tips merencanakan pernikahan yang sempurna. Dari make up hingga dekorasi, kami siap membantu...',
                'image_url' => 'https://images.unsplash.com/photo-1599599947980-0fbac7a77a5f?auto=format&fit=crop&w=800&q=80', // Bride getting makeup
                'is_published' => true,
                'published_at' => now(),
                'author_id' => $authorId,
            ],
            [
                'title' => 'Pilih Tema Wedding Bersama Devi Make Up',
                'content' => 'Rustic, Modern, atau Traditional? Devi Make Up membantu memilih tema yang tepat untuk hari bahagiamu.',
                'image_url' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=800&q=80', // Professional makeup artist
                'is_published' => true,
                'published_at' => now(),
                'author_id' => $authorId,
            ],
        ];

        foreach ($articles as $a) {
            $a['slug'] = Str::slug($a['title']);
            \App\Models\Article::updateOrCreate(['slug' => $a['slug']], $a);
        }

        // 5. Sample customer — user yang ingin memesan di Devi Make Up
        $customer = User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'full_name' => 'Customer Sample',
                'username' => 'customer1',
                'password' => Hash::make('password'),
            ]
        );
        if (! $customer->hasRole('user')) {
            $customer->assignRole('user');
        }
    }

    private function addImageFromUrl($model, string $url, string $collection): void
    {
        try {
            $model->addMediaFromUrl($url)->toMediaCollection($collection);
        } catch (\Throwable $e) {
            try {
                /** @var \Illuminate\Http\Client\Response $resp */
                $resp = Http::timeout(15)->get($url);

                if ($resp->successful()) {
                    $tmp = sys_get_temp_dir().'/'.Str::uuid().'.jpg';
                    file_put_contents($tmp, $resp->body());
                    $model->addMedia($tmp)->toMediaCollection($collection);
                    @unlink($tmp);
                }
            } catch (\Throwable) {
                // Skip jika gagal
            }
        }
    }
}
