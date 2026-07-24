# Panduan Deploy & Install via Docker

Karena semua file konfigurasi Docker (`Dockerfile`, `docker-compose.yml`, dan `docker-entrypoint.sh`) sudah disiapkan, proses instalasi dan _deploy_ aplikasi ini menjadi sangat mudah. Anda bisa menggunakan panduan ini baik di *environment* lokal (laptop) maupun di server *production* (VPS).

## 1. Persiapan: Instalasi Docker
Pastikan **Docker** dan **Docker Compose** sudah terinstal dan berjalan.
- **Mac/Windows:** Install [Docker Desktop](https://www.docker.com/products/docker-desktop/).
- **Linux/Ubuntu (Server):** Install Docker Engine via terminal (`sudo apt install docker.io docker-compose-v2`).

## 2. Proses Build & Run (One-Click Deploy)
Buka terminal/command prompt, pastikan Anda berada di direktori akar (root) project ini (tempat di mana file `docker-compose.yml` berada). Lalu jalankan perintah berikut:

```bash
docker compose up -d --build
```

*(Catatan: flag `--build` memaksa Docker untuk membuild ulang image Laravel + Node.js dari awal untuk memastikan Anda mendapatkan perubahan kode terbaru).*

### Proses Otomatis di Balik Layar:
1. Docker akan mengunduh OS Alpine, PHP 8.3, Nginx, dan PostgreSQL.
2. Dependensi NPM (Node) dan Composer (PHP) akan diinstal secara otomatis.
3. Container `postgres` (Database) akan menyala lebih dulu.
4. Container `app` (Web/Laravel) akan menyala, kemudian `docker-entrypoint.sh` akan otomatis:
   - Membuat direktori *cache* dan memperbaiki *permissions*.
   - Menjalankan **Migrate Database** (`php artisan migrate`).
   - Menjalankan **Seeder Database** (`php artisan db:seed`) untuk membuat akun admin, *dummy user*, kategori, project, dan 20 rekaman aktivitas dummy.
   - Menyalakan server Nginx dan daemon PHP-FPM melalui Supervisor.

## 3. Akses Aplikasi
Jika proses instalasi selesai tanpa error di terminal, buka browser dan akses URL berikut:
- **Lokal:** `http://localhost:8080`
- **Server:** `http://<IP_ADDRESS_SERVER_ANDA>:8080`

## 4. Akun Login Default
Karena Seeder otomatis dijalankan pada proses inisialisasi, Anda bisa langsung masuk untuk mengetes aplikasi menggunakan kredensial admin berikut:
- **Email:** `admin@klakoan.com`
- **Password:** `password`

---

## Referensi Perintah Penting (Cheat Sheet)

**Mematikan aplikasi (Menghentikan container):**
```bash
docker compose down
```

**Melihat log proses / pesan error secara real-time:**
```bash
docker compose logs -f
```

**Melihat status container yang sedang berjalan:**
```bash
docker compose ps
```

**Masuk ke terminal (shell) di dalam container aplikasi Laravel:**
```bash
docker compose exec app sh
```
