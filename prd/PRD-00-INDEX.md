# ARSIG — Archive Management System
## Product Requirements Document v3.0 — INDEX

| | |
|---|---|
| **Versi** | 3.0.0 |
| **Stack** | Vue 3 + PrimeVue + Capacitor + Laravel 11 + JWT |
| **Platform** | Web Browser + Android (Play Store — Internal Testing) |
| **Status** | Draft |
| **Pemilik** | Sanggar Indah Group — IT Division |

---

## Struktur Dokumen

Dokumen PRD ini dibagi menjadi beberapa file untuk kemudahan navigasi dan pemeliharaan.

| File | Konten |
|---|---|
| **PRD-00-INDEX.md** | Dokumen ini — daftar isi dan ringkasan eksekutif |
| **PRD-01-OVERVIEW.md** | Overview, tujuan, dan requirements (FR + NFR) |
| **PRD-02-FEATURES.md** | Core features dan user flow lengkap |
| **PRD-03-ARCHITECTURE.md** | Stack teknologi, deployment architecture, JWT strategy |
| **PRD-04-DATABASE.md** | Database schema, ERD, penjelasan tabel |
| **PRD-05-API.md** | API design, daftar endpoint, permission matrix |
| **PRD-06-TECHNICAL.md** | Technical constraints, setup backend (Laravel) & frontend (Vue+PrimeVue) |
| **PRD-07-MOBILE.md** | Mobile app — Capacitor, Android, FCM, Play Store |
| **PRD-08-CHANGELOG.md** | Riwayat perubahan PRD |

---

## Ringkasan Eksekutif

ARSIG adalah sistem manajemen arsip perusahaan berbasis web dan mobile yang menyatukan **arsip digital dan arsip fisik** dalam satu platform terpusat. Sistem ini digunakan oleh banyak perusahaan (PT), departemen, dan user dalam satu instansi — Sanggar Indah Group.

### Masalah yang Diselesaikan

Sulitnya melacak keberadaan dan aksesibilitas dokumen — baik soft file maupun hardfile — secara terpusat, terstruktur, dan aman.

### Stack v3.0

| Layer | Teknologi | Perubahan dari v2.x |
|---|---|---|
| Frontend | Vue 3 + Vite + **PrimeVue** | Bootstrap 5 → PrimeVue |
| Mobile | Capacitor 6 | Tidak berubah |
| State | Pinia | Tidak berubah |
| HTTP | Axios | Tidak berubah |
| Canvas | Konva.js | Tidak berubah |
| Backend | **Laravel 11** (PHP) | CodeIgniter 4 → Laravel 11 |
| Auth | **Laravel Sanctum** / `tymon/jwt-auth` | firebase/php-jwt → Laravel native |
| Database | MySQL | Tidak berubah |
| Storage | Local / S3-compatible | Tidak berubah |
| Web Server | Nginx + PHP-FPM | Tidak berubah |

### Alasan Migrasi Stack

- **Laravel → CI4:** Ekosistem lebih mature, Eloquent ORM lebih ekspresif, built-in tools (Artisan, Queues, Events, Notifications), komunitas lebih besar, dokumentasi lebih lengkap.
- **PrimeVue → Bootstrap:** Komponen UI siap pakai yang lebih kaya (DataTable, FileUpload, Dialog, Calendar, etc.) tanpa custom CSS berlebihan, konsisten dengan desain sistem.
