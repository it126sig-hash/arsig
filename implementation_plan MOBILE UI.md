# Rencana Desain UI Mobile ARSIG dengan Stitch

## Latar Belakang

ARSIG adalah aplikasi manajemen arsip yang sudah berjalan di web browser. Stack-nya menggunakan **Vue 3 + Vite + PrimeVue** untuk frontend dan ditargetkan juga untuk **mobile via Capacitor 6 (Android)**. Saat ini layout sudah memiliki conditional rendering untuk mobile (`md:hidden`), namun belum ada desain visual yang matang dan teroptimasi secara mobile-first.

Tujuan rencana ini: menggunakan **Google Stitch** (via StitchMCP) untuk membuat **mockup/prototype desain UI mobile** dari layar-layar utama aplikasi, kemudian menjadikannya referensi visual untuk implementasi di Vue.

---

## Cakupan Halaman yang Akan Didesain

Berdasarkan route yang ada di `router/index.js`, ada **13 halaman** + beberapa dialog penting. Prioritas desain dibagi 3 fase:

### Fase 1 — Core User Flow (Prioritas Tinggi)
Layar yang digunakan setiap hari oleh semua role:

| # | Stitch Screen | Keterangan |
|---|---|---|
| 1 | **Login Screen** | Form login (email + password), logo ARSIG |
| 2 | **Home / Search Archive** | Search bar, filter chips, list hasil pencarian (card mobile) |
| 3 | **Archive Detail Modal** | Detail arsip: metadata, preview file, lokasi fisik |
| 4 | **OTP Access Flow** | Bottom sheet request OTP + input 6-digit kode |
| 5 | **File Explorer** | Navigasi kategori hierarki + list arsip per folder |

### Fase 2 — PIC / Admin Flow (Prioritas Menengah)
Layar khusus untuk PIC dan Admin:

| # | Stitch Screen | Keterangan |
|---|---|---|
| 6 | **Request Approval Dashboard** | List permintaan akses OTP (approve/reject) |
| 7 | **Archive Upload Dialog** | Form upload arsip baru (stepper mobile) |
| 8 | **Archive Edit Dialog** | Form edit metadata arsip |
| 9 | **Location Visualizer** | Interaktif floor plan + cabinet slot selector |
| 10 | **Move Location Dialog** | Dual-panel: lokasi sekarang vs. pilih lokasi baru |

### Fase 3 — Admin Management (Prioritas Rendah)
Halaman konfigurasi yang jarang dibuka di mobile:

| # | Stitch Screen | Keterangan |
|---|---|---|
| 11 | **User Management** | CRUD pengguna |
| 12 | **Floor / Room / Cabinet Management** | Manajemen ruangan & lemari |
| 13 | **Company & Department** | Manajemen PT dan departemen |

---

## Komponen Layout Mobile yang Akan Dibuat di Stitch

### 1. Navigation System
- **Bottom Navigation Bar** (5 tab utama): Home, Explorer, Approvals, Lokasi, Profil
- **Top App Bar**: Judul halaman + action button (search, notifikasi)
- **Drawer / Side Sheet**: Menu lengkap untuk Admin

### 2. Design System (Stitch Design Tokens)
| Token | Nilai |
|---|---|
| **Primary Color** | `#1E40AF` (Biru ARSIG) |
| **Surface** | Dark mode atau light mode (pilih bersama user) |
| **Font** | Inter (modern, readable) |
| **Shape** | Rounded Medium (`12px`) |
| **Accent** | `#0EA5E9` untuk action items |

### 3. Shared Mobile Components
- **Archive Card** — representasi satu arsip di list
- **Filter Chip Row** — horizontal scroll filter chips
- **Status Badge** — PUBLIC / DEPT / USER / PRIVATE
- **File Type Icon** — PDF, DOCX, XLSX, IMG
- **Empty State** — ilustrasi ketika tidak ada data
- **Loading Skeleton** — placeholder saat data belum load

---

## Workflow Penggunaan Stitch

```
1. Create Project di Stitch
        ↓
2. Create Design System (warna, font, shape ARSIG)
        ↓
3. Generate Screen Fase 1 (5 screen utama)
        ↓
4. Review & Edit per screen jika ada koreksi
        ↓
5. Generate Variants untuk alternatif layout
        ↓
6. Export sebagai referensi visual
        ↓
7. Implementasikan ke Vue (revisi HomeView mobile cards, dll)
```

---

## Open Questions

> [!IMPORTANT]
> **Q1: Mode Warna Aplikasi**
> Apakah mobile app ARSIG akan menggunakan **Dark Mode**, **Light Mode**, atau keduanya (toggle)?

> [!IMPORTANT]
> **Q2: Prioritas Platform**
> Apakah desain Stitch ini untuk:
> - (A) Referensi visual saja (panduan implement di Vue), atau
> - (B) Langsung dijadikan kode React/Flutter oleh Stitch?
>
> *Catatan: Karena ARSIG pakai Vue + PrimeVue, Stitch hanya bisa jadi referensi visual. Kode yang dihasilkan Stitch tidak bisa langsung dipakai.*

> [!NOTE]
> **Q3: Fase Eksekusi**
> Mulai dari **Fase 1 saja** dulu (5 screen core), atau langsung semua 13 halaman?

---

## Proposed Changes (Stitch Actions)

### Step 1 — Create Stitch Project
Buat project baru bernama `ARSIG Mobile UI`.

### Step 2 — Create Design System
- Primary: `#1E40AF` (biru korporat)
- Font: Inter
- Shape: Medium rounded
- Mode: Light (dengan dark mode sebagai secondary)

### Step 3 — Generate Screens (per fase)
Setiap screen di-generate via `generate_screen_from_text` dengan prompt yang detail, mencakup:
- Layout mobile (`375px width`)
- Komponen yang relevan (equivalent PrimeVue di mobile)
- Data yang ditampilkan (berdasarkan kode Vue yang sudah ada)
- State/kondisi (empty, loading, populated, error)

### Step 4 — Generate Variants
Untuk screen **Home** dan **Archive Detail**: generate 2-3 variasi layout untuk dipilih mana yang paling baik.

### Step 5 — Edit & Refinement
Koreksi via `edit_screens` jika ada elemen yang kurang sesuai.

---

## Verification Plan

- **Visual Review**: Screenshot setiap screen di Stitch ditampilkan untuk review user
- **Konsistensi**: Cek apakah design system teraplikasi di semua screen
- **Coverage**: Pastikan semua state (loading, empty, error, populated) ter-cover
- **Mobile UX**: Pastikan touch target minimum 44px, readable text, gesture-friendly

---

## Catatan Teknis Penting

> [!WARNING]
> **Kode Stitch ≠ Kode Vue**: Stitch menghasilkan mockup visual (dan kemungkinan kode React/Web Components). Kode ini **TIDAK** bisa langsung digunakan di project ARSIG yang berbasis Vue 3 + PrimeVue. Stitch digunakan sebagai **referensi desain** saja, implementasi tetap dilakukan manual di Vue.

> [!NOTE]
> **Capacitor Consideration**: Desain mobile yang dibuat di Stitch akan menjadi acuan untuk memastikan UI di Capacitor (Android) terasa native dan nyaman digunakan dengan sentuhan jari.
