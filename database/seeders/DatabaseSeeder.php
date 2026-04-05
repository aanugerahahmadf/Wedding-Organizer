<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SuperAdminSeeder::class,
            WeddingOrganizerSeeder::class, // 1. Profil Studio Utama
            CategorySeeder::class,         // 2. Service Category
            PackageSeeder::class,          // 3. Makeup Package
            BannerSeeder::class,           // 4. Promotional Banner
            ArticleSeeder::class,          // 5. Blog Article
            BankSeeder::class,             // 6. Rekening Bank
            PaymentMethodSeeder::class,    // 7. Cara Pembayaran
            TermsAndConditionsSeeder::class,
            
        ]);
    }
}
