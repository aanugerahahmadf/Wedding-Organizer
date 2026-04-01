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

## 🚀 Visi Proyek

**Weeding Organizer (Wedding Organizer)** adalah platform *all-in-one* yang mendigitalisasi seluruh ekosistem pernikahan. Menggabungkan kemudahan aplikasi mobile untuk calon pengantin dan dashboard manajemen yang kuat untuk vendor. 

Dengan integrasi **AI Content-Based Image Retrieval (CBIR)**, pengguna dapat menemukan vendor gaun, dekorasi, hingga venue hanya dengan mengunggah gambar referensi yang mereka inginkan.

---

## ✨ Fitur Untuk Pengguna (Client Mobile App)

Dijalankan sebagai aplikasi asli (Android/iOS) menggunakan **NativePHP**, memberikan pengalaman belanja yang mulus:

- **📱 Mobile UI First**: Desain modern khusus untuk smartphone dengan performa aplikasi asli.
- **🔍 AI CBIR Search**: Cari paket pernikahan atau dekorasi berdasarkan foto referensi/mirip.
- **🛍️ Marketplace Paket**: Jelajahi ribuan paket wedding dari berbagai kategori (Venue, Catering, MUA, dll).
- **💳 Digital Wallet & Payments**: Top-up saldo, bayar pesanan, dan tarik dana melalui sistem terintegrasi.
- **💬 Direct Chat & Inbox**: Berkomunikasi langsung dengan vendor pilihan melalui sistem pesan real-time.
- **🎟️ Vouchers & Wishlist**: Simpan paket impian dan gunakan voucher promo untuk diskon eksklusif.
- **🤵 Wedding Management**: Kelola tanggal pernikahan, detail acara, dan koordinasi terpusat.

---

## 🛠️ Fitur Untuk Admin & Vendor (Dashboard Panel)

Menggunakan **Filament v3**, memberikan kontrol penuh atas operasional bisnis:

- **📊 Bisnis Analytics**: Pantau total transaksi, order terbaru, dan statistik pendapatan bulanan.
- **📦 Manajemen Paket**: Vendor dapat mengunggah paket pernikahan dengan galeri foto dan spesifikasi lengkap.
- **🧾 Order Processing**: Kelola siklus hidup pesanan mulai dari reservasi hingga penyelesaian acara.
- **👥 User & Role Management**: Pengaturan hak akses (Super Admin, Admin Verifikator, Owner Vendor).
- **🏦 Financial Management**: Verifikasi top-up dan permintaan tarik dana (Withdrawal) pengguna secara aman.
- **📰 Content & Banner**: Kelola artikel tips pernikahan dan banner promo di halaman utama aplikasi mobile.

---

## 🏗️ Elite Tech Stack

- **Framework**: [Laravel 11/12](https://laravel.com) (Server-side & API)
- **Mobile Runtime**: [NativePHP - Android & iOS](https://github.com/nativephp/mobile)
- **Admin Interface**: [Filament v3](https://filamentphp.com) (Dashboard Management)
- **AI Core Engine**: Flask / Python with CBIR Algorithm (Search by Image)
- **Real-time Engine**: Laravel Reverb (Messaging & Notifications)
- **Testing SDK**: [Pest PHP](https://pestphp.com) (Unit & Feature Testing)

---

## 📦 Instalasi & Setup Cepat

### 1. Persiapan Lingkungan
Pastikan kamu menggunakan **PHP 8.4**, Composer 2, dan Node.js terbaru.

```bash
# Clone & Install
git clone https://github.com/aanugerahahmadf/Admin-Panel-Mobile.git
cd Admin-Panel-Mobile
composer install
npm install && npm run build

# Environment Setup
cp .env.example .env
php artisan key:generate

# Database & Sample Data
php artisan migrate --seed
```

### 2. Jalankan Mode Mobile (NativePHP)
Pastikan emulator Android atau simulator iOS sudah menyala:
```bash
php artisan native:install
php artisan native:serve
```

---

## 🔑 Akses Default (Development)

Gunakan kredensial berikut untuk masuk ke dashboard admin setelah migrasi:
- **Email**: `devimakeup.wo@gmail.com`
- **Password**: `@Admin123`
- **Level**: Super Admin

---

## 🧪 Jaminan Kualitas (Test Suite)

Kami menjaga integritas data (terutama transaksi finansial) dengan pengujian ketat:
```bash
# Menjalankan Feature & Unit Tests (Pest)
php artisan test
```

---

## 🌉 Arsitektur AI CBIR
Aplikasi memproksi permintaan gambar ke server AI lokal. `NativeServiceProvider` akan secara otomatis mensinkronisasi URL IP host agar aplikasi mobile di emulator tetap bisa menjangkau server AI Core di komputer host melalui IP `10.0.2.2:5000`.

---

<p align="center">
  <b>Weeding Organizer</b> - Solusi Digital Terbaik untuk Momen Terindah Anda.
</p>

<p align="center">
  Dibuat dengan ❤️ oleh <b>Ahmad Anugerah</b>
</p>
