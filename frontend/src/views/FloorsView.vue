<template>
  <div>
    <!-- Manage Floors Page -->
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Floors (Lantai)</h1>
        <p class="text-slate-500 text-sm mt-0.5">Kelola data lantai gedung penyimpanan arsip</p>
      </div>
      <Button label="Tambah Data" icon="pi pi-plus" @click="openNew" class="shadow-sm" />
    </div>

    <!-- DataTable Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <!-- Table Toolbar -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <span class="text-sm font-semibold text-slate-700">
          <i class="pi pi-building mr-2 text-blue-500"></i>Daftar Lantai
        </span>
        <span class="p-input-icon-left">
          <i class="pi pi-search" />
          <InputText
            id="floors-global-search"
            v-model="globalFilter"
            placeholder="Cari lantai..."
            class="text-sm"
            style="width: 240px;"
          />
        </span>
      </div>

      <DataTable
        :value="locationStore.floors"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[5, 10, 25]"
        dataKey="id"
        :loading="locationStore.loading"
        :globalFilterFields="['id', 'name']"
        :filters="filters"
        :showGridlines="false"
        stripedRows
        responsiveLayout="scroll"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        currentPageReportTemplate="Menampilkan {first} - {last} dari {totalRecords} data"
      >
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="pi pi-building text-4xl mb-3 block opacity-40"></i>
            <p class="text-sm">Belum ada data lantai.</p>
          </div>
        </template>

        <Column field="id" header="ID" sortable style="width: 80px;" />
        <Column field="name" header="Nama Lantai" sortable />
        <Column header="Denah Lantai" style="width: 120px;">
          <template #body="slotProps">
            <img
              v-if="slotProps.data.floor_plan_image"
              :src="appBase + '/storage/' + slotProps.data.floor_plan_image"
              alt="Floor Plan"
              class="w-14 h-14 object-cover rounded-lg shadow-sm border border-slate-200"
            />
            <span v-else class="inline-flex items-center gap-1 text-xs text-slate-400 italic">
              <i class="pi pi-image"></i> Tidak ada
            </span>
          </template>
        </Column>
        <Column header="Aksi" style="width: 120px;" :exportable="false">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <Button
                icon="pi pi-pencil"
                outlined
                rounded
                size="small"
                severity="info"
                @click="editFloor(slotProps.data)"
                v-tooltip.top="'Edit'"
              />
              <Button
                icon="pi pi-trash"
                outlined
                rounded
                size="small"
                severity="danger"
                @click="confirmDeleteFloor(slotProps.data)"
                v-tooltip.top="'Hapus'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog
      v-model:visible="floorDialog"
      :style="{ width: '480px' }"
      header="Detail Lantai"
      :modal="true"
      :draggable="false"
    >
      <div class="flex flex-col gap-5">
        <!-- Name Field -->
        <div class="flex flex-col gap-1.5">
          <label for="floor-name" class="text-sm font-semibold text-slate-700">
            Nama Lantai <span class="text-red-500">*</span>
          </label>
          <InputText
            id="floor-name"
            v-model.trim="floor.name"
            placeholder="Contoh: Lantai 1"
            :invalid="submitted && !floor.name"
            autofocus
          />
          <small v-if="submitted && !floor.name" class="text-red-500 text-xs">
            Nama lantai wajib diisi.
          </small>
        </div>

        <!-- Image Field -->
        <div class="flex flex-col gap-1.5">
          <label for="floor-image" class="text-sm font-semibold text-slate-700">
            Gambar Denah Lantai
          </label>
          <input
            type="file"
            id="floor-image"
            accept="image/*"
            @change="onImageUpload"
            class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-2 cursor-pointer"
          />
        </div>
      </div>

      <template #footer>
        <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="hideDialog" />
        <Button label="Simpan" icon="pi pi-check" @click="saveFloor" :loading="locationStore.loading" />
      </template>
    </Dialog>

    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { useLocationStore } from '../store/location'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

const locationStore = useLocationStore()
const toast         = useToast()
const confirm       = useConfirm()
const apiBase       = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
const appBase       = apiBase.replace(/\/api\/v1\/?$/, '')

// State
const floorDialog  = ref(false)
const floor        = ref({})
const submitted    = ref(false)
const imageFile    = ref(null)
const globalFilter = ref('')

// DataTable filters
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

// Sync global filter input to filters object
import { watch } from 'vue'
watch(globalFilter, (val) => {
  filters.value.global.value = val
})

onMounted(() => {
  locationStore.fetchFloors().catch(() => {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data lantai', life: 3000 })
  })
})

const openNew = () => {
  floor.value = {}
  submitted.value = false
  imageFile.value = null
  floorDialog.value = true
}

const hideDialog = () => {
  floorDialog.value = false
  submitted.value = false
}

const onImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) imageFile.value = file
}

const saveFloor = async () => {
  submitted.value = true
  if (!floor.value.name?.trim()) return

  try {
    const data = { name: floor.value.name }
    if (imageFile.value) data.floor_plan_image = imageFile.value

    if (floor.value.id) {
      await locationStore.updateFloor(floor.value.id, data)
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data lantai diperbarui', life: 3000 })
    } else {
      await locationStore.createFloor(data)
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data lantai ditambahkan', life: 3000 })
    }
    floorDialog.value = false
    floor.value = {}
    imageFile.value = null
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menyimpan data lantai', life: 3000 })
  }
}

const editFloor = (editData) => {
  floor.value = { ...editData }
  imageFile.value = null
  floorDialog.value = true
}

const confirmDeleteFloor = (deleteData) => {
  confirm.require({
    message: `Yakin ingin menghapus "${deleteData.name}"?`,
    header: 'Konfirmasi Hapus',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Ya, Hapus',
    rejectLabel: 'Batal',
    accept: async () => {
      try {
        await locationStore.deleteFloor(deleteData.id)
        toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data lantai dihapus', life: 3000 })
      } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menghapus data lantai', life: 3000 })
      }
    }
  })
}
</script>
