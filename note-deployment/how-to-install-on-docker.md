# Panduan Instalasi Super Mudah (Untuk Pemula)

Jika Anda sama sekali tidak paham *coding* atau pengaturan server yang rumit, Anda tidak perlu khawatir. Aplikasi ini sudah dikemas di dalam "kotak ajaib" bernama Docker yang berisi semua hal yang dibutuhkan agar aplikasi bisa langsung jalan.

Berikut adalah langkah super simpel yang harus Anda ikuti dari nol:

### Langkah 1: Persiapkan Alatnya (Hanya Sekali)
Sama seperti Anda butuh aplikasi Microsoft Word untuk membuka file dokumen, Anda butuh aplikasi **Docker** untuk menjalankan program ini.
1. Kunjungi website [Docker Desktop](https://www.docker.com/products/docker-desktop/).
2. Unduh dan install aplikasinya seperti menginstal aplikasi biasa di komputer Anda (tinggal *Next -> Next -> Finish*).
3. Buka aplikasi Docker Desktop tersebut dan biarkan menyala di latar belakang (ikon kapal paus akan muncul di layar Anda).

### Langkah 2: Siapkan Folder Aplikasi
1. Jika Anda mendapatkan *source code* ini dalam bentuk file ZIP, ekstrak (unzip) filenya.
2. Ingat-ingat di mana Anda menyimpan folder hasil ekstrak tersebut (misalnya di folder `Downloads` atau `Documents`).

### Langkah 3: Nyalakan Mesinnya!
Di sinilah keajaiban instalasi otomatis terjadi.
1. Buka aplikasi **Terminal** (jika Anda pakai Mac) atau **Command Prompt / CMD** (jika Anda pakai Windows).
2. Ketik `cd ` (jangan lupa spasinya), lalu *drag & drop* (seret dan lepaskan) folder aplikasi tadi dari File Explorer Anda ke dalam jendela layar hitam (CMD/Terminal). Lalu tekan **Enter**. Ini tujuannya agar terminal tahu di folder mana Anda berada.
3. Setelah masuk, ketik mantra sakti ini persis seperti ini:
   ```bash
   docker compose up -d
   ```
4. Tekan **Enter**. 
   *(Sekarang, cukup duduk manis. Komputer Anda sedang mengunduh dan merakit semua komponen database dan server secara otomatis. Tunggu sampai proses di layar selesai dan kembali ke kursor awal).*

### Langkah 4: Nikmati Aplikasinya 🎉
Jika Langkah 3 sudah selesai:
1. Buka browser favorit Anda (Chrome, Safari, Firefox).
2. Ketikkan alamat ini di bagian atas pencarian:
   👉 **`http://localhost:8080`**
3. Aplikasi akan langsung terbuka! Anda bisa login menggunakan akun yang sudah saya siapkan secara otomatis:
   - **Email:** `admin@klakoan.com`
   - **Password:** `password`

### Catatan Tambahan:
- **Untuk Mematikan:** Kalau Anda sudah selesai menggunakannya dan ingin mematikan aplikasinya agar komputer tidak lambat, Anda cukup buka layar hitam tadi lagi, dan ketik: `docker compose down`.
- **Untuk Menyalakan Kembali:** Kapan pun Anda butuh, cukup masuk ke foldernya lewat terminal lagi dan ketik: `docker compose up -d`.
