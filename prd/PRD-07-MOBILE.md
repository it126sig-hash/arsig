# ARSIG — PRD-07: Mobile App (Capacitor + Android)

← [Technical](PRD-06-TECHNICAL.md) | [Changelog →](PRD-08-CHANGELOG.md)

---

## 10. Mobile App (Capacitor + Android)

### 10.1 Platform Target

| | |
|---|---|
| **Framework** | Capacitor 6 |
| **Platform** | Android |
| **Min SDK** | API 26 (Android 8.0 Oreo) |
| **Target SDK** | API 34 (Android 14) — wajib untuk Play Store 2024+ |
| **Distribusi** | Play Store — Internal Testing Track |
| **Update** | Over-the-air via Play Store (tidak ada CodePush) |

### 10.2 Strategi Distribusi Play Store

Karena ARSIG adalah aplikasi internal, gunakan **Internal Testing Track** di Google Play Console:

- Maksimal 100 tester terdaftar
- Tidak perlu review Google — update live dalam beberapa menit
- Tester diundang via email Google account
- Tidak perlu membayar lebih dari biaya satu kali developer account ($25)

Jika di masa depan perlu distribusi lebih luas ke seluruh karyawan grup, upgrade ke **Closed Testing (Alpha)** dengan unlimited tester.

### 10.3 Capacitor Plugins

| Plugin | Versi | Kegunaan | Permission Android |
|---|---|---|---|
| `@capacitor/push-notifications` | Latest | Receive FCM push notification | `POST_NOTIFICATIONS` |
| `@capacitor/camera` | Latest | Foto hardfile untuk upload | `CAMERA` |
| `@capacitor/filesystem` | Latest | Simpan file download ke /Downloads | `WRITE_EXTERNAL_STORAGE` (API < 29) |
| `capacitor-file-opener` | Latest | Buka file setelah didownload | — |

### 10.4 Permission Android

Semua permission dideklarasikan di `AndroidManifest.xml`. Google Play mengharuskan penjelasan penggunaan permission yang sensitif:

| Permission | Justifikasi (untuk Play Store review) |
|---|---|
| `POST_NOTIFICATIONS` | Mengirim notifikasi untuk request download dan reminder dokumen |
| `CAMERA` | Memfoto dokumen hardfile untuk dilampirkan ke arsip |
| `WRITE_EXTERNAL_STORAGE` | Menyimpan file arsip yang diunduh ke folder Downloads |
| `INTERNET` | Mengakses server ARSIG untuk manajemen arsip |

### 10.5 HTTPS Requirement

Backend Laravel **wajib menggunakan HTTPS**. Android secara default memblokir semua cleartext (HTTP) traffic dari app. Tanpa HTTPS:

- Axios request dari Capacitor akan gagal
- httpOnly cookie tidak bisa di-set (Secure flag membutuhkan HTTPS)
- App tidak akan lolos review Play Store

Gunakan SSL certificate dari **Let's Encrypt** (gratis) atau certificate dari provider hosting.

### 10.6 CORS untuk Capacitor

Laravel harus mengizinkan origin dari Capacitor scheme selain domain web biasa:

```php
// config/cors.php
'allowed_origins' => [
    'https://arsig.sanggarindah.com',   // web browser
    'capacitor://localhost',             // Capacitor Android
    'http://localhost',                  // development
],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,         // wajib untuk httpOnly cookie
```

### 10.7 Konfigurasi Capacitor

```json
// capacitor.config.json
{
  "appId": "com.sanggarindah.arsig",
  "appName": "ARSIG",
  "webDir": "dist",
  "server": {
    "androidScheme": "https"
  },
  "plugins": {
    "PushNotifications": {
      "presentationOptions": ["badge", "sound", "alert"]
    }
  }
}
```

### 10.8 Build & Release Workflow

```
Development
  └── vue dev server + Laravel localhost
        └── Capacitor live reload (npx cap run android)

Staging
  └── npm run build → npx cap sync android
        └── Android Studio build APK → Test di device fisik

Production
  └── npm run build → npx cap sync android
        └── Android Studio build AAB (App Bundle)
              └── Upload ke Play Console → Internal Testing Track
                    └── Tester install via Play Store link
```

### 10.9 Deep Link (Tap Notifikasi)

Notifikasi FCM membawa `data` payload untuk navigasi deep link:

```json
{
  "notification": {
    "title": "Request Download Disetujui",
    "body": "Download dokumen SPK-2024-001 sudah siap."
  },
  "data": {
    "type": "download_approved",
    "archive_id": "123",
    "request_id": "456"
  }
}
```

Vue listener di `App.vue`:

```javascript
import { PushNotifications } from '@capacitor/push-notifications'

PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
    const data = notification.notification.data
    if (data.type === 'download_approved') {
        router.push({ name: 'DownloadRequest', query: { id: data.request_id } })
    }
})
```
