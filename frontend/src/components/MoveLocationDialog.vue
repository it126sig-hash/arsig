<template>
    <Dialog 
        v-model:visible="visible" 
        header="Pindah Lokasi Fisik Arsip" 
        modal 
        class="p-fluid max-w-lg w-full"
    >
        <div class="flex flex-col gap-4 mt-2">
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 mb-2">
                <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Arsip yang dipilih</div>
                <div class="font-bold text-slate-700">{{ archive?.name }}</div>
                <div class="text-xs text-slate-500">{{ archive?.file_number }}</div>
            </div>

            <!-- Floor Selection -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-600">Lantai <span class="text-red-500">*</span></label>
                <Select 
                    v-model="form.new_floor_id" 
                    :options="floors" 
                    optionLabel="name" 
                    optionValue="id" 
                    placeholder="Pilih Lantai"
                    @change="onFloorChange"
                    :loading="loadingFloors"
                />
            </div>

            <!-- Room Selection -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-600">Ruangan <span class="text-red-500">*</span></label>
                <Select 
                    v-model="form.new_room_id" 
                    :options="rooms" 
                    optionLabel="name" 
                    optionValue="id" 
                    placeholder="Pilih Ruangan"
                    :disabled="!form.new_floor_id"
                    @change="onRoomChange"
                    :loading="loadingRooms"
                />
            </div>

            <!-- Cabinet Selection -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-600">Lemari <span class="text-red-500">*</span></label>
                <Select 
                    v-model="form.new_cabinet_id" 
                    :options="cabinets" 
                    optionLabel="name" 
                    optionValue="id" 
                    placeholder="Pilih Lemari"
                    :disabled="!form.new_room_id"
                    @change="onCabinetChange"
                    :loading="loadingCabinets"
                />
            </div>

            <!-- Slot Selection -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-600">Slot / Rak <span class="text-red-500">*</span></label>
                <Select 
                    v-model="form.new_cabinet_slot_id" 
                    :options="slots" 
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Pilih Slot"
                    :disabled="!form.new_cabinet_id"
                    :loading="loadingSlots"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value">
                            {{ getSlotLabel(slotProps.value) }}
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex flex-col">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">{{ slotProps.option.name }}</span>
                                <div v-if="slotProps.option.pic_users?.length" class="flex gap-1">
                                    <Tag v-for="user in slotProps.option.pic_users" :key="user.id" :value="user.name" severity="secondary" class="text-[9px] px-1 py-0" />
                                </div>
                            </div>
                            <small v-if="slotProps.option.keterangan" class="text-slate-400 mt-0.5">{{ slotProps.option.keterangan }}</small>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Notes -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-600">Keterangan Pemindahan</label>
                <Textarea 
                    v-model="form.notes" 
                    rows="3" 
                    placeholder="Contoh: Pemindahan untuk audit tahunan atau reposisi rak..."
                />
            </div>
        </div>

        <template #footer>
            <Button label="Batal" icon="pi pi-times" severity="secondary" text @click="visible = false" />
            <Button 
                label="Pindahkan Lokasi" 
                icon="pi pi-map-marker" 
                severity="primary" 
                :loading="submitting"
                :disabled="!isFormValid"
                @click="handleSubmit"
            />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'
import { fetchFloors, fetchRoomsByFloor, fetchCabinetsByRoom, fetchSlotsByCabinet } from '@/api/locationApi'
import { moveArchiveLocation } from '@/api/archiveApi'

const props = defineProps({
    modelValue: Boolean,
    archive: Object
})

const emit = defineEmits(['update:modelValue', 'moved'])

const toast = useToast()
const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

const form = reactive({
    new_floor_id: null,
    new_room_id: null,
    new_cabinet_id: null,
    new_cabinet_slot_id: null,
    notes: ''
})

const submitting = ref(false)
const floors = ref([])
const rooms = ref([])
const cabinets = ref([])
const slots = ref([])

const loadingFloors = ref(false)
const loadingRooms = ref(false)
const loadingCabinets = ref(false)
const loadingSlots = ref(false)

const isFormValid = computed(() => {
    return form.new_floor_id && form.new_room_id && form.new_cabinet_id && form.new_cabinet_slot_id
})

watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        loadFloors()
        resetForm()
    }
})

const resetForm = () => {
    form.new_floor_id = null
    form.new_room_id = null
    form.new_cabinet_id = null
    form.new_cabinet_slot_id = null
    form.notes = ''
    rooms.value = []
    cabinets.value = []
    slots.value = []
}

const loadFloors = async () => {
    loadingFloors.value = true
    try {
        const res = await fetchFloors()
        floors.value = res.data.data
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat data lantai', life: 3000 })
    } finally {
        loadingFloors.value = false
    }
}

const onFloorChange = async () => {
    form.new_room_id = null
    form.new_cabinet_id = null
    form.new_cabinet_slot_id = null
    rooms.value = []
    cabinets.value = []
    slots.value = []
    
    if (form.new_floor_id) {
        loadingRooms.value = true
        try {
            const res = await fetchRoomsByFloor(form.new_floor_id)
            rooms.value = res.data.data
        } finally {
            loadingRooms.value = false
        }
    }
}

const onRoomChange = async () => {
    form.new_cabinet_id = null
    form.new_cabinet_slot_id = null
    cabinets.value = []
    slots.value = []
    
    if (form.new_room_id) {
        loadingCabinets.value = true
        try {
            const res = await fetchCabinetsByRoom(form.new_room_id)
            cabinets.value = res.data.data
        } finally {
            loadingCabinets.value = false
        }
    }
}

const onCabinetChange = async () => {
    form.new_cabinet_slot_id = null
    slots.value = []
    
    if (form.new_cabinet_id) {
        loadingSlots.value = true
        try {
            const res = await fetchSlotsByCabinet(form.new_cabinet_id)
            slots.value = res.data.data
        } finally {
            loadingSlots.value = false
        }
    }
}

const getSlotLabel = (slotId) => {
    const slot = slots.value.find(s => s.id === slotId)
    if (!slot) return 'Pilih Slot'
    return slot.keterangan ? `${slot.name} (${slot.keterangan})` : slot.name
}

const handleSubmit = async () => {
    submitting.value = true
    try {
        const res = await moveArchiveLocation(props.archive.id, form)
        toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Lokasi fisik arsip berhasil dipindahkan', life: 3000 })
        emit('moved', res.data.data)
        visible.value = false
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal memindahkan lokasi', life: 3000 })
    } finally {
        submitting.value = false
    }
}
</script>
