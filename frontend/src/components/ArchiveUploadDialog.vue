<template>
    <Dialog 
        :visible="visible" 
        @update:visible="$emit('update:visible', $event)"
        header="Upload Arsip Baru" 
        :modal="true" 
        class="w-full max-w-4xl"
    >
        <form @submit.prevent="handleSubmit" class="grid grid-cols-12 gap-x-6 gap-y-4 mt-2">
            <!-- Kategori (Read Only) -->
            <div class="col-span-12">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Kategori Terpilih</label>
                <div class="p-3 bg-slate-50 rounded border border-slate-200 text-slate-600 font-semibold flex items-center gap-2">
                    <i class="pi pi-folder text-yellow-500"></i>
                    {{ preselectedCategory?.label || 'Belum dipilih' }}
                </div>
            </div>

            <!-- Nama Arsip & Nomor File -->
            <div class="col-span-12 md:col-span-8">
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Arsip *</label>
                <InputText id="name" v-model="form.name" class="w-full" placeholder="Contoh: Laporan Keuangan Q1 2024" required />
            </div>

            <div class="col-span-12 md:col-span-4">
                <label for="file_number" class="block text-sm font-medium text-slate-700 mb-1">Nomor File</label>
                <InputText id="file_number" v-model="form.file_number" class="w-full" placeholder="No. Reg / Ref" />
            </div>

            <!-- Keterangan -->
            <div class="col-span-12">
                <label for="keterangan" class="block text-sm font-medium text-slate-700 mb-1">Keterangan</label>
                <Textarea id="keterangan" v-model="form.keterangan" rows="2" class="w-full" placeholder="Deskripsi singkat arsip (opsional)" />
            </div>

            <!-- Tipe Arsip, Tanggal, PIC -->
            <div class="col-span-12 md:col-span-4">
                <label for="archive_type" class="block text-sm font-medium text-slate-700 mb-1">Tipe Arsip *</label>
                <Select id="archive_type" v-model="form.archive_type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" required />
            </div>

            <div class="col-span-12 md:col-span-4">
                <label for="issue_date" class="block text-sm font-medium text-slate-700 mb-1">Tanggal Terbit *</label>
                <DatePicker id="issue_date" v-model="form.issue_date" class="w-full" dateFormat="yy-mm-dd" required />
            </div>

            <div class="col-span-12 md:col-span-4">
                <label for="pic" class="block text-sm font-medium text-slate-700 mb-1">PIC *</label>
                <Select id="pic" v-model="form.pic_user_id" :options="users" optionLabel="name" optionValue="id" class="w-full" required />
            </div>

            <!-- Privacy & Granular Access -->
            <div class="col-span-12 md:col-span-6">
                <label for="privacy_type" class="block text-sm font-medium text-slate-700 mb-1">Privasi *</label>
                <Select id="privacy_type" v-model="form.privacy_type" :options="privacyOptions" optionLabel="label" optionValue="value" class="w-full" required />
            </div>

            <div class="col-span-12 md:col-span-6">
                <label for="download_policy" class="block text-sm font-medium text-slate-700 mb-1">Kebijakan Download *</label>
                <Select id="download_policy" v-model="form.download_policy" :options="policyOptions" optionLabel="label" optionValue="value" class="w-full" required />
            </div>

            <div class="col-span-12">
                <div class="flex items-start gap-3 p-3 rounded-lg border border-amber-200 bg-amber-50">
                    <Checkbox v-model="form.is_confidential" inputId="is_confidential" binary />
                    <label for="is_confidential" class="flex flex-col gap-0.5 cursor-pointer">
                        <span class="text-sm font-bold text-amber-800">Confidential</span>
                        <span class="text-xs text-amber-700">Akses OTP membutuhkan persetujuan PIC dan kepala departemen.</span>
                    </label>
                </div>
            </div>

            <!-- Department Access MultiSelect -->
            <div v-if="form.privacy_type === 'department'" class="col-span-12">
                <label class="block text-sm font-medium text-slate-700 mb-1">Pilih Departemen yang Berhak Akses *</label>
                <MultiSelect 
                    v-model="form.department_ids" 
                    :options="departments" 
                    optionLabel="name" 
                    optionValue="id" 
                    placeholder="Pilih satu atau lebih departemen" 
                    class="w-full" 
                    display="chip"
                    required
                />
            </div>

            <!-- User Access MultiSelect -->
            <div v-if="form.privacy_type === 'user'" class="col-span-12">
                <label class="block text-sm font-medium text-slate-700 mb-1">Pilih User yang Berhak Akses *</label>
                <MultiSelect 
                    v-model="form.user_ids" 
                    :options="allUsers" 
                    optionLabel="name" 
                    optionValue="id" 
                    placeholder="Pilih satu atau lebih user" 
                    class="w-full" 
                    display="chip"
                    required
                />
            </div>

            <!-- Physical Location (Conditional) -->
            <div v-if="['full', 'physical_only'].includes(form.archive_type)" class="col-span-12 grid grid-cols-12 gap-4 bg-slate-50 p-4 rounded-lg border border-slate-200">
                <h4 class="col-span-12 text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i class="pi pi-map-marker text-red-500"></i>
                    Lokasi Fisik
                </h4>
                
                <div class="col-span-12 md:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Lantai</label>
                    <Select v-model="form.floor_id" :options="floors" optionLabel="name" optionValue="id" placeholder="Pilih Lantai" class="w-full p-fluid" />
                </div>
                <div class="col-span-12 md:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ruangan</label>
                    <Select v-model="form.room_id" :options="rooms" optionLabel="name" optionValue="id" placeholder="Pilih Ruangan" class="w-full" :disabled="!form.floor_id" />
                </div>
                <div class="col-span-12 md:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Kabinet</label>
                    <Select v-model="form.cabinet_id" :options="cabinets" optionLabel="name" optionValue="id" placeholder="Pilih Kabinet" class="w-full" :disabled="!form.room_id" />
                </div>
                <div class="col-span-12 md:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Slot</label>
                    <Select v-model="form.cabinet_slot_id" :options="slots" optionLabel="name" optionValue="id" placeholder="Pilih Slot" class="w-full" :disabled="!form.cabinet_id" />
                </div>

                <!-- Floor Plan Image -->
                <div v-if="selectedFloor?.floor_plan_image" class="col-span-12 md:col-span-6 flex flex-col gap-1">
                    <label class="text-xs font-medium text-slate-500 italic">Denah Lantai</label>
                    <div class="h-80">
                        <FloorPlanViewer 
                            :imageUrl="getFullImageUrl(selectedFloor.floor_plan_image)" 
                            :highlightedRoomCoords="selectedRoomCoords"
                            :highlightedCabinetCoords="selectedCabinetCoords"
                        />

                    </div>

                </div>


                <!-- Cabinet Visual -->
                <div v-if="selectedCabinet" class="col-span-12 md:col-span-6 flex flex-col gap-1">
                    <label class="text-xs font-medium text-slate-500 italic">Visual Kabinet</label>
                    <div class="border rounded bg-white p-4 h-80 overflow-auto">
                        <CabinetDoorGrid 
                            :doorCount="selectedCabinet.door_count || 1" 
                            :slots="slots" 
                            :highlightedSlotId="form.cabinet_slot_id"
                            @slot-click="form.cabinet_slot_id = $event.id"
                        />
                    </div>
                </div>

            </div>

            <!-- File Upload -->
            <div v-if="['full', 'digital_only'].includes(form.archive_type)" class="col-span-12">
                <label class="block text-sm font-medium text-slate-700 mb-1">Upload File *</label>
                <FileUpload 
                    mode="advanced" 
                    name="file" 
                    :auto="false" 
                    :multiple="false" 
                    :maxFileSize="50000000"
                    @select="onFileSelect"
                    @remove="onFileRemove"
                >
                    <template #empty>
                        <div class="flex flex-col items-center justify-center py-4 text-slate-400">
                            <i class="pi pi-cloud-upload text-3xl mb-2"></i>
                            <p>Tarik file ke sini atau klik untuk memilih</p>
                        </div>
                    </template>
                </FileUpload>
            </div>

            <!-- Tags (Table Based) -->
            <div class="col-span-12">
                <label for="tags" class="block text-sm font-medium text-slate-700 mb-1">Tags *</label>
                <MultiSelect 
                    id="tags" 
                    v-model="form.tag_ids" 
                    :options="availableTags" 
                    optionLabel="nama" 
                    optionValue="id" 
                    placeholder="Pilih tag..." 
                    class="w-full" 
                    display="chip"
                    filter
                />
            </div>

            <!-- Expire & Reminder -->
            <div class="col-span-12 md:col-span-6">
                <label for="expire_date" class="block text-sm font-medium text-slate-700 mb-1">Tanggal Kadaluarsa</label>
                <DatePicker id="expire_date" v-model="form.expire_date" class="w-full" dateFormat="yy-mm-dd" showButtonBar />
            </div>

            <div v-if="form.expire_date" class="col-span-12 md:col-span-6">
                <label for="reminder_date" class="block text-sm font-medium text-slate-700 mb-1">Tanggal Pengingat *</label>
                <DatePicker id="reminder_date" v-model="form.reminder_date" class="w-full" dateFormat="yy-mm-dd" required />
            </div>
        </form>

        <template #footer>
            <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="$emit('update:visible', false)" />
            <Button label="Simpan Arsip" icon="pi pi-check" :loading="isUploading" @click="handleSubmit" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { uploadArchive } from '@/api/archiveApi'
import { fetchUsers } from '@/api/userApi'
import { fetchDepartments } from '@/api/departmentApi'
import { fetchTags } from '@/api/tagApi'
import { fetchFloors, fetchRoomsByFloor, fetchCabinetsByRoom, fetchSlotsByCabinet } from '@/api/locationApi'
import { useAuthStore } from '@/store/auth'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import FileUpload from 'primevue/fileupload'
import Checkbox from 'primevue/checkbox'
import Chips from 'primevue/chips'
import Button from 'primevue/button'
import CabinetDoorGrid from '@/components/CabinetDoorGrid.vue'
import FloorPlanViewer from '@/components/FloorPlanViewer.vue'


const props = defineProps({
    visible: Boolean,
    preselectedCategory: Object,
    companyId: [Number, String]
})

const emit = defineEmits(['update:visible', 'upload-success'])

const authStore = useAuthStore()
const isUploading = ref(false)
const users = ref([])
const allUsers = ref([])
const departments = ref([])
const floors = ref([])
const rooms = ref([])
const cabinets = ref([])
const slots = ref([])
const availableTags = ref([])
const selectedFile = ref(null)

const typeOptions = [
    { label: 'Digital + Fisik', value: 'full' },
    { label: 'Hanya Fisik', value: 'physical_only' },
    { label: 'Hanya Digital', value: 'digital_only' },
    { label: 'Placeholder', value: 'placeholder' }
]

const privacyOptions = [
    { label: 'Publik (Semua Orang)', value: 'public' },
    { label: 'Private (Hanya PIC)', value: 'private' },
    { label: 'Departemen Tertentu', value: 'department' },
    { label: 'User Tertentu', value: 'user' }
]

const policyOptions = [
    { label: 'Langsung Download', value: 'direct_download' },
    { label: 'Request ke PIC', value: 'request_to_pic' }
]

const form = reactive({
    name: '',
    keterangan: '',
    file_number: '',
    category_id: null,
    issue_date: new Date(),
    archive_type: 'full',
    privacy_type: 'public',
    download_policy: 'direct_download',
    is_confidential: false,
    pic_user_id: null,
    tag_ids: [],
    expire_date: null,
    reminder_date: null,
    company_id: null,
    department_ids: [],
    user_ids: [],
    floor_id: null,
    room_id: null,
    cabinet_id: null,
    cabinet_slot_id: null
})

const selectedFloor = computed(() => floors.value.find(f => f.id === form.floor_id))
const selectedRoom = computed(() => rooms.value.find(r => r.id === form.room_id))
const selectedCabinet = computed(() => cabinets.value.find(c => c.id === form.cabinet_id))

const selectedRoomCoords = computed(() => {
    if (form.room_id && selectedRoom.value?.coordinates) {
        const pts = selectedRoom.value.coordinates
        return typeof pts === 'string' ? JSON.parse(pts) : pts
    }
    return []
})

const selectedCabinetCoords = computed(() => {
    if (form.cabinet_id && selectedCabinet.value?.coordinates) {
        const pts = selectedCabinet.value.coordinates
        return typeof pts === 'string' ? JSON.parse(pts) : pts
    }
    return []
})




onMounted(async () => {
    loadInitialData()
})

const loadInitialData = async () => {
    try {
        const [uRes, dRes, fRes, tRes] = await Promise.all([
            fetchUsers(),
            fetchDepartments(),
            fetchFloors(),
            fetchTags()
        ])
        users.value = uRes.data.data
        allUsers.value = uRes.data.data
        departments.value = dRes.data.data
        floors.value = fRes.data.data
        availableTags.value = tRes.data.data
        
        // Default PIC to current user or first user
        if (users.value.length > 0) {
            form.pic_user_id = authStore.user?.id || users.value[0].id
        }
    } catch (e) {
        console.error('Failed to load initial data', e)
    }
}

// Reset semua field lokasi fisik setiap kali dialog dibuka ulang (Issue #22)
watch(() => props.visible, (isVisible) => {
    if (isVisible) {
        resetForm()
        rooms.value = []
        cabinets.value = []
        slots.value = []
    }
})

watch(() => props.preselectedCategory, (newVal) => {
    if (newVal) {
        form.category_id = newVal.data.id
        form.company_id = newVal.data.company_id || props.companyId
    }
}, { immediate: true })

watch(() => form.privacy_type, () => {
    form.department_ids = []
    form.user_ids = []
})

// Location Watchers
watch(() => form.floor_id, async (newId) => {
    form.room_id = null
    form.cabinet_id = null
    form.cabinet_slot_id = null
    rooms.value = []
    if (newId) {
        const res = await fetchRoomsByFloor(newId)
        rooms.value = res.data.data
    }
})

watch(() => form.room_id, async (newId) => {
    form.cabinet_id = null
    form.cabinet_slot_id = null
    cabinets.value = []
    if (newId) {
        const res = await fetchCabinetsByRoom(newId)
        cabinets.value = res.data.data
    }
})

watch(() => form.cabinet_id, async (newId) => {
    form.cabinet_slot_id = null
    slots.value = []
    if (newId) {
        const res = await fetchSlotsByCabinet(newId)
        slots.value = res.data.data.sort((a, b) => a.id - b.id)
    }
})

const onFileSelect = (event) => {
    selectedFile.value = event.files[0]
}

const onFileRemove = () => {
    selectedFile.value = null
}

const getFullImageUrl = (path) => {
    if (!path) return ''
    const base = import.meta.env.VITE_API_URL || 'http://localhost:8000'
    return `${base}/storage/${path}`
}

const formatDate = (date) => {
    if (!date) return null
    const d = new Date(date)
    let month = '' + (d.getMonth() + 1)
    let day = '' + d.getDate()
    const year = d.getFullYear()

    if (month.length < 2) month = '0' + month
    if (day.length < 2) day = '0' + day

    return [year, month, day].join('-')
}

const handleSubmit = async () => {
    if (!form.name || !form.category_id || !form.issue_date || !form.pic_user_id) {
        alert('Mohon lengkapi field wajib (*)')
        return
    }

    if (['full', 'digital_only'].includes(form.archive_type) && !selectedFile.value) {
        alert('File wajib diupload untuk tipe Digital')
        return
    }

    if (form.privacy_type === 'department' && !form.department_ids.length) {
        alert('Mohon pilih minimal satu departemen')
        return
    }

    if (form.privacy_type === 'user' && !form.user_ids.length) {
        alert('Mohon pilih minimal satu user')
        return
    }

    isUploading.value = true
    try {
        const formData = new FormData()
        formData.append('name', form.name)
        formData.append('keterangan', form.keterangan || '')
        formData.append('file_number', form.file_number || '')
        formData.append('category_id', form.category_id)
        formData.append('issue_date', formatDate(form.issue_date))
        formData.append('archive_type', form.archive_type)
        formData.append('privacy_type', form.privacy_type)
        formData.append('download_policy', form.download_policy)
        formData.append('is_confidential', form.is_confidential ? '1' : '0')
        formData.append('pic_user_id', form.pic_user_id)
        formData.append('company_id', form.company_id)

        // Privacy Access
        if (form.privacy_type === 'department') {
            form.department_ids.forEach(id => formData.append('department_ids[]', id))
        }
        if (form.privacy_type === 'user') {
            form.user_ids.forEach(id => formData.append('user_ids[]', id))
        }

        // Location
        if (['full', 'physical_only'].includes(form.archive_type)) {
            if (form.floor_id) formData.append('floor_id', form.floor_id)
            if (form.room_id) formData.append('room_id', form.room_id)
            if (form.cabinet_id) formData.append('cabinet_id', form.cabinet_id)
            if (form.cabinet_slot_id) formData.append('cabinet_slot_id', form.cabinet_slot_id)
        }

        if (form.tag_ids && form.tag_ids.length) {
            form.tag_ids.forEach(id => formData.append('tag_ids[]', id))
        }

        if (selectedFile.value) {
            formData.append('file', selectedFile.value)
        }

        if (form.expire_date) {
            formData.append('expire_date', formatDate(form.expire_date))
            formData.append('reminder_date', formatDate(form.reminder_date))
        }

        const response = await uploadArchive(formData)
        if (response.data.success) {
            emit('upload-success')
            resetForm()
        }
    } catch (error) {
        console.error('Upload failed:', error)
        alert(error.response?.data?.message || 'Gagal mengupload arsip')
    } finally {
        isUploading.value = false
    }
}

const resetForm = () => {
    Object.assign(form, {
        name: '',
        keterangan: '',
        file_number: '',
        expire_date: null,
        reminder_date: null,
        tag_ids: [],
        is_confidential: false,
        department_ids: [],
        user_ids: [],
        floor_id: null,
        room_id: null,
        cabinet_id: null,
        cabinet_slot_id: null,
        pic_user_id: authStore.user?.id || (users.value.length > 0 ? users.value[0].id : null)
    })
    selectedFile.value = null
}
</script>


