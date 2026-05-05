# Debug & Fix — ARSIG

Workflow untuk debug dan memperbaiki bug di project ARSIG.
Dipanggil via: `/fix-bug`

## Langkah-langkah

### Step 1 — Identifikasi Konteks Bug

Tanyakan atau periksa:
1. Di layer mana bug terjadi? (Backend Laravel / Frontend Vue / Mobile Capacitor)
2. Apa error message atau perilaku yang salah?
3. Endpoint atau halaman mana yang bermasalah?
4. Apakah ada error di Laravel log (`storage/logs/laravel.log`)?

### Step 2 — Cek Layer yang Tepat

Berdasarkan jenis bug, arahkan investigasi ke file yang benar:

| Jenis Bug | Cek di |
|---|---|
| Response 422 / validasi gagal | `app/Http/Requests/*.php` |
| Response 401 / token issue | `app/Http/Middleware/`, `config/jwt.php`, Pinia store |
| Response 403 / akses ditolak | `app/Policies/*.php`, role check di Service |
| Response 500 / server error | `app/Services/*.php`, `app/Models/*.php`, `storage/logs/` |
| Data tidak tersimpan | `app/Services/*.php`, `$fillable` di Model |
| Notifikasi tidak terkirim | `app/Jobs/*.php`, queue worker, `config/queue.php` |
| UI tidak update setelah aksi | `src/api/*.js`, Pinia store, component `onMounted` / `watch` |
| Cookie tidak terkirim di Android | `config/cors.php` — pastikan `supports_credentials: true` |
| Axios 401 loop | `src/api/axios.js` — cek interceptor `_retry` flag |

### Step 3 — Pola Fix yang Konsisten

**Fix validasi (FormRequest):**
```php
// Tambah atau perbaiki rule, JANGAN pindah validasi ke Controller
public function rules(): array
{
    return [
        'field' => 'required|string|max:255',
    ];
}
```

**Fix logika bisnis (Service):**
```php
// Perbaiki di Service, JANGAN tulis ulang di Controller
public function create(array $data): Model
{
    // fix di sini
}
```

**Fix response format:**
```php
// Selalu gunakan successResponse() / errorResponse() dari BaseController
return $this->successResponse($data, 'Berhasil.');
// JANGAN: return response()->json(['data' => $data]);
```

**Fix Axios interceptor (Vue):**
```javascript
// Pastikan interceptor ada _retry flag untuk mencegah loop
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config
        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true
            const { data } = await api.post('/auth/refresh')
            useAuthStore().setToken(data.data.access_token)
            return api(originalRequest)
        }
        return Promise.reject(error)
    }
)
```

**Fix CORS untuk Capacitor:**
```php
// config/cors.php — pastikan ini ada semua
'allowed_origins' => [
    'https://arsig.sanggarindah.com',
    'capacitor://localhost',
    'http://localhost',
],
'supports_credentials' => true,
```

### Step 4 — Jangan Lakukan Ini Saat Fix

- **Jangan** pindahkan logika ke Controller untuk "shortcut"
- **Jangan** bypass FormRequest dengan validasi manual di Controller
- **Jangan** simpan token di localStorage sebagai workaround cookie issue
- **Jangan** kirim FCM synchronous sebagai workaround queue issue — fix queue-nya
- **Jangan** ubah format response — selalu `{ success, data, message, errors }`

### Step 5 — Verifikasi Fix

Setelah fix diterapkan, verifikasi:
- [ ] Format response masih `{ success, data, message, errors }`
- [ ] Tidak ada logika bisnis baru di Controller
- [ ] Tidak ada token disimpan di localStorage
- [ ] Layer yang diubah sudah sesuai tanggung jawabnya
- [ ] Jika fix Observer — pastikan tidak ada infinite loop
