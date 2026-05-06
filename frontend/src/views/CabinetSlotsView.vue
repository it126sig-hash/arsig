<template>
  <div>
    <!-- Manage Cabinet Slots Page -->
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Cabinet Slots (Pintu Lemari)</h1>
        <p class="text-slate-500 text-sm mt-0.5">Kelola pintu/slot penyimpanan dokumen dalam lemari</p>
      </div>
      <Button label="Tambah Data" icon="pi pi-plus" @click="openNew" class="shadow-sm" />
    </div>

    <!-- Cabinet Selector for Door Grid -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
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

    <!-- DataTable Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <!-- Table Toolbar -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <span class="text-sm font-semibold text-slate-700">
          <i class="pi pi-inbox mr-2 text-purple-500"></i>Daftar Slot Lemari
        </span>
        <span class="p-input-icon-left">
          <i class="pi pi-search" />
          <InputText
            id="slots-global-search"
            v-model="globalFilter"
            placeholder="Cari slot..."
            class="text-sm"
            style="width: 240px;"
          />
        </span>
      </div>

      <DataTable
        :value="locationStore.cabinetSlots"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[5, 10, 25]"
        dataKey="id"
        :loading="locationStore.loading"
        :globalFilterFields="['id', 'name', 'keterangan', 'cabinet.name', 'cabinet.room.name']"
        :filters="filters"
        :showGridlines="false"
        stripedRows
        responsiveLayout="scroll"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
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
            <div class="flex items-center gap-2">
              <Button icon="pi pi-pencil" outlined rounded size="small" severity="info" @click="editCabinetSlot(slotProps.data)" v-tooltip.top="'Edit'" />
              <Button icon="pi pi-trash" outlined rounded size="small" severity="danger" @click="confirmDeleteCabinetSlot(slotProps.data)" v-tooltip.top="'Hapus'" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog v-model:visible="slotDialog" :style="{ width: '550px' }" :header="slot.id ? `Edit Pintu #${slot.name}` : 'Tambah Slot Lemari'" :modal="true" :draggable="false">
      <div class="flex flex-col gap-5">
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
            class="w-full"
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
          <InputText id="slot-name" v-model.trim="slot.name" placeholder="Contoh: 01 atau A1" :invalid="submitted && !slot.name" autofocus />
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
            class="w-full"
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
            class="w-full"
            :maxSelectedLabels="5"
          />
        </div>

        <!-- Keterangan -->
        <div class="flex flex-col gap-1.5">
          <label for="slot-keterangan" class="text-sm font-semibold text-slate-700">
            Keterangan <span class="text-slate-400 font-normal text-xs ml-1">opsional</span>
          </label>
          <Textarea id="slot-keterangan" v-model="slot.keterangan" placeholder="Catatan tambahan (opsional)" rows="2" class="w-full" />
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
            class="w-full"
            :maxSelectedLabels="5"
          />
        </div>
      </div>

      <template #footer>
        <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="hideDialog" />
        <Button label="Simpan" icon="pi pi-check" @click="saveCabinetSlot" :loading="locationStore.loading" />
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
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import CabinetDoorGrid from '../components/CabinetDoorGrid.vue'

const locationStore = useLocationStore()
const toast         = useToast()
const confirm       = useConfirm()

const slotDialog       = ref(false)
const slot             = ref({})
const submitted        = ref(false)
const globalFilter     = ref('')
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

const selectedCabinet = computed(() => {
  if (!selectedCabinetId.value) return null
  return locationStore.cabinets.find(c => c.id === selectedCabinetId.value) || null
})

const selectedCabinetSlots = computed(() => {
  if (!selectedCabinetId.value) return []
  return locationStore.cabinetSlots.filter(s => s.cabinet_id === selectedCabinetId.value)
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
    const [, , usersRes, tagsRes] = await Promise.all([
      locationStore.fetchCabinets(),
      locationStore.fetchCabinetSlots(),
      fetchUsers(),
      fetchTags()
    ])
    users.value = usersRes.data.data || usersRes.data || []
    tags.value = tagsRes.data.data || tagsRes.data || []
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data', life: 3000 })
  }
})

const openNew = () => {
  slot.value = { status: 'aktif', pic_user_ids: [], tag_ids: [] }
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
    await locationStore.fetchCabinetSlots()
  } catch { toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menyimpan data slot', life: 3000 }) }
}

const editCabinetSlot = (editData) => {
  slot.value = {
    ...editData,
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
