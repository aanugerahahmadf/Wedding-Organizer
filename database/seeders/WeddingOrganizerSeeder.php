<?php

namespace Database\Seeders;

use App\Models\WeddingOrganizer;
use Illuminate\Database\Seeder;

class WeddingOrganizerSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = WeddingOrganizer::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Devi Make Up Wedding Organizer',
                'description' => 'Selamat datang di Devi Make Up. Kami adalah penyedia jasa perias pengantin profesional dengan pengalaman lebih dari 10 tahun. Kami hanya menyajikan kualitas terbaik untuk hari bahagia Anda.',
                'address' => 'Jakarta, Indonesia',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'is_verified' => true,
            ]
        );

        // Tambahkan foto sampul (Cover) jika belum ada dengan Try-Catch
        if ($organizer->getMedia('gallery')->isEmpty()) {
            try {
                $organizer->addMediaFromUrl('https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200&auto=format&fit=crop')
                    ->toMediaCollection('gallery');
            } catch (\Exception $e) {
                // Lewati jika koneksi lambat
            }
        }

        // Tambahkan Logo jika belum ada dengan Try-Catch
        if ($organizer->getMedia('logo')->isEmpty()) {
            try {
                $organizer->addMediaFromUrl('https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?q=80&w=300&auto=format&fit=crop')
                    ->toMediaCollection('logo');
            } catch (\Exception $e) {
                // Lewati jika koneksi lambat
            }
        }
    }
}
