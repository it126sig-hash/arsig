<template>
    <div>
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Manajemen Perusahaan (PT)</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola data perusahaan / PT yang terdaftar dalam sistem.</p>
            </div>
            <Button label="Tambah Perusahaan" icon="pi pi-plus" @click="openCreate" />
        </div>

        <!-- Data Table -->
        <div class="bg-white shadow-sm border border-slate-200 rounded-lg overflow-hidden">
            <DataTable
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
                            <Button
                                icon="pi pi-pencil"
                                class="p-button-sm p-button-outlined"
                                v-tooltip.top="'Edit'"
                                @click="openEdit(data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                class="p-button-sm p-button-outlined p-button-danger"
                                v-tooltip.top="'Hapus'"
                                @click="confirmDelete(data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Dialog: Tambah / Edit -->
        <Dialog
            v-model:visible="showDialog"
            :header="isEditing ? 'Edit Perusahaan' : 'Tambah Perusahaan Baru'"
            :modal="true"
            class="w-full max-w-md"
        >
            <div class="flex flex-col gap-4 mt-2">
                <div class="flex flex-col gap-1">
                    <label for="company-name" class="text-sm font-medium text-slate-700">
                        Nama Perusahaan <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        id="company-name"
                        v-model="form.name"
                        placeholder="Contoh: PT Maju Jaya"
                        autofocus
                        @keyup.enter="handleSubmit"
                        :class="{ 'p-invalid': formErrors.name }"
                    />
                    <small class="text-red-500" v-if="formErrors.name">{{ formErrors.name }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="company-desc" class="text-sm font-medium text-slate-700">Deskripsi</label>
                    <Textarea
                        id="company-desc"
                        v-model="form.description"
                        placeholder="Deskripsi singkat perusahaan (opsional)"
                        rows="3"
                        autoResize
                    />
                </div>
            </div>

            <template #footer>
                <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="closeDialog" />
                <Button
                    :label="isEditing ? 'Simpan Perubahan' : 'Buat Perusahaan'"
                    icon="pi pi-check"
                    :loading="isSaving"
                    @click="handleSubmit"
                />
            </template>
        </Dialog>

        <!-- Confirm Dialog -->
        <ConfirmDialog />
        <Toast />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
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

const toast = useToast()
const confirm = useConfirm()

const companies = ref([])
const isLoading = ref(false)
const showDialog = ref(false)
const isSaving = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

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
