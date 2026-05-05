# ARSIG — PRD-01: Overview & Requirements

← [Index](PRD-00-INDEX.md) | [Features →](PRD-02-FEATURES.md)

---

## 1. Overview

ARSIG adalah sistem manajemen arsip perusahaan berbasis web dan mobile yang menyatukan **arsip digital dan arsip fisik** dalam satu platform terpusat. Sistem ini digunakan oleh banyak perusahaan (PT), departemen, dan user dalam satu instansi — Sanggar Indah Group.

Masalah utama yang diselesaikan adalah sulitnya melacak keberadaan dan aksesibilitas dokumen — baik soft file maupun hardfile — secara terpusat, terstruktur, dan aman.

### Tujuan Utama

- Menyediakan platform arsip digital dengan pencarian cepat dan terstruktur
- Mengontrol akses dokumen secara granular per user, departemen, atau PT
- Melacak lokasi fisik dokumen (lantai → ruangan → lemari → slot)
- Menyimpan audit trail lengkap atas seluruh aktivitas pengguna
- Mendukung alur request dan approval download untuk dokumen sensitif
- Tersedia sebagai aplikasi Android yang dapat diinstall dari Play Store (Internal Testing Track)

---

## 2. Requirements

### 2.1 Functional Requirements

| ID | Kategori | Requirement |
|---|---|---|
| FR-01 | Aksesibilitas | Aplikasi dapat diakses via Web Browser (desktop/laptop) dan Android app (Capacitor WebView). |
| FR-02 | Multi-entitas | Sistem mendukung banyak PT, departemen, dan user dalam satu instansi dengan isolasi data yang tepat. |
| FR-03 | Tipe Arsip | Tiga tipe: `full` (metadata + file digital), `physical_only` (metadata + lokasi hardfile), `placeholder` (metadata + arahan ke PIC). |
| FR-04 | Kontrol Akses | Level akses: `public`, `private`, `specific_user`, `specific_department`. Divalidasi di backend setiap request. |
| FR-05 | Download Policy | Direct download via signed URL (TTL 60 detik) atau request ke PIC dengan alur approve/reject. |
| FR-06 | Audit Trail | Semua aktivitas (upload, edit, hapus, download, pindah lokasi fisik, ganti PIC) tercatat di log table. |
| FR-07 | Push Notification | Notifikasi push native Android untuk: download request masuk, approval/rejection, dan reminder expire_date arsip. |
| FR-08 | Search | Full-text search dengan filter: keyword, PT, kategori, hashtag, tipe file, tipe arsip, dan rentang tanggal. |
| FR-09 | Scan Dokumen | Kamera device dapat digunakan untuk memfoto hardfile sebagai lampiran arsip `physical_only`. |
| FR-10 | Download ke Storage | File hasil download tersimpan ke folder Downloads device Android, bukan hanya dibuka di browser. |

### 2.2 Non-Functional Requirements

- Autentikasi menggunakan JWT — access token 15 menit + refresh token 7 hari via httpOnly cookie
- Backend **wajib HTTPS** — Android memblokir cleartext HTTP traffic secara default
- API RESTful dengan response format JSON konsisten di semua endpoint
- Signed URL selalu divalidasi bersamaan dengan JWT session — URL saja tidak cukup untuk download
- Semua file upload disimpan di storage lokal atau S3-compatible (dapat dikonfigurasi via `.env`)
- Koordinat peta Konva.js di-flag otomatis (`needs_coordinate_review = true`) saat floor plan image diganti
- Target Android SDK minimum **API level 26** (Android 8.0), target SDK **34** (Android 14) sesuai kebijakan Play Store
- Backend Laravel dikonfigurasi sebagai **pure REST API** — tidak serve HTML, tidak ada Blade views
- Response time API target < 500ms untuk operasi read, < 2 detik untuk upload/download
