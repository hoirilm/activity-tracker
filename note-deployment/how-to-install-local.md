# Cara Install Aplikasi Secara Lokal (Tanpa Docker)

Karena Anda menggunakan Mac dan meletakkan folder project ini di dalam direktori `Herd/`, itu artinya Anda sudah memiliki **Laravel Herd**! Herd adalah alat bantu super cepat dan ringan untuk menjalankan aplikasi Laravel dan PHP secara natif (langsung di mesin Anda), sehingga Anda **sama sekali tidak butuh Docker**.

Berikut adalah langkah-langkah praktis untuk menjalankan aplikasi ini:

### 1. Persiapan Awal (Install Dependencies)
Buka aplikasi Terminal (atau iTerm), lalu arahkan ke folder project ini:
```bash
cd ~/Herd/project/activity-tracker
```
Kemudian, *install* semua paket pustaka (PHP dan Javascript) yang dibutuhkan:
```bash
composer install
npm install
```

### 2. Konfigurasi Environment (.env)
Aplikasi butuh file rahasia `.env`. Buat file ini dengan menyalin dari file contoh yang sudah disediakan:
```bash
cp .env.example .env
```
Lalu, hasilkan kunci rahasia (*App Key*) untuk aplikasi:
```bash
php artisan key:generate
```

### 3. Setup Database (Saran Termudah: SQLite)
Secara bawaan, konfigurasi aplikasi ini di set untuk menggunakan PostgreSQL. Namun, jika Anda tidak mau repot men- *download* dan menjalankan *database* terpisah (seperti melalui aplikasi DBngin), Anda bisa menggunakan **SQLite** (database ringan berbasis file yang sudah otomatis didukung Mac).

Buka file `.env` menggunakan teks editor Anda (VSCode/Sublime), lalu cari baris `DB_CONNECTION` dan ubah menjadi:
```env
DB_CONNECTION=sqlite
# Anda bisa menghapus baris pengaturan DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, dan DB_PASSWORD di bawahnya.
```

Setelah itu, jalankan perintah migrasi untuk meracik tabel di dalam database:
```bash
php artisan migrate
```
*(Jika muncul peringatan file database belum ada dan bertanya "Would you like to create it?", ketik **yes** dan tekan Enter).*

### 4. Build Tampilan (Frontend)
Agar tampilan antarmukanya (CSS & Javascript) terbentuk sempurna, jalankan perintah *build*:
```bash
npm run build
```
*(Catatan: Jika Anda sedang dalam mode coding/mengedit tampilan, Anda bisa membiarkan `npm run dev` terus menyala di terminal).*

### 5. Akses Aplikasi Tanpa Perlu Dijalankan!
Selesai! Anda **TIDAK PERLU** menjalankan perintah kuno seperti `php artisan serve`. 
Karena Anda meletakkan project ini di dalam folder `Herd`, Laravel Herd secara otomatis mendeteksi dan melayani *web* Anda di latar belakang.

Buka *browser* (Chrome/Safari) dan langsung ketik alamat sakti ini:
👉 **http://activity-tracker.test**

Herd otomatis menyambungkan nama folder Anda (`activity-tracker`) dengan domain lokal `.test`. Selamat! Aplikasi Anda sekarang berjalan mulus murni di Mac Anda.
