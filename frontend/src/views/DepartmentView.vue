<template>
    <div>
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Manajemen Departemen</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola data departemen dalam sistem.</p>
            </div>
            <Button label="Tambah Departemen" icon="pi pi-plus" @click="openCreate" />
        </div>

        <!-- Data Table -->
        <div class="bg-white shadow-sm border border-slate-200 rounded-lg overflow-hidden">
            <DataTable
                :value="departments"
                :loading="isLoading"
                responsiveLayout="scroll"
                stripedRows
                class="w-full"
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
            :header="isEditing ? 'Edit Departemen' : 'Tambah Departemen Baru'"
            :modal="true"
            class="w-full max-w-md"
        >
            <div class="flex flex-col gap-4 mt-2">
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
                        :class="{ 'p-invalid': formErrors.name }"
                    />
                    <small class="text-red-500" v-if="formErrors.name">{{ formErrors.name }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="closeDialog" />
                <Button
                    :label="isEditing ? 'Simpan Perubahan' : 'Buat Departemen'"
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
import { fetchDepartments, createDepartment, updateDepartment, deleteDepartment } from '@/api/departmentApi'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

const toast = useToast()
const confirm = useConfirm()

const departments = ref([])
const isLoading = ref(false)
const showDialog = ref(false)
const isSaving = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

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
