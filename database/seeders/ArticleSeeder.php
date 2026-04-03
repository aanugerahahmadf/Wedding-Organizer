<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        // MASTER CLEAR: Sapu jagat semua media artikel sebelum seeding mulai!
        \App\Models\Article::all()->each(function ($article) {
            $article->clearMediaCollection('images');
            $article->clearMediaCollection('videos');
        });

        $admin = User::first(); // Assuming super_admin exists from previous seeders

        $articles = [
            [
                'title' => 'Cara Mempersiapkan Kulit Sebelum Hari-H Pernikahan',
                'excerpt' => 'Tips perawatan kulit esensial untuk calon pengantin agar tampil glowing.',
                'content' => '<p>Agar riasan pengantin dapat menempel dengan sempurna dan memberikan hasil yang maksimal, persiapan kulit adalah kuncinya. Pastikan Anda rajin menghidrasi kulit minimal 2 minggu sebelum hari H.</p><ul><li>Minum air putih 2 liter per hari.</li><li>Hindari mencoba produk perawatan baru yang berisiko iritasi.</li><li>Tidur teratur 8 jam semalam.</li></ul>',
                'category' => 'Tips Kecantikan',
                'image_url' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?q=80&w=1000&auto=format&fit=crop',
                'video_url' => 'https://media.w3.org/2010/05/sintel/trailer.mp4',
            ],
            [
                'title' => 'Inspirasi Riasan Pengantin Jawa Modern 2026',
                'excerpt' => 'Tren make up adat Jawa dengan sentuhan modern yang elegan.',
                'content' => '<p>Gaya riasan pengantin Jawa terus berkembang. Tahun 2026 ini, tren "Clean Jawa Look" sedang banyak diminati, memadukan paes tradisional dengan teknik riasan mata yang lembut dan bibir bernuansa nude rose.</p><p>Look ini lebih menekankan pada <em>flawless complexion</em> sehingga aura sang pengantin wanita semakin memancar tajam dan anggun.</p>',
                'category' => 'Tren Pernikahan',
                'image_url' => 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1000&auto=format&fit=crop',
                'video_url' => null,
            ],
            [
                'title' => 'Mengapa Harus Memilih Devi Make Up sebagai WO Anda?',
                'excerpt' => 'Mengenal lebih jauh kualitas dan dedikasi tim Devi Make Up.',
                'content' => '<p>Devi Make Up bukan sekadar perias, melainkan mitra dalam mewujudkan pernikahan impian Anda. Dengan pengalaman lebih dari 10 tahun, kami memahami detail kerumitan acara Anda.</p><p>Tim kami telah menangani berbagai gaya mulai dari adat tradisional Nusantara hingga gaya <em>International Look</em>.</p>',
                'category' => 'Profil Vendor',
                'image_url' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1000&auto=format&fit=crop',
                'video_url' => 'https://media.w3.org/2010/05/sintel/trailer.mp4',
            ],
            [
                'title' => 'Checklist Persiapan Pernikahan 6 Bulan Sebelum Acara',
                'excerpt' => 'Langkah-langkah strategis agar persiapan pernikahan berjalan lancar.',
                'content' => '<p>Mulai dari menentukan budget hingga memilih vendor utama, checklist ini akan membantu Anda mengorganisir jadwal tanpa merasa terbebani di menit-menit terakhir.</p><ol><li>Bulan 1: Budget dan konsep</li><li>Bulan 2: Tentukan venue</li><li>Bulan 3: MUA dan Fotografer</li></ol><p>Patuhi checklist ini ya!</p>',
                'category' => 'Persiapan Pernikahan',
                'image_url' => 'https://images.unsplash.com/photo-1510076857177-7470076d4098?q=80&w=1000&auto=format&fit=crop',
                'video_url' => null,
            ],
        ];

        foreach ($articles as $article) {
            // Retrieve or create the category based on the array's category string
            $category = \App\Models\Category::firstOrCreate(
                ['slug' => Str::slug($article['category'])],
                ['name' => $article['category']]
            );

            $articleModel = Article::updateOrCreate(
                ['slug' => Str::slug($article['title'])],
                [
                    'title' => $article['title'],
                    'excerpt' => $article['excerpt'],
                    'content' => $article['content'],
                    'category_id' => $category->id,
                    'author_id' => $admin?->id ?? 1,
                    'wedding_organizer_id' => 1,
                    'image_url' => $article['image_url'],
                    'video_url' => $article['video_url'],
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );

            // POWER FLUSH: Bersihkan images & videos biar gak ada logo nyangkut!
            $articleModel->clearMediaCollection('images');
            $articleModel->clearMediaCollection('videos');

            // Seed Images
            if ($articleModel->getMedia('images')->isEmpty()) {
                try {
                    $articleModel->addMediaFromUrl($article['image_url'])
                        ->toMediaCollection('images');
                } catch (\Exception $e) {}
            }

            // Seed Videos (HANYA INI YANG BIKIN PREVIEW MUNCUL!)
            if ($articleModel->video_url && $articleModel->getMedia('videos')->isEmpty()) {
                try {
                    // Pakai addMediaFromUrl untuk download video asli
                    $articleModel->addMediaFromUrl($articleModel->video_url)
                        ->toMediaCollection('videos');
                } catch (\Exception $e) {
                    // Jika gagal download, tetap simpan video_url sebagai backup
                }
            }
        }
    }
}
