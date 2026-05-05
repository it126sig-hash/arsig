<template>
  <div class="p-4">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Manage Rooms</h1>
      <Button label="Tambah Data" icon="pi pi-plus" @click="openNew" />
    </div>

    <DataTable :value="locationStore.rooms" :paginator="true" :rows="10" dataKey="id"
      :loading="locationStore.loading" class="p-datatable-sm">
      <Column field="id" header="ID" sortable></Column>
      <Column field="name" header="Name" sortable></Column>
      <Column field="floor.name" header="Floor" sortable></Column>
      <Column field="needs_coordinate_review" header="Review Coordinates?">
        <template #body="slotProps">
          <i class="pi" :class="{'text-green-500 pi-check-circle': slotProps.data.needs_coordinate_review, 'text-red-500 pi-times-circle': !slotProps.data.needs_coordinate_review}"></i>
        </template>
      </Column>
      <Column :exportable="false" style="min-width: 8rem">
        <template #body="slotProps">
          <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editRoom(slotProps.data)" />
          <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteRoom(slotProps.data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="roomDialog" :style="{width: '450px'}" header="Room Details" :modal="true" class="p-fluid">
      <div class="field mb-4">
        <label for="floor" class="font-bold block mb-2">Floor</label>
        <Dropdown id="floor" v-model="room.floor_id" :options="locationStore.floors" optionLabel="name" optionValue="id" placeholder="Select a Floor" class="w-full" :class="{'p-invalid': submitted && !room.floor_id}" />
        <small class="p-error" v-if="submitted && !room.floor_id">Floor is required.</small>
      </div>
      <div class="field mb-4">
        <label for="name" class="font-bold block mb-2">Name</label>
        <InputText id="name" v-model.trim="room.name" required="true" autofocus :class="{'p-invalid': submitted && !room.name}" />
        <small class="p-error" v-if="submitted && !room.name">Name is required.</small>
      </div>
      <div class="field mb-4">
        <label for="points" class="font-bold block mb-2">Points (JSON format e.g. [{"x":0,"y":0}])</label>
        <InputText id="points" v-model="pointsText" placeholder='[{"x":10, "y":20}]' :class="{'p-invalid': pointsError}" @input="validatePoints" />
        <small class="p-error" v-if="pointsError">{{ pointsError }}</small>
      </div>
      <div class="field-checkbox mb-4 flex items-center">
        <Checkbox inputId="review" v-model="room.needs_coordinate_review" :binary="true" />
        <label for="review" class="ml-2">Needs Coordinate Review</label>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="hideDialog"/>
        <Button label="Save" icon="pi pi-check" @click="saveRoom" :loading="locationStore.loading" />
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

const roomDialog = ref(false)
const room = ref({ needs_coordinate_review: false })
const submitted = ref(false)
const pointsText = ref('[]')
const pointsError = ref('')

onMounted(async () => {
  try {
    await Promise.all([
      locationStore.fetchFloors(),
      locationStore.fetchRooms()
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
  room.value = { needs_coordinate_review: false }
  pointsText.value = '[]'
  pointsError.value = ''
  submitted.value = false
  roomDialog.value = true
}

const hideDialog = () => {
  roomDialog.value = false
  submitted.value = false
}

const saveRoom = async () => {
  submitted.value = true

  if (!validatePoints()) return

  if (room.value.name && room.value.name.trim() && room.value.floor_id) {
    try {
      const data = { 
        ...room.value,
        points: JSON.parse(pointsText.value || '[]')
      }

      if (room.value.id) {
        await locationStore.updateRoom(room.value.id, data)
        toast.add({severity:'success', summary: 'Successful', detail: 'Room Updated', life: 3000})
      } else {
        await locationStore.createRoom(data)
        toast.add({severity:'success', summary: 'Successful', detail: 'Room Created', life: 3000})
      }
      roomDialog.value = false
      room.value = {}
    } catch (error) {
      toast.add({severity:'error', summary: 'Error', detail: 'Failed to save room', life: 3000})
    }
  }
}

const editRoom = (editData) => {
  room.value = { ...editData }
  pointsText.value = JSON.stringify(editData.points || [])
  pointsError.value = ''
  roomDialog.value = true
}

const confirmDeleteRoom = (deleteData) => {
  confirm.require({
    message: 'Are you sure you want to delete ' + deleteData.name + '?',
    header: 'Confirm',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await locationStore.deleteRoom(deleteData.id)
        toast.add({severity:'success', summary: 'Successful', detail: 'Room Deleted', life: 3000})
      } catch (error) {
        toast.add({severity:'error', summary: 'Error', detail: 'Failed to delete room', life: 3000})
      }
    }
  })
}
</script>
