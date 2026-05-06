---
trigger: always_on
---

# ARSIG — Project Rules

## Stack Wajib

Project ini menggunakan stack berikut. Jangan pernah menyimpang dari ini:

| Layer | Teknologi |
|---|---|
| Backend | Laravel 11, PHP 8.2+, `tymon/jwt-auth` |
| Frontend | Vue 3 + Vite, Composition API (`<script setup>`) |
| UI | PrimeVue (BUKAN Bootstrap, BUKAN Tailwind) |
| State | Pinia |
| HTTP | Axios dengan interceptor |
| Mobile | Capacitor 6 (Android) |
| Database | MySQL 8 + Eloquent ORM |
| Queue | Laravel Queue + Jobs |

## Larangan Keras

- **JANGAN** gunakan CodeIgniter, Bootstrap
- **JANGAN** simpan access token di `localStorage` atau `sessionStorage` — wajib memory (Pinia)
- **JANGAN** buat Blade views, route web, atau middleware `web` — Laravel adalah pure REST API
- **JANGAN** tulis logika bisnis di Controller — pindahkan ke Service
- **JANGAN** kirim FCM notification secara synchronous — wajib via Laravel Queue + Job
- **JANGAN** buat arsip tipe `placeholder` dengan `file_path` atau relasi `archive_locations`
- **JANGAN** hardcode URL, secret, atau API key — semua via `.env`
- **JANGAN** gunakan FCM Legacy API — wajib FCM HTTP v1 API
- **JANGAN** gunakan Options API di Vue — wajib Composition API `<script setup>`
- **JANGAN** panggil axios langsung di component — semua API call di folder `src/api/`

## Arsitektur Backend

Layer pattern yang selalu diikuti:

```
FormRequest (validasi) → Controller (tipis) → Service (logika bisnis) → Eloquent Model
```

**Controller** — hanya: terima request, panggil service, return response  
**FormRequest** — semua validasi input untuk POST/PUT/PATCH  
**Service** — semua logika bisnis, orkestrasi, exception handling  
**Model** — definisi tabel, relasi, scope, fillable — tidak ada logika bisnis  
**Observer** — audit log otomatis, side effects (flag koordinat, propagasi kategori)  
**Job/Notification** — kirim FCM push notification async via Queue  
**Policy** — otorisasi per resource  

## Format Response API

Semua endpoint wajib menggunakan format ini:

```json
{
  "success": true,
  "data": {},
  "message": "Pesan sukses.",
  "errors": null
}
```

Gunakan method `successResponse()` dan `errorResponse()` dari BaseController — jangan `response()->json()` langsung.

## Arsitektur Frontend

```
src/api/          ← semua axios call per resource
src/components/   ← komponen reusable
src/composables/  ← useAuth, useToast, dll
src/pages/        ← satu file per halaman/route
src/stores/       ← Pinia stores
src/router/       ← index.js + route guard
```

## Komponen PrimeVue Standar

| Kebutuhan | Gunakan |
|---|---|
| Tabel | `<DataTable>` + `<Column>` |
| Modal/form | `<Dialog>` |
| Upload | `<FileUpload>` |
| Dropdown | `<Select>` |
| Tanggal | `<DatePicker>` |
| Notifikasi | `<Toast>` via `useToast()` |
| Konfirmasi hapus | `<ConfirmDialog>` via `useConfirm()` |
| Loading | `<ProgressSpinner>` / `<Skeleton>` |
| Tag/chip | `<Tag>` / `<Chip>` |
| Hierarki | `<Tree>` / `<TreeTable>` |

## Business Rules Kritis

| Kondisi | Yang Harus Terjadi |
|---|---|
| Arsip tipe `placeholder` | Tidak boleh ada `file_path` atau `archive_locations` |
| `expire_date` diisi | `reminder_date` wajib diisi juga |
| `floor_plan_image` diganti | Flag semua rooms & cabinets `needs_coordinate_review = true` via Observer |
| Kategori master berubah | Propagate ke semua kategori PT via Observer |
| Download request approved | Kirim notifikasi via `SendFcmNotification` Job (async) |
| Aktivitas arsip (upload/edit/hapus/download) | Catat di `archive_activity_logs` via Observer |
| Signed URL + download | Selalu validasi signed URL **dan** JWT session bersamaan |

## Konvensi Penulisan PHP

- `declare(strict_types=1)` di semua file PHP
- Constructor property promotion: `public function __construct(private readonly ArchiveService $service) {}`
- Return type declaration wajib di semua method Service dan Repository
- Nama method: `camelCase` | Nama class: `PascalCase` | Nama tabel/kolom: `snake_case`
- Inject dependency via constructor — jangan `app()` atau `resolve()` di dalam method
- Gunakan `abort(403)` / `abort(404)` — jangan return error manual dari Service

## Konvensi Penulisan JavaScript/Vue

- Selalu `<script setup>` — jangan Options API
- Nama file komponen: `PascalCase.vue`
- Nama file api/composable: `camelCase.js`
- Gunakan `async/await` — jangan `.then().catch()` berantai
- State global di Pinia — jangan `provide/inject` untuk state lintas banyak komponen

## Auth Rules

- Access token: disimpan di Pinia (memory) — expire 15 menit
- Refresh token: httpOnly cookie — expire 7 hari
- Axios interceptor: auto-refresh saat 401, `withCredentials: true`
- Route guard: cek `isAuthenticated` dari Pinia store sebelum render halaman
