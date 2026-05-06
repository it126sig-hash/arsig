<template>
    <Dialog 
        :visible="visible" 
        @update:visible="$emit('update:visible', $event)"
        header="Upload Arsip Baru" 
        :modal="true" 
        class="w-full max-w-2xl"
    >
        <form @submit.prevent="handleSubmit" class="grid grid-cols-12 gap-4 mt-2">
            <!-- Kategori (Read Only) -->
            <div class="col-span-12">
                <label class="block text-sm font-medium text-slate-700 mb-1">Kategori Terpilih</label>
                <div class="p-3 bg-slate-100 rounded border border-slate-200 text-slate-600 font-semibold flex items-center gap-2">
                    <i class="pi pi-folder"></i>
                    {{ preselectedCategory?.label || 'Belum dipilih' }}
                </div>
            </div>

            <!-- Nama Arsip -->
            <div class="col-span-12 md:col-span-8">
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Arsip *</label>
                <InputText id="name" v-model="form.name" class="w-full" placeholder="Contoh: Laporan Keuangan Q1 2024" required />
            </div>

            <!-- Nomor File -->
            <div class="col-span-12 md:col-span-4">
                <label for="file_number" class="block text-sm font-medium text-slate-700 mb-1">Nomor File</label>
                <InputText id="file_number" v-model="form.file_number" class="w-full" placeholder="No. Reg / Ref" />
            </div>

            <!-- Tipe Arsip -->
            <div class="col-span-12 md:col-span-4">
                <label for="archive_type" class="block text-sm font-medium text-slate-700 mb-1">Tipe Arsip *</label>
                <Select id="archive_type" v-model="form.archive_type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" required />
            </div>

            <!-- Tanggal Terbit -->
            <div class="col-span-12 md:col-span-4">
                <label for="issue_date" class="block text-sm font-medium text-slate-700 mb-1">Tanggal Terbit *</label>
                <DatePicker id="issue_date" v-model="form.issue_date" class="w-full" dateFormat="yy-mm-dd" required />
            </div>

            <!-- PIC -->
            <div class="col-span-12 md:col-span-4">
                <label for="pic" class="block text-sm font-medium text-slate-700 mb-1">PIC *</label>
                <Select id="pic" v-model="form.pic_user_id" :options="users" optionLabel="name" optionValue="id" class="w-full" required />
            </div>

            <!-- Privacy & Download Policy -->
            <div class="col-span-12 md:col-span-6">
                <label for="privacy_type" class="block text-sm font-medium text-slate-700 mb-1">Privasi *</label>
                <Select id="privacy_type" v-model="form.privacy_type" :options="privacyOptions" optionLabel="label" optionValue="value" class="w-full" required />
            </div>

            <div class="col-span-12 md:col-span-6">
                <label for="download_policy" class="block text-sm font-medium text-slate-700 mb-1">Kebijakan Download *</label>
                <Select id="download_policy" v-model="form.download_policy" :options="policyOptions" optionLabel="label" optionValue="value" class="w-full" required />
            </div>

            <!-- Hashtags -->
            <div class="col-span-12">
                <label for="hashtags" class="block text-sm font-medium text-slate-700 mb-1">Hashtags (Gunakan Enter)</label>
                <Chips id="hashtags" v-model="form.hashtags" class="w-full" placeholder="audit, 2024, pajak" />
            </div>

            <!-- File Upload (Conditional) -->
            <div v-if="form.archive_type === 'full'" class="col-span-12">
                <label class="block text-sm font-medium text-slate-700 mb-1">Upload File (PDF/DOC/Image) *</label>
                <FileUpload 
                    mode="advanced" 
                    name="file" 
                    :auto="false" 
                    :multiple="false" 
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                    :maxFileSize="20000000"
                    @select="onFileSelect"
                    @remove="onFileRemove"
                >
                    <template #empty>
                        <p>Drag and drop files to here to upload.</p>
                    </template>
                </FileUpload>
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
import { ref, reactive, watch, onMounted } from 'vue'
import { uploadArchive } from '@/api/archiveApi'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import FileUpload from 'primevue/fileupload'
import Chips from 'primevue/chips'
import Button from 'primevue/button'
import api from '@/services/api'

const props = defineProps({
    visible: Boolean,
    preselectedCategory: Object
})

const emit = defineEmits(['update:visible', 'upload-success'])

const isUploading = ref(false)
const users = ref([])
const selectedFile = ref(null)

const typeOptions = [
    { label: 'Digital + Fisik', value: 'full' },
    { label: 'Hanya Fisik', value: 'physical_only' },
    { label: 'Placeholder', value: 'placeholder' }
]

const privacyOptions = [
    { label: 'Publik', value: 'public' },
    { label: 'Internal (Private)', value: 'private' }
]

const policyOptions = [
    { label: 'Langsung Download', value: 'direct_download' },
    { label: 'Request ke PIC', value: 'request_to_pic' }
]

const form = reactive({
    name: '',
    file_number: '',
    category_id: null,
    issue_date: new Date(),
    archive_type: 'full',
    privacy_type: 'public',
    download_policy: 'direct_download',
    pic_user_id: null,
    hashtags: [],
    expire_date: null,
    reminder_date: null,
    company_id: 1 // Default for now
})

onMounted(async () => {
    try {
        // Mocking user fetch or actual fetch if endpoint exists
        // const response = await api.get('/users')
        // users.value = response.data.data
        users.value = [{ id: 1, name: 'Admin User' }] // Default fallback
        form.pic_user_id = 1
    } catch (e) {
        console.error('Failed to load users', e)
    }
})

watch(() => props.preselectedCategory, (newVal) => {
    if (newVal) {
        form.category_id = newVal.data.id
        form.company_id = newVal.data.company_id
    }
}, { immediate: true })

const onFileSelect = (event) => {
    selectedFile.value = event.files[0]
}

const onFileRemove = () => {
    selectedFile.value = null
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
        return
    }

    if (form.archive_type === 'full' && !selectedFile.value) {
        alert('File wajib diupload untuk tipe Digital + Fisik')
        return
    }

    isUploading.value = true
    try {
        const formData = new FormData()
        formData.append('name', form.name)
        formData.append('file_number', form.file_number || '')
        formData.append('category_id', form.category_id)
        formData.append('issue_date', formatDate(form.issue_date))
        formData.append('archive_type', form.archive_type)
        formData.append('privacy_type', form.privacy_type)
        formData.append('download_policy', form.download_policy)
        formData.append('pic_user_id', form.pic_user_id)
        formData.append('company_id', form.company_id)

        if (form.hashtags && form.hashtags.length) {
            form.hashtags.forEach(tag => formData.append('hashtags[]', tag))
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
    form.name = ''
    form.file_number = ''
    form.expire_date = null
    form.reminder_date = null
    form.hashtags = []
    selectedFile.value = null
}
</script>
