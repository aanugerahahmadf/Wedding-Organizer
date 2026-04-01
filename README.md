# 💍 Weeding Organizer - AI-Powered Wedding Marketplace & Management Platform

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

## 🚀 Visi & Misi

**Weeding Organizer** adalah ekosistem digital pernikahan paling lengkap di Indonesia. Kami menjembatani kebutuhan calon pengantin dengan vendor profesional melalui teknologi **AI (CBIR)** dan aplikasi mobile asli yang responsif. Dari manajemen budget hingga pencarian visual, semua ada di genggaman Anda.

---

## ✨ Fitur All-in-One Untuk User (Calon Pengantin)

Aplikasi mobile berbasis **NativePHP** ini dirancang sebagai asisten pribadi yang cerdas untuk setiap tahap perencanaan pernikahan Anda:

- **🤵 Personal Wedding Planner**: Kelola **Wedding Date** dan detail acara langsung dari profil pengguna secara personal.
- **💰 Smart Budgeting Control**: Pantau dan atur **Budget Pernikahan** Anda agar perencanaan keuangan tetap terjaga.
- **🔍 AI Style Discovery (CBIR)**: Punya foto dekorasi impian? Unggah fotonya, dan AI akan mencarikan vendor dengan gaya visual serupa di marketplace.
- **📍 Location-Based Experience**: Integrasi **Address & GPS** untuk menemukan vendor terbaik di lokasi terdekat Anda.
- **💳 Wallet & Integrated Payment**: Sistem **Top-up Saldo** untuk pembayaran DP atau pelunasan paket pernikahan secara instan dan aman.
- **💬 Direct Real-time Chat**: Konsultasi langsung dengan vendor melalui fitur chat tanpa harus keluar dari aplikasi.
- **🛍️ Mega Marketplace**: Akses ribuan paket (MUA, Venue, Catering, Dekorasi) dengan sistem **Wishlist & Voucher** promo.
- **⭐ Trusted Reviews**: Lihat rating dan testimoni asli dari pengantin lain untuk menjamin kualitas vendor pilihan Anda.

---

## 🛠️ Fitur Khusus Admin & Vendor (Management Panel)

Menggunakan **Filament v3**, memberikan kontrol mutlak atas operasional bisnis dan platform:

- **📊 Management Analytics**: Dashboard intuitif untuk memantau transaksi, order terbaru, dan grafik pendapatan.
- **📦 Vendor Package Manager**: Vendor dapat mengelola etalase paket (foto galeri, harga, & spesifikasi) secara mandiri.
- **🧾 Lifecycle Order Processing**: Pantau status pesanan mulai dari reservasi, pembayaran, hingga hari H acara.
- **🏦 Ledger & Finance Control**: Kelola verifikasi transaksi **Top-up** dan permintaan **Withdrawal** vendor dengan sistem audit yang jelas.
- **👥 Enterprise Role Management**: Pengaturan hak akses berlapis (Super Admin, Verifikator, hingga Pemilik Vendor).
- **📰 CRM & Content Manager**: Publikasikan tips pernikahan melalui artikel dan kelola banner promo untuk meningkatkan konversi.

---

## 🏗️ Elite Tech Stack

- **Core**: [Laravel 11/12](https://laravel.com) (Server-side API & Admin Core)
- **Mobile Environment**: [NativePHP - Android & iOS](https://github.com/nativephp/mobile)
- **Dashboard Interface**: [Filament v3](https://filamentphp.com)
- **AI Service Engine**: Flask / Python with Content-Based Image Retrieval (CBIR) Algorithm
- **Messaging Engine**: Laravel Reverb (Real-time Communications)
- **Testing Standard**: [Pest PHP](https://pestphp.com)

---

## 📦 Instalasi & Persiapan Cepat

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

## 🔑 Akun Default (Super Admin)

- **Email**: `devimakeup.wo@gmail.com`
- **Password**: `@Admin123`

---

## 🧪 Jaminan Kualitas (Test Suite)

Project ini terlindungi oleh Automated Testing untuk menjaga keandalan fitur finansial dan pemrosesan data:
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
