<template>
    <div>
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Manajemen Tag (Hashtag)</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola tag untuk pencarian arsip.</p>
            </div>
            <Button label="Tambah Tag" icon="pi pi-plus" @click="openCreate" />
        </div>

        <!-- Data Table -->
        <div class="bg-white shadow-sm border border-slate-200 rounded-lg overflow-hidden">
            <DataTable
                :value="tags"
                :loading="isLoading"
                responsiveLayout="scroll"
                stripedRows
                class="w-full"
            >
                <template #empty>
                    <div class="text-center py-12 text-slate-400">
                        <i class="pi pi-hashtag text-5xl mb-3 block opacity-30"></i>
                        <p>Belum ada tag. Klik "Tambah Tag" untuk mulai.</p>
                    </div>
                </template>
                <template #loading>
                    <div class="text-center py-8 text-slate-400">Memuat data...</div>
                </template>

                <Column field="id" header="ID" style="width: 80px" />
                <Column field="nama" header="Nama Tag">
                    <template #body="{ data }">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            #{{ data.nama }}
                        </span>
                    </template>
                </Column>
                <Column header="Dibuat Oleh">
                    <template #body="{ data }">
                        <span class="text-slate-500">{{ data.creator?.name || '—' }}</span>
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
            :header="isEditing ? 'Edit Tag' : 'Tambah Tag Baru'"
            :modal="true"
            class="w-full max-w-md"
        >
            <div class="flex flex-col gap-4 mt-2">
                <div class="flex flex-col gap-1">
                    <label for="tag-nama" class="text-sm font-medium text-slate-700">
                        Nama Tag <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        id="tag-nama"
                        v-model="form.nama"
                        placeholder="Contoh: kontrak, sertifikat, penting"
                        autofocus
                        @keyup.enter="handleSubmit"
                        :class="{ 'p-invalid': formErrors.nama }"
                    />
                    <small class="text-red-500" v-if="formErrors.nama">{{ formErrors.nama }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="closeDialog" />
                <Button
                    :label="isEditing ? 'Simpan Perubahan' : 'Buat Tag'"
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
import { fetchTags, createTag, updateTag, deleteTag } from '@/api/tagApi'
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

const tags = ref([])
const isLoading = ref(false)
const showDialog = ref(false)
const isSaving = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

const form = reactive({ nama: '' })
const formErrors = reactive({ nama: '' })

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const loadTags = async () => {
    isLoading.value = true
    try {
        const res = await fetchTags()
        if (res.data.success) {
            tags.value = res.data.data
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data tag', life: 3000 })
    } finally {
        isLoading.value = false
    }
}

const resetForm = () => {
    form.nama = ''
    formErrors.nama = ''
    editingId.value = null
}

const openCreate = () => {
    resetForm()
    isEditing.value = false
    showDialog.value = true
}

const openEdit = (tag) => {
    resetForm()
    isEditing.value = true
    editingId.value = tag.id
    form.nama = tag.nama
    showDialog.value = true
}

const closeDialog = () => {
    showDialog.value = false
    resetForm()
}

const handleSubmit = async () => {
    formErrors.nama = ''
    if (!form.nama.trim()) {
        formErrors.nama = 'Nama tag wajib diisi.'
        return
    }

    isSaving.value = true
    try {
        const payload = { nama: form.nama.trim() }
        let res

        if (isEditing.value) {
            res = await updateTag(editingId.value, payload)
        } else {
            res = await createTag(payload)
        }

        if (res.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Sukses',
                detail: isEditing.value ? 'Tag berhasil diperbarui.' : 'Tag berhasil dibuat.',
                life: 3000
            })
            closeDialog()
            await loadTags()
        }
    } catch (e) {
        const msg = e.response?.data?.errors?.nama?.[0] || e.response?.data?.message || 'Terjadi kesalahan.'
        if (e.response?.status === 422) {
            formErrors.nama = msg
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 })
        }
    } finally {
        isSaving.value = false
    }
}

const confirmDelete = (tag) => {
    confirm.require({
        message: `Hapus tag "#${tag.nama}"? Tindakan ini tidak dapat dibatalkan.`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                const res = await deleteTag(tag.id)
                if (res.data.success) {
                    toast.add({ severity: 'success', summary: 'Dihapus', detail: res.data.message, life: 3000 })
                    await loadTags()
                }
            } catch (e) {
                toast.add({
                    severity: 'error',
                    summary: 'Gagal',
                    detail: e.response?.data?.message || 'Tidak dapat menghapus tag.',
                    life: 4000
                })
            }
        }
    })
}

onMounted(loadTags)
</script>
