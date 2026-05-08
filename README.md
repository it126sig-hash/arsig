# ARSIG - Arsip Sanggar Indah Group

ARSIG adalah sistem manajemen arsip perusahaan berbasis web dan mobile yang menyatukan **arsip digital dan arsip fisik** dalam satu platform terpusat. Sistem ini dirancang untuk Sanggar Indah Group untuk mengelola dokumen di berbagai entitas (PT), departemen, dan pengguna.

## 🚀 Fitur Utama

- **Pencarian Terpusat**: Full-text search dengan filter berdasarkan PT, kategori, hashtag, dan tipe file.
- **Manajemen Arsip**: Mendukung arsip digital (`digital_only`), arsip fisik (`physical_only`), gabungan (`full`), dan `placeholder`.
- **Kontrol Akses Granular**: Pembatasan akses berdasarkan user, departemen, atau PT.
- **Pelacakan Fisik**: Visualisasi interaktif lokasi dokumen (Lantai → Ruangan → Lemari → Slot) menggunakan Konva.js.
- **Alur Kerja Approval**: Sistem request dan approval untuk download dokumen sensitif.
- **Notifikasi Real-time**: Push notification untuk request download dan approval via FCM.
- **Audit Trail**: Pencatatan lengkap seluruh aktivitas pengguna terhadap arsip.
- **Aplikasi Mobile**: Tersedia aplikasi Android berbasis Capacitor dengan fitur scan kamera dan download ke storage lokal.

## 🛠 Tech Stack

### Backend (API)
- **Framework**: Laravel 11 (Pure REST API)
- **Auth**: JWT (tymon/jwt-auth) dengan strategi Access & Refresh Token
- **Database**: MySQL 8
- **Queue**: Laravel Queue untuk notifikasi push
- **Storage**: Laravel Filesystem (Local/S3)

### Frontend & Mobile
- **Framework**: Vue 3 + Vite
- **UI Framework**: PrimeVue
- **State Management**: Pinia
- **Mobile Wrapper**: Capacitor 6
- **Canvas Library**: Konva.js (untuk map lokasi fisik)

## 📂 Struktur Proyek

```text
.
├── api/            # Backend Laravel (REST API)
├── frontend/       # Frontend Vue 3 + Capacitor Android
├── prd/            # Dokumentasi Product Requirement Document (PRD)
└── issues/         # Log tracking issues/tugas
```

## 📖 Dokumentasi Terkait

- [PRD Index](prd/PRD-00-INDEX.md)
- [Overview & Requirements](prd/PRD-01-OVERVIEW.md)
- [Architecture](prd/PRD-03-ARCHITECTURE.md)
- [Database Schema](prd/PRD-04-DATABASE.md)
- [API Design](prd/PRD-05-API.md)

## 🛠 Cara Memulai

### Backend
Lihat [api/README.md](api/README.md) untuk instruksi setup backend.

### Frontend
Lihat [frontend/README.md](frontend/README.md) untuk instruksi setup frontend dan build Android.

---
© 2024 Sanggar Indah Group
