<template>
  <div>
    <!-- Manage Rooms Page -->
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Rooms (Ruangan)</h1>
        <p class="text-slate-500 text-sm mt-0.5">Kelola data ruangan per lantai gedung</p>
      </div>
      <Button label="Tambah Data" icon="pi pi-plus" @click="openNew" class="shadow-sm" />
    </div>

    <!-- DataTable Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <!-- Table Toolbar -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <span class="text-sm font-semibold text-slate-700">
          <i class="pi pi-table mr-2 text-emerald-500"></i>Daftar Ruangan
        </span>
        <span class="p-input-icon-left">
          <i class="pi pi-search" />
          <InputText
            id="rooms-global-search"
            v-model="globalFilter"
            placeholder="Cari ruangan..."
            class="text-sm"
            style="width: 240px;"
          />
        </span>
      </div>

      <DataTable
        :value="locationStore.rooms"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[5, 10, 25]"
        dataKey="id"
        :loading="locationStore.loading"
        :globalFilterFields="['id', 'name', 'floor.name']"
        :filters="filters"
        :showGridlines="false"
        stripedRows
        responsiveLayout="scroll"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
      >
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="pi pi-table text-4xl mb-3 block opacity-40"></i>
            <p class="text-sm">Belum ada data ruangan.</p>
          </div>
        </template>

        <Column field="id" header="ID" sortable style="width: 80px;" />
        <Column field="name" header="Nama Ruangan" sortable />
        <Column field="floor.name" header="Lantai" sortable>
          <template #body="slotProps">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium">
              <i class="pi pi-building text-xs"></i>
              {{ slotProps.data.floor?.name || '—' }}
            </span>
          </template>
        </Column>
        <Column field="needs_coordinate_review" header="Review Koordinat" style="width: 160px;">
          <template #body="slotProps">
            <span
              :class="[
                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                slotProps.data.needs_coordinate_review
                  ? 'bg-amber-50 text-amber-700'
                  : 'bg-slate-50 text-slate-500'
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
              <Button icon="pi pi-pencil" outlined rounded size="small" severity="info" @click="editRoom(slotProps.data)" v-tooltip.top="'Edit'" />
              <Button icon="pi pi-trash" outlined rounded size="small" severity="danger" @click="confirmDeleteRoom(slotProps.data)" v-tooltip.top="'Hapus'" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog v-model:visible="roomDialog" :style="{ width: '500px' }" header="Detail Ruangan" :modal="true" :draggable="false">
      <div class="flex flex-col gap-5">
        <!-- Floor Dropdown -->
        <div class="flex flex-col gap-1.5">
          <label for="room-floor" class="text-sm font-semibold text-slate-700">
            Lantai <span class="text-red-500">*</span>
          </label>
          <Dropdown
            id="room-floor"
            v-model="room.floor_id"
            :options="locationStore.floors"
            optionLabel="name"
            optionValue="id"
            placeholder="Pilih lantai..."
            :invalid="submitted && !room.floor_id"
            class="w-full"
          />
          <small v-if="submitted && !room.floor_id" class="text-red-500 text-xs">Lantai wajib dipilih.</small>
        </div>

        <!-- Name Field -->
        <div class="flex flex-col gap-1.5">
          <label for="room-name" class="text-sm font-semibold text-slate-700">
            Nama Ruangan <span class="text-red-500">*</span>
          </label>
          <InputText id="room-name" v-model.trim="room.name" placeholder="Contoh: Ruang Arsip A" :invalid="submitted && !room.name" autofocus />
          <small v-if="submitted && !room.name" class="text-red-500 text-xs">Nama ruangan wajib diisi.</small>
        </div>

        <!-- Points Field -->
        <div class="flex flex-col gap-1.5">
          <label for="room-points" class="text-sm font-semibold text-slate-700">
            Koordinat (JSON) <span class="text-slate-400 font-normal text-xs ml-1">opsional</span>
          </label>
          <InputText id="room-points" v-model="pointsText" placeholder='[{"x":10, "y":20}]' :invalid="!!pointsError" @input="validatePoints" />
          <small v-if="pointsError" class="text-red-500 text-xs">{{ pointsError }}</small>
          <small v-else class="text-slate-400 text-xs">Format: array of {x, y} objects</small>
        </div>

        <!-- Coordinate Review Checkbox -->
        <div class="flex items-center gap-3">
          <Checkbox inputId="room-review" v-model="room.needs_coordinate_review" :binary="true" />
          <label for="room-review" class="text-sm text-slate-700 cursor-pointer">Perlu Review Koordinat</label>
        </div>
      </div>

      <template #footer>
        <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="hideDialog" />
        <Button label="Simpan" icon="pi pi-check" @click="saveRoom" :loading="locationStore.loading" />
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

const roomDialog   = ref(false)
const room         = ref({ needs_coordinate_review: false })
const submitted    = ref(false)
const pointsText   = ref('[]')
const pointsError  = ref('')
const globalFilter = ref('')

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })
watch(globalFilter, (val) => { filters.value.global.value = val })

onMounted(async () => {
  try {
    await Promise.all([locationStore.fetchFloors(), locationStore.fetchRooms()])
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

const openNew  = () => { room.value = { needs_coordinate_review: false }; pointsText.value = '[]'; pointsError.value = ''; submitted.value = false; roomDialog.value = true }
const hideDialog = () => { roomDialog.value = false; submitted.value = false }

const saveRoom = async () => {
  submitted.value = true
  if (!validatePoints()) return
  if (!room.value.name?.trim() || !room.value.floor_id) return
  try {
    const data = { ...room.value, points: JSON.parse(pointsText.value || '[]') }
    if (room.value.id) {
      await locationStore.updateRoom(room.value.id, data)
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data ruangan diperbarui', life: 3000 })
    } else {
      await locationStore.createRoom(data)
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data ruangan ditambahkan', life: 3000 })
    }
    roomDialog.value = false; room.value = {}
  } catch { toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menyimpan data ruangan', life: 3000 }) }
}

const editRoom = (editData) => { room.value = { ...editData }; pointsText.value = JSON.stringify(editData.points || []); pointsError.value = ''; roomDialog.value = true }

const confirmDeleteRoom = (deleteData) => {
  confirm.require({
    message: `Yakin ingin menghapus "${deleteData.name}"?`,
    header: 'Konfirmasi Hapus', icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger', acceptLabel: 'Ya, Hapus', rejectLabel: 'Batal',
    accept: async () => {
      try { await locationStore.deleteRoom(deleteData.id); toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data ruangan dihapus', life: 3000 }) }
      catch { toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menghapus data ruangan', life: 3000 }) }
    }
  })
}
</script>
