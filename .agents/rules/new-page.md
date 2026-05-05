# New Vue Page — ARSIG

Workflow untuk membuat halaman baru di frontend ARSIG (Vue 3 + PrimeVue).
Dipanggil via: `/new-page`

## Langkah-langkah

### Step 1 — Tentukan Kebutuhan

Tanyakan ke user:
1. Nama halaman dan route URL-nya
2. Data apa yang ditampilkan (resource dari API mana)
3. Aksi apa yang tersedia (buat, edit, hapus, approve, dll)
4. Role mana yang boleh akses halaman ini

### Step 2 — Tambah Route

```javascript
// src/router/index.js
{
    path: '/{resource}',
    name: '{PageName}',
    component: () => import('@/pages/{PageName}.vue'),
    meta: { requiresAuth: true, roles: ['root', 'admin'] } // sesuaikan role
}
```

Route guard sudah menangani redirect ke login jika `requiresAuth: true` dan user belum login.

### Step 3 — Buat API Module (jika belum ada)

```javascript
// src/api/{resource}.js
import api from './axios'

export const {resource}Api = {
    list:    (params = {}) => api.get('/{resource}', { params }),
    show:    (id)          => api.get(`/{resource}/${id}`),
    store:   (data)        => api.post('/{resource}', data),
    update:  (id, data)    => api.put(`/{resource}/${id}`, data),
    destroy: (id)          => api.delete(`/{resource}/${id}`),
}
```

### Step 4 — Template Halaman List + CRUD

```vue
<script setup>
import { ref, onMounted } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { {resource}Api } from '@/api/{resource}'

const toast    = useToast()
const confirm  = useConfirm()
const items    = ref([])
const loading  = ref(false)
const dialog   = ref({ visible: false, mode: 'create', data: {} })

onMounted(() => fetchAll())

async function fetchAll() {
    loading.value = true
    try {
        const { data } = await {resource}Api.list()
        items.value = data.data
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message ?? e.message, life: 3000 })
    } finally {
        loading.value = false
    }
}

function openCreate() {
    dialog.value = { visible: true, mode: 'create', data: {} }
}

function openEdit(item) {
    dialog.value = { visible: true, mode: 'edit', data: { ...item } }
}

async function handleSubmit() {
    try {
        if (dialog.value.mode === 'create') {
            await {resource}Api.store(dialog.value.data)
            toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data berhasil disimpan.', life: 3000 })
        } else {
            await {resource}Api.update(dialog.value.data.id, dialog.value.data)
            toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data berhasil diperbarui.', life: 3000 })
        }
        dialog.value.visible = false
        fetchAll()
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: e.response?.data?.message ?? e.message, life: 3000 })
    }
}

function confirmDelete(item) {
    confirm.require({
        message: `Hapus "${item.name}"? Aksi ini tidak dapat dibatalkan.`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptSeverity: 'danger',
        accept: async () => {
            await {resource}Api.destroy(item.id)
            toast.add({ severity: 'success', summary: 'Dihapus', detail: 'Data berhasil dihapus.', life: 3000 })
            fetchAll()
        }
    })
}
</script>

<template>
    <div class="p-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-semibold">{Page Title}</h1>
            <Button label="Tambah" icon="pi pi-plus" @click="openCreate" />
        </div>

        <DataTable
            :value="items"
            :loading="loading"
            paginator
            :rows="20"
            :rowsPerPageOptions="[10, 20, 50]"
            stripedRows
        >
            <Column field="name" header="Nama" sortable />
            <!-- Tambah Column sesuai kebutuhan -->
            <Column header="Aksi" style="width: 120px">
                <template #body="{ data }">
                    <div class="flex gap-2">
                        <Button icon="pi pi-pencil" severity="secondary" size="small" @click="openEdit(data)" />
                        <Button icon="pi pi-trash" severity="danger" size="small" @click="confirmDelete(data)" />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Dialog Create/Edit -->
        <Dialog
            v-model:visible="dialog.visible"
            :header="dialog.mode === 'create' ? 'Tambah Data' : 'Edit Data'"
            modal
            style="width: 500px"
        >
            <div class="flex flex-col gap-3">
                <div class="flex flex-col gap-1">
                    <label>Nama</label>
                    <InputText v-model="dialog.data.name" placeholder="Masukkan nama" />
                </div>
                <!-- Tambah field lain sesuai kebutuhan -->
            </div>
            <template #footer>
                <Button label="Batal" severity="secondary" @click="dialog.visible = false" />
                <Button label="Simpan" @click="handleSubmit" />
            </template>
        </Dialog>
    </div>
</template>
```

### Step 5 — Aturan Tambahan

**Validasi error dari API:**
Tangkap error validasi Laravel (422) dan tampilkan per field:
```javascript
const errors = ref({})
// Di catch block:
if (e.response?.status === 422) {
    errors.value = e.response.data.errors
}
```

**Aksi yang butuh konfirmasi:**
Selalu gunakan `useConfirm()` + `<ConfirmDialog />` — jangan `window.confirm()`

**Loading state:**
Selalu ada loading indicator saat fetch data — gunakan `:loading="loading"` di DataTable atau `<ProgressSpinner>` jika halaman penuh.

**Role guard di UI:**
```javascript
import { useAuthStore } from '@/stores/auth'
const auth = useAuthStore()
// Sembunyikan tombol jika tidak punya akses:
// v-if="auth.user?.role === 'admin' || auth.user?.role === 'root'"
```

### Step 6 — Checklist

- [ ] Route sudah ditambah di `router/index.js`
- [ ] API module sudah ada di `src/api/`
- [ ] Komponen menggunakan PrimeVue — tidak ada Bootstrap atau HTML table manual
- [ ] Error ditangkap dan ditampilkan via `<Toast>`
- [ ] Konfirmasi hapus via `<ConfirmDialog>`, bukan `window.confirm()`
- [ ] Loading state ada saat fetch data
- [ ] Tombol aksi disembunyikan berdasarkan role jika diperlukan
