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
        :globalFilterFields="['id', 'name', 'keterangan', 'room.name', 'room.floor.name']"
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
        <Column field="keterangan" header="Keterangan" sortable>
          <template #body="slotProps">
            <span v-if="slotProps.data.keterangan" class="text-sm text-slate-600 truncate block max-w-[200px]" :title="slotProps.data.keterangan">{{ slotProps.data.keterangan }}</span>
            <span v-else class="text-xs text-slate-400 italic">—</span>
          </template>
        </Column>
        <Column field="door_count" header="Jumlah Pintu" sortable style="width: 160px;">
          <template #body="slotProps">
            <span v-if="slotProps.data.door_count" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
              <i class="pi pi-th-large text-xs"></i>
              {{ formatDoorCount(slotProps.data.door_count) }}
            </span>
            <span v-else class="text-xs text-slate-400 italic">—</span>
          </template>
        </Column>
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
    <Dialog v-model:visible="cabinetDialog" :style="{ width: '900px' }" header="Detail Lemari" :modal="true" :draggable="false">
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

        <!-- Keterangan Field -->
        <div class="flex flex-col gap-1.5">
          <label for="cabinet-keterangan" class="text-sm font-semibold text-slate-700">
            Keterangan <span class="text-slate-400 font-normal text-xs ml-1">opsional</span>
          </label>
          <Textarea id="cabinet-keterangan" v-model="cabinet.keterangan" placeholder="Catatan tambahan tentang lemari (opsional)" rows="2" class="w-full" />
        </div>

        <!-- Door Count Field -->
        <div class="flex flex-col gap-1.5">
          <label for="cabinet-door-count" class="text-sm font-semibold text-slate-700">
            Jumlah Pintu <span class="text-slate-400 font-normal text-xs ml-1">format: X * Y</span>
          </label>
          <InputText id="cabinet-door-count" v-model.trim="cabinet.door_count" placeholder="Contoh: 4 * 3" :invalid="submitted && cabinet.door_count && !isDoorCountValid" />
          <small v-if="cabinet.door_count && isDoorCountValid" class="text-emerald-600 text-xs flex items-center gap-1">
            <i class="pi pi-check-circle text-xs"></i>
            = {{ doorCountTotal }} pintu
          </small>
          <small v-else-if="cabinet.door_count && !isDoorCountValid" class="text-red-500 text-xs">
            Format tidak valid. Gunakan format: X * Y (contoh: 4 * 3)
          </small>
        </div>

        <!-- KonvaJS Polygon Drawer -->
        <div v-if="cabinet.room_id && selectedFloorImageUrl">
          <CabinetPolygonDrawer
            :floorImageUrl="selectedFloorImageUrl"
            :existingRooms="selectedFloorRooms"
            :initialPoints="cabinet.points || []"
            @update:points="(pts) => cabinet.points = pts"
          />
        </div>
        <div v-else-if="!cabinet.room_id" class="border border-dashed border-slate-300 rounded-xl p-6 text-center text-sm text-slate-400">
          <i class="pi pi-map-marker text-2xl mb-2 block opacity-40"></i>
          Pilih ruangan terlebih dahulu untuk menampilkan peta
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
import { ref, computed, onMounted, watch } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { useLocationStore } from '../store/location'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import CabinetPolygonDrawer from '../components/CabinetPolygonDrawer.vue'

const locationStore = useLocationStore()
const toast         = useToast()
const confirm       = useConfirm()

const cabinetDialog = ref(false)
const cabinet       = ref({ needs_coordinate_review: false })
const submitted     = ref(false)
const globalFilter  = ref('')

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })
watch(globalFilter, (val) => { filters.value.global.value = val })

const appBase = import.meta.env.VITE_API_BASE_URL.replace('/api/v1', '')

const selectedFloorImageUrl = computed(() => {
  if (!cabinet.value.room_id) return ''
  const room = locationStore.rooms.find(r => r.id === cabinet.value.room_id)
  if (!room) return ''
  const floor = locationStore.floors.find(f => f.id === room.floor_id)
  if (!floor || !floor.floor_plan_image) return ''
  return `${appBase}/storage/${floor.floor_plan_image}`
})

const selectedFloorRooms = computed(() => {
  if (!cabinet.value.room_id) return []
  const room = locationStore.rooms.find(r => r.id === cabinet.value.room_id)
  if (!room) return []
  return locationStore.rooms
    .filter(r => r.floor_id === room.floor_id && r.points && r.points.length >= 3)
    .map(r => ({ name: r.name, points: r.points }))
})

const isDoorCountValid = computed(() => {
  if (!cabinet.value.door_count) return false
  return /^\d+\s*\*\s*\d+$/.test(cabinet.value.door_count)
})

const doorCountTotal = computed(() => {
  if (!isDoorCountValid.value) return 0
  const [cols, rows] = cabinet.value.door_count.split('*').map(s => parseInt(s.trim()))
  return cols * rows
})

const formatDoorCount = (dc) => {
  if (!dc) return '—'
  const parts = dc.split('*').map(s => s.trim())
  if (parts.length !== 2) return dc
  const total = parseInt(parts[0]) * parseInt(parts[1])
  return `${parts[0]} × ${parts[1]} = ${total} pintu`
}

onMounted(async () => {
  try {
    await Promise.all([locationStore.fetchFloors(), locationStore.fetchRooms(), locationStore.fetchCabinets()])
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data', life: 3000 })
  }
})

const openNew    = () => { cabinet.value = { needs_coordinate_review: false, points: [] }; submitted.value = false; cabinetDialog.value = true }
const hideDialog = () => { cabinetDialog.value = false; submitted.value = false }

const saveCabinet = async () => {
  submitted.value = true
  if (!cabinet.value.name?.trim() || !cabinet.value.room_id) return
  if (cabinet.value.door_count && !isDoorCountValid.value) return
  try {
    const data = {
      room_id: cabinet.value.room_id,
      name: cabinet.value.name,
      keterangan: cabinet.value.keterangan || null,
      door_count: cabinet.value.door_count || null,
      points: cabinet.value.points || [],
      needs_coordinate_review: cabinet.value.needs_coordinate_review || false
    }
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

const editCabinet = (editData) => {
  cabinet.value = { ...editData }
  submitted.value = false
  cabinetDialog.value = true
}

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
