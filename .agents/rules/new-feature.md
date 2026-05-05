# New Feature — ARSIG

Workflow untuk membuat fitur baru end-to-end di project ARSIG.
Dipanggil via: `/new-feature`

## Langkah-langkah

### Step 1 — Pahami Konteks

Sebelum menulis kode apapun:
1. Baca `arsig-rules.md` untuk memastikan stack dan konvensi
2. Tanyakan ke user jika belum jelas:
   - Nama fitur / resource yang akan dibuat
   - Apakah ada tabel baru atau hanya menggunakan tabel yang ada
   - Role mana yang boleh akses (root/admin/user/PIC)
   - Apakah ada push notification yang terlibat

### Step 2 — Migration (jika ada tabel baru)

Buat Laravel migration dengan konvensi:

```php
// database/migrations/xxxx_create_{table}_table.php
Schema::create('{table}', function (Blueprint $table) {
    $table->id();
    // kolom-kolom
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->softDeletes(); // jika data sensitif / tidak boleh hilang permanen
});
```

Aturan migration:
- Semua FK gunakan `foreignId()->constrained()`
- Tambah `softDeletes()` untuk tabel yang berisi data bisnis penting
- Tipe JSON untuk koordinat Konva: `$table->json('points')`
- ENUM: `$table->enum('status', ['pending', 'approved', 'rejected'])`

### Step 3 — Eloquent Model

```php
<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class {ModelName} extends Model
{
    use SoftDeletes; // hapus jika tidak perlu

    protected $fillable = [
        // daftar kolom yang boleh diisi
    ];

    protected $casts = [
        'points'     => 'array',   // untuk kolom JSON
        'created_at' => 'datetime',
    ];

    // Relasi
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### Step 4 — FormRequest (Validasi)

```php
<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Store{Feature}Request extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'field'        => 'required|string|max:255',
            'company_id'   => 'required|integer|exists:companies,id',
            'expire_date'  => 'nullable|date',
            // jika ada expire_date, reminder_date wajib:
            'reminder_date' => 'required_with:expire_date|nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'field.required' => 'Field wajib diisi.',
        ];
    }
}
```

### Step 5 — Policy (Otorisasi)

Buat Policy jika ada perbedaan akses antar role:

```php
<?php
declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\{ModelName};

class {ModelName}Policy
{
    public function update(User $user, {ModelName} $model): bool
    {
        return $user->role === 'root'
            || $user->role === 'admin'
            || $model->pic_user_id === $user->id;
    }

    public function delete(User $user, {ModelName} $model): bool
    {
        return in_array($user->role, ['root', 'admin']);
    }
}
```

Daftarkan di `AppServiceProvider` atau `AuthServiceProvider`.

### Step 6 — Service (Logika Bisnis)

```php
<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\{ModelName};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class {Feature}Service
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return {ModelName}::query()
            ->when($filters['q'] ?? null, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): {ModelName}
    {
        // logika bisnis di sini
        return {ModelName}::create($data);
    }

    public function update({ModelName} $model, array $data): {ModelName}
    {
        $model->update($data);
        return $model->fresh();
    }

    public function delete({ModelName} $model): void
    {
        $model->delete(); // soft delete jika pakai SoftDeletes
    }
}
```

### Step 7 — Controller

```php
<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store{Feature}Request;
use App\Http\Requests\Update{Feature}Request;
use App\Models\{ModelName};
use App\Services\{Feature}Service;
use Illuminate\Http\JsonResponse;

class {Feature}Controller extends Controller
{
    public function __construct(
        private readonly {Feature}Service $service
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->service->getAll(request()->query());
        return $this->successResponse($data);
    }

    public function store(Store{Feature}Request $request): JsonResponse
    {
        $data = $this->service->create($request->validated());
        return $this->successResponse($data, '{Feature} berhasil disimpan.', 201);
    }

    public function show({ModelName} $model): JsonResponse
    {
        return $this->successResponse($model);
    }

    public function update(Update{Feature}Request $request, {ModelName} $model): JsonResponse
    {
        $this->authorize('update', $model);
        $data = $this->service->update($model, $request->validated());
        return $this->successResponse($data, '{Feature} berhasil diperbarui.');
    }

    public function destroy({ModelName} $model): JsonResponse
    {
        $this->authorize('delete', $model);
        $this->service->delete($model);
        return $this->successResponse(null, '{Feature} berhasil dihapus.');
    }
}
```

### Step 8 — Route

Tambahkan di `routes/api.php` dalam group `middleware('auth:api')`:

```php
Route::apiResource('{resource}', {Feature}Controller::class);
// Tambah route tambahan jika diperlukan:
// Route::post('{resource}/{id}/action', [{Feature}Controller::class, 'action']);
```

### Step 9 — Observer (jika ada audit log atau side effect)

```php
<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\{ModelName};

class {ModelName}Observer
{
    public function created({ModelName} $model): void
    {
        // catat activity log
    }

    public function updated({ModelName} $model): void
    {
        // cek jika ada side effect (misal: ganti floor_plan_image → flag koordinat)
    }
}
```

Daftarkan Observer di `AppServiceProvider`:
```php
{ModelName}::observe({ModelName}Observer::class);
```

### Step 10 — Frontend: API Module

```javascript
// src/api/{resource}.js
import api from './axios'

export const {resource}Api = {
    list: (params = {}) => api.get('/{resource}', { params }),
    show: (id) => api.get(`/{resource}/${id}`),
    store: (data) => api.post('/{resource}', data),
    update: (id, data) => api.put(`/{resource}/${id}`, data),
    destroy: (id) => api.delete(`/{resource}/${id}`),
}
```

### Step 11 — Frontend: Halaman Vue

Buat halaman di `src/pages/` menggunakan PrimeVue:
- List → `<DataTable>` dengan pagination dan filter
- Form create/edit → `<Dialog>` dengan field PrimeVue
- Konfirmasi hapus → `<ConfirmDialog>` via `useConfirm()`
- Feedback → `<Toast>` via `useToast()`

### Step 12 — Checklist Akhir

- [ ] Migration sudah dibuat dan dijalankan
- [ ] Model sudah ada dengan fillable, casts, relasi
- [ ] FormRequest sudah handle semua validasi
- [ ] Policy sudah dibuat dan didaftarkan
- [ ] Service berisi semua logika bisnis
- [ ] Controller tipis — hanya panggil service + return response
- [ ] Route sudah ditambah di `api.php`
- [ ] Observer sudah handle audit log
- [ ] Frontend API module sudah dibuat
- [ ] Halaman Vue menggunakan komponen PrimeVue
- [ ] Push notification (jika ada) via Job/Queue
- [ ] Business rules kritis dari arsig-rules.md sudah diterapkan
