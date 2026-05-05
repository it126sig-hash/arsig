<template>
  <div>
    <!-- Manage Cabinets Page -->
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Cabinets (Lemari)</h1>
        <p class="text-slate-500 text-sm mt-0.5">Kelola data lemari arsip dalam ruangan</p>
      </div>
      <Button label="Tambah Data" icon="pi pi-plus" @click="openNew" class="shadow-sm" />
    </div>

    <!-- DataTable Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <!-- Table Toolbar -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <span class="text-sm font-semibold text-slate-700">
          <i class="pi pi-server mr-2 text-orange-500"></i>Daftar Lemari
        </span>
        <span class="p-input-icon-left">
          <i class="pi pi-search" />
          <InputText
            id="cabinets-global-search"
            v-model="globalFilter"
            placeholder="Cari lemari..."
            class="text-sm"
            style="width: 240px;"
          />
        </span>
      </div>

      <DataTable
        :value="locationStore.cabinets"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[5, 10, 25]"
        dataKey="id"
        :loading="locationStore.loading"
        :globalFilterFields="['id', 'name', 'room.name', 'room.floor.name']"
        :filters="filters"
        :showGridlines="false"
        stripedRows
        responsiveLayout="scroll"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
      >
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="pi pi-server text-4xl mb-3 block opacity-40"></i>
            <p class="text-sm">Belum ada data lemari.</p>
          </div>
        </template>

        <Column field="id" header="ID" sortable style="width: 80px;" />
        <Column field="name" header="Nama Lemari" sortable />
        <Column field="room.name" header="Ruangan" sortable>
          <template #body="slotProps">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-medium">
              <i class="pi pi-table text-xs"></i>
              {{ slotProps.data.room?.name || '—' }}
            </span>
          </template>
        </Column>
        <Column field="room.floor.name" header="Lantai" sortable>
          <template #body="slotProps">
            <span class="text-sm text-slate-500">{{ slotProps.data.room?.floor?.name || '—' }}</span>
          </template>
        </Column>
        <Column field="needs_coordinate_review" header="Review Koordinat" style="width: 160px;">
          <template #body="slotProps">
            <span
              :class="[
                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                slotProps.data.needs_coordinate_review ? 'bg-amber-50 text-amber-700' : 'bg-slate-50 text-slate-500'
              ]"
            >
              <i :class="slotProps.data.needs_coordinate_review ? 'pi pi-clock' : 'pi pi-check'"></i>
              {{ slotProps.data.needs_coordinate_review ? 'Perlu Review' : 'OK' }}
            </span>
          </template>
        </Column>
        <Column header="Aksi" style="width: 120px;" :exportable="false">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <Button icon="pi pi-pencil" outlined rounded size="small" severity="info" @click="editCabinet(slotProps.data)" v-tooltip.top="'Edit'" />
              <Button icon="pi pi-trash" outlined rounded size="small" severity="danger" @click="confirmDeleteCabinet(slotProps.data)" v-tooltip.top="'Hapus'" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog v-model:visible="cabinetDialog" :style="{ width: '500px' }" header="Detail Lemari" :modal="true" :draggable="false">
      <div class="flex flex-col gap-5">
        <!-- Room Dropdown -->
        <div class="flex flex-col gap-1.5">
          <label for="cabinet-room" class="text-sm font-semibold text-slate-700">
            Ruangan <span class="text-red-500">*</span>
          </label>
          <Dropdown
            id="cabinet-room"
            v-model="cabinet.room_id"
            :options="locationStore.rooms"
            optionLabel="name"
            optionValue="id"
            placeholder="Pilih ruangan..."
            :invalid="submitted && !cabinet.room_id"
            class="w-full"
          >
            <template #option="slotProps">
              <span>{{ slotProps.option.name }} <span class="text-slate-400 text-xs">({{ slotProps.option.floor?.name }})</span></span>
            </template>
          </Dropdown>
          <small v-if="submitted && !cabinet.room_id" class="text-red-500 text-xs">Ruangan wajib dipilih.</small>
        </div>

        <!-- Name Field -->
        <div class="flex flex-col gap-1.5">
          <label for="cabinet-name" class="text-sm font-semibold text-slate-700">
            Nama Lemari <span class="text-red-500">*</span>
          </label>
          <InputText id="cabinet-name" v-model.trim="cabinet.name" placeholder="Contoh: Lemari A1" :invalid="submitted && !cabinet.name" autofocus />
          <small v-if="submitted && !cabinet.name" class="text-red-500 text-xs">Nama lemari wajib diisi.</small>
        </div>

        <!-- Points Field -->
        <div class="flex flex-col gap-1.5">
          <label for="cabinet-points" class="text-sm font-semibold text-slate-700">
            Koordinat (JSON) <span class="text-slate-400 font-normal text-xs ml-1">opsional</span>
          </label>
          <InputText id="cabinet-points" v-model="pointsText" placeholder='[{"x":10, "y":20}]' :invalid="!!pointsError" @input="validatePoints" />
          <small v-if="pointsError" class="text-red-500 text-xs">{{ pointsError }}</small>
          <small v-else class="text-slate-400 text-xs">Format: array of {x, y} objects</small>
        </div>

        <!-- Coordinate Review Checkbox -->
        <div class="flex items-center gap-3">
          <Checkbox inputId="cabinet-review" v-model="cabinet.needs_coordinate_review" :binary="true" />
          <label for="cabinet-review" class="text-sm text-slate-700 cursor-pointer">Perlu Review Koordinat</label>
        </div>
      </div>

      <template #footer>
        <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="hideDialog" />
        <Button label="Simpan" icon="pi pi-check" @click="saveCabinet" :loading="locationStore.loading" />
      </template>
    </Dialog>

    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { useLocationStore } from '../store/location'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

const locationStore = useLocationStore()
const toast         = useToast()
const confirm       = useConfirm()

const cabinetDialog = ref(false)
const cabinet       = ref({ needs_coordinate_review: false })
const submitted     = ref(false)
const pointsText    = ref('[]')
const pointsError   = ref('')
const globalFilter  = ref('')

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })
watch(globalFilter, (val) => { filters.value.global.value = val })

onMounted(async () => {
  try {
    await Promise.all([locationStore.fetchRooms(), locationStore.fetchCabinets()])
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data', life: 3000 })
  }
})

const validatePoints = () => {
  try {
    const parsed = JSON.parse(pointsText.value || '[]')
    if (!Array.isArray(parsed)) { pointsError.value = 'Harus berupa JSON array'; return false }
    pointsError.value = ''; return true
  } catch { pointsError.value = 'Format JSON tidak valid'; return false }
}

const openNew    = () => { cabinet.value = { needs_coordinate_review: false }; pointsText.value = '[]'; pointsError.value = ''; submitted.value = false; cabinetDialog.value = true }
const hideDialog = () => { cabinetDialog.value = false; submitted.value = false }

const saveCabinet = async () => {
  submitted.value = true
  if (!validatePoints()) return
  if (!cabinet.value.name?.trim() || !cabinet.value.room_id) return
  try {
    const data = { ...cabinet.value, points: JSON.parse(pointsText.value || '[]') }
    if (cabinet.value.id) {
      await locationStore.updateCabinet(cabinet.value.id, data)
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data lemari diperbarui', life: 3000 })
    } else {
      await locationStore.createCabinet(data)
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data lemari ditambahkan', life: 3000 })
    }
    cabinetDialog.value = false; cabinet.value = {}
  } catch { toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menyimpan data lemari', life: 3000 }) }
}

const editCabinet = (editData) => { cabinet.value = { ...editData }; pointsText.value = JSON.stringify(editData.points || []); pointsError.value = ''; cabinetDialog.value = true }

const confirmDeleteCabinet = (deleteData) => {
  confirm.require({
    message: `Yakin ingin menghapus "${deleteData.name}"?`,
    header: 'Konfirmasi Hapus', icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger', acceptLabel: 'Ya, Hapus', rejectLabel: 'Batal',
    accept: async () => {
      try { await locationStore.deleteCabinet(deleteData.id); toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data lemari dihapus', life: 3000 }) }
      catch { toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menghapus data lemari', life: 3000 }) }
    }
  })
}
</script>
