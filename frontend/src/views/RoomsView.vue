<template>
  <div class="p-2 md:p-0">
    <!-- Manage Rooms Page -->
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
      <div>
        <h1 class="text-xl md:text-2xl font-bold text-slate-800">Ruangan (Rooms)</h1>
        <p class="text-xs md:text-sm text-slate-500 mt-1">Kelola data ruangan per lantai gedung</p>
      </div>
      <Button label="Tambah Ruangan" icon="pi pi-plus" @click="openNew" class="w-full md:w-auto !rounded-xl shadow-md" />
    </div>

    <!-- DataTable Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <!-- Table Toolbar (Desktop Search) -->
      <div class="hidden md:flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <span class="text-sm font-semibold text-slate-700">
          <i class="pi pi-table mr-2 text-emerald-500"></i>Daftar Ruangan
        </span>
        <IconField>
          <InputIcon class="pi pi-search" />
          <InputText
            v-model="globalFilter"
            placeholder="Cari ruangan..."
            class="!rounded-lg text-sm"
            style="width: 240px;"
          />
        </IconField>
      </div>

      <!-- Mobile Search Bar -->
      <div class="md:hidden p-4 border-b border-slate-100">
        <IconField class="w-full">
            <InputIcon class="pi pi-search" />
            <InputText v-model="globalFilter" placeholder="Cari ruangan..." class="w-full !rounded-xl" />
        </IconField>
      </div>

      <!-- Desktop View -->
      <DataTable
        v-if="!isMobile"
        :value="locationStore.rooms"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[5, 10, 25]"
        dataKey="id"
        :loading="locationStore.loading"
        :globalFilterFields="['id', 'name', 'floor.name']"
        :filters="filters"
        stripedRows
        responsiveLayout="scroll"
      >
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="pi pi-table text-4xl mb-3 block opacity-40"></i>
            <p class="text-sm">Belum ada data ruangan.</p>
          </div>
        </template>

        <Column field="id" header="ID" sortable style="width: 80px;" />
        <Column field="name" header="Nama Ruangan" sortable />
        <Column field="keterangan" header="Keterangan" sortable>
          <template #body="slotProps">
            <span class="text-sm text-slate-600 truncate inline-block" style="max-width: 250px;">
              {{ slotProps.data.keterangan || '—' }}
            </span>
          </template>
        </Column>
        <Column field="floor.name" header="Lantai" sortable>
          <template #body="slotProps">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium">
              <i class="pi pi-building text-xs"></i>
              {{ slotProps.data.floor?.name || '—' }}
            </span>
          </template>
        </Column>
        <Column header="Aksi" style="width: 120px;">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <Button icon="pi pi-pencil" text rounded severity="secondary" @click="editRoom(slotProps.data)" />
              <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDeleteRoom(slotProps.data)" />
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
        <div v-else-if="filteredRooms.length === 0" class="p-10 text-center text-slate-400 italic text-sm">
            Tidak ada data ruangan.
        </div>
        <div v-for="data in filteredRooms" :key="data.id" class="p-4 flex flex-col gap-2 hover:bg-slate-50">
            <div class="flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-slate-800">{{ data.name }}</span>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <i class="pi pi-building text-[10px] text-blue-500"></i>
                        <span class="text-[10px] font-bold text-blue-600 uppercase">{{ data.floor?.name }}</span>
                    </div>
                </div>
                <div class="flex gap-1">
                    <Button icon="pi pi-pencil" text rounded severity="secondary" @click="editRoom(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDeleteRoom(data)" />
                </div>
            </div>
            <p v-if="data.keterangan" class="text-[10px] text-slate-500 line-clamp-1">{{ data.keterangan }}</p>
        </div>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog 
        v-model:visible="roomDialog" 
        :modal="true" 
        class="w-full max-w-4xl"
        :maximized="isMobile"
        :showHeader="true"
    >
      <template #header>
          <div class="flex items-center justify-between w-full pr-8 md:pr-0">
              <h3 class="text-base md:text-xl font-bold text-slate-800">
                  {{ room.id ? 'Edit Ruangan' : 'Tambah Ruangan' }}
              </h3>
          </div>
      </template>

      <div class="flex flex-col gap-5 mt-2 p-2 md:p-0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Floor Dropdown -->
            <div class="flex flex-col gap-1.5">
                <label for="room-floor" class="text-sm font-semibold text-slate-700">
                    Lantai <span class="text-red-500">*</span>
                </label>
                <Select
                    id="room-floor"
                    v-model="room.floor_id"
                    :options="locationStore.floors"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Pilih lantai..."
                    :invalid="submitted && !room.floor_id"
                    class="w-full !rounded-xl"
                />
                <small v-if="submitted && !room.floor_id" class="text-red-500 text-xs">Lantai wajib dipilih.</small>
            </div>

            <!-- Name Field -->
            <div class="flex flex-col gap-1.5">
                <label for="room-name" class="text-sm font-semibold text-slate-700">
                    Nama Ruangan <span class="text-red-500">*</span>
                </label>
                <InputText id="room-name" v-model.trim="room.name" placeholder="Contoh: Ruang Arsip A" class="!rounded-xl" :invalid="submitted && !room.name" />
                <small v-if="submitted && !room.name" class="text-red-500 text-xs">Nama ruangan wajib diisi.</small>
            </div>
        </div>

        <!-- Keterangan Field -->
        <div class="flex flex-col gap-1.5">
          <label for="room-keterangan" class="text-sm font-semibold text-slate-700">
            Keterangan <span class="text-slate-400 font-normal text-xs ml-1">opsional</span>
          </label>
          <Textarea id="room-keterangan" v-model="room.keterangan" placeholder="Catatan tambahan tentang ruangan (opsional)" rows="2" class="w-full !rounded-xl" />
        </div>

        <!-- Polygon Drawer -->
        <div v-if="room.floor_id" class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-slate-700">Tentukan Area Ruangan pada Denah</label>
          <div class="rounded-xl overflow-hidden border border-slate-200">
              <RoomPolygonDrawer
                :floorImageUrl="selectedFloorImageUrl"
                :initialPoints="room.points || []"
                @update:points="(pts) => room.points = pts"
              />
          </div>
          <small v-if="submitted && pointsError" class="text-red-500 text-xs">{{ pointsError }}</small>
        </div>
        
        <div v-else class="flex flex-col gap-1.5">
          <label class="text-sm font-semibold text-slate-700">Area Ruangan</label>
          <div class="border border-dashed border-slate-200 rounded-xl p-6 text-center text-slate-400 text-sm bg-slate-50">
            <i class="pi pi-map-marker text-2xl mb-2 block opacity-40"></i>
            Pilih lantai terlebih dahulu untuk menggambar area ruangan
          </div>
        </div>

        <!-- Coordinate Review Checkbox -->
        <div class="flex items-center gap-3 bg-slate-50 p-3 rounded-xl border border-slate-100">
          <Checkbox inputId="room-review" v-model="room.needs_coordinate_review" :binary="true" />
          <label for="room-review" class="text-sm text-slate-700 cursor-pointer font-medium">Perlu Review Koordinat</label>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 p-2 md:p-0">
            <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="hideDialog" class="hidden md:flex" />
            <Button label="Simpan" icon="pi pi-check" @click="saveRoom" :loading="locationStore.loading" class="!rounded-xl px-6" />
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
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import ProgressSpinner from 'primevue/progressspinner'
import RoomPolygonDrawer from '../components/RoomPolygonDrawer.vue'

const locationStore = useLocationStore()
const toast         = useToast()
const confirm       = useConfirm()

// Mobile detection
const windowWidth = ref(window.innerWidth)
const updateWidth = () => { windowWidth.value = window.innerWidth }
onMounted(() => window.addEventListener('resize', updateWidth))
onUnmounted(() => window.removeEventListener('resize', updateWidth))
const isMobile = computed(() => windowWidth.value < 768)

const roomDialog   = ref(false)
const room         = ref({ needs_coordinate_review: false })
const submitted    = ref(false)
const pointsText   = ref('[]')
const pointsError  = ref('')
const globalFilter = ref('')

const filteredRooms = computed(() => {
    if (!globalFilter.value) return locationStore.rooms
    const q = globalFilter.value.toLowerCase()
    return locationStore.rooms.filter(r => 
        r.name?.toLowerCase().includes(q) || 
        r.floor?.name?.toLowerCase().includes(q) ||
        r.id?.toString().includes(q)
    )
})

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })
watch(globalFilter, (val) => { filters.value.global.value = val })

onMounted(async () => {
  try {
    await Promise.all([locationStore.fetchFloors(), locationStore.fetchRooms()])
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data', life: 3000 })
  }
})

const selectedFloorImageUrl = computed(() => {
  if (!room.value.floor_id) return ''
  const floorId = Number(room.value.floor_id)
  const floor = locationStore.floors.find(f => Number(f.id) === floorId)
  if (!floor || !floor.floor_plan_image) return ''
  const baseUrl = import.meta.env.VITE_API_BASE_URL.replace('/api/v1', '').replace(/\/$/, '')
  return `${baseUrl}/storage/${floor.floor_plan_image}`
})

const validatePoints = () => {
  const pts = room.value.points
  if (!Array.isArray(pts) || pts.length < 4) {
    pointsError.value = 'Minimal 4 titik diperlukan untuk membentuk area ruangan'
    return false
  }
  pointsError.value = ''
  return true
}

const openNew  = () => { room.value = { needs_coordinate_review: false, points: [] }; pointsError.value = ''; submitted.value = false; roomDialog.value = true }
const hideDialog = () => { roomDialog.value = false; submitted.value = false }

const saveRoom = async () => {
  submitted.value = true
  if (!validatePoints()) return
  if (!room.value.name?.trim() || !room.value.floor_id) return
  try {
    const data = { ...room.value }
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

const editRoom = (editData) => { room.value = { ...editData, floor_id: Number(editData.floor_id), points: editData.points || [] }; pointsError.value = ''; roomDialog.value = true }

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
