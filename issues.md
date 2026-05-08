# Rencana Pengerjaan: Fitur Riwayat Pemindahan Lokasi Fisik Arsip

Dokumen ini berisi rincian rencana (issue) untuk mengimplementasikan fitur pencatatan dan pelacakan riwayat perpindahan lokasi fisik arsip sesuai dengan spesifikasi yang diminta.

## 1. Backend (Laravel API)

### A. Database & Migrations
1. **Buat Tabel `archive_location_histories`**
   - **Tujuan**: Menyimpan log setiap kali arsip dipindahkan dari satu lokasi fisik ke lokasi lain.
   - **Kolom yang dibutuhkan**:
     - `id` (Primary Key)
     - `archive_id` (Foreign Key ke tabel `archives`)
     - `old_location_id` (Foreign Key ke lokasi lama, nullable jika arsip baru)
     - `new_location_id` (Foreign Key ke lokasi baru)
     - `user_id` (Foreign Key ke `users`, mencatat siapa yang memindahkan)
     - `notes` (Text, kolom keterangan pemindahan sesuai permintaan)
     - `moved_at` (Timestamp)
     - `created_at`, `updated_at`

### B. API Endpoints
1. **Endpoint Pindah Lokasi** 
   - `POST /api/archives/{archive_id}/move-location`
   - **Payload**: `new_location_id` (atau cascade id floor/room/cabinet), dan `notes` (keterangan pemindahan).
   - **Response**: Sukses memindahkan dan mencatat log.
2. **Endpoint Daftar Riwayat Global (Untuk Page)**
   - `GET /api/location-histories`
   - **Response**: Paginated list riwayat pemindahan seluruh arsip (bisa di-filter berdasarkan tanggal, nama arsip, dll).
3. **Endpoint Riwayat Spesifik Arsip (Untuk Modal Detail)**
   - `GET /api/archives/{archive_id}/location-histories`
   - **Query Params**: `limit=3` (untuk mengambil 3 riwayat terakhir).
   - **Response**: List riwayat pemindahan untuk satu arsip.

### C. Services & Observers
1. **ArchiveService**
   - Buat method `movePhysicalLocation($archive, $data)` yang meng-handle update kolom lokasi pada tabel `archives` dan membuat record baru di tabel `archive_location_histories`.
2. **Observer (Opsional tapi direkomendasikan)**
   - Manfaatkan `ArchiveObserver` pada event `updated` atau `saving` untuk mendeteksi perubahan `location_id` (via `$archive->isDirty('location_id')`), dan secara otomatis memicu job/proses pencatatan ke `archive_location_histories`. Namun karena butuh kolom `notes` (keterangan), lebih baik di-handle secara eksplisit di Service.

---

## 2. Frontend (Vue 3 + PrimeVue)

### A. Fitur Pindah Lokasi (Form/Action)
1. **Action Button**: Tambahkan tombol "Pindah Lokasi" pada tabel (opsi menu) atau di dalam modal detail arsip.
2. **MoveLocationDialog.vue**: 
   - Modal khusus untuk proses pemindahan.
   - **Form Fields**: 
     - Cascade Dropdown (Lantai > Ruangan > Lemari > Slot) untuk memilih lokasi baru.
     - Textarea untuk input "Keterangan/Alasan Pemindahan".
   - **API Call**: Mengirim payload lokasi dan `notes` ke endpoint move-location.

### B. Halaman List Riwayat Pemindahan (Page)
1. **Route Baru**: `/location-histories` (Misal di sidebar admin/PIC).
2. **LocationHistoryView.vue**:
   - Menampilkan `DataTable` PrimeVue.
   - **Kolom Tabel**: Tanggal Pemindahan, Nama Arsip, Lokasi Lama, Lokasi Baru, Dipindahkan Oleh, dan Keterangan.
   - Dilengkapi fitur pencarian dan paginasi API.

### C. Update Modal Detail Arsip (`ArchiveDetailModal.vue`)
1. **Penambahan UI Tab / Tombol**:
   - Di bagian informasi lokasi fisik, tambahkan tombol/link: **"Lihat Riwayat Lokasi"** atau langsung tampilkan section **"Riwayat Pemindahan (3 Terakhir)"**.
2. **Komponen Timeline / List Sederhana**:
   - Gunakan komponen `<Timeline>` dari PrimeVue atau list sederhana (ul/li) yang di-styling rapi.
   - Lakukan fetch data ke `GET /api/archives/{id}/location-histories?limit=3` ketika modal detail dibuka atau ketika tombol "Lihat Riwayat" diklik.
   - Menampilkan: Tanggal, Dari mana -> Ke mana, PIC, dan Keterangan.

---

## Urutan Pengerjaan yang Disarankan (Milestones)

- [ ] **Fase 1: Database & Backend Logic** (Migrasi tabel history, setup relasi Model, dan implementasi logika pemindahan beserta input `notes` di Service).
- [ ] **Fase 2: API Endpoints** (Buat endpoint untuk eksekusi move location, get history list, dan get 3 last history by archive ID).
- [ ] **Fase 3: Frontend Pindah Lokasi** (Buat form/dialog UI untuk input lokasi baru + keterangan di Vue, serta integrasi API).
- [ ] **Fase 4: Frontend Detail & Riwayat (Modal)** (Integrasikan komponen timeline/list riwayat 3 terakhir di `ArchiveDetailModal.vue`).
- [ ] **Fase 5: Frontend Halaman Log** (Buat page penuh `LocationHistoryView.vue` menggunakan DataTable untuk meninjau seluruh pergerakan fisik arsip).
