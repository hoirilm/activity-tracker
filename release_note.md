# Release Notes

Proyek ini menggunakan [Semantic Versioning](https://semver.org/).

---

## [v1.1.0] - 2026-07-22 🚀

Versi ini memperkenalkan pembaruan besar yang mencakup sistem multi-tenancy pengguna, peran administrator, modul pelaporan isu/bug terintegrasi, laporan aktivitas harian otomatis, dan fitur Help Center interaktif.

### ✨ Fitur Baru (New Features)

#### 1. Help Center & FAQ Terintegrasi
- **Menu Bantuan Melayang (Floating Help & Support Menu)**: Menambahkan menu melayang interaktif di sudut kiri bawah (samping kanan sidebar) dengan tombol berikon tanda tanya (`?`) yang berotasi `180°` saat aktif.
- **Halaman Help Center (`/help`)**: Halaman panduan pengguna yang berisi instruksi pelacakan waktu, opsi pelaporan bug, dan informasi kontak admin.
- **Halaman FAQ (`/faq`)**: Halaman khusus tanya-jawab umum terkait penggunaan aplikasi Activity Tracker.
- **Navigasi Instan**: Integrasi dengan `wire:navigate` bawaan Livewire pada rute FAQ & Help Center untuk memastikan transisi halaman super cepat dan mulus tanpa reload penuh.

#### 2. Pelaporan Isu & Manajemen Bug (Issue Tracking)
- **Modul Pelaporan Isu (`⚡report-issue`)**: Form popup modal yang dapat diakses dari menu bantuan melayang maupun navbar untuk mengirim laporan bug atau saran fitur secara real-time.
- **Panel Manajemen Isu Admin (`⚡issue-manager`)**: Antarmuka khusus admin (`/issues`) untuk memantau, mengubah status (open, in_progress, resolved, closed), dan mengelola bug report dari pengguna.

#### 3. Multi-Tenancy & Autentikasi Pengguna
- **Data Terisolasi Per-User**: Mengintegrasikan relasi `user_id` di database pada tabel `activities`, `categories`, dan `projects` sehingga data pelacakan terisolasi secara aman untuk masing-masing akun pengguna.
- **Peran Admin (`is_admin`)**: Mendukung kolom `is_admin` di tabel `users` untuk membedakan pengguna biasa dengan administrator sistem.
- **Registrasi & Login Terbuka**: Menghapus middleware pembatasan email (`CheckWhitelistedEmail`) lama guna mendukung pendaftaran dan autentikasi yang lebih dinamis.

#### 4. Laporan Aktivitas Harian Otomatis (Daily Reports)
- **Email Laporan Harian**: Fitur notifikasi email otomatis (`DailyActivityReport`) yang merangkum durasi kerja dan aktivitas harian pengguna.
- **Penjadwal Tugas (Scheduler)**: Menambahkan perintah scheduler di `routes/console.php` untuk memicu pengiriman email laporan harian secara otomatis.

### 🎨 Peningkatan & Perbaikan (Aesthetics & Improvements)
- **Penyelarasan Warna Tema (Zinc Color)**: Mengganti warna merah pemicu bug lama menjadi warna primer arang (`bg-zinc-800` / `bg-zinc-100`) agar selaras dengan skema warna minimalis bawaan website.
- **Perbaikan Kontras Mode Gelap**: Mengoreksi kelas warna kartu bantuan dari `dark:bg-zinc-850/50` menjadi `dark:bg-zinc-800/50` untuk memastikan keterbacaan teks putih di atas latar belakang abu-abu gelap.

---

## [v1.0.0] - 2026-07-21 🚀

Versi ini merupakan rilis perdana (Initial Release) untuk aplikasi Activity Tracker. Aplikasi ini dirancang untuk memudahkan manajemen waktu dan pencatatan pekerjaan dengan dukungan *parallel tracking* serta analitik yang intuitif.

### ✨ Fitur Utama (Features)
- **Real-Time Activity Tracking**: Catat pekerjaan secara real-time dengan Detail, *Project*, dan *Category*.
- **Parallel Tasks**: Jalankan beberapa aktivitas bersamaan tanpa *double-counting* waktu yang bertindihan pada dasbor.
- **Dashboard & Analytics**: Pengatur waktu langsung (*Live Timer*), grafik aktivitas mingguan, dan visualisasi alokasi waktu per-proyek.
- **Manajemen Riwayat**: Pengelompokan riwayat berdasarkan tanggal, pengeditan log waktu, dan penghapusan data.
- **Ekspor & Impor Data**: Ekspor riwayat aktivitas ke Excel dan impor log aktivitas dari Excel/CSV.
- **Modern UI**: Dukungan mode gelap (*Dark Mode*) bawaan dan shortcut keyboard cepat (`Ctrl + /` dan `Ctrl + Enter`).
