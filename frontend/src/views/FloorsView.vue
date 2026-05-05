<template>
  <div class="p-4">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Manage Floors</h1>
      <Button label="Tambah Data" icon="pi pi-plus" @click="openNew" />
    </div>

    <DataTable :value="locationStore.floors" :paginator="true" :rows="10" dataKey="id"
      :loading="locationStore.loading" class="p-datatable-sm">
      <Column field="id" header="ID" sortable></Column>
      <Column field="name" header="Name" sortable></Column>
      <Column header="Floor Plan">
        <template #body="slotProps">
          <img v-if="slotProps.data.floor_plan_image" :src="apiBase + '/storage/' + slotProps.data.floor_plan_image" alt="Floor Plan" class="w-16 h-16 object-cover rounded-md shadow" />
          <span v-else class="text-gray-500 italic">No image</span>
        </template>
      </Column>
      <Column :exportable="false" style="min-width: 8rem">
        <template #body="slotProps">
          <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editFloor(slotProps.data)" />
          <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteFloor(slotProps.data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="floorDialog" :style="{width: '450px'}" header="Floor Details" :modal="true" class="p-fluid">
      <div class="field mb-4">
        <label for="name" class="font-bold block mb-2">Name</label>
        <InputText id="name" v-model.trim="floor.name" required="true" autofocus :class="{'p-invalid': submitted && !floor.name}" />
        <small class="p-error" v-if="submitted && !floor.name">Name is required.</small>
      </div>
      <div class="field mb-4">
        <label for="image" class="font-bold block mb-2">Floor Plan Image</label>
        <input type="file" id="image" accept="image/*" @change="onImageUpload" class="w-full p-2 border rounded-md" />
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="hideDialog"/>
        <Button label="Save" icon="pi pi-check" @click="saveFloor" :loading="locationStore.loading" />
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
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

const locationStore = useLocationStore()
const toast = useToast()
const confirm = useConfirm()

const floorDialog = ref(false)
const floor = ref({})
const submitted = ref(false)
const imageFile = ref(null)

const apiBase = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'

onMounted(() => {
  locationStore.fetchFloors().catch(err => {
    toast.add({severity:'error', summary: 'Error', detail: 'Failed to fetch floors', life: 3000})
  })
})

const openNew = () => {
  floor.value = {}
  submitted.value = false
  imageFile.value = null
  floorDialog.value = true
}

const hideDialog = () => {
  floorDialog.value = false
  submitted.value = false
}

const onImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    imageFile.value = file
  }
}

const saveFloor = async () => {
  submitted.value = true

  if (floor.value.name && floor.value.name.trim()) {
    try {
      const data = { name: floor.value.name }
      if (imageFile.value) {
        data.floor_plan_image = imageFile.value
      }

      if (floor.value.id) {
        await locationStore.updateFloor(floor.value.id, data)
        toast.add({severity:'success', summary: 'Successful', detail: 'Floor Updated', life: 3000})
      } else {
        await locationStore.createFloor(data)
        toast.add({severity:'success', summary: 'Successful', detail: 'Floor Created', life: 3000})
      }
      floorDialog.value = false
      floor.value = {}
      imageFile.value = null
    } catch (error) {
      toast.add({severity:'error', summary: 'Error', detail: 'Failed to save floor', life: 3000})
    }
  }
}

const editFloor = (editData) => {
  floor.value = { ...editData }
  imageFile.value = null
  floorDialog.value = true
}

const confirmDeleteFloor = (deleteData) => {
  confirm.require({
    message: 'Are you sure you want to delete ' + deleteData.name + '?',
    header: 'Confirm',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await locationStore.deleteFloor(deleteData.id)
        toast.add({severity:'success', summary: 'Successful', detail: 'Floor Deleted', life: 3000})
      } catch (error) {
        toast.add({severity:'error', summary: 'Error', detail: 'Failed to delete floor', life: 3000})
      }
    }
  })
}
</script>
