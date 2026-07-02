<template>
  <div class="p-2 md:p-0">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
      <div>
        <h1 class="text-xl md:text-2xl font-bold text-slate-800">Matriks Izin</h1>
        <p class="text-xs md:text-sm text-slate-500 mt-1">
          Atur akses View/Create/Update/Delete per modul untuk role Admin dan User. Role Root selalu memiliki akses penuh.
        </p>
      </div>
      <Button label="Simpan" icon="pi pi-check" :loading="isSaving" @click="handleSave" class="w-full md:w-auto !rounded-xl shadow-md" />
    </div>

    <div class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-slate-200 bg-slate-50">
            <th class="text-left px-4 py-3 font-semibold text-slate-700">Modul</th>
            <th class="text-center px-4 py-3 font-semibold text-slate-700" colspan="4">Admin</th>
            <th class="text-center px-4 py-3 font-semibold text-slate-700" colspan="4">User</th>
          </tr>
          <tr class="border-b border-slate-200 text-xs text-slate-500">
            <th></th>
            <th class="px-2 py-1">View</th>
            <th class="px-2 py-1">Create</th>
            <th class="px-2 py-1">Update</th>
            <th class="px-2 py-1">Delete</th>
            <th class="px-2 py-1">View</th>
            <th class="px-2 py-1">Create</th>
            <th class="px-2 py-1">Update</th>
            <th class="px-2 py-1">Delete</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="isLoading">
            <td colspan="9" class="text-center py-10 text-slate-400">Memuat data...</td>
          </tr>
          <tr v-for="mod in modules" :key="mod.key" class="border-b border-slate-100 hover:bg-slate-50">
            <td class="px-4 py-3 font-medium text-slate-800">{{ mod.label }}</td>
            <td v-for="action in actions" :key="`admin-${action}`" class="text-center px-2 py-3">
              <Checkbox v-model="matrix[mod.key].admin[action]" binary />
            </td>
            <td v-for="action in actions" :key="`user-${action}`" class="text-center px-2 py-3">
              <Checkbox v-model="matrix[mod.key].user[action]" binary />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { fetchRolePermissions, updateRolePermissions } from '@/api/rolePermissionApi'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import { useAuthStore } from '@/store/auth'

const toast = useToast()
const authStore = useAuthStore()

const modules = [
  { key: 'companies', label: 'Perusahaan (PT)' },
  { key: 'departments', label: 'Departemen' },
  { key: 'floors', label: 'Floors (Lantai)' },
  { key: 'rooms', label: 'Rooms (Ruangan)' },
  { key: 'cabinets', label: 'Cabinets (Lemari)' },
  { key: 'cabinet_slots', label: 'Cabinet Slots' },
  { key: 'categories', label: 'Kategori' },
  { key: 'tags', label: 'Tag (Hashtag)' },
  { key: 'users', label: 'User' },
]
const actions = ['can_view', 'can_create', 'can_update', 'can_delete']

const isLoading = ref(false)
const isSaving = ref(false)

const emptyRow = () => ({ can_view: false, can_create: false, can_update: false, can_delete: false })
const matrix = reactive(
  Object.fromEntries(modules.map((m) => [m.key, { admin: emptyRow(), user: emptyRow() }]))
)

const loadMatrix = async () => {
  isLoading.value = true
  try {
    const res = await fetchRolePermissions()
    if (res.data.success) {
      for (const row of res.data.data) {
        if (matrix[row.module] && (row.role === 'admin' || row.role === 'user')) {
          matrix[row.module][row.role] = {
            can_view: !!row.can_view,
            can_create: !!row.can_create,
            can_update: !!row.can_update,
            can_delete: !!row.can_delete,
          }
        }
      }
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat matriks izin.', life: 3000 })
  } finally {
    isLoading.value = false
  }
}

const handleSave = async () => {
  isSaving.value = true
  try {
    const permissions = []
    for (const mod of modules) {
      for (const role of ['admin', 'user']) {
        permissions.push({ role, module: mod.key, ...matrix[mod.key][role] })
      }
    }
    const res = await updateRolePermissions(permissions)
    if (res.data.success) {
      toast.add({ severity: 'success', summary: 'Sukses', detail: res.data.message, life: 3000 })
      await authStore.fetchPermissions()
    }
  } catch (e) {
    toast.add({
      severity: 'error',
      summary: 'Gagal',
      detail: e.response?.data?.message || 'Tidak dapat menyimpan matriks izin.',
      life: 4000
    })
  } finally {
    isSaving.value = false
  }
}

onMounted(loadMatrix)
</script>
