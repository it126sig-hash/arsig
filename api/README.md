# ARSIG Backend (API)

Backend ARSIG dibangun menggunakan **Laravel 11** yang dikonfigurasi sebagai **pure REST API**.

## 🛠 Tech Stack

- **PHP**: 8.3+
- **Framework**: Laravel 11
- **Authentication**: JWT via `tymon/jwt-auth`
- **Database**: MySQL 8
- **Queue**: Database driver (default) untuk notifikasi FCM

## 🗝 Autentikasi (JWT Strategy)

ARSIG menggunakan strategi Access + Refresh Token:
- **Access Token**: Expire dalam 15 menit, dikirim via `Authorization: Bearer` header.
- **Refresh Token**: Expire dalam 7 hari, disimpan dalam `httpOnly` cookie.

Endpoint Auth:
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout`

## 🚀 Instalasi

1. **Clone repository dan masuk ke direktori api**:
   ```bash
   cd api
   ```

2. **Install dependensi**:
   ```bash
   composer install
   ```

3. **Setup environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   ```

4. **Konfigurasi Database** di `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=arsig_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate --seed
   ```

6. **Run Worker** (untuk notifikasi):
   ```bash
   php artisan queue:work
   ```

## 📡 API Overview

Semua endpoint API diawali dengan `/api/v1/`.

| Resource | Endpoint | Deskripsi |
|---|---|---|
| **Archives** | `/archives` | CRUD arsip (metadata & file) |
| **Categories** | `/categories` | Navigasi kategori per PT |
| **Requests** | `/download-requests` | Alur approval download |
| **Floors** | `/floors` | Data lokasi fisik (Lantai/Ruangan/Lemari) |
| **Devices** | `/auth/device-token` | Register FCM token untuk push notif |

Dokumentasi API lengkap dapat dilihat di [PRD-05-API.md](../prd/PRD-05-API.md).
