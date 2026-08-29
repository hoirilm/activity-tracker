<div align="center">
  <img src="public/apple-touch-icon.png" width="72" height="72" alt="Klakoan Logo" />
  <h1>⏱️ Klakoan Time Tracker</h1>
  <p><strong>Aplikasi Manajemen Waktu & Pelacakan Produktivitas Modern Berbasis TALL Stack</strong></p>
  <p>Solusi pelacakan waktu presisi tinggi yang dirancang untuk individu dan tim modern dengan tema eksklusif <em>Graphite Monochrome</em>, autentikasi biometrik modern, dan analitik produktivitas interaktif.</p>

  <p>
    <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13" /></a>
    <a href="https://livewire.laravel.com"><img src="https://img.shields.io/badge/Livewire-4.x-FB70A9?style=for-the-badge&logo=livewire&logoColor=white" alt="Livewire 4" /></a>
    <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind_CSS-v4.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS v4" /></a>
    <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3" />
    <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="MIT License" />
  </p>
</div>

---

## 📌 Tentang Proyek

**Klakoan Time Tracker** hadir untuk memecahkan kendala pencatatan jam kerja yang rumit, tidak akurat, dan lambat. Dibangun di atas fondasi **Laravel 13, Livewire 4, Flux UI, dan Tailwind CSS v4**, aplikasi ini memberikan kecepatan interaksi tingkat tinggi tanpa *page reload*, kemampuan pelacakan waktu paralel (*multitasking*), serta visualisasi metrik performa kerja secara *real-time*.

---

## ✨ Fitur Utama

- ⏱️ **Timer Paralel Real-Time**: Jalankan dan lacak beberapa aktivitas kerja sekaligus secara bersamaan tanpa saling tumpang tindih waktu.
- 🎨 **Antarmuka Eksklusif (*Graphite Monochrome*)**: Desain visual premium berbasis *Zinc* dengan komponen **Flux UI (Pro)**, animasi mikro interaktif, dan efek transisi *Scroll Reveal*.
- 🔐 **Autentikasi Masa Depan (Biometrik & 2FA)**:
  - **Passkeys (WebAuthn)**: Masuk akun menggunakan Touch ID, Face ID, atau kunci keamanan perangkat.
  - **Two-Factor Authentication (2FA)** & Google OAuth Login.
- 📊 **Ekspor Laporan & Otomasi Email**:
  - Ekspor log aktivitas ke lembar kerja **Microsoft Excel (.xlsx)** dalam satu klik.
  - Pengiriman email transaksional dan rangkuman harian terintegrasi dengan **Resend API**.
- 👥 **Multi-User & Manajemen Tim**: Isolasi data per pengguna dengan kontrol hak akses berbasis peran (*Admin* & *Member*) serta fitur siaran pengumuman (*Broadcast Notifications*).
- 🐞 **Pusat Bantuan & Issue Tracker**: Widget menu melayang bagi pengguna untuk melaporkan *bug* atau saran fitur yang langsung masuk ke meja kerja Admin.
- 🧭 **Tur Pengenalan Interaktif (Onboarding)**: Panduan langkah-demi-langkah bagi pengguna baru menggunakan **Driver.js**.
- 🌐 **Dukungan Multibahasa**: Antarmuka responsif yang dapat beralih antara Bahasa Indonesia dan Bahasa Inggris secara instan.

---

## 🛠️ Spesifikasi Teknologi (Tech Stack)

| Layer | Teknologi & Pustaka |
| :--- | :--- |
| **Backend Framework** | Laravel 13.x (PHP 8.3+) |
| **Fullstack Reactive** | Livewire 4.x, Livewire Blaze, Alpine.js |
| **Styling & Komponen** | Tailwind CSS v4, Flux UI (Pro), Lucide Icons |
| **Autentikasi & Keamanan** | Laravel Fortify, `@laravel/passkeys` (WebAuthn), Laravel Socialite |
| **Pengolahan Data & Email** | Maatwebsite Excel 3.x, Resend PHP SDK |
| **UI Interaktif & Onboarding** | Driver.js, SortableJS |
| **Basis Data** | PostgreSQL / MySQL / SQLite |
| **Kualitas Kode & Pengujian** | Pest PHP 4.x, Larastan (PHPStan L5), Laravel Pint |
| **Infrastruktur & Rilis** | Docker (Multi-stage build), Nginx, Supervisord |

---

## 🚀 Panduan Instalasi Lokal

### Prasyarat Sistem
- **PHP** >= 8.3 (dengan ekstensi `pdo`, `intl`, `mbstring`, `gd`, `zip`)
- **Composer** >= 2.x
- **Node.js** >= 20.x & **NPM**

### Langkah Demi Langkah

```bash
# 1. Kloning repositori
git clone https://github.com/username/activity-tracker.git
cd activity-tracker

# 2. Pasang dependensi Backend & Frontend
composer install
npm install

# 3. Buat file konfigurasi lingkungan (.env)
cp .env.example .env
php artisan key:generate

# 4. Atur konfigurasi database di .env, lalu jalankan migrasi & seeder
php artisan migrate --seed

# 5. Bangun aset frontend Tailwind CSS v4 (Wajib)
npm run build
# Atau jalankan hot-reloading untuk proses development:
# npm run dev

# 6. Jalankan server lokal
php artisan serve
```

Buka peramban Anda di: **`http://localhost:8000`**

---

## 🔑 Kredensial Akun Default (Seeder)

Setelah menjalankan `php artisan migrate --seed`, Anda dapat langsung masuk menggunakan akun bawaan berikut:

| Peran (*Role*) | Alamat Email | Kata Sandi | Cakupan Hak Akses |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@klakoan.com` | `password` | Akses penuh dashboard, manajemen anggota, broadcast, & issue tracker |
| **Member (User)** | `user@klakoan.com` | `password` | Akses pelacakan aktivitas, timer, laporan pribadi, & passkey |

---

## 📁 Struktur Direktori Utama

```text
activity-tracker/
├── app/
│   ├── Actions/            # Business actions & Fortify handlers
│   ├── Exports/            # Generator ekspor Excel (Maatwebsite)
│   ├── Livewire/           # Komponen UI interaktif Livewire
│   ├── Models/             # Model data Eloquent ORM
│   └── Providers/          # Service & Route Providers
├── config/                 # Konfigurasi aplikasi & service pihak ketiga
├── database/
│   ├── factories/          # Generator data tiruan
│   ├── migrations/         # Skema tabel basis data
│   └── seeders/            # Seeder data pengguna & demo
├── docker/                 # Konfigurasi Nginx & Supervisord
├── public/                 # Berkas statis publik & hasil build
├── resources/
│   ├── css/                # Konfigurasi Tailwind CSS v4
│   ├── js/                 # Skrip frontend & integrasi pustaka
│   └── views/              # Template Blade & komponen Flux UI
├── routes/
│   ├── console.php         # Artisan command routes
│   └── web.php             # Rute HTTP & modul otorisasi
├── tests/                  # Unit & Feature Test (Pest PHP)
├── Dockerfile              # Konfigurasi container produksi multi-stage
└── phpstan.neon            # Konfigurasi analisis statis PHPStan/Larastan
```

---

## 🧪 Pengujian & Kualitas Kode

Proyek ini telah dikonfigurasi dengan standar kualitas kode yang ketat:

```bash
# Menjalankan pengujian fungsional & unit test (Pest)
php artisan test

# Menjalankan analisis statis tipe data (PHPStan / Larastan Level 5)
vendor/bin/phpstan analyse

# Memformat kode sesuai standar PSR-12 / Laravel (Laravel Pint)
vendor/bin/pint
```

---

## ☁️ Deployment (Produksi)

Aplikasi ini sudah dilengkapi konfigurasi kontainer Docker siap pakai (*production-grade*):

- **Dockerfile Multi-Stage**: Mengompilasi dependensi PHP dan *asset* Vite secara terisolasi untuk menghasilkan ukuran image akhir yang minimalis dan cepat.
- **Reverse Proxy & HTTPS**: Siap di-deploy pada platform *Cloud* seperti **Railway**, **Render**, atau **VPS pribadi**.
- Set variabel lingkungan `APP_ENV=production` pada server produksi untuk mengaktifkan pemaksaan skema HTTPS (`TrustProxies`).

---

## 📄 Lisensi

Aplikasi ini bersifat *open-source* dan didistribusikan di bawah [Lisensi MIT](LICENSE).

<div align="center">
  <sub>Dibuat dengan dedikasi untuk produktivitas kerja yang lebih efisien.</sub>
</div>
