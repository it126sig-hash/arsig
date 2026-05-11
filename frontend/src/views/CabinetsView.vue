<template>
  <div class="p-2 md:p-0">
    <!-- Manage Cabinets Page -->
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
      <div>
        <h1 class="text-xl md:text-2xl font-bold text-slate-800">Lemari (Cabinets)</h1>
        <p class="text-xs md:text-sm text-slate-500 mt-1">Kelola data lemari arsip dalam ruangan</p>
      </div>
      <Button label="Tambah Lemari" icon="pi pi-plus" @click="openNew" class="w-full md:w-auto !rounded-xl shadow-md" />
    </div>

    <!-- DataTable Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <!-- Table Toolbar (Desktop Search) -->
      <div class="hidden md:flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <span class="text-sm font-semibold text-slate-700">
          <i class="pi pi-server mr-2 text-orange-500"></i>Daftar Lemari
        </span>
        <IconField>
          <InputIcon class="pi pi-search" />
          <InputText
            v-model="globalFilter"
            placeholder="Cari lemari..."
            class="!rounded-lg text-sm"
            style="width: 240px;"
          />
        </IconField>
      </div>

      <!-- Mobile Search Bar -->
      <div class="md:hidden p-4 border-b border-slate-100">
        <IconField class="w-full">
            <InputIcon class="pi pi-search" />
            <InputText v-model="globalFilter" placeholder="Cari lemari..." class="w-full !rounded-xl" />
        </IconField>
      </div>

      <!-- Desktop View -->
      <DataTable
        v-if="!isMobile"
        :value="locationStore.cabinets"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[5, 10, 25]"
        dataKey="id"
        :loading="locationStore.loading"
        :globalFilterFields="['id', 'name', 'keterangan', 'room.name', 'room.floor.name']"
        :filters="filters"
        stripedRows
        responsiveLayout="scroll"
      >
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="pi pi-server text-4xl mb-3 block opacity-40"></i>
            <p class="text-sm">Belum ada data lemari.</p>
          </div>
        </template>

        <Column field="id" header="ID" sortable style="width: 80px;" />
        <Column field="name" header="Nama Lemari" sortable />
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
        <Column header="Aksi" style="width: 120px;">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <Button icon="pi pi-pencil" text rounded severity="secondary" @click="editCabinet(slotProps.data)" />
              <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDeleteCabinet(slotProps.data)" />
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
        <div v-else-if="filteredCabinets.length === 0" class="p-10 text-center text-slate-400 italic text-sm">
            Tidak ada data lemari.
        </div>
        <div v-for="data in filteredCabinets" :key="data.id" class="p-4 flex flex-col gap-2 hover:bg-slate-50">
            <div class="flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-slate-800">{{ data.name }}</span>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <i class="pi pi-table text-[10px] text-emerald-500"></i>
                        <span class="text-[10px] font-bold text-emerald-600 uppercase">{{ data.room?.name }}</span>
                        <span class="text-slate-300 text-[10px]">•</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase">{{ data.room?.floor?.name }}</span>
                    </div>
                </div>
                <div class="flex gap-1">
                    <Button icon="pi pi-pencil" text rounded severity="secondary" @click="editCabinet(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDeleteCabinet(data)" />
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Tag v-if="data.door_count" :value="formatDoorCountShort(data.door_count)" severity="warn" class="!text-[9px] !px-1.5" />
                <p v-if="data.keterangan" class="text-[10px] text-slate-500 truncate">{{ data.keterangan }}</p>
            </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog 
        v-model:visible="cabinetDialog" 
        :modal="true" 
        class="w-full max-w-4xl"
        :maximized="isMobile"
        :showHeader="true"
    >
      <template #header>
          <div class="flex items-center justify-between w-full pr-8 md:pr-0">
              <h3 class="text-base md:text-xl font-bold text-slate-800">
                  {{ cabinet.id ? 'Edit Lemari' : 'Tambah Lemari' }}
              </h3>
          </div>
      </template>

      <div class="flex flex-col gap-5 mt-2 p-2 md:p-0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Room Dropdown -->
            <div class="flex flex-col gap-1.5">
                <label for="cabinet-room" class="text-sm font-semibold text-slate-700">
                    Ruangan <span class="text-red-500">*</span>
                </label>
                <Select
                    id="cabinet-room"
                    v-model="cabinet.room_id"
                    :options="locationStore.rooms"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Pilih ruangan..."
                    :invalid="submitted && !cabinet.room_id"
                    class="w-full !rounded-xl"
                >
                    <template #option="slotProps">
                        <span>{{ slotProps.option.name }} <span class="text-slate-400 text-xs">({{ slotProps.option.floor?.name }})</span></span>
                    </template>
                </Select>
                <small v-if="submitted && !cabinet.room_id" class="text-red-500 text-xs">Ruangan wajib dipilih.</small>
            </div>

            <!-- Name Field -->
            <div class="flex flex-col gap-1.5">
                <label for="cabinet-name" class="text-sm font-semibold text-slate-700">
                    Nama Lemari <span class="text-red-500">*</span>
                </label>
                <InputText id="cabinet-name" v-model.trim="cabinet.name" placeholder="Contoh: Lemari A1" class="!rounded-xl" :invalid="submitted && !cabinet.name" autofocus />
                <small v-if="submitted && !cabinet.name" class="text-red-500 text-xs">Nama lemari wajib diisi.</small>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Door Count Field -->
            <div class="flex flex-col gap-1.5">
                <label for="cabinet-door-count" class="text-sm font-semibold text-slate-700">
                    Jumlah Pintu <span class="text-slate-400 font-normal text-xs ml-1">format: Kolom * Baris</span>
                </label>
                <InputText id="cabinet-door-count" v-model.trim="cabinet.door_count" placeholder="Contoh: 4 * 3" class="!rounded-xl" :invalid="submitted && cabinet.door_count && !isDoorCountValid" />
                <small v-if="cabinet.door_count && isDoorCountValid" class="text-emerald-600 text-[10px] flex items-center gap-1">
                    <i class="pi pi-check-circle"></i>
                    = {{ doorCountTotal }} pintu
                </small>
                <small v-else-if="cabinet.door_count && !isDoorCountValid" class="text-red-500 text-[10px]">
                    Format tidak valid. Gunakan format: X * Y (contoh: 4 * 3)
                </small>
            </div>

            <!-- Coordinate Review Checkbox -->
            <div class="flex items-center gap-3 bg-slate-50 p-3 rounded-xl border border-slate-100 self-end">
                <Checkbox inputId="cabinet-review" v-model="cabinet.needs_coordinate_review" :binary="true" />
                <label for="cabinet-review" class="text-sm text-slate-700 cursor-pointer font-medium">Perlu Review Koordinat</label>
            </div>
        </div>

        <!-- Keterangan Field -->
        <div class="flex flex-col gap-1.5">
          <label for="cabinet-keterangan" class="text-sm font-semibold text-slate-700">
            Keterangan <span class="text-slate-400 font-normal text-xs ml-1">opsional</span>
          </label>
          <Textarea id="cabinet-keterangan" v-model="cabinet.keterangan" placeholder="Catatan tambahan tentang lemari (opsional)" rows="2" class="w-full !rounded-xl" />
        </div>

        <!-- KonvaJS Polygon Drawer -->
        <div v-if="cabinet.room_id && selectedFloorImageUrl" class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-slate-700">Tentukan Posisi Lemari dalam Ruangan</label>
          <div class="rounded-xl overflow-hidden border border-slate-200">
              <CabinetPolygonDrawer
                :floorImageUrl="selectedFloorImageUrl"
                :existingRooms="selectedFloorRooms"
                :initialPoints="cabinet.points || []"
                @update:points="(pts) => cabinet.points = pts"
              />
          </div>
        </div>
        <div v-else-if="!cabinet.room_id" class="border border-dashed border-slate-200 rounded-xl p-6 text-center text-sm text-slate-400 bg-slate-50">
          <i class="pi pi-map-marker text-2xl mb-2 block opacity-40"></i>
          Pilih ruangan terlebih dahulu untuk menggambar posisi lemari
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 p-2 md:p-0">
            <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="hideDialog" class="hidden md:flex" />
            <Button label="Simpan" icon="pi pi-check" @click="saveCabinet" :loading="locationStore.loading" class="!rounded-xl px-6" />
        </div>
      </template>
    </Dialog>

    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
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
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import ProgressSpinner from 'primevue/progressspinner'
import CabinetPolygonDrawer from '../components/CabinetPolygonDrawer.vue'

const locationStore = useLocationStore()
const toast         = useToast()
const confirm       = useConfirm()

// Mobile detection
const windowWidth = ref(window.innerWidth)
const updateWidth = () => { windowWidth.value = window.innerWidth }
onMounted(() => window.addEventListener('resize', updateWidth))
onUnmounted(() => window.removeEventListener('resize', updateWidth))
const isMobile = computed(() => windowWidth.value < 768)

const cabinetDialog = ref(false)
const cabinet       = ref({ needs_coordinate_review: false })
const submitted     = ref(false)
const globalFilter  = ref('')

const filteredCabinets = computed(() => {
    if (!globalFilter.value) return locationStore.cabinets
    const q = globalFilter.value.toLowerCase()
    return locationStore.cabinets.filter(c => 
        c.name?.toLowerCase().includes(q) || 
        c.room?.name?.toLowerCase().includes(q) ||
        c.room?.floor?.name?.toLowerCase().includes(q) ||
        c.id?.toString().includes(q)
    )
})

const formatDoorCountShort = (dc) => {
    if (!dc) return ''
    const parts = dc.split('*').map(s => s.trim())
    if (parts.length !== 2) return dc
    return `${parts[0]}×${parts[1]}`
}

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })
watch(globalFilter, (val) => { filters.value.global.value = val })

const appBase = import.meta.env.VITE_API_BASE_URL.replace('/api/v1', '').replace(/\/$/, '')

const selectedFloorImageUrl = computed(() => {
  if (!cabinet.value.room_id) return ''
  const roomId = Number(cabinet.value.room_id)
  const room = locationStore.rooms.find(r => Number(r.id) === roomId)
  if (!room) return ''
  const floor = locationStore.floors.find(f => Number(f.id) === Number(room.floor_id))
  if (!floor || !floor.floor_plan_image) return ''
  return `${appBase}/storage/${floor.floor_plan_image}`
})

const selectedFloorRooms = computed(() => {
  if (!cabinet.value.room_id) return []
  const roomId = Number(cabinet.value.room_id)
  return locationStore.rooms
    .filter(r => Number(r.id) === roomId && r.points && r.points.length >= 3)
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
  cabinet.value = {
    ...editData,
    room_id: Number(editData.room_id),
    points: editData.points || []
  }
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
