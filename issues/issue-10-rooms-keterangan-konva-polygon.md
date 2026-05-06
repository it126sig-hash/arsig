# Issue #10 — Penambahan Keterangan Ruangan & Visual Polygon KonvaJS

---

## Latar Belakang

Saat ini halaman **Rooms (Ruangan)** (`RoomsView.vue`) sudah memiliki CRUD dasar, namun:
1. Tabel `rooms` belum memiliki kolom **keterangan** (deskripsi tambahan ruangan).
2. Input koordinat ruangan masih berupa **text field JSON manual** — tidak user-friendly.
3. Belum ada visualisasi peta lantai (floor plan) menggunakan **KonvaJS** saat menambah/mengedit ruangan.

Issue ini menambahkan kolom `keterangan` dan mengganti input koordinat menjadi **visual polygon drawing** di atas gambar denah lantai menggunakan KonvaJS.

---

## Scope Pekerjaan

1. **Backend:** Migration untuk menambah kolom `keterangan` di tabel `rooms`
2. **Backend:** Update Model, Request, dan Controller agar mendukung kolom baru
3. **Frontend:** Tampilkan kolom `keterangan` di DataTable dan form dialog
4. **Frontend:** Buat komponen visual KonvaJS untuk menggambar polygon ruangan di atas floor plan
5. **Frontend:** Integrasikan komponen KonvaJS ke dialog tambah/edit ruangan

---

## Skema Database

### Kolom Baru di Tabel `rooms`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `keterangan` | TEXT nullable | Deskripsi atau catatan tambahan tentang ruangan |

Kolom yang sudah ada (tidak diubah):

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT PK | Auto increment |
| `floor_id` | BIGINT FK | Relasi ke `floors.id` |
| `name` | VARCHAR(255) | Nama ruangan |
| `points` | JSON | Koordinat polygon `[{x, y}, ...]` |
| `needs_coordinate_review` | BOOLEAN | Flag review koordinat |
| `created_at` | TIMESTAMP | - |
| `updated_at` | TIMESTAMP | - |

---

## Tugas Backend (Laravel API)

### 1. Buat Migration Baru

Buat migration untuk menambah kolom `keterangan` ke tabel `rooms`:

```bash
php artisan make:migration add_keterangan_to_rooms_table --table=rooms
```

Isi migration:
- Tambah kolom `keterangan` bertipe `text`, nullable, setelah kolom `name`

### 2. Update Model `Room`

**File:** `api/app/Models/Room.php`

- Tambahkan `'keterangan'` ke array `$fillable`

### 3. Update Request Validation

**File:** `StoreRoomRequest.php` dan `UpdateRoomRequest.php` (atau file validasi room yang digunakan)

- Tambahkan rule: `keterangan → nullable | string | max:1000`

### 4. Jalankan Migration

```bash
php artisan migrate
```

### 5. Test via Postman

- `POST /api/v1/rooms` dengan field `keterangan` → pastikan tersimpan
- `GET /api/v1/rooms` → pastikan field `keterangan` muncul di response

---

## Tugas Frontend (Vue 3 + PrimeVue + KonvaJS)

> **Catatan:** Package `konva` dan `vue-konva` sudah terinstall di `package.json`.

### 6. Tampilkan Kolom Keterangan di DataTable

**File:** `frontend/src/views/RoomsView.vue`

- Tambah `<Column>` baru untuk field `keterangan` di DataTable, letakkan setelah kolom "Nama Ruangan"
- Tampilkan text biasa, truncate jika terlalu panjang (gunakan `style="max-width: 250px"` + CSS `truncate`)

### 7. Tambahkan Input Keterangan di Dialog Form

**File:** `frontend/src/views/RoomsView.vue`

- Tambahkan field `<Textarea>` untuk `keterangan` di dalam Dialog, letakkan setelah field "Nama Ruangan"
- Label: "Keterangan", placeholder: "Catatan tambahan tentang ruangan (opsional)"
- Field ini opsional, tidak perlu validasi required

### 8. Buat Komponen KonvaJS Polygon Drawing

**File baru:** `frontend/src/components/RoomPolygonDrawer.vue`

Komponen ini bertugas menampilkan gambar denah lantai dan memungkinkan user mengklik titik-titik untuk membentuk polygon ruangan.

**Props yang diterima:**
- `floorImageUrl` (String, required) — URL gambar denah lantai yang di-load ke canvas
- `initialPoints` (Array, default `[]`) — Titik-titik polygon yang sudah ada (untuk mode edit)

**Emit yang dikeluarkan:**
- `update:points` (Array) — Array titik-titik polygon `[{x, y}, {x, y}, ...]`

**Perilaku komponen:**

```
+--------------------------------------------------+
| [Gambar Denah Lantai]                             |
|                                                   |
|    ● ─────────── ●                                |
|    |               \                              |
|    |                ●                             |
|    |               /                              |
|    ● ─────────── ●                                |
|                                                   |
| Titik: 5  |  [Undo Titik Terakhir] [Reset Semua] |
+--------------------------------------------------+
```

**Cara kerja:**
1. Komponen merender `<v-stage>` dan `<v-layer>` dari `vue-konva`
2. Di dalam layer, tampilkan `<v-image>` berisi gambar denah lantai (load dari `floorImageUrl`)
3. Saat user **klik di atas canvas**, tambahkan titik baru `{x, y}` ke array `points`
4. Render titik-titik sebagai `<v-circle>` (warna hijau, radius 6px) dan hubungkan dengan `<v-line>` (polygon outline)
5. Jika sudah ada ≥ 4 titik, tampilkan polygon filled semi-transparan (warna hijau, opacity 0.2) menggunakan `<v-line>` dengan `closed: true`
6. Tombol **"Undo Titik Terakhir"** → hapus titik terakhir dari array
7. Tombol **"Reset Semua"** → kosongkan array titik
8. Setiap perubahan titik → emit `update:points` ke parent
9. Validasi minimal: jika titik < 4, tampilkan pesan **"Minimal 4 titik diperlukan untuk membentuk area ruangan"**

**Detail teknis KonvaJS:**
- Gunakan `Konva.Image` atau `<v-image>` untuk load gambar denah — buat `new Image()` di JS, set `src` ke `floorImageUrl`, dan assign ke config `image` setelah `onload`
- Stage size menyesuaikan ukuran gambar denah (atau container parent)
- Koordinat `{x, y}` disimpan relatif terhadap ukuran canvas/gambar

### 9. Integrasikan Komponen ke Dialog Room

**File:** `frontend/src/views/RoomsView.vue`

Modifikasi dialog tambah/edit ruangan:

1. **Hapus** field input text JSON koordinat yang sekarang (`<InputText id="room-points" ...>`)
2. **Ganti** dengan komponen `<RoomPolygonDrawer>`:
   - Tampilkan komponen ini **hanya jika user sudah memilih floor** dari dropdown
   - Ambil `floorImageUrl` dari data floor yang dipilih (field `floor_plan_image` di object floor)
   - Bind `v-model:points` ke `room.points`
3. **Perbesar dialog** agar cukup menampilkan canvas denah — ubah width dialog menjadi minimal `900px`
4. Saat simpan, konversi array points ke JSON string yang dikirim ke API
5. Jika titik < 4 saat submit, tampilkan error dan jangan kirim ke server

**Layout dialog setelah modifikasi:**

```
+----------------------------------------------+
| Detail Ruangan                          [✕]  |
|----------------------------------------------|
| Lantai:     [▼ Pilih lantai...]              |
| Nama:       [___________________________]    |
| Keterangan: [___________________________]    |
|                                              |
| Koordinat Ruangan:                           |
| +------------------------------------------+|
| | [Gambar denah lantai + klik polygon]      ||
| |                                           ||
| +------------------------------------------+|
| Titik: 5  [Undo Titik Terakhir] [Reset]     |
|                                              |
| ☐ Perlu Review Koordinat                    |
|----------------------------------------------|
|                      [Batal]  [Simpan]       |
+----------------------------------------------+
```

### 10. Format Penyimpanan Points

Data `points` disimpan ke database sebagai **JSON string** dengan format:

```json
[
  {"x": 120, "y": 45},
  {"x": 340, "y": 45},
  {"x": 340, "y": 220},
  {"x": 120, "y": 220}
]
```

- Backend menerima dan menyimpan sebagai kolom `JSON`
- Frontend mengirim sebagai array of objects `[{x, y}]`
- Minimal 4 titik — validasi di frontend sebelum submit

---

## Urutan Pengerjaan yang Disarankan

```
1.  Backend: Buat migration add_keterangan_to_rooms_table
2.  Backend: Jalankan migration
3.  Backend: Update Model Room — tambah 'keterangan' ke $fillable
4.  Backend: Update Request validation — tambah rule keterangan
5.  Backend: Test via Postman — pastikan keterangan tersimpan dan muncul di response
6.  Frontend: Tambah kolom keterangan di DataTable RoomsView.vue
7.  Frontend: Tambah field Textarea keterangan di dialog form
8.  Frontend: Buat komponen RoomPolygonDrawer.vue (KonvaJS)
9.  Frontend: Integrasikan RoomPolygonDrawer ke dialog room (ganti input JSON manual)
10. Frontend: Perbesar dialog & atur layout agar canvas denah muat
11. Testing end-to-end: pilih floor → klik ≥4 titik → isi nama & keterangan → simpan → cek di tabel
```

---

## Kriteria Selesai (Definition of Done)

- [ ] Migration berhasil dijalankan, kolom `keterangan` ada di tabel `rooms`
- [ ] API `POST /api/v1/rooms` dan `PUT /api/v1/rooms/{id}` menerima & menyimpan field `keterangan`
- [ ] API `GET /api/v1/rooms` mengembalikan field `keterangan` di response
- [ ] Kolom `keterangan` tampil di DataTable halaman Rooms
- [ ] Dialog form punya field Textarea untuk `keterangan`
- [ ] Komponen `RoomPolygonDrawer.vue` menampilkan gambar denah lantai dari floor yang dipilih
- [ ] User bisa klik di atas gambar denah untuk menambahkan titik polygon
- [ ] Polygon terbentuk secara visual (garis + fill semi-transparan) setelah ≥ 4 titik
- [ ] Ada tombol "Undo Titik Terakhir" dan "Reset Semua"
- [ ] Validasi: tidak bisa simpan jika titik < 4
- [ ] Data points tersimpan ke database dalam format JSON `[{x, y}, ...]`
- [ ] Mode edit: titik-titik polygon yang sudah ada ditampilkan kembali di canvas
- [ ] Input JSON manual sudah dihapus / diganti sepenuhnya oleh komponen visual

---

## Catatan Teknis

- Package `konva@^10.3.0` dan `vue-konva@^3.4.0` sudah ada di `package.json` — tidak perlu install ulang
- URL gambar denah lantai ada di field `floor_plan_image` dari object floor (ambil dari `locationStore.floors`)
- Pastikan gambar denah di-load via URL yang benar (base URL API + path storage)
- Koordinat `{x, y}` bersifat relatif terhadap ukuran canvas — jika ukuran canvas berubah (responsive), titik tetap akurat selama proporsi dijaga
- Kolom `points` di database bertipe `JSON`, di Model di-cast ke `array` — tidak perlu mengubah cast yang sudah ada
