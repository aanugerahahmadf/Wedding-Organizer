<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Akad & Resepsi Tradisional',
                'description' => 'Gaya riasan adat yang pakem namun tetap elegan sesuai tradisi.',
                'color' => '#be123c', // Red-700
                'icon' => 'heroicon-o-sparkles',
            ],
            [
                'name' => 'Modern & International Look',
                'description' => 'Riasan modern minimalis untuk gaya pernikahan internasional yang chic.',
                'color' => '#1d4ed8', // Blue-700
                'icon' => 'heroicon-o-face-smile',
            ],
            [
                'name' => 'Make Up Wisuda & Pesta',
                'description' => 'Layanan make up glamor untuk acara wisuda, lamaran, atau pesta formal.',
                'color' => '#047857', // Emerald-700
                'icon' => 'heroicon-o-academic-cap',
            ],
            [
                'name' => 'Sesi Pre-Wedding',
                'description' => 'Riasan khusus sesi pemotretan pre-wedding outdoor maupun indoor.',
                'color' => '#7e22ce', // Purple-700
                'icon' => 'heroicon-o-camera',
            ],
            [
                'name' => 'Paket Dekorasi & Katering',
                'description' => 'Layanan tambahan dekorasi pelaminan dan katering pernikahan.',
                'color' => '#a16207', // Gold-700
                'icon' => 'heroicon-o-home-modern',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'color' => $category['color'],
                    'icon' => $category['icon'],
                ]
            );
        }
    }
}
