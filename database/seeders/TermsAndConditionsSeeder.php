<?php

namespace Database\Seeders;

use App\Models\TermsOfService;
use App\Models\PrivacyPolicy;
use Illuminate\Database\Seeder;

class TermsAndConditionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Terms of Service
        TermsOfService::updateOrCreate(
            ['id' => 1],
            [
                'title' => 'Terms & Conditions',
                'content' => [
                    ['heading' => 'PENDAHULUAN', 'body' => 'Selamat datang di platform Wedding Organizer Devi Make Up. Sebelum menggunakan Situs ini atau membuat Akun, harap baca Syarat Layanan berikut dengan cermat untuk memahami hak dan kewajiban hukum Anda sehubungan dengan manajemen acara in-house kami.', 'is_italic' => false],
                    ['heading' => 'AKUN DAN KEAMANAN', 'body' => 'Devi Make Up berhak menolak akses ke Situs atau Layanan demi melindungi integritas jadwal layanan kami. Anda bertanggung jawab menjaga kerahasiaan kata sandi dan aktivitas akun. Setiap tindakan dalam akun dianggap sebagai persetujuan Anda.', 'is_italic' => true],
                    ['heading' => 'LAYANAN DAN TRANSAKSI', 'body' => 'Pemesanan paket dianggap permanen setelah validasi Down Payment. Dashboard berfungsi sebagai bukti digital transaksional yang sah. Amandemen rincian paket hanya diizinkan melalui konfirmasi sistem selambat-lambatnya 30 hari sebelum hari acara.', 'is_italic' => false],
                    ['heading' => 'PEMBATALAN & REFUND', 'body' => 'DP bersifat non-refundable karena penjadwalan tim eksklusif. Untuk Force Majeure (Bencana alam/pandemi), opsi penjadwalan ulang akan ditawarkan berdasarkan ketersediaan kalender internal kami dengan menjunjung tinggi asas kekeluargaan.', 'is_italic' => true],
                    ['heading' => 'HAK CIPTA & PORTOFOLIO', 'body' => 'Dokumentasi rias pengantin adalah hak intelektual Devi Make Up dan dapat digunakan sebagai portofolio resmi. Penggunaan aset digital kami secara komersial oleh pihak luar tanpa izin tertulis dilarang keras secara hukum.', 'is_italic' => false],
                ],
            ]
        );

        // 2. Privacy Policy
        PrivacyPolicy::updateOrCreate(
            ['id' => 1],
            [
                'title' => 'Privacy Policy',
                'content' => [
                    ['heading' => 'KOMITMEN PRIVASI', 'body' => 'Devi Make Up menangani tanggung jawab perlindungan data pribadi sesuai dengan UU Pelindungan Data Pribadi (UU PDP) dengan sangat serius. Kami berkomitment penuh untuk melindungi kerahasiaan seluruh data in-house wedding organizer Anda.', 'is_italic' => false],
                    ['heading' => 'PENGUMPULAN DATA', 'body' => 'Kami mengumpulkan data pribadi riil seperti nama lengkap, alamat email, lokasi acara, kontak MUA in-house kami, dan riwayat transaksi. Data otentikasi cepat melalui Google Login hanya digunakan untuk pembuatan identitas digital unik pada portal rias kami.', 'is_italic' => true],
                    ['heading' => 'PENGGUNAAN INFORMASI', 'body' => 'Kami menggunakan informasi Anda semata-mata untuk memproses pesanan rias pengantin, koordinasi internal, notifikasi jadwal, dan audit perlindungan hak hukum. Seluruh data koordinasi vendor in-house tetap berada di bawah pengawasan audit internal kami.', 'is_italic' => false],
                    ['heading' => 'PENARIKAN PERSETUJUAN', 'body' => 'Anda dapat menarik persetujuan pengumpulan data atau meminta penghapusan akun mandiri. Kami akan memproses permintaan Anda dalam waktu yang wajar setelah pemberitahuan diterima, meskipun hal ini dapat berakibat pembatalan layanan aktif yang sedang berjalan.', 'is_italic' => true],
                    ['heading' => 'KEAMANAN SISTEM', 'body' => 'Platform kami menggunakan enkripsi SSL tingkat tinggi untuk seluruh transmisi data. Keamanan sesi login (cookie Antigravity) bersifat temporer guna menjamin perlindungan privasi real-time saat Anda mengakses dashboard Wedding Organizer.', 'is_italic' => false],
                ],
            ]
        );
    }
}
