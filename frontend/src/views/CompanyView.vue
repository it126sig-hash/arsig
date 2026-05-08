<template>
    <div class="p-2 md:p-0">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-800">Manajemen PT</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-1">Kelola data perusahaan / PT yang terdaftar.</p>
            </div>
            <Button label="Tambah PT" icon="pi pi-plus" @click="openCreate" class="w-full md:w-auto !rounded-xl shadow-md" />
        </div>

        <!-- Data Table (Desktop) / List (Mobile) -->
        <div class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden">
            <!-- Desktop View -->
            <DataTable
                v-if="!isMobile"
                :value="companies"
                :loading="isLoading"
                responsiveLayout="scroll"
                stripedRows
                class="w-full"
            >
                <template #empty>
                    <div class="text-center py-12 text-slate-400">
                        <i class="pi pi-building text-5xl mb-3 block opacity-30"></i>
                        <p>Belum ada data perusahaan. Klik "Tambah Perusahaan" untuk mulai.</p>
                    </div>
                </template>
                <template #loading>
                    <div class="text-center py-8 text-slate-400">Memuat data...</div>
                </template>

                <Column field="id" header="ID" style="width: 80px" />
                <Column field="name" header="Nama Perusahaan" />
                <Column field="description" header="Deskripsi">
                    <template #body="{ data }">
                        <span class="text-slate-500">{{ data.description || '—' }}</span>
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
                <div v-else-if="companies.length === 0" class="p-10 text-center text-slate-400 italic text-sm">
                    Belum ada data perusahaan.
                </div>
                <div v-for="company in companies" :key="company.id" class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-800">{{ company.name }}</span>
                        <span class="text-[10px] text-slate-400 mt-0.5 truncate max-w-[200px]">{{ company.description || 'Tanpa deskripsi' }}</span>
                    </div>
                    <div class="flex gap-1">
                        <Button icon="pi pi-pencil" text rounded severity="secondary" @click="openEdit(company)" />
                        <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDelete(company)" />
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
                        {{ isEditing ? 'Edit PT' : 'Tambah PT' }}
                    </h3>
                </div>
            </template>

            <div class="flex flex-col gap-4 mt-2 p-2 md:p-0">
                <div class="flex flex-col gap-1">
                    <label for="company-name" class="text-sm font-semibold text-slate-700">
                        Nama Perusahaan <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        id="company-name"
                        v-model="form.name"
                        placeholder="Contoh: PT Maju Jaya"
                        autofocus
                        class="!rounded-xl"
                        @keyup.enter="handleSubmit"
                        :class="{ 'p-invalid': formErrors.name }"
                    />
                    <small class="text-red-500" v-if="formErrors.name">{{ formErrors.name }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="company-desc" class="text-sm font-semibold text-slate-700">Deskripsi</label>
                    <Textarea
                        id="company-desc"
                        v-model="form.description"
                        placeholder="Deskripsi singkat perusahaan (opsional)"
                        rows="3"
                        autoResize
                        class="!rounded-xl"
                    />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 p-2 md:p-0">
                    <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="closeDialog" class="hidden md:flex" />
                    <Button
                        :label="isEditing ? 'Simpan' : 'Buat PT'"
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
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { fetchCompanies, createCompany, updateCompany, deleteCompany } from '@/api/companyApi'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import ProgressSpinner from 'primevue/progressspinner'

const toast = useToast()
const confirm = useConfirm()

const companies = ref([])
const isLoading = ref(false)
const showDialog = ref(false)
const isSaving = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

// Mobile detection
const windowWidth = ref(window.innerWidth)
const updateWidth = () => { windowWidth.value = window.innerWidth }
onMounted(() => window.addEventListener('resize', updateWidth))
onUnmounted(() => window.removeEventListener('resize', updateWidth))
const isMobile = computed(() => windowWidth.value < 768)

const form = reactive({ name: '', description: '' })
const formErrors = reactive({ name: '' })

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const loadCompanies = async () => {
    isLoading.value = true
    try {
        const res = await fetchCompanies()
        if (res.data.success) {
            companies.value = res.data.data
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data perusahaan', life: 3000 })
    } finally {
        isLoading.value = false
    }
}

const resetForm = () => {
    form.name = ''
    form.description = ''
    formErrors.name = ''
    editingId.value = null
}

const openCreate = () => {
    resetForm()
    isEditing.value = false
    showDialog.value = true
}

const openEdit = (company) => {
    resetForm()
    isEditing.value = true
    editingId.value = company.id
    form.name = company.name
    form.description = company.description || ''
    showDialog.value = true
}

const closeDialog = () => {
    showDialog.value = false
    resetForm()
}

const handleSubmit = async () => {
    formErrors.name = ''
    if (!form.name.trim()) {
        formErrors.name = 'Nama perusahaan wajib diisi.'
        return
    }

    isSaving.value = true
    try {
        const payload = { name: form.name.trim(), description: form.description.trim() || null }
        let res

        if (isEditing.value) {
            res = await updateCompany(editingId.value, payload)
        } else {
            res = await createCompany(payload)
        }

        if (res.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Sukses',
                detail: isEditing.value ? 'Perusahaan berhasil diperbarui.' : 'Perusahaan berhasil dibuat.',
                life: 3000
            })
            closeDialog()
            await loadCompanies()
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

const confirmDelete = (company) => {
    confirm.require({
        message: `Hapus perusahaan "${company.name}"? Tindakan ini tidak dapat dibatalkan.`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                const res = await deleteCompany(company.id)
                if (res.data.success) {
                    toast.add({ severity: 'success', summary: 'Dihapus', detail: res.data.message, life: 3000 })
                    await loadCompanies()
                }
            } catch (e) {
                toast.add({
                    severity: 'error',
                    summary: 'Gagal',
                    detail: e.response?.data?.message || 'Tidak dapat menghapus perusahaan.',
                    life: 4000
                })
            }
        }
    })
}

onMounted(loadCompanies)
</script>
