# Release Notes v1.0.0 🚀

Versi ini merupakan rilis perdana (Initial Release) untuk aplikasi Activity Tracker. Aplikasi ini dirancang untuk memudahkan manajemen waktu dan pencatatan pekerjaan dengan dukungan *parallel tracking* serta analitik yang intuitif.

## ✨ Fitur Utama (Features)

- **Real-Time Activity Tracking**: Catat pekerjaan Anda secara real-time dengan melampirkan Detail, *Project*, dan *Category*.
- **Parallel Tasks**: Dukungan untuk menjalankan beberapa aktivitas (multitasking) secara bersamaan menggunakan opsi *Parallel*. Dasbor akan secara otomatis menghitung durasi waktu riil tanpa menghitung ganda (*double-counting*) waktu yang tumpang tindih.
- **Dashboard & Analytics**:
  - Pengatur waktu langsung (*Live Timer*) untuk setiap aktivitas yang sedang berjalan.
  - Ringkasan total waktu "Hari Ini" (Today) dan "Minggu Ini" (This Week).
  - Grafik bar aktivitas mingguan (7 hari terakhir) yang dihitung secara akurat.
  - Visualisasi persentase alokasi waktu per-proyek (*Time Allocation*).
- **Manajemen Riwayat (*History*)**:
  - Riwayat aktivitas yang dikelompokkan secara rapi berdasarkan tanggal.
  - Dukungan filter berdasarkan *Start Date* dan *End Date*.
  - Kemampuan untuk mengubah waktu aktivitas (Edit *Start Time* & *End Time*) atau menghapusnya.
- **Ekspor & Impor Data**:
  - *Export* riwayat aktivitas ke dalam format Excel (berdasarkan rentang waktu tertentu atau keseluruhan).
  - *Import* data aktivitas dari file Excel/CSV.
- **User Interface Modern**:
  - Antarmuka *clean* dan responsif (mendukung *Dark Mode*).
  - Shortcut keyboard cepat (`Ctrl + /` untuk fokus input, `Ctrl + Enter` untuk mulai aktivitas).

---

## 🚧 Batasan Saat Ini (Limitations / Known Issues)

- **Akses Pengguna Terbatas (*Restricted Users*)**: Pendaftaran pengguna (*registration*) belum terbuka untuk umum. Akses sistem dibatasi hanya untuk *selected users* yang emailnya telah didaftarkan secara eksplisit pada *environment variable* `ALLOWED_EMAILS` di level `.env`.
- **Manajemen Proyek dan Kategori**: Modul pengelolaan Proyek dan Kategori mungkin masih membutuhkan pembaruan lebih lanjut agar lebih dinamis jika ingin dikustomisasi secara komprehensif oleh masing-masing pengguna.
- **Otomasi Notifikasi**: Belum ada fitur pengingat (*reminder*) otomatis jika ada *timer* aktivitas yang dibiarkan berjalan terlalu lama melebihi jam kerja normal.
