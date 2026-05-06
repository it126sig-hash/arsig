# Sub-Issue #09a — Manajemen Perusahaan (Company) sebelum Kategori

**Parent Issue:** [#13 — File Explorer: Kategori & Upload Arsip Terintegrasi](https://github.com/it126sig-hash/arsig/issues/13)

---

## Latar Belakang

Tabel `categories` memiliki kolom `company_id` (Foreign Key ke tabel `companies`).
Artinya, **sebelum bisa membuat kategori, harus sudah ada data perusahaan (company) di database**.

Issue ini membangun fitur CRUD untuk manajemen Company (`PT / Perusahaan`) yang akan menjadi prasyarat sebelum Issue #13 bisa digunakan sepenuhnya.

---

## Scope Pekerjaan

Issue ini mencakup:
1. Backend API CRUD untuk resource `Company`
2. Halaman manajemen Company di frontend
3. Integrasi pilihan Company ke File Explorer (dropdown pilih PT saat membuka halaman)

---

## Skema Database (sudah ada)

Tabel `companies` (dari migration yang sudah ada):

| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | Auto increment |
| name | varchar | Nama perusahaan / PT |
| description | varchar nullable | Deskripsi singkat |
| created_at | timestamp | - |
| updated_at | timestamp | - |

> Catatan: Kolom `code` dan `address` ada di Model tapi **belum ada di migration**. Untuk sekarang, cukup gunakan `name` dan `description` saja sesuai migration.

---

## Tugas Backend (Laravel API)

### 1. Perbaiki Model `Company`

**File:** `api/app/Models/Company.php`

Sesuaikan `$fillable` agar hanya menggunakan kolom yang benar-benar ada di tabel:

```php
protected $fillable = [
    'name',
    'description',
];
```

Hapus `code` dan `address` dari `$fillable` karena tidak ada di migration.

### 2. Buat `CompanyService`

**File baru:** `api/app/Services/CompanyService.php`

Method yang diperlukan:

```php
public function getAll(): Collection          // Ambil semua company
public function store(array $data): Company   // Buat company baru
public function update(Company $company, array $data): bool  // Update company
public function destroy(Company $company): bool              // Hapus company
```

Aturan bisnis untuk `destroy()`:
- Jika company masih punya `categories`, lempar Exception dengan pesan yang jelas.
- Gunakan `abort(400)` atau `throw new \Exception(...)` — jangan return error manual.

### 3. Buat `StoreCompanyRequest`

**File baru:** `api/app/Http/Requests/StoreCompanyRequest.php`

Validasi:
```
name        → required | string | max:255 | unique:companies,name
description → nullable | string | max:500
```

### 4. Buat `UpdateCompanyRequest`

**File baru:** `api/app/Http/Requests/UpdateCompanyRequest.php`

Validasi:
```
name        → required | string | max:255 | unique:companies,name,{id}
description → nullable | string | max:500
```

(Gunakan `Rule::unique('companies', 'name')->ignore($this->company)` agar nama bisa tetap sama saat update.)

### 5. Buat `CompanyController`

**File baru:** `api/app/Http/Controllers/CompanyController.php`

Extend dari `BaseController`. Endpoint yang diperlukan:

| Method | URL | Deskripsi |
|--------|-----|-----------|
| GET | `/api/v1/companies` | List semua perusahaan |
| POST | `/api/v1/companies` | Tambah perusahaan baru |
| PUT | `/api/v1/companies/{id}` | Update data perusahaan |
| DELETE | `/api/v1/companies/{id}` | Hapus perusahaan |

Semua method harus menggunakan `successResponse()` / `errorResponse()` dari `BaseController`.

Contoh struktur:
```php
public function index(): JsonResponse
{
    return $this->successResponse($this->service->getAll());
}

public function store(StoreCompanyRequest $request): JsonResponse
{
    $company = $this->service->store($request->validated());
    return $this->successResponse($company, 'Perusahaan berhasil dibuat.', 201);
}
```

### 6. Daftarkan Route

**File:** `api/routes/api.php`

Tambahkan di dalam grup middleware `auth:sanctum`:
```php
Route::apiResource('companies', \App\Http\Controllers\CompanyController::class);
```

---

## Tugas Frontend (Vue 3 + PrimeVue)

### 7. Buat `companyApi.js`

**File baru:** `frontend/src/api/companyApi.js`

```js
import api from '@/services/api'

export function fetchCompanies() { ... }          // GET /api/v1/companies
export function createCompany(data) { ... }       // POST /api/v1/companies
export function updateCompany(id, data) { ... }   // PUT /api/v1/companies/{id}
export function deleteCompany(id) { ... }         // DELETE /api/v1/companies/{id}
```

### 8. Buat Halaman Company Manager

**File baru:** `frontend/src/views/CompanyView.vue`

Layout halaman ini (tabel + form inline atau dialog):

```
+-----------------------------------------------+
| Manajemen Perusahaan (PT)         [+ Tambah]  |
+-----------------------------------------------+
| Nama Perusahaan    | Deskripsi   | Aksi        |
|--------------------|-------------|-------------|
| PT Maju Jaya       | ...         | Edit Hapus  |
| CV Berkah Abadi    | ...         | Edit Hapus  |
+-----------------------------------------------+
```

Komponen PrimeVue yang digunakan:
- **Tabel:** `<DataTable>` + `<Column>`
- **Form tambah/edit:** `<Dialog>` dengan `<InputText>` dan `<Textarea>`
- **Konfirmasi hapus:** `<ConfirmDialog>` via `useConfirm()`
- **Notifikasi:** `<Toast>` via `useToast()`

**Alur kerja:**
1. Saat halaman dibuka → panggil `fetchCompanies()`, tampilkan di `<DataTable>`
2. Klik tombol "Tambah" → buka `<Dialog>` form kosong
3. Klik "Edit" di baris tabel → buka `<Dialog>` form yang sudah terisi data baris tersebut
4. Klik "Hapus" → tampilkan `<ConfirmDialog>` → jika confirm → panggil `deleteCompany(id)`
5. Setelah simpan/hapus sukses → refresh data tabel + tampilkan `<Toast>`

### 9. Integrasikan Company ke File Explorer

**File yang dimodifikasi:** `frontend/src/views/FileExplorerView.vue`

Tambahkan **dropdown pilih perusahaan** di bagian atas sidebar panel, sebelum tree kategori tampil:

```
+---------------------------+
| File Explorer             |
| [Pilih PT: ▾ PT Maju Jaya]|  ← Dropdown ini
| [🔍 Cari kategori...]    |
| 📁 Keuangan               |
|   📁 Laporan Bulanan      |
+---------------------------+
```

Logika:
- Saat halaman dibuka, panggil `fetchCompanies()` untuk isi dropdown
- Jika user pilih company dari dropdown → panggil `categoryStore.loadCategoryTree(selectedCompanyId)`
- Tree kategori akan otomatis ter-refresh sesuai company yang dipilih
- `company_id` yang terpilih juga diteruskan ke tombol "Tambah Kategori" dan "Upload Arsip" agar tersimpan dengan `company_id` yang benar

Ganti baris hardcode `company_id: 1` menjadi nilai dinamis dari dropdown.

### 10. Perbaiki Store Kategori

**File yang dimodifikasi:** `frontend/src/store/category.js`

Tambahkan state `selectedCompanyId` agar bisa dibagi ke seluruh komponen:

```js
state: () => ({
    categoryTree: [],
    selectedCategory: null,
    selectedCompanyId: null, // ← tambahkan ini
    isLoading: false
}),
actions: {
    setSelectedCompany(companyId) {
        this.selectedCompanyId = companyId
        this.selectedCategory = null // reset pilihan kategori saat ganti company
        this.loadCategoryTree(companyId)
    }
}
```

### 11. Daftarkan Route Company

**File:** `frontend/src/router/index.js`

Tambahkan di dalam children AppLayout:
```js
{
    path: 'companies',
    name: 'companies',
    component: () => import('@/views/CompanyView.vue')
}
```

### 12. Tambahkan Menu di Sidebar

**File:** `frontend/src/components/AppSidebar.vue`

Tambahkan di dalam array `menuItems` atau buat section baru "Administrasi":
```js
{ to: '/companies', label: 'Perusahaan (PT)', icon: 'pi pi-building' }
```

---

## Urutan Pengerjaan yang Disarankan

```
1. Backend: Perbaiki Model Company ($fillable)
2. Backend: Buat CompanyService
3. Backend: Buat StoreCompanyRequest + UpdateCompanyRequest
4. Backend: Buat CompanyController
5. Backend: Daftarkan route di api.php
6. Backend: Test via Postman — GET, POST, PUT, DELETE /api/v1/companies
7. Frontend: companyApi.js
8. Frontend: CompanyView.vue (DataTable + Dialog CRUD)
9. Frontend: Modifikasi categoryStore.js (tambah selectedCompanyId)
10. Frontend: Modifikasi FileExplorerView.vue (tambah dropdown pilih company)
11. Frontend: Daftarkan route + tambah menu di sidebar
12. Testing end-to-end: tambah company → buka File Explorer → pilih PT → tambah kategori → upload arsip
```

---

## Kriteria Selesai (Definition of Done)

- [ ] Endpoint `GET /api/v1/companies` mengembalikan list perusahaan
- [ ] Endpoint `POST /api/v1/companies` bisa membuat perusahaan baru
- [ ] Endpoint `PUT /api/v1/companies/{id}` bisa mengupdate perusahaan
- [ ] Endpoint `DELETE /api/v1/companies/{id}` gagal dengan pesan jelas jika company masih punya kategori
- [ ] Halaman `/companies` menampilkan daftar perusahaan dalam DataTable
- [ ] Form tambah/edit perusahaan berfungsi via Dialog
- [ ] Konfirmasi hapus muncul sebelum delete
- [ ] Halaman File Explorer punya dropdown pilih perusahaan
- [ ] Tree kategori otomatis berubah saat user pilih perusahaan berbeda
- [ ] Tombol "Tambah Kategori" dan "Upload Arsip" menggunakan `company_id` yang dipilih (tidak lagi hardcode `1`)

---

## Catatan Teknis

- Kolom `code` dan `address` **tidak ada di migration** — jangan masukkan ke `$fillable` atau validasi
- Jika ke depan perlu menambah kolom tersebut, buat migration baru: `add_code_address_to_companies_table`
- Dropdown perusahaan di File Explorer sebaiknya **persist ke localStorage** agar pilihan tidak hilang saat refresh halaman
- Semua hardcode `company_id: 1` di `FileExplorerView.vue`, `ArchiveUploadDialog.vue`, dan store harus diganti dengan nilai dinamis dari state store
