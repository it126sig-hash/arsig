<template>
  <div class="p-4">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Manage Cabinets</h1>
      <Button label="Tambah Data" icon="pi pi-plus" @click="openNew" />
    </div>

    <DataTable :value="locationStore.cabinets" :paginator="true" :rows="10" dataKey="id"
      :loading="locationStore.loading" class="p-datatable-sm">
      <Column field="id" header="ID" sortable></Column>
      <Column field="name" header="Name" sortable></Column>
      <Column field="room.name" header="Room" sortable></Column>
      <Column field="room.floor.name" header="Floor" sortable></Column>
      <Column field="needs_coordinate_review" header="Review Coordinates?">
        <template #body="slotProps">
          <i class="pi" :class="{'text-green-500 pi-check-circle': slotProps.data.needs_coordinate_review, 'text-red-500 pi-times-circle': !slotProps.data.needs_coordinate_review}"></i>
        </template>
      </Column>
      <Column :exportable="false" style="min-width: 8rem">
        <template #body="slotProps">
          <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editCabinet(slotProps.data)" />
          <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteCabinet(slotProps.data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="cabinetDialog" :style="{width: '450px'}" header="Cabinet Details" :modal="true" class="p-fluid">
      <div class="field mb-4">
        <label for="room" class="font-bold block mb-2">Room</label>
        <Dropdown id="room" v-model="cabinet.room_id" :options="locationStore.rooms" optionLabel="name" optionValue="id" placeholder="Select a Room" class="w-full" :class="{'p-invalid': submitted && !cabinet.room_id}">
          <template #option="slotProps">
            <span>{{ slotProps.option.name }} (Floor: {{ slotProps.option.floor?.name }})</span>
          </template>
        </Dropdown>
        <small class="p-error" v-if="submitted && !cabinet.room_id">Room is required.</small>
      </div>
      <div class="field mb-4">
        <label for="name" class="font-bold block mb-2">Name</label>
        <InputText id="name" v-model.trim="cabinet.name" required="true" autofocus :class="{'p-invalid': submitted && !cabinet.name}" />
        <small class="p-error" v-if="submitted && !cabinet.name">Name is required.</small>
      </div>
      <div class="field mb-4">
        <label for="points" class="font-bold block mb-2">Points (JSON format e.g. [{"x":0,"y":0}])</label>
        <InputText id="points" v-model="pointsText" placeholder='[{"x":10, "y":20}]' :class="{'p-invalid': pointsError}" @input="validatePoints" />
        <small class="p-error" v-if="pointsError">{{ pointsError }}</small>
      </div>
      <div class="field-checkbox mb-4 flex items-center">
        <Checkbox inputId="review" v-model="cabinet.needs_coordinate_review" :binary="true" />
        <label for="review" class="ml-2">Needs Coordinate Review</label>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="hideDialog"/>
        <Button label="Save" icon="pi pi-check" @click="saveCabinet" :loading="locationStore.loading" />
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
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

const locationStore = useLocationStore()
const toast = useToast()
const confirm = useConfirm()

const cabinetDialog = ref(false)
const cabinet = ref({ needs_coordinate_review: false })
const submitted = ref(false)
const pointsText = ref('[]')
const pointsError = ref('')

onMounted(async () => {
  try {
    await Promise.all([
      locationStore.fetchRooms(), // needed for dropdown
      locationStore.fetchCabinets()
    ])
  } catch(err) {
    toast.add({severity:'error', summary: 'Error', detail: 'Failed to fetch data', life: 3000})
  }
})

const validatePoints = () => {
  try {
    const parsed = JSON.parse(pointsText.value || '[]')
    if (!Array.isArray(parsed)) {
      pointsError.value = 'Points must be a JSON array'
      return false
    }
    pointsError.value = ''
    return true
  } catch (e) {
    pointsError.value = 'Invalid JSON format'
    return false
  }
}

const openNew = () => {
  cabinet.value = { needs_coordinate_review: false }
  pointsText.value = '[]'
  pointsError.value = ''
  submitted.value = false
  cabinetDialog.value = true
}

const hideDialog = () => {
  cabinetDialog.value = false
  submitted.value = false
}

const saveCabinet = async () => {
  submitted.value = true

  if (!validatePoints()) return

  if (cabinet.value.name && cabinet.value.name.trim() && cabinet.value.room_id) {
    try {
      const data = { 
        ...cabinet.value,
        points: JSON.parse(pointsText.value || '[]')
      }

      if (cabinet.value.id) {
        await locationStore.updateCabinet(cabinet.value.id, data)
        toast.add({severity:'success', summary: 'Successful', detail: 'Cabinet Updated', life: 3000})
      } else {
        await locationStore.createCabinet(data)
        toast.add({severity:'success', summary: 'Successful', detail: 'Cabinet Created', life: 3000})
      }
      cabinetDialog.value = false
      cabinet.value = {}
    } catch (error) {
      toast.add({severity:'error', summary: 'Error', detail: 'Failed to save cabinet', life: 3000})
    }
  }
}

const editCabinet = (editData) => {
  cabinet.value = { ...editData }
  pointsText.value = JSON.stringify(editData.points || [])
  pointsError.value = ''
  cabinetDialog.value = true
}

const confirmDeleteCabinet = (deleteData) => {
  confirm.require({
    message: 'Are you sure you want to delete ' + deleteData.name + '?',
    header: 'Confirm',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await locationStore.deleteCabinet(deleteData.id)
        toast.add({severity:'success', summary: 'Successful', detail: 'Cabinet Deleted', life: 3000})
      } catch (error) {
        toast.add({severity:'error', summary: 'Error', detail: 'Failed to delete cabinet', life: 3000})
      }
    }
  })
}
</script>
