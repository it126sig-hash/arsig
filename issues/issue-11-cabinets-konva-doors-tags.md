# Issue #11 — Visualisasi Lemari KonvaJS, Pintu Lemari, Keterangan & Tags

---

## Latar Belakang

Saat ini halaman **Cabinets (Lemari)** dan **Cabinet Slots (Pintu Lemari)** sudah memiliki CRUD dasar, namun:

1. Input koordinat lemari masih berupa **text field JSON manual** — tidak user-friendly
2. Belum ada visualisasi **KonvaJS** seperti di Rooms yang menampilkan floor plan + polygon room
3. Tabel `cabinets` belum punya kolom **keterangan** dan **jumlah pintu** (`door_count`)
4. Tabel `cabinet_slots` belum punya kolom **keterangan** dan **status**
5. Belum ada sistem **tags/hashtag** untuk `cabinet_slots`
6. Belum ada visualisasi pintu lemari secara visual (HTML+CSS grid)
7. `cabinet_slots` hanya mendukung 1 PIC, belum bisa **lebih dari 1 PIC**

Issue ini mencakup:
- Integrasi KonvaJS untuk placement lemari di atas peta ruangan
- Penambahan kolom keterangan & door_count di cabinets
- Penambahan keterangan & status di cabinet_slots
- Sistem tags (hashtag) untuk cabinet_slots
- Visualisasi pintu lemari (HTML+CSS grid) dengan auto-numbering
- Multi-PIC untuk setiap pintu lemari

---

## Scope Pekerjaan

| # | Area | Deskripsi |
|---|------|-----------|
| A | Backend — Migration | Tambah kolom baru di `cabinets`, `cabinet_slots`, buat tabel `cabinet_slot_tags`, ubah PIC jadi many-to-many |
| B | Backend — Model & Relations | Update Model, Request, Resource, Controller |
| C | Frontend — Cabinets | KonvaJS polygon drawer + kolom keterangan & door_count di tabel |
| D | Frontend — Cabinet Slots | Visualisasi pintu, auto-numbering, multi-PIC, status, keterangan, tags |

---

## Bagian A: Skema Database

### A1. Kolom Baru di Tabel `cabinets`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `keterangan` | TEXT nullable | Deskripsi/catatan tambahan tentang lemari |
| `door_count` | VARCHAR(20) nullable | Jumlah pintu format `X * Y` (contoh: `4 * 3` = 4 kolom × 3 baris = 12 pintu) |

Kolom yang sudah ada (tidak diubah):

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT PK | Auto increment |
| `room_id` | BIGINT FK | Relasi ke `rooms.id` |
| `name` | VARCHAR(255) | Nama lemari |
| `points` | JSON | Koordinat polygon `[{x, y}, ...]` |
| `needs_coordinate_review` | BOOLEAN | Flag review koordinat |
| `created_at` | TIMESTAMP | — |
| `updated_at` | TIMESTAMP | — |

### A2. Kolom Baru di Tabel `cabinet_slots`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `keterangan` | TEXT nullable | Deskripsi/catatan tentang pintu |
| `status` | ENUM('aktif', 'nonaktif', 'rusak') | Status pintu, default `aktif` |

**Perubahan:** Hapus kolom `pic_user_id` (FK tunggal) → diganti dengan tabel pivot `cabinet_slot_user` (many-to-many)

### A3. Tabel Baru: `cabinet_slot_user` (Pivot Multi-PIC)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT PK | Auto increment |
| `cabinet_slot_id` | BIGINT FK | Relasi ke `cabinet_slots.id`, cascadeOnDelete |
| `user_id` | BIGINT FK | Relasi ke `users.id`, cascadeOnDelete |
| `created_at` | TIMESTAMP | — |
| `updated_at` | TIMESTAMP | — |

> **Unique constraint:** `cabinet_slot_id` + `user_id` harus unik (tidak boleh assign user yang sama dua kali ke slot yang sama)

### A4. Tabel Baru: `cabinet_slot_tags`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT PK | Auto increment |
| `cabinet_slot_id` | BIGINT FK | Relasi ke `cabinet_slots.id`, cascadeOnDelete |
| `tag` | VARCHAR(100) | Nama hashtag (tanpa `#`, contoh: `penting`, `rahasia`, `keuangan`) |
| `created_at` | TIMESTAMP | — |
| `updated_at` | TIMESTAMP | — |

---

## Bagian B: Tugas Backend (Laravel API)

### B1. Buat Migration — Tambah Kolom di `cabinets`

```bash
php artisan make:migration add_keterangan_and_door_count_to_cabinets_table --table=cabinets
```

**Isi migration:**
- `$table->text('keterangan')->nullable()->after('name');`
- `$table->string('door_count', 20)->nullable()->after('keterangan');`

### B2. Buat Migration — Modifikasi `cabinet_slots`

```bash
php artisan make:migration modify_cabinet_slots_add_keterangan_status_remove_pic --table=cabinet_slots
```

**Isi migration up():**
- `$table->text('keterangan')->nullable()->after('name');`
- `$table->enum('status', ['aktif', 'nonaktif', 'rusak'])->default('aktif')->after('keterangan');`
- `$table->dropForeign(['pic_user_id']);`
- `$table->dropColumn('pic_user_id');`

**Isi migration down():**
- `$table->dropColumn(['keterangan', 'status']);`
- `$table->foreignId('pic_user_id')->constrained('users')->cascadeOnDelete();`

### B3. Buat Migration — Tabel `cabinet_slot_user`

```bash
php artisan make:migration create_cabinet_slot_user_table
```

**Isi migration:**
```php
Schema::create('cabinet_slot_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cabinet_slot_id')->constrained('cabinet_slots')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['cabinet_slot_id', 'user_id']);
});
```

### B4. Buat Migration — Tabel `cabinet_slot_tags`

```bash
php artisan make:migration create_cabinet_slot_tags_table
```

**Isi migration:**
```php
Schema::create('cabinet_slot_tags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cabinet_slot_id')->constrained('cabinet_slots')->cascadeOnDelete();
    $table->string('tag', 100);
    $table->timestamps();
});
```

### B5. Update Model `Cabinet`

**File:** `api/app/Models/Cabinet.php`

- Tambahkan `'keterangan'` dan `'door_count'` ke array `$fillable`
- Relasi `cabinetSlots()` sudah ada, tidak perlu diubah

### B6. Update Model `CabinetSlot`

**File:** `api/app/Models/CabinetSlot.php`

- Ubah `$fillable` menjadi: `['cabinet_id', 'name', 'keterangan', 'status']`
- Hapus relasi `picUser()` (belongsTo tunggal)
- Tambah relasi baru:
  ```php
  public function picUsers()
  {
      return $this->belongsToMany(User::class, 'cabinet_slot_user');
  }

  public function tags()
  {
      return $this->hasMany(CabinetSlotTag::class);
  }
  ```

### B7. Buat Model `CabinetSlotTag`

**File baru:** `api/app/Models/CabinetSlotTag.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CabinetSlotTag extends Model
{
    protected $fillable = ['cabinet_slot_id', 'tag'];

    public function cabinetSlot()
    {
        return $this->belongsTo(CabinetSlot::class);
    }
}
```

### B8. Update Request Validation

**File:** `StoreCabinetRequest.php`
- Tambah rule: `'keterangan' => 'nullable|string|max:1000'`
- Tambah rule: `'door_count' => 'nullable|string|max:20|regex:/^\d+\s*\*\s*\d+$/'`

**File:** `UpdateCabinetRequest.php`
- Tambah rule yang sama dengan prefix `sometimes`

**File:** `StoreCabinetSlotRequest.php`
- Hapus rule `pic_user_id`
- Tambah rule: `'keterangan' => 'nullable|string|max:1000'`
- Tambah rule: `'status' => 'sometimes|in:aktif,nonaktif,rusak'`
- Tambah rule: `'pic_user_ids' => 'nullable|array'` dan `'pic_user_ids.*' => 'exists:users,id'`
- Tambah rule: `'tags' => 'nullable|array'` dan `'tags.*' => 'string|max:100'`

**File:** `UpdateCabinetSlotRequest.php`
- Sama seperti Store tapi dengan prefix `sometimes` di field required

### B9. Update Resource `CabinetResource`

**File:** `api/app/Http/Resources/CabinetResource.php`

Tambahkan field baru di array return:
```php
'keterangan' => $this->keterangan,
'door_count' => $this->door_count,
'slots' => CabinetSlotResource::collection($this->whenLoaded('cabinetSlots')),
```

### B10. Update Resource `CabinetSlotResource`

**File:** `api/app/Http/Resources/CabinetSlotResource.php`

Ubah array return menjadi:
```php
return [
    'id' => $this->id,
    'cabinet_id' => $this->cabinet_id,
    'name' => $this->name,
    'keterangan' => $this->keterangan,
    'status' => $this->status,
    'cabinet' => new CabinetResource($this->whenLoaded('cabinet')),
    'pic_users' => $this->whenLoaded('picUsers', function () {
        return $this->picUsers->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ]);
    }),
    'tags' => $this->whenLoaded('tags', function () {
        return $this->tags->pluck('tag');
    }),
    'created_at' => $this->created_at,
    'updated_at' => $this->updated_at,
];
```

### B11. Update Controller `CabinetController`

**File:** `api/app/Http/Controllers/Api/CabinetController.php`

- Di `index()`: tambahkan eager load `cabinetSlots` → `Cabinet::with(['room.floor', 'cabinetSlots'])->latest()->get()`
- Di `show()`: load `cabinetSlots.picUsers` dan `cabinetSlots.tags` juga

### B12. Update Controller `CabinetSlotController`

**File:** `api/app/Http/Controllers/Api/CabinetSlotController.php`

- Di `index()`: ganti `picUser` dengan `picUsers`, tambah `tags` → `CabinetSlot::with(['cabinet.room.floor', 'picUsers', 'tags'])`
- Di `store()`: setelah `CabinetSlot::create($data)`, tambahkan:
  ```php
  if ($request->has('pic_user_ids')) {
      $slot->picUsers()->sync($request->pic_user_ids);
  }
  if ($request->has('tags')) {
      $slot->tags()->delete();
      foreach ($request->tags as $tag) {
          $slot->tags()->create(['tag' => $tag]);
      }
  }
  ```
- Di `update()`: logika yang sama untuk sync PIC dan tags
- Di `show()`: load `picUsers` dan `tags`

### B13. Jalankan Migration

```bash
php artisan migrate
```

---

## Bagian C: Tugas Frontend — Cabinets (Vue 3 + PrimeVue + KonvaJS)

### C1. Buat Komponen `CabinetPolygonDrawer.vue`

**File baru:** `frontend/src/components/CabinetPolygonDrawer.vue`

Komponen ini **mirip dengan `RoomPolygonDrawer.vue`** tapi dengan perbedaan:

1. Menerima **2 data tambahan** sebagai props:
   - `floorImageUrl` (String, required) — URL gambar denah lantai
   - `existingRooms` (Array, default `[]`) — Array of rooms `[{name, points: [{x,y}]}]` dari floor yang sama
   - `initialPoints` (Array, default `[]`) — Titik-titik polygon cabinet yang sudah ada

2. **Render layer room terlebih dahulu:**
   - Load gambar denah lantai sama seperti `RoomPolygonDrawer`
   - Render semua polygon dari `existingRooms` sebagai `<v-line>` dengan:
     - `fill: 'rgba(100, 116, 139, 0.08)'` (sangat tipis, abu-abu)
     - `stroke: 'rgba(100, 116, 139, 0.3)'`
     - `strokeWidth: 1 / scale`
     - `closed: true`
     - `listening: false`
   - Tampilkan label nama room di tengah polygon menggunakan `<v-text>`

3. **Di atas layer room**, user bisa klik titik untuk membuat polygon cabinet:
   - Warna polygon cabinet: **oranye** (`rgba(249, 115, 22, 0.25)` fill, `rgba(249, 115, 22, 0.8)` stroke)
   - Vertex circle: oranye (`#f97316`)
   - Selebihnya perilaku sama dengan `RoomPolygonDrawer` (zoom, pan, undo, reset, drag vertex)

4. **Emit:** `update:points` → Array `[{x, y}]`

**Wireframe:**

```
+------------------------------------------------------------+
| [Gambar Denah Lantai]                                       |
|                                                             |
|    ┌─── Room A ────┐    ┌─── Room B ────┐                  |
|    │  (abu tipis)   │    │  (abu tipis)   │                 |
|    │    ●━━━━●      │    │               │                  |
|    │    ┃LMRI┃      │    │               │                  |
|    │    ●━━━━●      │    │               │                  |
|    │  (oranye)      │    │               │                  |
|    └────────────────┘    └───────────────┘                  |
|                                                             |
| Titik: 4  Zoom: 100%     [Undo] [Reset] [Fit]              |
+------------------------------------------------------------+
```

**Instruksi implementasi:**
1. **Copy** file `RoomPolygonDrawer.vue` dan rename jadi `CabinetPolygonDrawer.vue`
2. Tambahkan prop `existingRooms`
3. Di dalam `<v-layer>`, setelah `<v-image>`, tambahkan loop untuk render room polygons:
   ```html
   <template v-for="(room, rIdx) in existingRooms" :key="'room-' + rIdx">
     <v-line :config="{
       points: room.points.flatMap(p => [p.x, p.y]),
       fill: 'rgba(100, 116, 139, 0.08)',
       stroke: 'rgba(100, 116, 139, 0.3)',
       strokeWidth: 1 / scale,
       closed: true,
       listening: false
     }" />
     <v-text :config="{
       x: Math.min(...room.points.map(p => p.x)),
       y: Math.min(...room.points.map(p => p.y)),
       text: room.name,
       fontSize: 12 / scale,
       fill: 'rgba(100, 116, 139, 0.5)',
       listening: false
     }" />
   </template>
   ```
4. Ubah warna polygon dan vertex dari hijau ke oranye
5. Ubah label dari "Koordinat Ruangan" menjadi "Koordinat Lemari"

### C2. Tambah Kolom Keterangan & Door Count di DataTable

**File:** `frontend/src/views/CabinetsView.vue`

- Tambah `<Column>` untuk `keterangan` setelah kolom "Nama Lemari":
  - Tampilkan text biasa, truncate jika panjang (`max-width: 200px`, CSS `truncate`)
- Tambah `<Column>` untuk `door_count` setelah kolom keterangan:
  - Header: "Jumlah Pintu"
  - Tampilkan badge: `4 × 3 = 12 pintu`

### C3. Tambah Input Keterangan & Door Count di Dialog Form

**File:** `frontend/src/views/CabinetsView.vue`

- Setelah field "Nama Lemari", tambahkan:
  1. **Keterangan** — `<Textarea>` opsional, placeholder: "Catatan tambahan tentang lemari (opsional)"
  2. **Jumlah Pintu** — `<InputText>` format `X * Y`, placeholder: "Contoh: 4 * 3"
     - Validasi regex di frontend: `/^\d+\s*\*\s*\d+$/`
     - Tampilkan preview: "= 12 pintu" di bawah input (hitung X × Y)

### C4. Integrasikan `CabinetPolygonDrawer` ke Dialog Cabinet

**File:** `frontend/src/views/CabinetsView.vue`

1. **Hapus** field input text JSON koordinat (`<InputText id="cabinet-points" ...>`)
2. **Ganti** dengan `<CabinetPolygonDrawer>`:
   - Tampilkan **hanya jika user sudah memilih room** dari dropdown
   - Ambil `floorImageUrl` dari floor milik room yang dipilih
   - Ambil `existingRooms` → filter semua rooms yang ada di floor yang sama
   - Bind `v-model:points` ke `cabinet.points`
3. Perbesar dialog width menjadi `900px`
4. Pada saat `openNew` / `editCabinet`, juga fetch rooms jika belum ter-load

**Layout dialog setelah modifikasi:**

```
+----------------------------------------------+
| Detail Lemari                          [✕]   |
|----------------------------------------------|
| Ruangan:    [▼ Pilih ruangan...]             |
| Nama:       [___________________________]    |
| Keterangan: [___________________________]    |
| Jml Pintu:  [4 * 3    ] = 12 pintu           |
|                                              |
| Koordinat Lemari:                            |
| +------------------------------------------+|
| | [Denah lantai + polygon room tipis]       ||
| | [Klik untuk membuat polygon lemari]       ||
| +------------------------------------------+|
| Titik: 4   [Undo] [Reset] [Fit]             |
|                                              |
| ☐ Perlu Review Koordinat                    |
|----------------------------------------------|
|                      [Batal]  [Simpan]       |
+----------------------------------------------+
```

---

## Bagian D: Tugas Frontend — Cabinet Slots (Pintu Lemari)

### D1. Visualisasi Pintu Lemari (HTML+CSS Grid)

**File:** `frontend/src/views/CabinetSlotsView.vue` (atau buat komponen baru `CabinetDoorGrid.vue`)

Saat user memilih lemari yang sudah punya `door_count` (contoh `4 * 3`), render grid visualisasi pintu:

```
+--------------------------------------------------+
| Visualisasi Pintu — Lemari A1 (4 × 3 = 12 pintu) |
|--------------------------------------------------|
| +------+ +------+ +------+ +------+             |
| |  01  | |  02  | |  03  | |  04  |  ← Baris 1  |
| | PIC: | | PIC: | | PIC: | | PIC: |             |
| | Andi | | Budi | |  —   | |  —   |             |
| |[aktf]| |[aktf]| |[aktf]| |[rsak]|             |
| +------+ +------+ +------+ +------+             |
| +------+ +------+ +------+ +------+             |
| |  05  | |  06  | |  07  | |  08  |  ← Baris 2  |
| +------+ +------+ +------+ +------+             |
| +------+ +------+ +------+ +------+             |
| |  09  | |  10  | |  11  | |  12  |  ← Baris 3  |
| +------+ +------+ +------+ +------+             |
+--------------------------------------------------+
```

**Cara implementasi:**
1. Parse `door_count` → ambil `cols` dan `rows` dari format `X * Y`
2. Buat CSS Grid dengan `grid-template-columns: repeat(cols, 1fr)`, total cell = `cols × rows`
3. Setiap cell merender 1 pintu:
   - **Nomor pintu** (auto-generated: 01, 02, ..., atau nama custom jika sudah di-rename)
   - **Warna berdasarkan status:**
     - `aktif` → hijau muda (`bg-emerald-50 border-emerald-300`)
     - `nonaktif` → abu-abu (`bg-slate-100 border-slate-300`)
     - `rusak` → merah muda (`bg-red-50 border-red-300`)
   - Tampilkan daftar PIC (avatar kecil / nama)
   - Tampilkan tags sebagai badge kecil

### D2. Auto-Generate Cabinet Slots dari Door Count

Saat cabinet baru dibuat atau `door_count` diubah:
1. Parse `door_count` → hitung total pintu (X × Y)
2. Auto-create `cabinet_slots` sejumlah total pintu dengan nama default:
   - Format: `"01"`, `"02"`, ..., `"12"` (zero-padded 2 digit)
   - Status default: `aktif`
   - PIC: kosong (belum di-assign)
3. Jika `door_count` berubah di cabinet yang sudah ada slot:
   - Tampilkan konfirmasi: "Mengubah jumlah pintu akan menghapus slot lama. Lanjutkan?"
   - Jika Ya → hapus slot lama, buat slot baru

**Implementasi:**
- Bisa dilakukan di **backend** (Observer atau di Controller setelah create/update cabinet)
- Atau di **frontend** (setelah berhasil save cabinet, kirim batch create slot)
- **Rekomendasi:** Lakukan di backend via Observer `CabinetObserver` pada event `created` dan `updated`

### D3. Klik Pintu untuk Edit Detail

Saat user klik salah satu cell pintu di grid:

1. **Buka dialog** "Edit Pintu" dengan field:
   - **Nama/Nomor Pintu** — `<InputText>`, default: "01" dst, user bisa ganti jadi "A1" atau nama custom
   - **PIC** — `<MultiSelect>` pilih user (bisa lebih dari 1)
   - **Status** — `<Dropdown>` pilihan: Aktif, Nonaktif, Rusak
   - **Keterangan** — `<Textarea>` opsional
   - **Tags** — `<Chips>` input hashtag (user ketik lalu Enter untuk menambah)

2. **Layout dialog Edit Pintu:**

```
+------------------------------------------+
| Edit Pintu #03                     [✕]   |
|------------------------------------------|
| Nama/Nomor:  [03                 ]       |
| Status:      [▼ Aktif            ]       |
| PIC:         [▼ Pilih user... ×Andi ×Bu] |
| Keterangan:  [___________________]       |
| Tags:        [keuangan × ] [rahasia × ]  |
|              [ketik tag + Enter]         |
|------------------------------------------|
|                    [Batal]  [Simpan]     |
+------------------------------------------+
```

### D4. Tampilkan Kolom Keterangan & Status di DataTable Slots

**File:** `frontend/src/views/CabinetSlotsView.vue`

- Tambah `<Column>` untuk `keterangan` (truncate)
- Tambah `<Column>` untuk `status` — tampilkan sebagai badge warna:
  - `aktif` → badge hijau
  - `nonaktif` → badge abu-abu
  - `rusak` → badge merah
- Ubah kolom PIC dari single user menjadi **daftar user** (comma separated atau avatar group)
- Tambah `<Column>` untuk tags — tampilkan sebagai badge/chip kecil

### D5. Update Store `location.js`

**File:** `frontend/src/store/location.js`

- Update method `createCabinetSlot` dan `updateCabinetSlot` untuk mengirim `pic_user_ids` (array), `tags` (array), `status`, dan `keterangan`
- Pastikan `fetchCabinetSlots` menerima data baru dari API (pic_users array, tags, status, keterangan)

---

## Urutan Pengerjaan yang Disarankan

```
TAHAP 1 — Backend Database & API (kerjakan berurutan)
  1.  Migration: add_keterangan_and_door_count_to_cabinets_table
  2.  Migration: modify_cabinet_slots_add_keterangan_status_remove_pic
  3.  Migration: create_cabinet_slot_user_table
  4.  Migration: create_cabinet_slot_tags_table
  5.  Jalankan: php artisan migrate
  6.  Buat Model: CabinetSlotTag
  7.  Update Model: Cabinet ($fillable)
  8.  Update Model: CabinetSlot ($fillable, relasi picUsers, tags)
  9.  Update Request: StoreCabinetRequest, UpdateCabinetRequest
  10. Update Request: StoreCabinetSlotRequest, UpdateCabinetSlotRequest
  11. Update Resource: CabinetResource
  12. Update Resource: CabinetSlotResource
  13. Update Controller: CabinetController (eager load)
  14. Update Controller: CabinetSlotController (sync PIC & tags)
  15. (Opsional) Buat CabinetObserver untuk auto-generate slots dari door_count
  16. Test API via Postman

TAHAP 2 — Frontend Cabinets
  17. Buat komponen CabinetPolygonDrawer.vue (copy dari RoomPolygonDrawer, modifikasi)
  18. Tambah kolom keterangan & door_count di DataTable CabinetsView
  19. Tambah input keterangan & door_count di dialog form CabinetsView
  20. Integrasikan CabinetPolygonDrawer ke dialog (ganti input JSON)
  21. Perbesar dialog, atur layout

TAHAP 3 — Frontend Cabinet Slots
  22. Buat komponen CabinetDoorGrid.vue (visualisasi pintu HTML+CSS grid)
  23. Integrasikan CabinetDoorGrid ke CabinetSlotsView
  24. Buat dialog "Edit Pintu" dengan MultiSelect PIC, Chips tags, Dropdown status
  25. Tambah kolom keterangan, status, tags di DataTable CabinetSlotsView
  26. Update store location.js untuk field baru

TAHAP 4 — Testing
  27. Test: Buat cabinet baru via KonvaJS → pastikan polygon tersimpan
  28. Test: Isi door_count → pastikan slots auto-generated
  29. Test: Klik pintu di grid → edit nama, PIC, status, tags → simpan
  30. Test: Ubah door_count → konfirmasi reset slots
  31. Test end-to-end lengkap
```

---

## Kriteria Selesai (Definition of Done)

### Backend
- [ ] Migration berhasil, kolom `keterangan` & `door_count` ada di tabel `cabinets`
- [ ] Migration berhasil, kolom `keterangan` & `status` ada di tabel `cabinet_slots`, kolom `pic_user_id` dihapus
- [ ] Tabel `cabinet_slot_user` terbuat dengan FK & unique constraint
- [ ] Tabel `cabinet_slot_tags` terbuat dengan FK
- [ ] Model `CabinetSlotTag` dibuat dengan relasi
- [ ] API Cabinet menerima & mengembalikan field `keterangan`, `door_count`, `slots`
- [ ] API Cabinet Slot menerima & mengembalikan field `keterangan`, `status`, `pic_users` (array), `tags` (array)
- [ ] Sync multi-PIC via `pic_user_ids` berfungsi di store & update
- [ ] Sync tags berfungsi di store & update

### Frontend — Cabinets
- [ ] Komponen `CabinetPolygonDrawer.vue` menampilkan denah lantai + polygon room (tipis) + polygon cabinet (oranye)
- [ ] User bisa klik di atas denah untuk membuat polygon lemari
- [ ] Dialog form cabinet sudah menggunakan KonvaJS (input JSON dihapus)
- [ ] Kolom `keterangan` dan `door_count` tampil di DataTable
- [ ] Input `door_count` format `X * Y` dengan preview total pintu

### Frontend — Cabinet Slots
- [ ] Visualisasi grid pintu sesuai `door_count` (HTML+CSS grid)
- [ ] Auto-numbering pintu (01, 02, ...)
- [ ] Warna pintu sesuai status (hijau/abu/merah)
- [ ] Klik pintu → buka dialog edit (nama, PIC multi-select, status, keterangan, tags)
- [ ] Tags menggunakan Chips input
- [ ] Kolom status, keterangan, tags tampil di DataTable

---

## Catatan Teknis

- Package `konva@^10.3.0` dan `vue-konva@^3.4.0` sudah ada di `package.json`
- Komponen `RoomPolygonDrawer.vue` sudah ada sebagai referensi — **copy dan modifikasi** untuk cabinet
- URL gambar denah lantai ada di field `floor_plan_image` dari object floor (ambil dari `locationStore.floors`)
- Untuk mengambil rooms dari floor yang dipilih: filter `locationStore.rooms` berdasarkan `floor_id` dari room yang dipilih di dropdown
- Format `door_count` disimpan sebagai string `"X * Y"` — parsing di frontend: `const [cols, rows] = doorCount.split('*').map(s => parseInt(s.trim()))`
- Untuk auto-generate slots, gunakan Laravel Observer agar logic tersentralisasi
- `MultiSelect` dari PrimeVue digunakan untuk pilih multi-PIC
- `Chips` dari PrimeVue digunakan untuk input tags
- Pastikan saat delete cabinet, semua slots, pivot PIC, dan tags ikut terhapus (cascadeOnDelete)
