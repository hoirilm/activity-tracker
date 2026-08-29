# ⏱️ Klakoan Time Tracker

![License](https://img.shields.io/badge/License-MIT-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20.svg?logo=laravel)
![Livewire](https://img.shields.io/badge/Livewire-4.x-FB70A9.svg?logo=livewire)
![TailwindCSS](https://img.shields.io/badge/Tailwind-v4.0-38B2AC.svg?logo=tailwind-css)

**Klakoan Time Tracker** adalah aplikasi manajemen waktu dan pelacakan produktivitas berdesain premium yang dibangun menggunakan ekosistem TALL stack modern. Aplikasi ini dirancang untuk tim dan individu yang membutuhkan pencatatan waktu yang cepat, pengorganisasian proyek yang rapi, serta pelaporan yang terstruktur.

Pada versi **3.0.0**, aplikasi ini menggunakan tema eksklusif *Graphite Monochrome* (Zinc) yang dilengkapi dengan berbagai animasi mikro interaktif untuk pengalaman pengguna (*UX*) kelas atas, serta infrastruktur *Docker* yang dioptimalkan untuk *deployment* tanpa hambatan di platform produksi.

---

## ✨ Fitur Utama (Features)

* 🎨 **Desain Premium & Interaktif**: Antarmuka bergaya *SaaS* modern menggunakan komponen **Flux UI** dengan tema *Graphite Monochrome*. Dilengkapi dengan animasi *Scroll Reveal* dan responsivitas pada setiap tombol.
* ⏱️ **Timer Real-Time Paralel**: Lacak beberapa aktivitas sekaligus tanpa tumpang tindih waktu. Cukup ketik tugas Anda dan tekan mulai!
* 🔐 **Autentikasi Masa Depan (Passkeys)**: Mendukung *login* tanpa sandi menggunakan biometrik perangkat (Sidik Jari / FaceID) berkat integrasi WebAuthn.
* 📊 **Laporan & Ekspor**: Ekspor data pelacakan waktu Anda ke format Microsoft Excel dalam satu klik, atau terima rangkuman harian secara otomatis melalui Email.
* 👥 **Multi-Tenancy & Manajemen Tim**: Data yang terisolasi per pengguna dengan sistem peran (Admin & Member). Admin dapat mengelola anggota dan mengirimkan pengumuman (*Broadcast*).
* 🐞 **Sistem Pelaporan Isu (Issue Tracker)**: Pengguna dapat langsung melaporkan *bug* atau saran fitur melalui menu *Help Center* melayang, yang akan langsung masuk ke *dashboard* Admin.
* 🌐 **Dukungan Multibahasa**: Antarmuka tersedia dalam Bahasa Indonesia dan Bahasa Inggris yang dapat diganti kapan saja.

---

## 💻 Teknologi (Tech Stack)

* **Backend**: Laravel 13 (PHP 8.3+)
* **Frontend**: Livewire 4, Alpine.js, Tailwind CSS v4
* **UI Components**: Flux UI (Pro)
* **Database**: PostgreSQL / MySQL / SQLite
* **Infrastruktur**: Docker, Nginx

---

## 🚀 Panduan Instalasi Lokal

Ikuti langkah-langkah di bawah ini untuk menjalankan aplikasi ini di komputer lokal Anda:

### 1. Kloning Repositori
```bash
git clone https://github.com/username/activity-tracker.git
cd activity-tracker
```

### 2. Instalasi Dependensi
```bash
composer install
npm install
```

### 3. Konfigurasi Lingkungan (.env)
Salin *file* konfigurasi bawaan dan hasilkan kunci aplikasi:
```bash
cp .env.example .env
php artisan key:generate
```
*(Pastikan Anda mengatur kredensial database Anda di file `.env`)*

### 4. Migrasi Basis Data & Seeder
Jalankan migrasi tabel beserta *seeder* untuk membuat akun admin *default*:
```bash
php artisan migrate --seed
```
*Akun Admin Bawaan:*
- **Email:** `admin@klakoan.com`
- **Password:** `password`

### 5. Bangun Aset Frontend (Penting!)
Karena aplikasi ini menggunakan Tailwind v4, Anda **wajib** mem-*build* aset CSS agar desain tampil dengan sempurna:
```bash
npm run build
# Atau gunakan `npm run dev` untuk hot-reloading selama pengembangan
```

### 6. Jalankan Server
```bash
php artisan serve
```
Kunjungi `http://localhost:8000` di *browser* Anda.

---

## ☁️ Deployment (Production)

Aplikasi ini sudah dilengkapi dengan `Dockerfile` dan `docker-entrypoint.sh` yang dikonfigurasi khusus untuk *deployment* di layanan seperti **Railway** atau Render.

**Catatan Penting untuk Production:**
- Pastikan menetapkan `APP_ENV=production` di *environment variables* server Anda. Konfigurasi ini akan memicu aplikasi untuk memaksa penggunaan `https://` (*TrustProxies*).

---

## 📜 Lisensi
Aplikasi ini bersifat *open-source* dan tersedia di bawah [Lisensi MIT](LICENSE).

---
*Dibuat dengan ❤️ untuk produktivitas yang lebih baik.*
