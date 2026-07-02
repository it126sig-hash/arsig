<template>
  <div>
    <!-- Manage Cabinet Slots Page -->
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
      <div>
        <h1 class="text-xl md:text-2xl font-bold text-slate-800">Pintu Lemari (Slots)</h1>
        <p class="text-xs md:text-sm text-slate-500 mt-1">Kelola pintu/slot penyimpanan dokumen dalam lemari</p>
      </div>
      <div class="flex gap-2 w-full md:w-auto">
        <Button
          :label="showTrashed ? 'Tampilkan Aktif' : 'Tampilkan Terhapus'"
          :icon="showTrashed ? 'pi pi-list' : 'pi pi-trash'"
          severity="secondary"
          outlined
          @click="toggleTrashed"
          class="w-1/2 md:w-auto !rounded-xl"
        />
        <Button label="Tambah Data" icon="pi pi-plus" @click="openNew" class="w-1/2 md:w-auto !rounded-xl shadow-md" v-if="!showTrashed" />
      </div>
    </div>

    <!-- Cabinet Selector for Door Grid -->
    <div v-if="!showTrashed" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
      <div class="flex items-center gap-4">
        <label class="text-sm font-semibold text-slate-700 whitespace-nowrap">Pilih Lemari:</label>
        <Dropdown
          v-model="selectedCabinetId"
          :options="locationStore.cabinets"
          optionLabel="name"
          optionValue="id"
          placeholder="Pilih lemari untuk melihat visualisasi pintu..."
          class="w-full max-w-md"
        >
          <template #option="slotProps">
            <span>{{ slotProps.option.name }} <span class="text-slate-400 text-xs">({{ slotProps.option.room?.name }})</span></span>
          </template>
        </Dropdown>
      </div>

      <!-- Door Grid Visualization -->
      <div v-if="selectedCabinet && selectedCabinet.door_count" class="mt-5">
        <CabinetDoorGrid
          :doorCount="selectedCabinet.door_count"
          :slots="selectedCabinetSlots"
          :cabinetName="selectedCabinet.name"
          @slot-click="onClickDoor"
        />
      </div>
      <div v-else-if="selectedCabinetId && !selectedCabinet?.door_count" class="mt-4 text-center py-8 text-slate-400 text-sm">
        <i class="pi pi-info-circle text-2xl mb-2 block opacity-40"></i>
        Lemari ini belum memiliki konfigurasi jumlah pintu (door_count).
      </div>
    </div>

    <!-- Placeholder when no cabinet selected -->
    <div v-if="!showTrashed && !selectedCabinetId" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center text-slate-400">
      <i class="pi pi-server text-4xl mb-3 block opacity-40"></i>
      <p class="text-sm">Pilih lemari terlebih dahulu untuk melihat daftar slot.</p>
    </div>

    <!-- DataTable Card -->
    <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <!-- Table Toolbar (Desktop Search) -->
      <div class="hidden md:flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <span class="text-sm font-semibold text-slate-700">
          <i class="pi pi-inbox mr-2 text-purple-500"></i>Daftar Slot Lemari
        </span>
        <IconField>
          <InputIcon class="pi pi-search" />
          <InputText
            v-model="globalFilter"
            placeholder="Cari slot..."
            class="!rounded-lg text-sm"
            style="width: 240px;"
          />
        </IconField>
      </div>

      <!-- Mobile Search Bar -->
      <div class="md:hidden p-4 border-b border-slate-100">
        <IconField class="w-full">
            <InputIcon class="pi pi-search" />
            <InputText v-model="globalFilter" placeholder="Cari slot..." class="w-full !rounded-xl" />
        </IconField>
      </div>

      <!-- Desktop View -->
      <DataTable
        v-if="!isMobile"
        :value="locationStore.cabinetSlots"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[5, 10, 25]"
        dataKey="id"
        :loading="locationStore.loading"
        :globalFilterFields="['id', 'name', 'keterangan', 'cabinet.name', 'cabinet.room.name']"
        :filters="filters"
        stripedRows
        responsiveLayout="scroll"
      >
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="pi pi-inbox text-4xl mb-3 block opacity-40"></i>
            <p class="text-sm">Belum ada data slot lemari.</p>
          </div>
        </template>

        <Column field="id" header="ID" sortable style="width: 80px;" />
        <Column field="name" header="Nama Slot" sortable />
        <Column field="status" header="Status" sortable style="width: 120px;">
          <template #body="slotProps">
            <span
              :class="[
                'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
                statusClass(slotProps.data.status)
              ]"
            >
              {{ statusLabel(slotProps.data.status) }}
            </span>
          </template>
        </Column>
        <Column field="keterangan" header="Keterangan" sortable>
          <template #body="slotProps">
            <span v-if="slotProps.data.keterangan" class="text-sm text-slate-600 truncate block max-w-[200px]" :title="slotProps.data.keterangan">{{ slotProps.data.keterangan }}</span>
            <span v-else class="text-xs text-slate-400 italic">—</span>
          </template>
        </Column>
        <Column field="cabinet.name" header="Lemari" sortable>
          <template #body="slotProps">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
              <i class="pi pi-server text-xs"></i>
              {{ slotProps.data.cabinet?.name || '—' }}
            </span>
          </template>
        </Column>
        <Column field="cabinet.room.name" header="Ruangan" sortable>
          <template #body="slotProps">
            <span class="text-sm text-slate-500">{{ slotProps.data.cabinet?.room?.name || '—' }}</span>
          </template>
        </Column>
        <Column header="PIC" style="width: 180px;">
          <template #body="slotProps">
            <div v-if="slotProps.data.pic_users && slotProps.data.pic_users.length > 0" class="flex flex-wrap gap-1">
              <span
                v-for="pic in slotProps.data.pic_users"
                :key="pic.id"
                class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full text-xs font-medium"
              >
                <span class="w-4 h-4 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold">{{ pic.name[0].toUpperCase() }}</span>
                {{ pic.name }}
              </span>
            </div>
            <span v-else class="text-xs text-slate-400 italic">Tidak ada</span>
          </template>
        </Column>
        <Column header="Tags" style="width: 160px;">
          <template #body="slotProps">
            <div v-if="slotProps.data.tags && slotProps.data.tags.length > 0" class="flex flex-wrap gap-1">
              <span
                v-for="tag in slotProps.data.tags"
                :key="tag.id"
                class="inline-flex items-center px-1.5 py-0.5 bg-violet-50 text-violet-600 rounded text-[10px] font-medium"
              >
                #{{ tag.nama }}
              </span>
            </div>
            <span v-else class="text-xs text-slate-400 italic">—</span>
          </template>
        </Column>
        <Column header="Aksi" style="width: 120px;" :exportable="false">
          <template #body="slotProps">
            <div class="flex items-center gap-2" v-if="!showTrashed">
              <Button icon="pi pi-pencil" text rounded severity="secondary" @click="editCabinetSlot(slotProps.data)" />
              <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDeleteCabinetSlot(slotProps.data)" />
            </div>
            <div class="flex items-center gap-2" v-else>
              <Button icon="pi pi-refresh" text rounded severity="success" @click="handleRestore(slotProps.data)" title="Pulihkan" />
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
        <div v-else-if="filteredSlots.length === 0" class="p-10 text-center text-slate-400 italic text-sm">
            Tidak ada data slot.
        </div>
        <div v-for="data in filteredSlots" :key="data.id" class="p-4 flex flex-col gap-3 hover:bg-slate-50">
            <div class="flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-slate-800">Slot {{ data.name }}</span>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <i class="pi pi-server text-[10px] text-orange-500"></i>
                        <span class="text-[10px] font-bold text-orange-600 uppercase">{{ data.cabinet?.name }}</span>
                        <span class="text-slate-300 text-[10px]">•</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase">{{ data.cabinet?.room?.name }}</span>
                    </div>
                </div>
                <span
                  :class="[
                    'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-tight',
                    statusClass(data.status)
                  ]"
                >
                  {{ statusLabel(data.status) }}
                </span>
            </div>

            <!-- PIC & Tags Section -->
            <div class="flex flex-wrap gap-2">
                <div v-if="data.pic_users && data.pic_users.length > 0" class="flex flex-wrap gap-1">
                    <span v-for="pic in data.pic_users" :key="pic.id" class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded text-[9px] font-medium border border-blue-100">
                        {{ pic.name }}
                    </span>
                </div>
                <div v-if="data.tags && data.tags.length > 0" class="flex flex-wrap gap-1">
                    <span v-for="tag in data.tags" :key="tag.id" class="text-[9px] text-slate-400">#{{ tag.nama }}</span>
                </div>
            </div>

            <p v-if="data.keterangan" class="text-[10px] text-slate-500 line-clamp-2 italic">{{ data.keterangan }}</p>

            <!-- Actions Row -->
            <div class="flex gap-2 border-t border-slate-50 pt-2" v-if="!showTrashed">
                <Button label="Edit" icon="pi pi-pencil" size="small" text severity="secondary" class="flex-1 !bg-slate-50 !rounded-lg" @click="editCabinetSlot(data)" />
                <Button label="Hapus" icon="pi pi-trash" size="small" text severity="danger" class="flex-1 !bg-red-50 !rounded-lg" @click="confirmDeleteCabinetSlot(data)" />
            </div>
            <div class="flex gap-2 border-t border-slate-50 pt-2" v-else>
                <Button label="Pulihkan" icon="pi pi-refresh" size="small" text severity="success" class="flex-1 !bg-green-50 !rounded-lg" @click="handleRestore(data)" />
            </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog 
        v-model:visible="slotDialog" 
        :modal="true" 
        class="w-full max-w-lg"
        :maximized="isMobile"
        :showHeader="true"
    >
      <template #header>
          <div class="flex items-center justify-between w-full pr-8 md:pr-0">
              <h3 class="text-base md:text-xl font-bold text-slate-800">
                  {{ slot.id ? `Edit Pintu #${slot.name}` : 'Tambah Slot Lemari' }}
              </h3>
          </div>
      </template>
      <div class="flex flex-col gap-5 mt-2 p-2 md:p-0">
        <!-- Cabinet Dropdown -->
        <div class="flex flex-col gap-1.5">
          <label for="slot-cabinet" class="text-sm font-semibold text-slate-700">
            Lemari <span class="text-red-500">*</span>
          </label>
          <Dropdown
            id="slot-cabinet"
            v-model="slot.cabinet_id"
            :options="locationStore.cabinets"
            optionLabel="name"
            optionValue="id"
            placeholder="Pilih lemari..."
            :invalid="submitted && !slot.cabinet_id"
            class="w-full !rounded-xl"
          >
            <template #option="slotProps">
              <span>{{ slotProps.option.name }} <span class="text-slate-400 text-xs">({{ slotProps.option.room?.name }})</span></span>
            </template>
          </Dropdown>
          <small v-if="submitted && !slot.cabinet_id" class="text-red-500 text-xs">Lemari wajib dipilih.</small>
        </div>

        <!-- Slot Name -->
        <div class="flex flex-col gap-1.5">
          <label for="slot-name" class="text-sm font-semibold text-slate-700">
            Nama/Nomor Pintu <span class="text-red-500">*</span>
          </label>
          <InputText id="slot-name" v-model.trim="slot.name" placeholder="Contoh: 01 atau A1" :invalid="submitted && !slot.name" class="!rounded-xl" autofocus />
          <small v-if="submitted && !slot.name" class="text-red-500 text-xs">Nama slot wajib diisi.</small>
        </div>

        <!-- Status Dropdown -->
        <div class="flex flex-col gap-1.5">
          <label for="slot-status" class="text-sm font-semibold text-slate-700">Status</label>
          <Dropdown
            id="slot-status"
            v-model="slot.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Pilih status..."
            class="w-full !rounded-xl"
          />
        </div>

        <!-- Multi-PIC Select -->
        <div class="flex flex-col gap-1.5">
          <label for="slot-pic" class="text-sm font-semibold text-slate-700">
            PIC <span class="text-slate-400 font-normal text-xs ml-1">bisa lebih dari 1</span>
          </label>
          <MultiSelect
            id="slot-pic"
            v-model="slot.pic_user_ids"
            :options="users"
            optionLabel="name"
            optionValue="id"
            placeholder="Pilih user..."
            display="chip"
            class="w-full !rounded-xl"
            :maxSelectedLabels="5"
          />
        </div>

        <!-- Keterangan -->
        <div class="flex flex-col gap-1.5">
          <label for="slot-keterangan" class="text-sm font-semibold text-slate-700">
            Keterangan <span class="text-slate-400 font-normal text-xs ml-1">opsional</span>
          </label>
          <Textarea id="slot-keterangan" v-model="slot.keterangan" placeholder="Catatan tambahan (opsional)" rows="2" class="w-full !rounded-xl" />
        </div>

        <!-- Tags -->
        <div class="flex flex-col gap-1.5">
          <label for="slot-tags" class="text-sm font-semibold text-slate-700">
            Tags <span class="text-slate-400 font-normal text-xs ml-1">bisa lebih dari 1</span>
          </label>
          <MultiSelect
            id="slot-tags"
            v-model="slot.tag_ids"
            :options="tags"
            optionLabel="nama"
            optionValue="id"
            placeholder="Pilih tag..."
            display="chip"
            class="w-full !rounded-xl"
            :maxSelectedLabels="5"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 p-2 md:p-0">
            <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="hideDialog" class="hidden md:flex" />
            <Button label="Simpan" icon="pi pi-check" @click="saveCabinetSlot" :loading="locationStore.loading" class="!rounded-xl px-6" />
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
import { fetchUsers } from '../api/userApi'
import { fetchTags } from '../api/tagApi'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import MultiSelect from 'primevue/multiselect'
import ConfirmDialog from 'primevue/confirmdialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import CabinetDoorGrid from '../components/CabinetDoorGrid.vue'

const locationStore = useLocationStore()
const toast         = useToast()
const confirm       = useConfirm()

// Mobile detection
const windowWidth = ref(window.innerWidth)
const updateWidth = () => { windowWidth.value = window.innerWidth }
onMounted(() => window.addEventListener('resize', updateWidth))
onUnmounted(() => window.removeEventListener('resize', updateWidth))
const isMobile = computed(() => windowWidth.value < 768)

const slotDialog       = ref(false)
const slot             = ref({})
const submitted        = ref(false)
const globalFilter     = ref('')
const showTrashed      = ref(false)
const selectedCabinetId = ref(null)
const users            = ref([])
const tags             = ref([])

const statusOptions = [
  { label: 'Aktif', value: 'aktif' },
  { label: 'Nonaktif', value: 'nonaktif' },
  { label: 'Rusak', value: 'rusak' }
]

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })
watch(globalFilter, (val) => { filters.value.global.value = val })

const filteredSlots = computed(() => {
    if (!globalFilter.value) return locationStore.cabinetSlots
    const q = globalFilter.value.toLowerCase()
    return locationStore.cabinetSlots.filter(s => 
        s.name?.toLowerCase().includes(q) || 
        s.cabinet?.name?.toLowerCase().includes(q) ||
        s.cabinet?.room?.name?.toLowerCase().includes(q) ||
        s.keterangan?.toLowerCase().includes(q)
    )
})

const selectedCabinet = computed(() => {
  if (!selectedCabinetId.value) return null
  return locationStore.cabinets.find(c => c.id === selectedCabinetId.value) || null
})

const selectedCabinetSlots = computed(() => {
  if (!selectedCabinetId.value) return []
  return locationStore.cabinetSlots
    .filter(s => s.cabinet_id === selectedCabinetId.value)
    .sort((a, b) => a.id - b.id)
})

const statusClass = (status) => {
  switch (status) {
    case 'nonaktif': return 'bg-slate-100 text-slate-600'
    case 'rusak': return 'bg-red-50 text-red-600'
    default: return 'bg-emerald-50 text-emerald-700'
  }
}

const statusLabel = (status) => {
  switch (status) {
    case 'nonaktif': return 'Nonaktif'
    case 'rusak': return 'Rusak'
    default: return 'Aktif'
  }
}

onMounted(async () => {
  try {
    const [, usersRes, tagsRes] = await Promise.all([
      locationStore.fetchCabinets(),
      fetchUsers(),
      fetchTags()
    ])
    users.value = usersRes.data.data || usersRes.data || []
    tags.value = tagsRes.data.data || tagsRes.data || []
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data', life: 3000 })
  }
})

watch(selectedCabinetId, async (cabinetId) => {
  if (showTrashed.value) return
  if (!cabinetId) { locationStore.cabinetSlots = []; return }
  try {
    await locationStore.fetchCabinetSlots(cabinetId)
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat slot lemari', life: 3000 })
  }
})

const toggleTrashed = async () => {
  showTrashed.value = !showTrashed.value
  if (showTrashed.value) {
    try {
      await locationStore.fetchTrashedCabinetSlots()
    } catch {
      toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat slot terhapus', life: 3000 })
    }
  } else {
    locationStore.cabinetSlots = []
    selectedCabinetId.value = null
  }
}

const handleRestore = async (data) => {
  try {
    await locationStore.restoreCabinetSlot(data.id)
    toast.add({ severity: 'success', summary: 'Dipulihkan', detail: 'Data slot berhasil dipulihkan', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Tidak dapat memulihkan data slot', life: 4000 })
  }
}

const openNew = () => {
  slot.value = { cabinet_id: selectedCabinetId.value || null, status: 'aktif', pic_user_ids: [], tag_ids: [] }
  submitted.value = false
  slotDialog.value = true
}
const hideDialog = () => { slotDialog.value = false; submitted.value = false }

const onClickDoor = (doorSlot) => {
  if (doorSlot.id) {
    editCabinetSlot(doorSlot)
  } else {
    slot.value = {
      cabinet_id: selectedCabinetId.value,
      name: doorSlot.name,
      status: 'aktif',
      pic_user_ids: [],
      tag_ids: [],
      keterangan: ''
    }
    submitted.value = false
    slotDialog.value = true
  }
}

const saveCabinetSlot = async () => {
  submitted.value = true
  if (!slot.value.name?.trim() || !slot.value.cabinet_id) return
  try {
    const data = {
      id: slot.value.id,
      cabinet_id: slot.value.cabinet_id,
      name: slot.value.name,
      status: slot.value.status || 'aktif',
      keterangan: slot.value.keterangan || null,
      pic_user_ids: slot.value.pic_user_ids || [],
      tag_ids: slot.value.tag_ids || []
    }
    if (slot.value.id) {
      await locationStore.updateCabinetSlot(slot.value.id, data)
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data slot diperbarui', life: 3000 })
    } else {
      await locationStore.createCabinetSlot(data)
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data slot ditambahkan', life: 3000 })
    }
    slotDialog.value = false; slot.value = {}
    await locationStore.fetchCabinetSlots(selectedCabinetId.value)
  } catch { toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menyimpan data slot', life: 3000 }) }
}

const editCabinetSlot = (editData) => {
  slot.value = {
    ...editData,
    cabinet_id: editData.cabinet?.id || editData.cabinet_id,
    pic_user_ids: editData.pic_users ? editData.pic_users.map(u => u.id) : [],
    tag_ids: editData.tags ? editData.tags.map(t => t.id) : []
  }
  submitted.value = false
  slotDialog.value = true
}

const confirmDeleteCabinetSlot = (deleteData) => {
  confirm.require({
    message: `Yakin ingin menghapus "${deleteData.name}"?`,
    header: 'Konfirmasi Hapus', icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger', acceptLabel: 'Ya, Hapus', rejectLabel: 'Batal',
    accept: async () => {
      try {
        await locationStore.deleteCabinetSlot(deleteData.id)
        toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data slot dihapus', life: 3000 })
      } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menghapus data slot', life: 3000 })
      }
    }
  })
}
</script>
