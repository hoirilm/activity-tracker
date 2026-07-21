# Product Requirements Document (PRD)
**Project Name:** Developer Activity Tracker  
**Document Status:** Approved (MVP)  
**Target Audience:** Internal/Personal Use (Developer)

---

## 1. Project Overview
Aplikasi berbasis web sederhana untuk melacak aktivitas harian (*time-based activity tracking*). Aplikasi ini dirancang khusus untuk alur kerja seorang *developer*, dengan antarmuka yang mengutamakan penggunaan *keyboard* (*keyboard-first UX*) agar proses pencatatan tidak mengganggu *flow* kerja utama.

## 2. Technology Stack
*   **Backend:** Laravel (PHP)
*   **Frontend:** Tailwind CSS, Alpine.js
*   **Interactivity:** Livewire
*   **Database:** MySQL / PostgreSQL
*   **Key Packages:** 
    *   `laravel/breeze` (Authentication)
    *   `maatwebsite/excel` (Export/Import CSV & Excel)

---

## 3. Database Schema (MVP)

Aplikasi akan menggunakan struktur relasional dengan tabel inti sebagai berikut:

### Tabel: `projects`
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | PK | Primary Key |
| `name` | String | Nama proyek |
| `client_name` | String | Nama klien (opsional) |
| `timestamps` | Timestamp | Created at & Updated at |

### Tabel: `categories` (Activity Categories)
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | PK | Primary Key |
| `name` | String | Contoh: Development, Support, DevOps |
| `timestamps` | Timestamp | Created at & Updated at |

### Tabel: `activities`
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | PK | Primary Key |
| `project_id` | FK | Relasi ke tabel `projects` |
| `category_id` | FK | Relasi ke tabel `categories` |
| `detail` | Text | Deskripsi spesifik kegiatan |
| `start_time` | Timestamp | Waktu aktivitas dimulai |
| `end_time` | Timestamp | Waktu selesai (Nullable - jika null berarti sedang berjalan) |
| `is_parallel` | Boolean | Default `false`. Menandakan aktivitas bisa berjalan bersamaan |
| `timestamps` | Timestamp | Created at & Updated at |

---

## 4. Core Features & Business Logic

### 4.1. Time-Based Tracking
*   Sistem mencatat waktu mulai (`start_time`) saat *task* dibuat.
*   Durasi dihitung secara *real-time* di sisi *client* (UI) menggunakan Alpine.js untuk mencegah beban *server*.
*   Durasi total di-*generate* menggunakan Eloquent Accessor (contoh: `01:30:00`) saat `end_time` telah terisi.

### 4.2. Sequential vs Parallel Activities
*   **Sequential (Default):** Sistem harus memvalidasi agar tidak ada aktivitas normal yang berjalan bersamaan. Jika *user* memulai aktivitas baru, aktivitas yang sedang berjalan harus dihentikan terlebih dahulu (atau dihentikan otomatis oleh sistem).
*   **Parallel:** *User* dapat mencentang opsi "Parallel" jika aktivitas tersebut boleh berjalan berbarengan dengan aktivitas lain (misal: *rendering video* sambil *coding*).

### 4.3. Data Portability (Export / Import)
*   **Export:** *User* dapat mengunduh riwayat aktivitas dalam format `.xlsx` atau `.csv`. Data yang diekspor mencakup relasi nama *project* dan *category*, serta kalkulasi durasi.
*   **Import:** *User* dapat mengunggah file `.xlsx` atau `.csv` untuk memasukkan data historis ke dalam *database*.

---

## 5. UI / UX Specifications

### 5.1. Layout
*   **Dark-Mode Ready:** Menggunakan *class* bawaan Tailwind (`dark:bg-gray-800`, dll).
*   **Sticky Input Bar:** Form input untuk menambah aktivitas selalu berada di bagian atas layar (`sticky top-0`) agar selalu dapat diakses meskipun *user* sedang melakukan *scroll* riwayat aktivitas.
*   **Activity Feed:** Menampilkan daftar aktivitas di bawah form, diurutkan dari yang terbaru, dikelompokkan berdasarkan hari.

### 5.2. Keyboard-First Navigation (Developer UX)
Memanfaatkan Alpine.js untuk integrasi *shortcut* pada level *window*:
*   `Ctrl + /` (atau `Cmd + /`): Memindahkan fokus kursor secara instan ke kolom *input detail* aktivitas.
*   `Ctrl + Enter` (atau `Cmd + Enter`): Memicu aksi *Submit* (Start Activity) tanpa perlu menggunakan *mouse*.
*   **Visual Hint:** Menampilkan instruksi *shortcut* dalam ukuran teks kecil (`text-xs`) di area form agar *user* mudah mengingatnya.

---

## 6. Security & Infrastructure

### 6.1. Authentication
*   Menggunakan *scaffolding* bawaan Laravel.
*   Seluruh halaman utama (seperti `/tracker`, `/projects`, `/categories`) dilindungi oleh *middleware* `auth`.

### 6.2. Whitelist Access Control
*   Pendaftaran akun terbuka, tetapi akses masuk dilindungi oleh *custom middleware* `CheckWhitelistedEmail`.
*   *Middleware* akan memeriksa *email* pengguna yang *login* terhadap daftar *email* yang diizinkan.
*   Daftar *email* yang diizinkan disimpan di *environment variable* `.env` (contoh: `ALLOWED_EMAILS=user@domain.com,admin@domain.com`) untuk mematuhi prinsip *12-Factor App* dan memudahkan *deployment* via Kubernetes ConfigMap/Secret.

---
*End of Document*