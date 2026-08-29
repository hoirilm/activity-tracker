# Release Notes

Proyek ini menggunakan [Semantic Versioning](https://semver.org/).

---

## [v5.5.0] - 2026-08-14 🚀

Versi **v5.5.0** menghadirkan pembaruan signifikan yang mencakup **Sistem Pause & Resume Activity Tracking** waktu nyata (*real-time*), **Presisi Detik (H:i:s)** pada seluruh log aktivitas, **Antarmuka Minimalis Layout Jam**, **Modal Repository Terdedikasi untuk Archived Tasks**, serta **Penyelarasan Ekspor Excel & Backup JSON System (termasuk dukungan Subtask/Checklist)**.

### ⏸️ Sistem Pause & Resume Activity Tracking (Real-Time Timer)
- **Kontrol Pause & Resume**: Tombol **Pause** (amber ⏸️) dan **Resume** (emerald ▶️) pada sesi pelacakan waktu aktif di menu Tracking dan Dashboard Command Center.
- **Visual State Interaktif**: 
  - Sesi **RUNNING**: Tema card emerald glowing dengan pulsa hijau (`LIVE` 🟢).
  - Sesi **PAUSED**: Tema card amber glassmorphic dengan pulsa kuning (`PAUSED` 🟡).
- **Akumulasi Presisi Rest Time**: Menghitung dan menyimpan `paused_seconds` serta `paused_at`, secara otomatis mengurangkan durasi pause dari total waktu kerja bersih (*net working duration*).
- **Alpine.js Ticker Management**: Timer detik membeku (*freeze*) saat di-pause dan melanjutkan hitungan secara mulus saat di-resume tanpa *memory leak*.

### 🕒 Presisi Waktu & Penataan Layout Minimalis
- **Format Detik General (`H:i:s`)**: Tampilan jam mulai (*start time*) dan selesai (*end time*) selalu menggunakan presisi hingga detik (contoh: `13:25:15` – `13:25:33`).
- **Penyisipan Durasi Pause**: Durasi pause disisipkan langsung di antara waktu mulai dan selesai (contoh: `13:25:15 ⏸️ 6s 13:25:33`).
- **Posisi Layout Kanan**: Indikator jam dipindahkan ke sisi kanan, bertumpuk di bawah total durasi aktivitas.
- **Desain Monokrom Minimalis**: Teks menggunakan warna abu-abu netral polos (`text-zinc-500 dark:text-zinc-400 font-mono text-[10px]`) tanpa kotak background untuk antarmuka yang bersih dan tidak mencolok.

### 📦 Modal Terdedikasi Archived Tasks & Pencarian Khusus
- **Dedicated Archive Repository Modal**: Penampung task ter-archive dipindahkan dari bagian bawah halaman ke modal khusus (`archived-tasks-modal`).
- **4 Action Buttons Restore**: Tombol restore langsung ke status target (`On Hold 🟠`, `New 🔵`, `Progress 🟡`, `Done 🟢`).
- **Pencarian Khusus Data Archive**: Input pencarian independen (`$archiveSearchQuery`) di dalam modal yang otomatis di-reset saat modal ditutup.

### 📊 Alignment Export Excel & Backup JSON System (Subtask Support)
- **Ekspor Excel (`.xlsx`)**: Menambahkan kolom `Pause Duration` & `Net Duration` serta memformat kolom waktu dengan detik (`YYYY-MM-DD HH:MM:SS`).
- **Import Excel (`.xlsx` / `.csv`)**: Mendukung impor data dengan kolom `paused_seconds`.
- **Backup & Restore System (JSON)**: Memperbarui fitur backup & restore JSON pada menu *Settings* untuk mengikutsertakan data `paused_seconds` serta relasi **Subtasks / Checklists** pada task secara utuh.

### 🐛 Visual & Bug Fixes
- **Perbaikan Checklist Drag & Drop**: Mengatasi bug duplikasi dan hilangnya checklist saat reorder dengan mengisolasi node SortableJS menggunakan `wire:key` dinamis berbasis jumlah item.
- **Redesain Tombol Aksi Modal**: Pembaruan gaya visual tombol modal Task (*Create*, *Edit*, *Detail*) dengan gradien warna indigo modern dan animasi mikro.
- **Keandalan Testing**: Menambahkan `tests/Feature/ActivityTest.php` dan memperbarui `BackupTest.php`. Seluruh 69 unit test di aplikasi lulus 100% (198 assertions).

---

## [v5.0.0] - 2026-08-03 🚀

Versi **v5.0.0** merupakan rilis mayor yang menghadirkan **Task Stream Command Center** yang interaktif & profesional di Halaman Dashboard, fitur pembuatan tugas kilat (*Quick Add*), meteran kemajuan visual (*Momentum Progress Meter*), pencarian *case-insensitive*, penataan ulang tata letak Kanban di halaman Manage Workspace, serta penambahan *test suite* komprehensif.

### 🎯 Widget Task Dashboard Interaktif & Profesional (Dashboard Command Center)
- **Fokus On-Progress Stream**: Menampilkan aliran tugas aktif yang sedang dikerjakan pengguna secara real-time.
- **Pencarian Cepat Case-Insensitive**: Memungkinkan pencarian judul tugas maupun nama proyek tanpa terpengaruh huruf besar/kecil (`qris`, `QRIS`, `Qris`).
- **Pencipta Tugas Kilat (Quick Add Task)**: Pembuatan tugas baru secara instan langsung dari dashboard lengkap dengan pilihan proyek tanpa harus berpindah ke halaman Manage Tasks.
- **Tampilan Selebrasi Kosong (Celebratory Empty State)**: Desain visual khusus berbahasa Inggris yang menampilkan ucapan selamat (*"All On-Progress Tasks Completed! 🎉"*) dan badge animasi bercahaya ketika seluruh tugas *on progress* telah diselesaikan.
- **Pemisahan State Empty Search**: Menampilkan informasi pencarian kontekstual (*"No tasks match 'xxx'"*) lengkap dengan tombol reset filter saat pencarian kata kunci tidak ditemukan.
- **Pembalikan Status Tugas Selesai (Revert Done Task)**: Tombol penyelesaian (`✓`) pada tugas yang sudah selesai (*Done Today*) akan beralih menjadi tombol pengembalian (*revert to on-progress*) dengan icon animasi *reload* saat di-hover.
- **Perhitungan Indikator Progres Presisi**: Meteran kemajuan (`X/Y done (%)`) secara presisi hanya mengukur item aliran *on-progress stream* (`on_progress` + `done_today`), secara otomatis mengabaikan status *new*, *on_hold*, dan *archived*.

### 📋 Penataan Ulang Layot Kanban Workspace (Manage Page)
- **Penataan Kolom Kanban Baru**: Menyesuaikan urutan kolom Kanban dan opsi filter status pada halaman Manage Workspace menjadi:
  1. `On Hold 🟠`
  2. `New 🔵`
  3. `On Progress 🟡`
  4. `Done 🟢`

### 🧪 Keandalan & Pengujian Otomatis (*Testing & Quality*)
- **Perluasan Pest Test Suite (55 Passing Tests)**: Penambahan pengujian unit & fitur lengkap di `DashboardTaskWidgetInteractiveTest.php` untuk memverifikasi penciptaan task kilat, pencarian *case-insensitive*, pengembalian status *done*, dan akurasi rumus progres. Seluruh 55 pengujian lulus 100% (137 *assertions*).
- **Produksi Assets Build**: Kompilasi aset Vite produksi terbaru dengan sukses.

---

## [v4.0.0] - 2026-07-30 🚀

Versi **v4.0.0** merupakan pembaruan mayor yang memperkenalkan penamaan ulang resmi aplikasi menjadi **Klakoan**, pusat manajemen **Backup & Restore Data** tingkat lanjut di menu Settings, refactoring profesional basis kode (PHPStan Level 7), penyempurnaan UI/UX responsive, serta penyeragaman visual widget analitik.

### 🏷️ Rebranding Aplikasi
- **Identitas Baru "Klakoan"**: Memperbarui nama aplikasi secara global dari "Klakoan Activity Tracker" / "Activity Tracker" menjadi **Klakoan** pada konfigurasi sistem (`APP_NAME`), brand header, logo sidebar, footer, dan dokumen pendukung.

### 📦 Pusat Cadangan & Pemulihan Data (Backup & Restore Manager)
- **Menu Pengaturan Khusus (`/settings/backup`)**: Menambahkan tab **Backup & Restore** pada navigasi Settings.
- **Ekspor Cadangan Restore-Friendly**: Fitur ekspor seluruh riwayat aktivitas pengguna hingga aktivitas paling baru ke dalam format JSON berstruktur bersih yang tidak bergantung pada ID mentah database.
- **Validasi Keamanan Email**: Deteksi otomatis kecocokan email akun pengguna pada berkas backup. Memulihkan berkas dari akun lain mewajibkan konfirmasi eksplisit pengguna untuk keamanan data.
- **Dua Metode Pemulihan Data**:
  - **Gabungkan (Merge - Recommended)**: Mengimpor aktivitas baru tanpa menghapus data yang sudah ada (pencegahan otomatis aktivitas duplikat).
  - **Timpa (Replace)**: Reset riwayat lama dan menggantikannya dengan data dari berkas backup.
- **Pratinjau Berkas & Respon 0ms**: Kartu pratinjau statistik (total proyek, kategori, aktivitas) dengan perpindahan mode Alpine yang sangat cepat dan bebas lag jaringan.

### 🛠️ Refactoring & Keandalan Kode (*Code Quality & Testing*)
- **Peningkatan PHPStan ke Level 7**: Membersihkan seluruh tipe data dan docblock, memastikan 0 error pada PHPStan Level 7.
- **Cakupan Pengujian Pest (100% Passing)**: Seluruh 38 pengujian unit dan fitur lulus dengan total 89 *assertions*.
- **Pembaruan Data Dummy Seeder**: Seeder basis data baru yang menghasilkan 45 riwayat aktivitas realistis per pengguna, tugas berjalan (*running activities*), notifikasi, dan akun demo (`admin@klakoan.com`, `user@klakoan.com`, `designer@klakoan.com`).

### 🎨 Penyempurnaan UI/UX & Analitik Visual
- **Modal Ekspor Layar Penuh (`<flux:modal>`)**: Mengubah modal ekspor aktivitas menjadi teleported modal layar penuh dengan latar belakang *backdrop-blur* dan tema Zinc yang menyatu.
- **Tombol Back to Top Responsif**: Penataan tombol kembali ke atas yang presisi (di kanan layar pada PC dan melayang di atas bar tracker pada mode mobile).
- **Penyelarasan Warna Widget Time Allocation**: Menyeragamkan warna indikator proyek utama menggunakan 1 warna biru (*blue*) dan sub-proyek/kategori menggunakan warna hijau kalem (*emerald/green*).

---

## [v3.2.0] - 2026-07-28 🚀

Versi ini berfokus pada integrasi autentikasi pihak ketiga, peningkatan fungsionalitas manajemen aktivitas, dan optimasi *background job* di lingkungan produksi.

### ✨ Fitur Baru & Pembaruan Utama
- **Google Sign-In / SSO**: Mengintegrasikan Laravel Socialite untuk memungkinkan pendaftaran (*Sign Up*) dan akses masuk (*Log In*) instan menggunakan Akun Google, lengkap dengan penyesuaian tombol autentikasi di halaman masuk dan daftar.
- **Pengeditan Detail Aktivitas**: Melengkapi fitur riwayat aktivitas; pengguna kini dapat mengubah nama/detail aktivitas secara bersamaan dengan waktu mulai dan selesainya pada jendela "Edit Activity".
- **Optimasi Produksi (Railway/Docker)**: Menambahkan konfigurasi *Worker* pada *Supervisord* (`schedule:work`) agar tugas-tugas terjadwal (*Cron Jobs*) Laravel dapat berjalan otomatis di lingkungan *containerized* (seperti Railway).

---

## [v3.1.0] - 2026-07-26 🚀

Versi ini menghadirkan fitur *Onboarding Tour* interaktif untuk memandu pengguna baru, serta standardisasi infrastruktur pengiriman email (*password reset*).

### ✨ Fitur Baru & Pembaruan Utama
- **Interactive Onboarding Tour**: Mengintegrasikan `driver.js` untuk membuat tur pengenalan dinamis bagi pengguna baru saat pertama kali *login*.
- **Admin-Specific Tour Logic**: Alur tur yang pintar dan mampu mendeteksi tingkat akses pengguna. *Administrator* akan mendapatkan 3 langkah tambahan (Issues, Members, Broadcast).
- **Tour Reset on Promotion**: Logika otomatis yang akan mereset status tur pengguna jika mereka dipromosikan menjadi Administrator, memastikan mereka mendapatkan panduan fitur admin.
- **Konsistensi Bahasa (English)**: Seluruh teks pada tur telah diterjemahkan secara rapi ke bahasa Inggris agar konsisten dengan antarmuka aplikasi.
- **Integrasi Resend API untuk Email**: Mengganti protokol SMTP standar dengan integrasi resmi pihak ketiga **Resend API** (`resend/resend-php`) untuk mengirim email (*Reset Password*). Hal ini diimplementasikan untuk melewati *error* Gateway Timeout (504) akibat pemblokiran *port* SMTP *outbound* pada layanan *cloud hosting* seperti Railway.

---

## [v3.0.0] - 2026-07-26 🚀

Versi ini berfokus pada penyelesaian masalah infrastruktur saat *deployment* ke *production* (Railway & PostgreSQL), perombakan total pada desain Halaman Depan dengan gaya *Graphite Monochrome*, serta penambahan puluhan animasi interaktif yang membuat aplikasi terasa lebih "hidup". 

**Perbedaan Utama dari v2.0.0:** 
Jika v2.0.0 sebelumnya berfokus pada penambahan fitur internal (Notifikasi, Manajemen Anggota, *Broadcast*, Passkey), maka v3.0.0 difokuskan sepenuhnya pada **Keandalan Infrastruktur (*Reliability*)** dan **Estetika Visual Premium (*Aesthetics*)** yang meningkatkan nilai jual aplikasi secara drastis di mata pengguna baru.

### 🛠️ Infrastruktur & Keandalan (*Infrastructure & Reliability*)
- **Perbaikan Koneksi PostgreSQL**: Optimasi konfigurasi koneksi PostgreSQL di lingkungan produksi.
- **Optimasi *Build* Docker & Nginx**: 
  - Mengubah struktur tahapan `Dockerfile` agar aset *frontend* dibangun setelah instalasi dependensi Composer (Vendor), memastikan semua kelas UI dari *Livewire/Flux* dikompilasi dengan sempurna ke dalam CSS.
  - Memperbarui `nginx.conf` dengan inklusi `mime.types`, menghilangkan potensi masalah *rendering file* statis (CSS/JS) di *browser*.
- **Konfigurasi *Proxy* Global (`TrustProxies`)**: Memperbarui *middleware* aplikasi untuk secara otomatis mempercayai *Load Balancer* agar seluruh URL dan muatan *asset* dipaksa menggunakan skema HTTPS yang aman tanpa *error* `Mixed Content`.

### 🎨 Desain Premium (*Aesthetics*)
- **Tema *Graphite Monochrome* (Zinc)**: Mengganti seluruh aksen warna (yang sebelumnya Oranye) ke warna *Zinc-700* (untuk mode terang) dan *Zinc-400* (untuk mode gelap). Warna abu-abu yang pekat dan elegan ini menciptakan kesan *SaaS* eksklusif (*Apple-like*).
- **Konsistensi Gaya Visual**: Menyelaraskan seluruh palet warna (teks, *badge*, tombol, hingga efek kursor/seleksi) di Halaman Depan agar sama persis dan menyatu dengan *Dashboard* aplikasi utama.

### ✨ Interaktivitas & Animasi Mikro (*Micro-animations*)
- **Transisi *Scroll Reveal* Pintar**: Teks dan elemen di halaman depan kini tidak kaku, melainkan akan meluncur naik secara perlahan (fade-in & slide-up) saat pengguna menggulir halaman.
- **Dashboard Melayang (*Floating Animation*)**: Gambar *Mockup Dashboard* kini seolah "bernapas" dengan efek melayang berkesinambungan. Elemen di dalamnya juga dirancang interaktif (membesar saat disorot kursor).
- **Autentikasi yang Halus**: Halaman *Login* dan *Register* kini tidak muncul mendadak, melainkan menyambut pengguna dengan animasi meluncur naik yang sangat elegan.
- **Responsivitas Komponen Penuh**: Memberikan reaksi interaktif (skala membesar/menyusut) pada kartu fitur, lingkaran indikator langkah, serta semua tombol utama saat diarahkan atau ditekan.

---

## [v2.0.0] - 2026-07-23 🚀

Versi ini merupakan pembaruan mayor yang menghadirkan dukungan multibahasa (Internationalization), sistem notifikasi bergaya macOS, panel manajemen anggota (Member Management), pusat siaran pengumuman (Broadcast Manager), integrasi autentikasi Passkey (WebAuthn), serta penyempurnaan UX form dan optimasi basis data.

### ✨ Fitur Baru & Pembaruan Utama (Major Features)

#### 1. Dukungan Multibahasa (Multi-Language i18n)
- **Pemilih Bahasa (Language Switcher)**: Menambahkan fitur peralihan bahasa dinamis antara Bahasa Indonesia (`id`) dan Bahasa Inggris (`en`) melalui kontrol rute `/lang/{locale}` dan middleware `SetLocale`.
- **Kamus Bahasa Lengkap**: Penyediaan berkas penerjemahan di direktori `lang/id` dan `lang/en` untuk halaman utama (*welcome page*), navigasi sidebar, dan komponen aplikasi.

#### 2. Sistem Notifikasi Bergaya macOS (`⚡notifications`)
- **Laci Notifikasi Slide-over**: Antarmuka melayang ber-ikon lonceng dengan *badge count* jumlah notifikasi belum dibaca (*unread count*).
- **Manajemen Notifikasi**: Pengguna dapat menandai notifikasi sebagai dibaca (*mark as read*) atau menghapus seluruh riwayat notifikasi (*clear all*).
- **Tabel Database Notifikasi**: Skema database `notifications` khusus untuk menyimpan judul, isi, tipe (*info*, *success*, *warning*, *danger*), dan stempel waktu baca (*read_at*).

#### 3. Panel Manajemen Anggota Admin (`⚡member-manager`)
- **Halaman Manajemen Anggota (`/members`)**: Fitur khusus administrator untuk mengelola hak akses seluruh anggota workspace.
- **Filter & Pencarian**: Filter cepat berdasarkan peran (*All*, *Admin*, *Member*) serta pencarian real-time berdasarkan nama atau email.
- **Promosi & Demosi Administrator**: Mengubah peran user secara instan dengan pencegahan *self-lockout* dan pengiriman notifikasi otomatis kepada user terkait ("Hak Akses Diperbarui 👑").

#### 4. Manajer Siaran Pengumuman Admin (`⚡broadcast-manager`)
- **Halaman Broadcast Pengumuman (`/broadcast`)**: Administrator dapat mengirimkan pesan pengumuman/sistem notifikasi secara massal ke seluruh pengguna atau pengguna tertentu.
- **Pilihan Tipe Notifikasi**: Mendukung berbagai jenis pesan (Info, Success, Warning, Danger) untuk komunikasi internal tim.

#### 5. Integrasi Passkey WebAuthn
- **Autentikasi Tanpa Sandi (Passkeys)**: Mengintegrasikan Laravel Fortify Passkey (`PasskeyUser`, `PasskeyAuthenticatable`) dan komponen UI `<x-passkey-registration />` serta `<x-passkey-verify />` untuk registrasi dan verifikasi kredensial biomekanik/perangkat.

#### 6. Pencarian Tiket Isu Canggih (`⚡issue-manager`)
- **Filter & Pencarian Tiket**: Mendukung pencarian spesifik menggunakan format tiket (`TKT-xxxx`), judul, deskripsi, maupun nama pembuat isu pada halaman manajemen tiket (`/issues`).

### 🛡️ Keamanan & Penyempurnaan UX Form

- **Penonaktifan Auto-Fill Input (`autocomplete="off"`)**: Menambahkan atribut `autocomplete="off"` pada tag form dan input halaman Login, Registrasi, dan Lupa Password untuk mencegah pop-up teks riwayat masa lalu dari browser tanpa menghilangkan *placeholder*.
- **Fresh Database Setup & Seeder Admin**: Pembersihan dan penataan ulang seeder basis data agar secara default menghasilkan 1 akun administrator utama (`admin@klakoan.com`).

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
