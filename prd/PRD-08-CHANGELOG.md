# ARSIG — PRD-08: Changelog

← [Mobile](PRD-07-MOBILE.md) | [Index →](PRD-00-INDEX.md)

---

## 11. Changelog

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | — | PRD awal — stack Bootstrap + CI4 + MythAuth |
| 2.0.0 | — | Migrasi stack ke Vue 3 + CI4 + JWT, tambah section API Design |
| 2.1.0 | — | Tambah Capacitor untuk Android, FCM push notification, tabel `user_devices`, section Mobile App |
| 3.0.0 | 2025 | **Migrasi stack:** CodeIgniter 4 → **Laravel 11**, Bootstrap 5 → **PrimeVue**. Pemecahan PRD menjadi 9 file terpisah. Update semua referensi teknis ke Laravel (Eloquent, Form Request, Policy, Observer, Queue, Notification). |

---

## Catatan Migrasi v2.x → v3.0

### Backend: CI4 → Laravel 11

| Konsep CI4 | Setara Laravel |
|---|---|
| `BaseController` + `ResponseTrait` | `BaseApiController` + `Response Macro` |
| CI4 Validation Library | `Laravel Form Request` |
| CI4 Model | `Eloquent Model` |
| CI4 Query Builder | `Eloquent ORM` / `Query Builder` |
| `JWTAuthFilter` | `auth:api` middleware (`tymon/jwt-auth`) |
| Manual observer pattern | `Eloquent Observers` |
| Manual push job | `Laravel Notifications` + `Queue` |
| `firebase/php-jwt` | `tymon/jwt-auth` |
| `app/Config/Cors.php` | `config/cors.php` |
| `php spark serve` | `php artisan serve` |

### Frontend: Bootstrap 5 → PrimeVue

| Komponen Bootstrap | Setara PrimeVue |
|---|---|
| `<table>` manual | `<DataTable>` dengan sorting, filtering, pagination bawaan |
| Custom modal HTML | `<Dialog>` |
| Custom file input | `<FileUpload>` |
| Custom dropdown | `<Dropdown>` / `<Select>` |
| Custom date picker | `<DatePicker>` (Calendar) |
| Custom chip/badge | `<Chip>` / `<Tag>` |
| Custom tree HTML | `<Tree>` / `<TreeTable>` |
| Toastr (library terpisah) | `<Toast>` bawaan PrimeVue |
| Manual pagination | Bawaan `<DataTable>` |
