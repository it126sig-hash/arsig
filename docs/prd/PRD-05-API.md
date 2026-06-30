# ARSIG — PRD-05: API Design & Permission Matrix

← [Database](PRD-04-DATABASE.md) | [Technical →](PRD-06-TECHNICAL.md)

---

## 7. API Design

### 7.1 Konvensi

- Base URL: `/api/v1/`
- Format response: `{ success, data, message, errors }`
- Autentikasi: `Authorization: Bearer <access_token>` di semua endpoint kecuali `/auth/*`
- Pagination: `?page=1&per_page=20`, response include `meta.total` dan `meta.last_page`
- HTTP error codes: `400` (validation), `401` (unauthenticated), `403` (unauthorized), `404` (not found), `422` (unprocessable — Laravel default validation), `500` (server error)

**Standar Response Laravel:**

```json
{
  "success": true,
  "data": {},
  "message": "Arsip berhasil disimpan.",
  "errors": null
}
```

> Di Laravel, gunakan `Response Macros` atau `BaseApiController` untuk memastikan format response konsisten di semua endpoint.

### 7.2 Endpoint: Auth

| Method | Endpoint | Deskripsi | Auth |
|---|---|---|---|
| `POST` | `/auth/login` | Login, return access token | — |
| `POST` | `/auth/refresh` | Refresh access token (baca httpOnly cookie) | — |
| `POST` | `/auth/logout` | Revoke refresh token aktif | Ya |
| `POST` | `/auth/device-token` | Daftarkan / update FCM token device | Ya |
| `DELETE` | `/auth/device-token` | Hapus FCM token saat logout dari device | Ya |

### 7.3 Endpoint: Arsip

| Method | Endpoint | Deskripsi | Auth |
|---|---|---|---|
| `GET` | `/archives` | List arsip dengan search & filter | Ya |
| `POST` | `/archives` | Upload arsip baru | Ya |
| `GET` | `/archives/{id}` | Detail arsip | Ya |
| `PUT` | `/archives/{id}` | Edit arsip | Ya (PIC / admin / root) |
| `DELETE` | `/archives/{id}` | Hapus arsip (soft delete) | Ya (admin / root) |
| `POST` | `/archives/{id}/download` | Generate signed URL atau submit request ke PIC | Ya |
| `GET` | `/archives/{id}/logs` | Riwayat aktivitas arsip | Ya |

**Query params untuk `GET /archives`:**

| Param | Tipe | Keterangan |
|---|---|---|
| `q` | string | Keyword full-text search |
| `company_id` | int | Filter per PT |
| `category_id` | int | Filter per kategori |
| `tags` | string (comma-sep) | Filter per hashtag |
| `archive_type` | enum | `full` / `physical_only` / `placeholder` |
| `date_from` | date | Filter tanggal terbit mulai |
| `date_to` | date | Filter tanggal terbit sampai |
| `page` | int | Halaman pagination |
| `per_page` | int | Jumlah per halaman (default: 20) |

### 7.4 Endpoint: Download Request

| Method | Endpoint | Deskripsi | Auth |
|---|---|---|---|
| `GET` | `/download-requests` | List request download masuk (untuk PIC) | Ya |
| `PATCH` | `/download-requests/{id}` | Approve atau reject request | Ya (PIC / root) |

### 7.5 Endpoint: Kategori

| Method | Endpoint | Deskripsi | Auth |
|---|---|---|---|
| `GET` | `/categories` | Hierarki kategori per PT | Ya |
| `GET` | `/master-categories` | Master kategori global | Ya (admin / root) |
| `POST` | `/master-categories` | Tambah master kategori | Ya (admin / root) |
| `PUT` | `/master-categories/{id}` | Edit master kategori | Ya (admin / root) |
| `DELETE` | `/master-categories/{id}` | Hapus master kategori | Ya (root) |

### 7.6 Endpoint: Physical Storage

| Method | Endpoint | Deskripsi | Auth |
|---|---|---|---|
| `GET` | `/floors` | List lantai beserta ruangan dan lemari | Ya |
| `POST` | `/floors` | Tambah lantai | Ya (admin / root) |
| `PUT` | `/floors/{id}` | Edit lantai / ganti floor plan image | Ya (admin / root) |
| `GET` | `/floors/{id}/rooms` | List ruangan di lantai | Ya |
| `POST` | `/floors/{id}/rooms` | Tambah ruangan | Ya (admin / root) |

### 7.7 Endpoint: Notifikasi

| Method | Endpoint | Deskripsi | Auth |
|---|---|---|---|
| `GET` | `/notifications` | Notifikasi in-app user yang login | Ya |
| `PATCH` | `/notifications/{id}/read` | Tandai notifikasi sudah dibaca | Ya |
| `PATCH` | `/notifications/read-all` | Tandai semua notifikasi sudah dibaca | Ya |

---

## 8. Permission Matrix

| Aksi | root | admin | user (PIC) | user (bukan PIC) |
|---|:---:|:---:|:---:|:---:|
| Upload arsip | ✅ | ✅ | ✅ | ✅ |
| Edit arsip | ✅ | ✅ | ✅ | ❌ |
| Hapus arsip | ✅ | ✅ | ❌ | ❌ |
| Approve download request | ✅ | ❌ | ✅ | ❌ |
| Ubah status fisik arsip | ✅ | ✅ | ✅ | ❌ |
| Kelola lantai / ruangan / lemari | ✅ | ✅ | ❌ | ❌ |
| Kelola master kategori | ✅ | ✅ | ❌ | ❌ |
| Pindahtangankan PIC | ✅ | ✅ | ✅ | ❌ |
| Lihat metadata semua arsip | ✅ | ✅ | ✅ | ✅ |
| Akses penuh arsip private | ✅ | ❌ | ✅ | ❌ |
| Revoke semua refresh token user | ✅ | ❌ | ❌ | ❌ |
| Lihat activity log semua user | ✅ | ✅ | ❌ | ❌ |

### 8.1 Implementasi Permission di Laravel

Gunakan **Laravel Policies** dan **Gates** untuk permission logic:

```php
// app/Policies/ArchivePolicy.php
class ArchivePolicy
{
    public function update(User $user, Archive $archive): bool
    {
        return $user->isRoot()
            || $user->isAdmin()
            || $archive->pic_user_id === $user->id;
    }

    public function delete(User $user, Archive $archive): bool
    {
        return $user->isRoot() || $user->isAdmin();
    }
}
```

Panggil di controller: `$this->authorize('update', $archive);`
