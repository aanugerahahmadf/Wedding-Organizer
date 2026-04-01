# 💍 Admin Panel Mobile - Wedding Organizer (CBIR)

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/artisan/master/docs/ls-logo.7.png" width="200" alt="Laravel Logo">
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11/12-FF2D20?style=for-the-badge&logo=laravel" alt="Laravel"></a>
  <a href="https://nativephp.com"><img src="https://img.shields.io/badge/NativePHP-Mobile-4F46E5?style=for-the-badge&logo=php" alt="NativePHP"></a>
  <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-3.x-FDBE11?style=for-the-badge&logo=filament" alt="Filament"></a>
  <a href="https://pestphp.com"><img src="https://img.shields.io/badge/Pest-Test-01BDC7?style=for-the-badge&logo=pest" alt="Pest"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php" alt="PHP 8.4"></a>
</p>

---

## 🚀 Tentang Proyek
**Admin Panel Mobile** adalah solusi manajemen *Wedding Organizer* yang revolusioner. Dibangun menggunakan ekosistem **Laravel** terbaru dan **NativePHP**, platform ini memungkinkan pengelolaan operasional pernikahan langsung dari smartphone dengan performa aplikasi asli (Android/iOS).

Fitur unggulan proyek ini adalah integrasi **CBIR (Content-Based Image Retrieval)**, yang memungkinkan pencarian aset pernikahan (gaun, dekorasi, katering) berdasarkan kemiripan visual menggunakan teknologi AI.

## ✨ Fitur Utama
-   **Admin Dashboard (Filament)**: Antarmuka administrasi yang elegan, cepat, dan responsif.
-   **Native Mobile Experience**: Dijalankan sebagai aplikasi asli menggunakan NativePHP (Cross-platform).
-   **CBIR Integration**: Mencari referensi vendor dan dekorasi melalui unggahan foto.
-   **Role-based Access Control**: Keamanan tingkat tinggi menggunakan Spatie Permission (`super_admin`, `admin`, `vendor`).
-   **Real-time Synchronization**: Mendukung Reverb untuk notifikasi dan update data real-time.
-   **Scalable Architecture**: Siap dideploy ke cloud atau dijalankan secara lokal dengan manajemen koneksi database yang lincah.

## 🛠️ Tech Stack
-   **Backend**: [Laravel 11/12](https://laravel.com) (PHP 8.4+)
-   **Admin Panel**: [Filament v3](https://filamentphp.com)
-   **Mobile Runtime**: [NativePHP - Android & iOS](https://github.com/nativephp/mobile)
-   **Testing Framework**: [Pest PHP](https://pestphp.com)
-   **AI Core**: Flask-based CBIR Service Engine (Python)

## 📦 Instalasi & Persiapan

### 1. Prasyarat (Prerequisites)
Pastikan sistem kamu sudah terinstal:
-   PHP 8.4 (Wajib untuk fitur terbaru)
-   Composer 2.x
-   MySQL / MariaDB
-   Node.js & NPM (untuk build assets)

### 2. Langkah Instalasi (Local Backend)
```bash
# Clone repository
git clone https://github.com/aanugerahahmadf/Admin-Panel-Mobile.git
cd Admin-Panel-Mobile

# Install dependensi PHP
composer install

# Install & Build assets
npm install
npm run build

# Copy & Setup Environment
cp .env.example .env
php artisan key:generate

# Migrasi Database & Seeding (PENTING untuk Role Admin)
php artisan migrate --seed
```

### 3. Setup Mobile (NativePHP)
Untuk menjalankan aplikasi di emulator atau perangkat asli:
```bash
# Instalasi plugin mobile
php artisan native:install

# Menjalankan di mode Mobile Debug
php artisan native:serve
```

## 🧪 Pengujian (Testing)
Proyek ini mengutamakan stabilitas kode dengan cakupan pengujian melalui **Pest PHP**.

```bash
# Menjalankan semua test suite (Unit & Feature)
php artisan test
```
*Environment CI/CD di GitHub Actions telah terkonfigurasi otomatis untuk PHP 8.4 dan SQLite.*

## 🔒 Keamanan & Kontrol Akses
Aplikasi ini menggunakan sistem *Seeder* untuk inisialisasi awal. Secara default, test suite akan menggunakan database SQLite untuk isolasi data.
-   **Role**: `super_admin`, `admin`, `vendor`
-   **Permissions**: Dikelola secara dinamis melalui UI Filament.

## 🌉 Integrasi CBIR
Pastikan server AI Core (Flask) berjalan di port 5000. `NativeServiceProvider` proyek ini secara otomatis menangani routing IP emulator (`10.0.2.2`) agar tetap bisa berkomunikasi dengan backend AI di komputer host.

## 🤝 Kontribusi (Contribution)
Jika ingin berkontribusi, silakan buat *Pull Request* atau laporkan *Issue* pada tab yang tersedia. Pastikan kode mengikuti standar PSR-12 dan lulus semua pengujian `php artisan test`.

---

<p align="center">
  Dukungan Penuh Untuk Pengembangan <b>Admin Panel Mobile - Wedding Organizer</b>
</p>
<p align="center">
  Dibuat dengan ❤️ oleh <b>Ahmad Anugerah</b>
</p>
