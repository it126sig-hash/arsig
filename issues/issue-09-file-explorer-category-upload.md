# Issue #09 — File Explorer: Kategori & Upload Arsip Terintegrasi

## Ringkasan

Implementasi halaman **File Explorer** yang menampilkan hierarki kategori PT → Kategori → Sub-kategori dalam bentuk file explorer (seperti Windows Explorer / VS Code sidebar), dilengkapi dengan tombol **Upload Arsip** yang tertanam langsung di dalam panel kategori tersebut.

**Aturan kunci:** Ketika user menekan tombol upload di dalam sebuah node kategori, form upload arsip otomatis meng-*pre-select* kategori tersebut — user tidak perlu memilih kategori lagi di dalam form.

---

## Referensi

- `prd/PRD-02-FEATURES.md` → F-02 (File Explorer), F-03 (Manajemen Arsip)
- `prd/PRD-02-FEATURES.md` → 4.2 Alur Upload Arsip
- Stack: Vue 3 `<script setup>`, PrimeVue `Tree` / `TreeTable`, Pinia, Axios via `src/api/`

---

## Scope Pekerjaan

Issue ini **hanya** mencakup:
1. UI File Explorer (navigasi hierarki kategori)
2. Tombol Upload Arsip yang terintegrasi di dalam File Explorer
3. Form Upload Arsip (Dialog) dengan kategori yang sudah terpilih otomatis
4. Backend API: endpoint kategori & endpoint upload arsip

Issue ini **tidak** mencakup: fitur Download, Physical Storage Map, Push Notification, atau Activity Log.

---

## Tugas Backend (Laravel API)

### 1. Buat Model `Category`

**File:** `api/app/Models/Category.php`

- Tabel: `categories` (sudah ada di database dari migrasi sebelumnya)
- Kolom penting: `id`, `parent_id` (nullable, self-referential), `name`, `company_id` (PT), `master_category_id` (nullable, untuk salinan dari kategori master)
- Relasi:
  - `parent()` → `belongsTo(Category::class, 'parent_id')`
  - `children()` → `hasMany(Category::class, 'parent_id')`
  - `archives()` → `hasMany(Archive::class)`

### 2. Buat Model `Archive`

**File:** `api/app/Models/Archive.php`

- Tabel: `archives`
- Kolom penting: `id`, `category_id`, `name`, `file_number`, `file_path`, `type` (`digital` / `physical_only`), `privacy` (`direct_download` / `request_to_pic`), `published_date`, `pic_user_id`, `hashtags` (JSON), `expire_date`, `reminder_date`
- Relasi:
  - `category()` → `belongsTo(Category::class)`
  - `pic()` → `belongsTo(User::class, 'pic_user_id')`

### 3. Buat API Kategori

**File baru:** `api/app/Http/Controllers/CategoryController.php`

Endpoint yang diperlukan:

| Method | URL | Deskripsi |
|--------|-----|-----------|
| GET | `/api/v1/categories` | Ambil semua kategori (terstruktur sebagai tree, sudah include children) |
| POST | `/api/v1/categories` | Tambah kategori baru |
| PUT | `/api/v1/categories/{id}` | Edit nama kategori |
| DELETE | `/api/v1/categories/{id}` | Hapus kategori (hanya jika tidak ada arsip di dalamnya) |

**Logika `GET /api/v1/categories`:**
- Query dari root (di mana `parent_id IS NULL`) dengan eager load `children.children` (2 level kedalaman)
- Kembalikan sebagai nested array yang bisa langsung dipakai oleh PrimeVue `<Tree>`

**Contoh format response untuk Tree:**
```json
{
  "success": true,
  "data": [
    {
      "key": "1",
      "label": "Keuangan",
      "data": { "id": 1, "name": "Keuangan" },
      "children": [
        {
          "key": "3",
          "label": "Laporan Bulanan",
          "data": { "id": 3, "name": "Laporan Bulanan" },
          "children": []
        }
      ]
    }
  ],
  "message": "Berhasil.",
  "errors": null
}
```

**File baru:** `api/app/Http/Requests/StoreCategoryRequest.php`
- Validasi: `name` required|string|max:255, `parent_id` nullable|exists:categories,id

**File baru:** `api/app/Services/CategoryService.php`
- Pindahkan semua logika bisnis dari controller ke sini
- Method: `getTree()`, `store(array $data)`, `update(Category $category, array $data)`, `destroy(Category $category)`

### 4. Buat API Archive (Upload)

**File baru:** `api/app/Http/Controllers/ArchiveController.php`

Endpoint yang diperlukan untuk issue ini:

| Method | URL | Deskripsi |
|--------|-----|-----------|
| POST | `/api/v1/archives` | Upload arsip baru |

**Logika upload:**
- Request menggunakan `multipart/form-data`
- Jika ada `file`, simpan ke `storage/app/private/archives/{company_id}/{year}/`
- Simpan metadata ke tabel `archives`
- Kembalikan response sukses dengan data arsip yang baru dibuat

**File baru:** `api/app/Http/Requests/StoreArchiveRequest.php`

Validasi field:
```
name           → required, string, max:255
file_number    → nullable, string
category_id    → required, exists:categories,id
published_date → required, date
type           → required, in:digital,physical_only
privacy        → required, in:direct_download,request_to_pic
pic_user_id    → required, exists:users,id
hashtags       → nullable, array
file           → required_if:type,digital | file | mimes:pdf,doc,docx,jpg,png | max:20480
expire_date    → nullable, date
reminder_date  → required_with:expire_date | nullable, date
```

**Aturan bisnis:**
- Jika `type = physical_only`, field `file` TIDAK wajib (dan jangan simpan `file_path`)
- Jika `expire_date` diisi, maka `reminder_date` WAJIB diisi juga
- Jangan buat arsip tipe `placeholder` dengan `file_path`

**File baru:** `api/app/Services/ArchiveService.php`
- Method: `store(array $data, ?UploadedFile $file): Archive`

### 5. Daftarkan Routes

**File:** `api/routes/api.php`

Tambahkan di dalam grup middleware `auth:api`:
```php
// Categories
Route::apiResource('categories', CategoryController::class);

// Archives
Route::post('archives', [ArchiveController::class, 'store']);
```

---

## Tugas Frontend (Vue 3 + PrimeVue)

### 6. Buat API Module Kategori

**File baru:** `frontend/src/api/categoryApi.js`

```js
// Berisi fungsi-fungsi untuk memanggil API kategori
// Gunakan instance axios dari src/services/api.js yang sudah ada
export function fetchCategoryTree() { ... }   // GET /api/v1/categories
export function createCategory(data) { ... }  // POST /api/v1/categories
export function updateCategory(id, data) { ... } // PUT /api/v1/categories/{id}
export function deleteCategory(id) { ... }    // DELETE /api/v1/categories/{id}
```

### 7. Buat API Module Arsip

**File baru:** `frontend/src/api/archiveApi.js`

```js
export function uploadArchive(formData) { ... } // POST /api/v1/archives
// Kirim sebagai multipart/form-data
// Header: { 'Content-Type': 'multipart/form-data' }
```

### 8. Buat Pinia Store untuk Kategori

**File baru:** `frontend/src/store/categoryStore.js`

State yang perlu disimpan:
- `categoryTree` → array (hasil dari API, format Tree PrimeVue)
- `selectedCategory` → object (node kategori yang sedang aktif/dipilih)
- `isLoading` → boolean

Actions:
- `loadCategoryTree()` → panggil `fetchCategoryTree()` dari `categoryApi.js`, simpan hasilnya ke `categoryTree`
- `setSelectedCategory(node)` → update `selectedCategory`

### 9. Buat Halaman File Explorer

**File baru:** `frontend/src/views/FileExplorerView.vue`

**Layout halaman ini:**
```
+--------------------------------------------------+
| [Sidebar Panel]          | [Main Content Panel]   |
|                          |                        |
| [🔍 Search kategori]    |  (Konten halaman       |
|                          |   sesuai kategori      |
| 📁 Keuangan             |   yang dipilih,        |
|   📁 Laporan Bulanan    |   untuk sekarang       |
|     📄 ...              |   bisa kosong dulu)    |
|                          |                        |
| [+ Tambah Kategori]     |                        |
| [⬆ Upload Arsip]        |                        |
+--------------------------------------------------+
```

**Aturan penting untuk layout ini:**

1. **Sidebar (kiri):** Tampilkan hierarki kategori menggunakan komponen PrimeVue `<Tree>`.
   - Setiap node kategori bisa di-klik untuk memilihnya (highlight aktif)
   - Tampilkan ikon folder (📁) untuk node yang punya children, ikon dokumen untuk leaf node

2. **Tombol aksi di bawah Tree:**
   - **"+ Tambah Sub-kategori"** → hanya aktif jika ada kategori yang dipilih; tombol ini membuka dialog kecil untuk input nama sub-kategori baru
   - **"+ Tambah Kategori Root"** → selalu aktif; untuk tambah kategori di level paling atas
   - **"⬆ Upload Arsip"** → hanya aktif jika ada kategori yang dipilih; tombol ini membuka Dialog form upload arsip

3. **Ketika tombol "Upload Arsip" diklik:**
   - Ambil `selectedCategory` dari Pinia store
   - Buka Dialog form upload
   - Di dalam form, field "Kategori" sudah otomatis terisi dengan kategori yang dipilih tersebut
   - User tidak perlu memilih kategori lagi (field ini bisa dibuat read-only / tampilan saja, bukan input)

### 10. Buat Komponen Dialog Upload Arsip

**File baru:** `frontend/src/components/ArchiveUploadDialog.vue`

**Props yang diterima:**
- `visible` → Boolean (apakah dialog terbuka)
- `preselectedCategory` → Object (kategori yang sudah dipilih, dari Pinia store)

**Emits:**
- `update:visible` → untuk menutup dialog (v-model pattern)
- `upload-success` → setelah upload berhasil, agar halaman induk bisa refresh data

**Field-field di dalam form:**

| Field | Komponen PrimeVue | Keterangan |
|-------|-------------------|------------|
| Kategori | Tampilan teks biasa (read-only) | Otomatis terisi dari `preselectedCategory` |
| Nama Arsip | `<InputText>` | Wajib diisi |
| Nomor File | `<InputText>` | Opsional |
| Tipe Arsip | `<Select>` | Pilihan: `Digital`, `Physical Only` |
| Tanggal Terbit | `<DatePicker>` | Wajib diisi |
| Privacy | `<Select>` | Pilihan: `Direct Download`, `Request ke PIC` |
| PIC | `<Select>` | Data dari API users |
| Hashtag | `<Chips>` atau `<InputText>` dengan enter | Opsional |
| File | `<FileUpload>` | Wajib jika tipe `Digital`, sembunyikan jika `Physical Only` |
| Tanggal Kadaluarsa | `<DatePicker>` | Opsional |
| Tanggal Reminder | `<DatePicker>` | Wajib muncul & wajib diisi jika Tanggal Kadaluarsa diisi |

**Logika submit:**
1. Buat `FormData` dari semua field
2. Panggil `uploadArchive(formData)` dari `archiveApi.js`
3. Jika sukses: tampilkan `<Toast>` sukses, emit `upload-success`, tutup dialog
4. Jika error: tampilkan `<Toast>` error dengan pesan dari API

### 11. Daftarkan Route Halaman

**File:** `frontend/src/router/index.js`

Tambahkan route baru (di dalam grup yang butuh auth):
```js
{
  path: '/file-explorer',
  name: 'file-explorer',
  component: () => import('@/views/FileExplorerView.vue'),
  meta: { requiresAuth: true }
}
```

### 12. Tambahkan Menu di Sidebar/Navigasi

**File:** `frontend/src/layouts/AppLayout.vue` (atau file layout yang digunakan)

Tambahkan link menu:
- Label: "File Explorer"
- Icon: folder
- Route: `/file-explorer`

---

## Urutan Pengerjaan yang Disarankan

Ikuti urutan ini agar tidak ada dependency yang terputus:

```
1. Backend: Model Category + CategoryService + CategoryController + Route
2. Backend: Model Archive + ArchiveService + ArchiveController + Route
3. Backend: Test endpoint via Postman/curl untuk memastikan response benar
4. Frontend: categoryApi.js + archiveApi.js
5. Frontend: categoryStore.js (Pinia)
6. Frontend: FileExplorerView.vue (mulai dari Tree + tombol aksi saja dulu)
7. Frontend: ArchiveUploadDialog.vue
8. Frontend: Integrasikan Dialog ke FileExplorerView
9. Frontend: Daftarkan route + tambah menu navigasi
10. Testing end-to-end: pilih kategori → klik upload → submit → cek data di DB
```

---

## Kriteria Selesai (Definition of Done)

- [ ] Halaman `/file-explorer` dapat diakses setelah login
- [ ] Hierarki kategori tampil sebagai tree (expandable, collapse-able)
- [ ] User dapat mengklik node kategori untuk memilihnya (ada visual highlight aktif)
- [ ] Tombol "Upload Arsip" hanya aktif jika ada kategori yang dipilih
- [ ] Ketika tombol "Upload Arsip" diklik, Dialog terbuka dengan kategori sudah terisi otomatis
- [ ] Form upload dapat disubmit dan data tersimpan ke database
- [ ] Jika tipe `Physical Only`, field file tersembunyi dan tidak wajib
- [ ] Jika `expire_date` diisi, field `reminder_date` langsung muncul dan wajib diisi
- [ ] Toast notifikasi muncul setelah upload sukses atau gagal
- [ ] Tambah kategori baru berfungsi dan langsung muncul di tree tanpa reload halaman

---

## Catatan Teknis

- Gunakan `src/api/` untuk semua pemanggilan Axios — **JANGAN** panggil axios langsung di dalam `.vue` file
- Semua logika bisnis di backend ada di `Service` — **JANGAN** tulis logika di `Controller`
- Gunakan `BaseController::successResponse()` dan `errorResponse()` untuk semua response API
- Access token sudah dihandle oleh Axios interceptor — tidak perlu tambah header manual di setiap call
- Untuk PrimeVue `<Tree>`, pastikan format data menggunakan `key`, `label`, `data`, `children` sesuai dokumentasi PrimeVue
