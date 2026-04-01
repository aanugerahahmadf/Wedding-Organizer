# 💍 Weeding Organizer - AI-Powered Wedding Management Platform

<p align="center">
  <img src="public/favicon.ico" width="200" alt="Weeding Organizer Logo">
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11/12-FF2D20?style=for-the-badge&logo=laravel" alt="Laravel"></a>
  <a href="https://nativephp.com"><img src="https://img.shields.io/badge/NativePHP-Mobile-4F46E5?style=for-the-badge&logo=php" alt="NativePHP"></a>
  <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-3.x-FDBE11?style=for-the-badge&logo=filament" alt="Filament"></a>
  <a href="https://pestphp.com"><img src="https://img.shields.io/badge/Pest-Test-01BDC7?style=for-the-badge&logo=pest" alt="Pest"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php" alt="PHP 8.4"></a>
</p>

---

## 🚀 Visi & Misi

**Weeding Organizer** adalah platform digital terintegrasi yang dirancang khusus untuk mempermudah calon pengantin merencanakan hari bahagia mereka. Dengan dukungan teknologi **AI (CBIR)** untuk pencarian gaya visual dan aplikasi mobile asli yang responsif, kami menghadirkan pengalaman Wedding Planning yang modern, aman, dan efisien.

---

## ✨ Fitur Lengkap Untuk User (Calon Pengantin)

Aplikasi mobile berbasis **NativePHP** ini hadir sebagai asisten pribadi yang cerdas untuk memandu setiap tahap perencanaan pernikahan:

- **🤵 Personal Wedding Planner**: Kelola **Wedding Date** dan detail acara pernikahan Anda secara personal.
- **💰 Smart Budgeting Control**: Atur dan pantau **Budget Pernikahan** agar tetap sesuai dengan perencanaan keuangan.
- **🔍 AI Style Discovery (CBIR)**: Temukan gaya dekorasi, makeup, atau venue impian hanya dengan mengunggah foto referensi melalui teknologi AI.
- **📍 Location-Based Service**: Temukan detail lokasi acara dan integrasi alamat yang memudahkan koordinasi lapangan.
- **💳 Integrated Wallet & Payments**: Sistem **Top-up Saldo** untuk kemudahan pembayaran DP atau pelunasan layanan secara instan dan aman.
- **💬 Direct Real-time Chat**: Konsultasi langsung dengan tim kami melalui fitur pesan instan di dalam aplikasi.
- **🛍️ Katalog Layanan Lengkap**: Pilih berbagai paket (Makeup, Venue, Catering, Dekorasi) dengan sistem **Wishlist & Voucher** promo eksklusif.
- **⭐ Trusted Reviews**: Lihat testimoni dan berikan feedback untuk menjamin kualitas layanan kami.

---

## 🛠️ Fitur Admin (Dashboard Management)

Menggunakan **Filament v3**, memberikan kontrol mutlak bagi tim internal untuk mengelola operasional:

- **📊 Business Analytics**: Pantau total pesanan, grafik pendapatan terbaru, dan statistik performa bulanan secara intuitif.
- **📦 Service Package Manager**: Kelola seluruh paket layanan (galeri foto, spesifikasi, dan harga) dengan mudah.
- **🧾 Lifecycle Order Processing**: Kelola seluruh tahap pesanan mulai dari booking awal hingga hari pelaksanaan acara.
- **🏦 Ledger & Finance Control**: Verifikasi transaksi **Top-up** saldo pengguna dan kelola laporan keuangan secara internal.
- **👥 Access Control**: Pengaturan hak akses tim khusus untuk manajemen data dan operasional aplikasi.
- **📰 CRM & Content Manager**: Publikasikan tips pernikahan melalui artikel dan kelola banner promo untuk memanjakan pengguna.

---

## 🏗️ Elite Tech Stack

- **Framework**: [Laravel 11/12](https://laravel.com)
- **Mobile Runtime**: [NativePHP - Android & iOS](https://github.com/nativephp/mobile)
- **Dashboard Interface**: [Filament v3](https://filamentphp.com)
- **AI Core Engine**: Flask / Python with Content-Based Image Retrieval (CBIR) Algorithm
- **Messaging Engine**: Laravel Reverb (Real-time Communications)
- **Testing Standard**: [Pest PHP](https://pestphp.com)

---

## 📦 Instalasi & Setup Cepat

```bash
# Clone & Install
git clone https://github.com/aanugerahahmadf/Admin-Panel-Mobile.git
cd Admin-Panel-Mobile
composer install && npm install && npm run build

# Setup
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
```

### Jalankan Mode Mobile (NativePHP)
```bash
php artisan native:install
php artisan native:serve
```

---

## 🔑 Akun Akses Default

Gunakan kredensial berikut untuk masuk ke dashboard admin:
- **Email**: `devimakeup.wo@gmail.com`
- **Password**: `@Admin123`

---

## 🧪 Automated Testing

Menjamin keandalan fitur finansial dan pemrosesan data secara otomatis:
```bash
php artisan test
```

---

<p align="center">
  <b>Weeding Organizer</b> - Mewujudkan Pernikahan Impian Anda Menjadi Nyata.
</p>

<p align="center">
  Dibuat dengan ❤️ oleh <b>Ahmad Anugerah</b>
</p>
