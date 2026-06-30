# ARSIG — PRD-06: Technical Constraints & Setup

← [API Design](PRD-05-API.md) | [Mobile →](PRD-07-MOBILE.md)

---

## 9. Design & Technical Constraints

### 9.1 Auth & Security

- Access token disimpan hanya di memory (Pinia) — **tidak pernah** di `localStorage` atau `sessionStorage`.
- Refresh token disimpan di httpOnly cookie dengan flag `Secure` dan `SameSite=Strict`.
- Capacitor WebView memperlakukan cookie seperti browser biasa — httpOnly cookie berfungsi normal di Android.
- Signed URL selalu divalidasi bersamaan dengan JWT session.
- Semua endpoint Laravel yang membutuhkan auth dilindungi oleh `auth:api` middleware (`tymon/jwt-auth`).
- CORS dikonfigurasi di Laravel agar hanya menerima origin dari domain frontend dan Capacitor scheme.

### 9.2 Business Rules (Backend Laravel)

- Laravel dikonfigurasi sebagai **pure REST API** — hapus semua route web, tidak ada Blade views.
- Validasi input menggunakan **Laravel Form Request** di semua endpoint `POST` / `PUT` / `PATCH`.
- Arsip `placeholder` tidak boleh memiliki `file_path` atau `archive_locations` — validasi di `StoreArchiveRequest`.
- Jika `floor_plan_image` diganti, semua rooms dan cabinets di lantai itu otomatis di-flag `needs_coordinate_review = true` menggunakan **Eloquent Observer** atau **Event/Listener**.
- `reminder_date` wajib diisi jika `expire_date` diisi — validasi custom rule di Form Request.
- Approval oleh `root` dicatat secara eksplisit di `reviewed_by_user_id`.
- Kategori adalah salinan relasional dari master — perubahan propagate otomatis ke semua PT via **Observer**.
- Audit log (upload, edit, hapus, download, pindah lokasi, ganti PIC) dicatat otomatis via **Eloquent Observers**.

### 9.3 Frontend (Vue 3 + PrimeVue)

- Vue Router menggunakan **History Mode**.
- Nginx dikonfigurasi fallback ke `index.html` untuk semua route non-`/api/*`.
- Axios instance dikonfigurasi dengan:
  - **Request interceptor:** attach `Authorization: Bearer <token>`.
  - **Response interceptor:** auto-refresh pada 401, retry request asal.
- Route guard di Vue Router memeriksa access token di Pinia sebelum render halaman terproteksi.
- Konva.js untuk physical storage map — koordinat disimpan sebagai JSON di DB.
- UI didesain **mobile-first** mengingat mayoritas user akan mengakses via Android app.
- Gunakan **PrimeVue theme** yang mendukung dark/light mode dan mobile responsif.

---

### 9.4 Setup Backend (Laravel 11)

#### Prasyarat

- PHP 8.2+
- Composer
- MySQL 8+
- Node.js (untuk asset — opsional di API-only setup)

#### Instalasi

```bash
composer create-project laravel/laravel arsig-backend
cd arsig-backend
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

#### Konfigurasi `.env` Backend

```env
APP_NAME=ARSIG
APP_ENV=production
APP_URL=https://arsig.sanggarindah.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arsig_db
DB_USERNAME=arsig_user
DB_PASSWORD=secret

JWT_SECRET=             # generate dengan php artisan jwt:secret
JWT_TTL=15              # access token expire: 15 menit
JWT_REFRESH_TTL=10080   # refresh token expire: 7 hari (dalam menit)

FILESYSTEM_DISK=local   # atau 's3' untuk S3-compatible

FCM_SERVER_KEY=         # dari Firebase Console

QUEUE_CONNECTION=database  # atau redis jika tersedia
```

#### Setup CORS untuk Capacitor

```php
// config/cors.php
'allowed_origins' => [
    'https://arsig.sanggarindah.com',
    'capacitor://localhost',
    'http://localhost',
],
'supports_credentials' => true,  // wajib untuk httpOnly cookie
```

#### Route API

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::middleware('auth:api')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('device-token', [DeviceTokenController::class, 'store']);
            Route::delete('device-token', [DeviceTokenController::class, 'destroy']);
        });
    });

    Route::middleware('auth:api')->group(function () {
        Route::apiResource('archives', ArchiveController::class);
        Route::post('archives/{archive}/download', [ArchiveController::class, 'download']);
        Route::get('archives/{archive}/logs', [ArchiveController::class, 'logs']);
        Route::apiResource('master-categories', MasterCategoryController::class);
        Route::get('categories', [CategoryController::class, 'index']);
        Route::apiResource('floors', FloorController::class);
        Route::get('download-requests', [DownloadRequestController::class, 'index']);
        Route::patch('download-requests/{request}', [DownloadRequestController::class, 'review']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::patch('notifications/{id}/read', [NotificationController::class, 'markRead']);
    });
});
```

---

### 9.5 Setup Frontend (Vue 3 + PrimeVue)

#### Prasyarat

- Node.js LTS
- NPM atau PNPM

#### Instalasi

```bash
npm create vite@latest arsig-frontend -- --template vue
cd arsig-frontend
npm install
npm install primevue @primevue/themes primeicons
npm install pinia vue-router axios
npm install konva vue-konva
npm install @capacitor/core @capacitor/cli
npm install @capacitor/push-notifications @capacitor/camera @capacitor/filesystem capacitor-file-opener
```

#### Setup PrimeVue di `main.js`

```javascript
import { createApp } from 'vue'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'  // atau Lara, Nora
import 'primeicons/primeicons.css'

const app = createApp(App)
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.dark-mode',
        }
    }
})
```

#### Konfigurasi `.env` Frontend

```env
VITE_API_BASE_URL=https://arsig.sanggarindah.com/api/v1
```

> Pada Android (Capacitor), `VITE_API_BASE_URL` **wajib HTTPS** agar cookie `Secure` dan request tidak diblokir cleartext policy.

#### Scripts

```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview",
    "android:sync": "npm run build && npx cap sync android",
    "android:run": "npx cap run android"
  }
}
```
