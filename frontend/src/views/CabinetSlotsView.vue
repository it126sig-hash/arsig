<template>
  <div>
    <!-- Manage Cabinet Slots Page -->
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Cabinet Slots</h1>
        <p class="text-slate-500 text-sm mt-0.5">Kelola slot penyimpanan dokumen dalam lemari</p>
      </div>
      <Button label="Tambah Data" icon="pi pi-plus" @click="openNew" class="shadow-sm" />
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
        :globalFilterFields="['id', 'name', 'cabinet.name', 'cabinet.room.name']"
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
        <Column field="pic_user.name" header="PIC" sortable>
          <template #body="slotProps">
            <div v-if="slotProps.data.pic_user?.name" class="flex items-center gap-2">
              <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold">
                {{ slotProps.data.pic_user.name[0].toUpperCase() }}
              </div>
              <span class="text-sm text-slate-700">{{ slotProps.data.pic_user.name }}</span>
            </div>
            <span v-else class="text-xs text-slate-400 italic">Tidak ada</span>
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
    <Dialog v-model:visible="slotDialog" :style="{ width: '500px' }" header="Detail Slot Lemari" :modal="true" :draggable="false">
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
            Nama Slot <span class="text-red-500">*</span>
          </label>
          <InputText id="slot-name" v-model.trim="slot.name" placeholder="Contoh: Slot A1-01" :invalid="submitted && !slot.name" autofocus />
          <small v-if="submitted && !slot.name" class="text-red-500 text-xs">Nama slot wajib diisi.</small>
        </div>

        <!-- PIC User ID (temporary) -->
        <div class="flex flex-col gap-1.5">
          <label for="slot-pic" class="text-sm font-semibold text-slate-700">
            PIC User ID <span class="text-red-500">*</span>
          </label>
          <InputText id="slot-pic" v-model.number="slot.pic_user_id" type="number" placeholder="ID pengguna penanggung jawab" :invalid="submitted && !slot.pic_user_id" />
          <small v-if="submitted && !slot.pic_user_id" class="text-red-500 text-xs">PIC User ID wajib diisi.</small>
          <small class="text-slate-400 text-xs">Sementara menggunakan ID. Akan diganti dropdown user setelah modul user tersedia.</small>
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
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

const locationStore = useLocationStore()
const toast         = useToast()
const confirm       = useConfirm()

const slotDialog   = ref(false)
const slot         = ref({})
const submitted    = ref(false)
const globalFilter = ref('')

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })
watch(globalFilter, (val) => { filters.value.global.value = val })

onMounted(async () => {
  try {
    await Promise.all([locationStore.fetchCabinets(), locationStore.fetchCabinetSlots()])
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data', life: 3000 })
  }
})

const openNew    = () => { slot.value = {}; submitted.value = false; slotDialog.value = true }
const hideDialog = () => { slotDialog.value = false; submitted.value = false }

const saveCabinetSlot = async () => {
  submitted.value = true
  if (!slot.value.name?.trim() || !slot.value.cabinet_id || !slot.value.pic_user_id) return
  try {
    if (slot.value.id) {
      await locationStore.updateCabinetSlot(slot.value.id, slot.value)
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data slot diperbarui', life: 3000 })
    } else {
      await locationStore.createCabinetSlot(slot.value)
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data slot ditambahkan', life: 3000 })
    }
    slotDialog.value = false; slot.value = {}
  } catch { toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menyimpan data slot', life: 3000 }) }
}

const editCabinetSlot = (editData) => { slot.value = { ...editData }; slotDialog.value = true }

const confirmDeleteCabinetSlot = (deleteData) => {
  confirm.require({
    message: `Yakin ingin menghapus "${deleteData.name}"?`,
    header: 'Konfirmasi Hapus', icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger', acceptLabel: 'Ya, Hapus', rejectLabel: 'Batal',
    accept: async () => {
      try { await locationStore.deleteCabinetSlot(deleteData.id); toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data slot dihapus', life: 3000 }) }
      catch { toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menghapus data slot', life: 3000 }) }
    }
  })
}
</script>
