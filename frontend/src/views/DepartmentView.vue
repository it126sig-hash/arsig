<template>
    <div>
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-800">Manajemen Departemen</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-1">Kelola data departemen dalam sistem.</p>
            </div>
            <Button label="Tambah Departemen" icon="pi pi-plus" @click="openCreate" class="w-full md:w-auto !rounded-xl shadow-md" />
        </div>

        <!-- Data Table Card -->
        <div class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden">
            <!-- Table Toolbar (Desktop Search) -->
            <div class="hidden md:flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <span class="text-sm font-semibold text-slate-700">
                    <i class="pi pi-sitemap mr-2 text-blue-500"></i>Daftar Departemen
                </span>
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="globalFilter"
                        placeholder="Cari departemen..."
                        class="!rounded-lg text-sm"
                        style="width: 240px;"
                    />
                </IconField>
            </div>

            <!-- Mobile Search Bar -->
            <div class="md:hidden p-4 border-b border-slate-100">
                <IconField class="w-full">
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="globalFilter" placeholder="Cari departemen..." class="w-full !rounded-xl" />
                </IconField>
            </div>

            <!-- Desktop View -->
            <DataTable
                v-if="!isMobile"
                :value="departments"
                :loading="isLoading"
                responsiveLayout="scroll"
                stripedRows
                class="w-full"
                :filters="filters"
            >
                <template #empty>
                    <div class="text-center py-12 text-slate-400">
                        <i class="pi pi-sitemap text-5xl mb-3 block opacity-30"></i>
                        <p>Belum ada data departemen. Klik "Tambah Departemen" untuk mulai.</p>
                    </div>
                </template>
                <template #loading>
                    <div class="text-center py-8 text-slate-400">Memuat data...</div>
                </template>

                <Column field="id" header="ID" style="width: 80px" />
                <Column field="name" header="Nama Departemen" />
                <Column field="users_count" header="Jumlah User" style="width: 140px">
                    <template #body="{ data }">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ data.users_count ?? 0 }} user
                        </span>
                    </template>
                </Column>
                <Column field="created_at" header="Dibuat">
                    <template #body="{ data }">
                        <span class="text-slate-500 text-sm">{{ formatDate(data.created_at) }}</span>
                    </template>
                </Column>
                <Column header="Aksi" style="width: 140px">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Button icon="pi pi-pencil" text rounded severity="secondary" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- Mobile View (List) -->
            <div v-else class="flex flex-col divide-y divide-slate-100">
                <div v-if="isLoading" class="p-10 flex flex-col items-center justify-center gap-3">
                    <ProgressSpinner style="width: 30px; height: 30px" />
                    <p class="text-xs text-slate-400">Memuat data...</p>
                </div>
                <div v-else-if="filteredDepartments.length === 0" class="p-10 text-center text-slate-400 italic text-sm">
                    Tidak ada data departemen.
                </div>
                <div v-for="dept in filteredDepartments" :key="dept.id" class="p-4 flex flex-col gap-3 hover:bg-slate-50">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-800">{{ dept.name }}</span>
                            <span class="text-[10px] text-slate-400 mt-0.5">Dibuat: {{ formatDate(dept.created_at) }}</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100">
                            {{ dept.users_count ?? 0 }} User
                        </span>
                    </div>
                    
                    <!-- Actions Row -->
                    <div class="flex gap-2 border-t border-slate-50 pt-2">
                        <Button label="Edit" icon="pi pi-pencil" size="small" text severity="secondary" class="flex-1 !bg-slate-50 !rounded-lg" @click="openEdit(dept)" />
                        <Button label="Hapus" icon="pi pi-trash" size="small" text severity="danger" class="flex-1 !bg-red-50 !rounded-lg" @click="confirmDelete(dept)" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog: Tambah / Edit -->
        <Dialog
            v-model:visible="showDialog"
            :modal="true"
            class="w-full max-w-md"
            :maximized="isMobile"
            :showHeader="true"
        >
            <template #header>
                <div class="flex items-center justify-between w-full pr-8 md:pr-0">
                    <h3 class="text-base md:text-xl font-bold text-slate-800">
                        {{ isEditing ? 'Edit Departemen' : 'Tambah Departemen Baru' }}
                    </h3>
                </div>
            </template>
            <div class="flex flex-col gap-4 mt-2 p-2 md:p-0">
                <div class="flex flex-col gap-1">
                    <label for="dept-name" class="text-sm font-medium text-slate-700">
                        Nama Departemen <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        id="dept-name"
                        v-model="form.name"
                        placeholder="Contoh: IT, HRD, Finance"
                        autofocus
                        @keyup.enter="handleSubmit"
                        class="!rounded-xl"
                        :class="{ 'p-invalid': formErrors.name }"
                    />
                    <small class="text-red-500" v-if="formErrors.name">{{ formErrors.name }}</small>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 p-2 md:p-0">
                    <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="closeDialog" class="hidden md:flex" />
                    <Button
                        :label="isEditing ? 'Simpan' : 'Buat'"
                        icon="pi pi-check"
                        :loading="isSaving"
                        @click="handleSubmit"
                        class="!rounded-xl px-6"
                    />
                </div>
            </template>
        </Dialog>

        <!-- Confirm Dialog -->
        <ConfirmDialog />
        <Toast />
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { fetchDepartments, createDepartment, updateDepartment, deleteDepartment } from '@/api/departmentApi'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import ProgressSpinner from 'primevue/progressspinner'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

const toast = useToast()
const confirm = useConfirm()

// Mobile detection
const windowWidth = ref(window.innerWidth)
const updateWidth = () => { windowWidth.value = window.innerWidth }
onMounted(() => window.addEventListener('resize', updateWidth))
onUnmounted(() => window.removeEventListener('resize', updateWidth))
const isMobile = computed(() => windowWidth.value < 768)

const departments = ref([])
const isLoading = ref(false)
const showDialog = ref(false)
const isSaving = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const globalFilter = ref('')

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })
watch(globalFilter, (val) => { filters.value.global.value = val })

const filteredDepartments = computed(() => {
    if (!globalFilter.value) return departments.value
    const q = globalFilter.value.toLowerCase()
    return departments.value.filter(d => 
        d.name?.toLowerCase().includes(q) || 
        d.id?.toString().includes(q)
    )
})

const form = reactive({ name: '' })
const formErrors = reactive({ name: '' })

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const loadDepartments = async () => {
    isLoading.value = true
    try {
        const res = await fetchDepartments()
        if (res.data.success) {
            departments.value = res.data.data
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data departemen', life: 3000 })
    } finally {
        isLoading.value = false
    }
}

const resetForm = () => {
    form.name = ''
    formErrors.name = ''
    editingId.value = null
}

const openCreate = () => {
    resetForm()
    isEditing.value = false
    showDialog.value = true
}

const openEdit = (dept) => {
    resetForm()
    isEditing.value = true
    editingId.value = dept.id
    form.name = dept.name
    showDialog.value = true
}

const closeDialog = () => {
    showDialog.value = false
    resetForm()
}

const handleSubmit = async () => {
    formErrors.name = ''
    if (!form.name.trim()) {
        formErrors.name = 'Nama departemen wajib diisi.'
        return
    }

    isSaving.value = true
    try {
        const payload = { name: form.name.trim() }
        let res

        if (isEditing.value) {
            res = await updateDepartment(editingId.value, payload)
        } else {
            res = await createDepartment(payload)
        }

        if (res.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Sukses',
                detail: isEditing.value ? 'Departemen berhasil diperbarui.' : 'Departemen berhasil dibuat.',
                life: 3000
            })
            closeDialog()
            await loadDepartments()
        }
    } catch (e) {
        const msg = e.response?.data?.errors?.name?.[0] || e.response?.data?.message || 'Terjadi kesalahan.'
        if (e.response?.status === 422) {
            formErrors.name = msg
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 })
        }
    } finally {
        isSaving.value = false
    }
}

const confirmDelete = (dept) => {
    confirm.require({
        message: `Hapus departemen "${dept.name}"? Tindakan ini tidak dapat dibatalkan.`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                const res = await deleteDepartment(dept.id)
                if (res.data.success) {
                    toast.add({ severity: 'success', summary: 'Dihapus', detail: res.data.message, life: 3000 })
                    await loadDepartments()
                }
            } catch (e) {
                toast.add({
                    severity: 'error',
                    summary: 'Gagal',
                    detail: e.response?.data?.message || 'Tidak dapat menghapus departemen.',
                    life: 4000
                })
            }
        }
    })
}

onMounted(loadDepartments)
</script>
