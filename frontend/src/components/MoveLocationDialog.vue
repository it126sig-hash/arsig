<template>
    <Dialog 
        v-model:visible="visible" 
        header="Pindah Lokasi Fisik Arsip" 
        modal 
        class="p-fluid max-w-4xl w-full"
    >
        <div class="grid grid-cols-12 gap-6 mt-2">
            <!-- Left Column: Info & Form -->
            <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">
                <!-- Current Info -->
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <div class="text-[10px] uppercase font-bold text-slate-400 mb-2 tracking-wider">Arsip yang dipilih</div>
                    <div class="font-bold text-slate-800 text-lg leading-tight mb-1">{{ archive?.name }}</div>
                    <div class="text-xs text-slate-500 mb-4">{{ archive?.file_number }}</div>
                    
                    <div class="pt-3 border-t border-slate-200/60">
                        <div class="text-[10px] uppercase font-bold text-slate-400 mb-2 tracking-wider">Lokasi Saat Ini</div>
                        <div v-if="archive?.floor || archive?.room || archive?.cabinet || archive?.cabinet_slot" class="flex flex-col gap-2">
                            <div class="flex items-center gap-2 text-sm text-slate-600">
                                <i class="pi pi-map-marker text-red-500 text-xs"></i>
                                <span>{{ archive.floor?.name || '-' }} / {{ archive.room?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-600">
                                <i class="pi pi-box text-blue-500 text-xs"></i>
                                <span class="font-medium text-slate-700">{{ archive.cabinet?.name || '-' }}</span>
                                <span class="text-slate-400 px-2">•</span>
                                <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-[11px] font-bold">{{ archive.cabinet_slot?.name || '-' }}</span>
                            </div>
                        </div>
                        <div v-else class="text-sm text-slate-400 italic">Belum ditentukan</div>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2 uppercase tracking-wide">
                        <i class="pi pi-external-link text-primary"></i>
                        Pilih Lokasi Baru
                    </h4>

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
                            rows="2" 
                            placeholder="Contoh: Pemindahan untuk audit tahunan..."
                        />
                    </div>
                </div>
            </div>

            <!-- Right Column: Visualizer -->
            <div class="col-span-12 lg:col-span-7 flex flex-col gap-4">
                <div v-if="selectedFloor?.floor_plan_image" class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-slate-500 flex items-center gap-2">
                        <i class="pi pi-map"></i>
                        VISUALISASI LOKASI BARU
                    </label>
                    <div class="bg-slate-50 rounded-xl border border-slate-200 overflow-hidden relative" style="height: 320px;">
                        <FloorPlanViewer 
                            :imageUrl="getFullImageUrl(selectedFloor.floor_plan_image)" 
                            :highlightedRoomCoords="selectedRoomCoords"
                            :highlightedCabinetCoords="selectedCabinetCoords"
                        />
                    </div>
                </div>

                <div v-if="selectedCabinet" class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-slate-500 flex items-center gap-2">
                        <i class="pi pi-th-large"></i>
                        GRID KABINET BARU
                    </label>
                    <div class="bg-white rounded-xl border border-slate-200 p-4 overflow-auto" style="height: 320px;">
                        <CabinetDoorGrid 
                            :doorCount="selectedCabinet.door_count || 1" 
                            :slots="slots" 
                            :highlightedSlotId="form.new_cabinet_slot_id"
                            @slot-click="form.new_cabinet_slot_id = $event.id"
                        />
                    </div>
                </div>

                <div v-if="!selectedFloor" class="flex-1 flex flex-col items-center justify-center text-slate-300 py-20 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    <i class="pi pi-map-marker text-4xl mb-3"></i>
                    <p class="text-sm font-medium">Pilih lokasi baru untuk melihat denah</p>
                </div>
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
import CabinetDoorGrid from '@/components/CabinetDoorGrid.vue'
import FloorPlanViewer from '@/components/FloorPlanViewer.vue'

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

// Computed helpers for visualizers
const selectedFloor = computed(() => floors.value.find(f => f.id === form.new_floor_id))
const selectedRoom = computed(() => rooms.value.find(r => r.id === form.new_room_id))
const selectedCabinet = computed(() => cabinets.value.find(c => c.id === form.new_cabinet_id))

const selectedRoomCoords = computed(() => {
    if (form.new_room_id && selectedRoom.value?.coordinates) {
        const pts = selectedRoom.value.coordinates
        return typeof pts === 'string' ? JSON.parse(pts) : pts
    }
    return []
})

const selectedCabinetCoords = computed(() => {
    if (form.new_cabinet_id && selectedCabinet.value?.coordinates) {
        const pts = selectedCabinet.value.coordinates
        return typeof pts === 'string' ? JSON.parse(pts) : pts
    }
    return []
})

const getFullImageUrl = (path) => {
    if (!path) return ''
    const base = import.meta.env.VITE_API_URL || 'http://localhost:8000'
    return `${base}/storage/${path}`
}

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

