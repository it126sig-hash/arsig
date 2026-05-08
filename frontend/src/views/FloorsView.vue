<template>
  <div class="p-2 md:p-0">
    <!-- Manage Floors Page -->
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
      <div>
        <h1 class="text-xl md:text-2xl font-bold text-slate-800">Lantai (Floors)</h1>
        <p class="text-xs md:text-sm text-slate-500 mt-1">Kelola data lantai gedung penyimpanan arsip</p>
      </div>
      <Button label="Tambah Lantai" icon="pi pi-plus" @click="openNew" class="w-full md:w-auto !rounded-xl shadow-md" />
    </div>

    <!-- DataTable Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <!-- Table Toolbar (Desktop Search) -->
      <div class="hidden md:flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <span class="text-sm font-semibold text-slate-700">
          <i class="pi pi-building mr-2 text-blue-500"></i>Daftar Lantai
        </span>
        <IconField>
          <InputIcon class="pi pi-search" />
          <InputText
            v-model="globalFilter"
            placeholder="Cari lantai..."
            class="!rounded-lg text-sm"
            style="width: 240px;"
          />
        </IconField>
      </div>

      <!-- Mobile Search Bar -->
      <div class="md:hidden p-4 border-b border-slate-100">
        <IconField class="w-full">
            <InputIcon class="pi pi-search" />
            <InputText v-model="globalFilter" placeholder="Cari lantai..." class="w-full !rounded-xl" />
        </IconField>
      </div>

      <!-- Desktop View -->
      <DataTable
        v-if="!isMobile"
        :value="locationStore.floors"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[5, 10, 25]"
        dataKey="id"
        :loading="locationStore.loading"
        :globalFilterFields="['id', 'name']"
        :filters="filters"
        stripedRows
        responsiveLayout="scroll"
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
        <Column header="Aksi" style="width: 120px;">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <Button icon="pi pi-pencil" text rounded severity="secondary" @click="editFloor(slotProps.data)" />
              <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDeleteFloor(slotProps.data)" />
            </div>
          </template>
        </Column>
      </DataTable>

      <!-- Mobile View (List) -->
      <div v-else class="flex flex-col divide-y divide-slate-100">
        <div v-if="locationStore.loading" class="p-10 flex flex-col items-center justify-center gap-3">
            <ProgressSpinner style="width: 30px; height: 30px" />
            <p class="text-xs text-slate-400">Memuat data...</p>
        </div>
        <div v-else-if="filteredFloors.length === 0" class="p-10 text-center text-slate-400 italic text-sm">
            Tidak ada data lantai.
        </div>
        <div v-for="floorData in filteredFloors" :key="floorData.id" class="p-4 flex items-center justify-between hover:bg-slate-50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center">
                    <img v-if="floorData.floor_plan_image" :src="appBase + '/storage/' + floorData.floor_plan_image" class="w-full h-full object-cover" />
                    <i v-else class="pi pi-building text-slate-300"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-slate-800">{{ floorData.name }}</span>
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">ID: {{ floorData.id }}</span>
                </div>
            </div>
            <div class="flex gap-1">
                <Button icon="pi pi-pencil" text rounded severity="secondary" @click="editFloor(floorData)" />
                <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDeleteFloor(floorData)" />
            </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog
      v-model:visible="floorDialog"
      :modal="true"
      class="w-full max-w-md"
      :maximized="isMobile"
      :showHeader="true"
    >
      <template #header>
          <div class="flex items-center justify-between w-full pr-8 md:pr-0">
              <h3 class="text-base md:text-xl font-bold text-slate-800">
                  {{ floor.id ? 'Edit Lantai' : 'Tambah Lantai' }}
              </h3>
          </div>
      </template>

      <div class="flex flex-col gap-5 mt-2 p-2 md:p-0">
        <!-- Name Field -->
        <div class="flex flex-col gap-1.5">
          <label for="floor-name" class="text-sm font-semibold text-slate-700">
            Nama Lantai <span class="text-red-500">*</span>
          </label>
          <InputText
            id="floor-name"
            v-model.trim="floor.name"
            placeholder="Contoh: Lantai 1"
            class="!rounded-xl"
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
          <div v-if="floor.floor_plan_image && !imageFile" class="mb-2 relative w-32 h-20 rounded-lg overflow-hidden border border-slate-200">
            <img :src="appBase + '/storage/' + floor.floor_plan_image" class="w-full h-full object-cover" />
          </div>
          <input
            type="file"
            id="floor-image"
            accept="image/*"
            @change="onImageUpload"
            class="block w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-2 cursor-pointer"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 p-2 md:p-0">
            <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="hideDialog" class="hidden md:flex" />
            <Button label="Simpan" icon="pi pi-check" @click="saveFloor" :loading="locationStore.loading" class="!rounded-xl px-6" />
        </div>
      </template>
    </Dialog>

    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
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
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import ProgressSpinner from 'primevue/progressspinner'

const locationStore = useLocationStore()
const toast         = useToast()
const confirm       = useConfirm()
const apiBase       = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
const appBase       = apiBase.replace(/\/api\/v1\/?$/, '')

// Mobile detection
const windowWidth = ref(window.innerWidth)
const updateWidth = () => { windowWidth.value = window.innerWidth }
onMounted(() => window.addEventListener('resize', updateWidth))
onUnmounted(() => window.removeEventListener('resize', updateWidth))
const isMobile = computed(() => windowWidth.value < 768)

// State
const floorDialog  = ref(false)
const floor        = ref({})
const submitted    = ref(false)
const imageFile    = ref(null)
const globalFilter = ref('')

// Computed
const filteredFloors = computed(() => {
    if (!globalFilter.value) return locationStore.floors
    const q = globalFilter.value.toLowerCase()
    return locationStore.floors.filter(f => 
        f.name?.toLowerCase().includes(q) || 
        f.id?.toString().includes(q)
    )
})

// DataTable filters
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

// Sync global filter input to filters object
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
