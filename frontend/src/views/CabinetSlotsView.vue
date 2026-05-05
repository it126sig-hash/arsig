<template>
  <div class="p-4">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Manage Cabinet Slots</h1>
      <Button label="Tambah Data" icon="pi pi-plus" @click="openNew" />
    </div>

    <DataTable :value="locationStore.cabinetSlots" :paginator="true" :rows="10" dataKey="id"
      :loading="locationStore.loading" class="p-datatable-sm">
      <Column field="id" header="ID" sortable></Column>
      <Column field="name" header="Name" sortable></Column>
      <Column field="cabinet.name" header="Cabinet" sortable></Column>
      <Column field="cabinet.room.name" header="Room" sortable></Column>
      <Column field="pic_user.name" header="PIC User" sortable>
        <template #body="slotProps">
          {{ slotProps.data.pic_user?.name || 'N/A' }}
        </template>
      </Column>
      <Column :exportable="false" style="min-width: 8rem">
        <template #body="slotProps">
          <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editCabinetSlot(slotProps.data)" />
          <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteCabinetSlot(slotProps.data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="slotDialog" :style="{width: '450px'}" header="Cabinet Slot Details" :modal="true" class="p-fluid">
      <div class="field mb-4">
        <label for="cabinet" class="font-bold block mb-2">Cabinet</label>
        <Dropdown id="cabinet" v-model="slot.cabinet_id" :options="locationStore.cabinets" optionLabel="name" optionValue="id" placeholder="Select a Cabinet" class="w-full" :class="{'p-invalid': submitted && !slot.cabinet_id}">
          <template #option="slotProps">
            <span>{{ slotProps.option.name }} (Room: {{ slotProps.option.room?.name }})</span>
          </template>
        </Dropdown>
        <small class="p-error" v-if="submitted && !slot.cabinet_id">Cabinet is required.</small>
      </div>
      <div class="field mb-4">
        <label for="name" class="font-bold block mb-2">Name</label>
        <InputText id="name" v-model.trim="slot.name" required="true" autofocus :class="{'p-invalid': submitted && !slot.name}" />
        <small class="p-error" v-if="submitted && !slot.name">Name is required.</small>
      </div>
      <div class="field mb-4">
        <label for="pic" class="font-bold block mb-2">PIC User ID (Temporary input until User dropdown is available)</label>
        <InputText id="pic" v-model.number="slot.pic_user_id" required="true" type="number" :class="{'p-invalid': submitted && !slot.pic_user_id}" />
        <small class="p-error" v-if="submitted && !slot.pic_user_id">PIC User ID is required.</small>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="hideDialog"/>
        <Button label="Save" icon="pi pi-check" @click="saveCabinetSlot" :loading="locationStore.loading" />
      </template>
    </Dialog>

    <ConfirmDialog></ConfirmDialog>
    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
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
const toast = useToast()
const confirm = useConfirm()

const slotDialog = ref(false)
const slot = ref({})
const submitted = ref(false)

onMounted(async () => {
  try {
    await Promise.all([
      locationStore.fetchCabinets(), // needed for dropdown
      locationStore.fetchCabinetSlots()
    ])
  } catch(err) {
    toast.add({severity:'error', summary: 'Error', detail: 'Failed to fetch data', life: 3000})
  }
})

const openNew = () => {
  slot.value = {}
  submitted.value = false
  slotDialog.value = true
}

const hideDialog = () => {
  slotDialog.value = false
  submitted.value = false
}

const saveCabinetSlot = async () => {
  submitted.value = true

  if (slot.value.name && slot.value.name.trim() && slot.value.cabinet_id && slot.value.pic_user_id) {
    try {
      if (slot.value.id) {
        await locationStore.updateCabinetSlot(slot.value.id, slot.value)
        toast.add({severity:'success', summary: 'Successful', detail: 'Slot Updated', life: 3000})
      } else {
        await locationStore.createCabinetSlot(slot.value)
        toast.add({severity:'success', summary: 'Successful', detail: 'Slot Created', life: 3000})
      }
      slotDialog.value = false
      slot.value = {}
    } catch (error) {
      toast.add({severity:'error', summary: 'Error', detail: 'Failed to save slot', life: 3000})
    }
  }
}

const editCabinetSlot = (editData) => {
  slot.value = { ...editData }
  slotDialog.value = true
}

const confirmDeleteCabinetSlot = (deleteData) => {
  confirm.require({
    message: 'Are you sure you want to delete ' + deleteData.name + '?',
    header: 'Confirm',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await locationStore.deleteCabinetSlot(deleteData.id)
        toast.add({severity:'success', summary: 'Successful', detail: 'Slot Deleted', life: 3000})
      } catch (error) {
        toast.add({severity:'error', summary: 'Error', detail: 'Failed to delete slot', life: 3000})
      }
    }
  })
}
</script>
