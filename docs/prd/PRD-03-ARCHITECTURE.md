# ARSIG — PRD-03: Architecture

← [Features](PRD-02-FEATURES.md) | [Database →](PRD-04-DATABASE.md)

---

## 5. Architecture

### 5.1 Stack Teknologi

| Layer | Teknologi | Keterangan |
|---|---|---|
| Frontend / App Shell | Vue 3 + Vite | SPA — di-build jadi static files |
| Mobile Wrapper | Capacitor 6 | Wrap Vue build jadi Android APK |
| State Management | Pinia | Auth state, access token in-memory |
| HTTP Client | Axios | Interceptor untuk auto-refresh JWT |
| UI Framework | **PrimeVue** | Komponen kaya: DataTable, FileUpload, Dialog, Calendar, TreeTable, etc. |
| Canvas / Map | Konva.js | Physical storage map |
| Push Notification | Capacitor Push Notifications + FCM | Native Android push |
| Kamera | Capacitor Camera | Foto hardfile untuk arsip |
| File Download | Capacitor Filesystem + FileOpener | Simpan file ke /Downloads Android |
| Backend | **Laravel 11** (PHP 8.2+) | Pure REST API — tidak serve HTML, tidak ada Blade |
| Auth | **`tymon/jwt-auth`** | JWT access + refresh token untuk Laravel |
| Push (server-side) | **Laravel Notifications + Queue** | Laravel dispatch job → FCM HTTP v1 API |
| Database | MySQL 8 | Relasional, multi-company |
| File Storage | Local / S3-compatible | Laravel Filesystem — konfigurasi via `.env` |
| Web Server | Nginx + PHP-FPM | HTTPS wajib |

> **Pilihan Auth:** `tymon/jwt-auth` adalah library JWT paling populer untuk Laravel. Alternatif: `Laravel Sanctum` dengan stateless token jika tidak butuh refresh token via cookie. Rekomendasi untuk ARSIG: **`tymon/jwt-auth`** karena mendukung pattern access + refresh token yang sudah dirancang.

### 5.2 Deployment Architecture

```
┌─────────────────────────────────────────┐
│           User Device                   │
│                                         │
│  ┌─────────────────┐                    │
│  │  Android App    │  ← Play Store      │
│  │  (Capacitor     │    Internal Track  │
│  │   WebView)      │                    │
│  └────────┬────────┘                    │
│           │                             │
│  ┌────────▼────────┐                    │
│  │  Web Browser    │  ← Alternatif      │
│  └────────┬────────┘                    │
└───────────┼─────────────────────────────┘
            │ HTTPS
┌───────────▼─────────────────────────────┐
│              Nginx (HTTPS)              │
│                                         │
│  /* (non /api)  →  /dist/index.html     │
│  /api/*         →  PHP-FPM (Laravel)    │
└───────────┬─────────────────────────────┘
            │
┌───────────▼─────────────────────────────┐
│         Laravel 11 (REST API)           │
│                                         │
│  ├── MySQL Database (Eloquent ORM)      │
│  ├── File Storage (Laravel Filesystem)  │
│  ├── Queue Worker (Notifications)       │
│  └── Firebase FCM (push notification)  │
└─────────────────────────────────────────┘
```

### 5.3 Laravel Project Structure (API-only)

```
laravel-arsig/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── AuthController.php
│   │   │   ├── ArchiveController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── DownloadRequestController.php
│   │   │   ├── FloorController.php
│   │   │   └── NotificationController.php
│   │   ├── Middleware/
│   │   │   └── JwtMiddleware.php
│   │   └── Requests/
│   │       ├── StoreArchiveRequest.php
│   │       └── UpdateArchiveRequest.php
│   ├── Models/
│   │   ├── Archive.php
│   │   ├── Category.php
│   │   ├── User.php
│   │   └── ... (model per tabel)
│   ├── Services/
│   │   ├── ArchiveService.php
│   │   ├── DownloadService.php
│   │   └── NotificationService.php
│   ├── Observers/
│   │   └── ArchiveObserver.php   ← untuk audit log otomatis
│   ├── Notifications/
│   │   ├── DownloadRequestReceived.php
│   │   └── DownloadRequestApproved.php
│   └── Jobs/
│       └── SendFcmNotification.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php                   ← semua route di sini
└── config/
    ├── jwt.php
    └── filesystems.php
```

### 5.4 JWT Token Strategy

| | Access Token | Refresh Token |
|---|---|---|
| **Expire** | 15 menit | 7 hari |
| **Penyimpanan** | Memory (Pinia store) | httpOnly Cookie |
| **Dikirim via** | `Authorization: Bearer` header | Cookie otomatis oleh browser/WebView |
| **Bisa diakses JS** | Ya (hanya di tab/session aktif) | Tidak (httpOnly) |
| **Digunakan untuk** | Semua request API | Refresh access token saja |
| **Laravel Handler** | `tymon/jwt-auth` middleware | Custom `RefreshTokenController` |

> **Catatan Capacitor:** Capacitor WebView memperlakukan cookie seperti browser biasa — httpOnly cookie tetap berfungsi normal di Android WebView.

### 5.5 Capacitor Plugin Map

| Plugin | Package | Kegunaan |
|---|---|---|
| Push Notifications | `@capacitor/push-notifications` | FCM token, receive & handle push |
| Camera | `@capacitor/camera` | Foto hardfile untuk upload arsip |
| Filesystem | `@capacitor/filesystem` | Simpan file download ke /Downloads |
| File Opener | `capacitor-file-opener` | Buka file setelah didownload |
