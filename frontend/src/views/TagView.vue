<template>
    <div>
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-800">Manajemen Tag (Hashtag)</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-1">Kelola tag untuk pencarian arsip.</p>
            </div>
            <div class="flex gap-2 w-full md:w-auto">
                <Button
                    v-if="authStore.canModule('tags', 'delete')"
                    :label="showTrashed ? 'Tampilkan Aktif' : 'Tampilkan Terhapus'"
                    :icon="showTrashed ? 'pi pi-list' : 'pi pi-trash'"
                    severity="secondary"
                    outlined
                    @click="toggleTrashed"
                    class="w-1/2 md:w-auto !rounded-xl"
                />
                <Button
                    v-if="authStore.canModule('tags', 'create') && !showTrashed"
                    label="Tambah Tag"
                    icon="pi pi-plus"
                    @click="openCreate"
                    class="w-1/2 md:w-auto !rounded-xl shadow-md"
                />
            </div>
        </div>

        <!-- Data Table Card -->
        <div class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden">
            <!-- Table Toolbar (Desktop Search) -->
            <div class="hidden md:flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <span class="text-sm font-semibold text-slate-700">
                    <i class="pi pi-hashtag mr-2 text-indigo-500"></i>Daftar Tag
                </span>
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="globalFilter"
                        placeholder="Cari tag..."
                        class="!rounded-lg text-sm"
                        style="width: 240px;"
                    />
                </IconField>
            </div>

            <!-- Mobile Search Bar -->
            <div class="md:hidden p-4 border-b border-slate-100">
                <IconField class="w-full">
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="globalFilter" placeholder="Cari tag..." class="w-full !rounded-xl" />
                </IconField>
            </div>

            <!-- Desktop View -->
            <DataTable
                v-if="!isMobile"
                :value="tags"
                :loading="isLoading"
                responsiveLayout="scroll"
                stripedRows
                class="w-full"
                :filters="filters"
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
                        <div class="flex gap-2" v-if="!showTrashed">
                            <Button v-if="authStore.canModule('tags', 'update')" icon="pi pi-pencil" text rounded severity="secondary" @click="openEdit(data)" />
                            <Button v-if="authStore.canModule('tags', 'delete')" icon="pi pi-trash" text rounded severity="danger" @click="confirmDelete(data)" />
                        </div>
                        <div class="flex gap-2" v-else>
                            <Button icon="pi pi-refresh" text rounded severity="success" @click="handleRestore(data)" title="Pulihkan" />
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
                <div v-else-if="filteredTags.length === 0" class="p-10 text-center text-slate-400 italic text-sm">
                    Tidak ada data tag.
                </div>
                <div v-for="tag in filteredTags" :key="tag.id" class="p-4 flex flex-col gap-3 hover:bg-slate-50">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-600 border border-indigo-100 w-fit">
                                #{{ tag.nama }}
                            </span>
                            <div class="flex flex-col mt-2">
                                <span class="text-[10px] text-slate-400">Oleh: {{ tag.creator?.name || '—' }}</span>
                                <span class="text-[10px] text-slate-400">Dibuat: {{ formatDate(tag.created_at) }}</span>
                            </div>
                        </div>
                        <div class="flex gap-1 self-start" v-if="!showTrashed">
                             <Button v-if="authStore.canModule('tags', 'update')" icon="pi pi-pencil" text rounded severity="secondary" @click="openEdit(tag)" />
                             <Button v-if="authStore.canModule('tags', 'delete')" icon="pi pi-trash" text rounded severity="danger" @click="confirmDelete(tag)" />
                        </div>
                        <div class="flex gap-1 self-start" v-else>
                             <Button icon="pi pi-refresh" text rounded severity="success" @click="handleRestore(tag)" />
                        </div>
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
                        {{ isEditing ? 'Edit Tag' : 'Tambah Tag Baru' }}
                    </h3>
                </div>
            </template>
            <div class="flex flex-col gap-4 mt-2 p-2 md:p-0">
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
                        class="!rounded-xl"
                        :class="{ 'p-invalid': formErrors.nama }"
                    />
                    <small class="text-red-500" v-if="formErrors.nama">{{ formErrors.nama }}</small>
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
import { fetchTags, createTag, updateTag, deleteTag, fetchTrashedTags, restoreTag } from '@/api/tagApi'
import { useAuthStore } from '@/store/auth'
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
const authStore = useAuthStore()

// Mobile detection
const windowWidth = ref(window.innerWidth)
const updateWidth = () => { windowWidth.value = window.innerWidth }
onMounted(() => window.addEventListener('resize', updateWidth))
onUnmounted(() => window.removeEventListener('resize', updateWidth))
const isMobile = computed(() => windowWidth.value < 768)

const tags = ref([])
const isLoading = ref(false)
const showTrashed = ref(false)
const showDialog = ref(false)
const isSaving = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const globalFilter = ref('')

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })
watch(globalFilter, (val) => { filters.value.global.value = val })

const filteredTags = computed(() => {
    if (!globalFilter.value) return tags.value
    const q = globalFilter.value.toLowerCase()
    return tags.value.filter(t => 
        t.nama?.toLowerCase().includes(q) || 
        t.id?.toString().includes(q)
    )
})

const form = reactive({ nama: '' })
const formErrors = reactive({ nama: '' })

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const loadTags = async () => {
    isLoading.value = true
    try {
        const res = showTrashed.value ? await fetchTrashedTags() : await fetchTags()
        if (res.data.success) {
            tags.value = res.data.data
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data tag', life: 3000 })
    } finally {
        isLoading.value = false
    }
}

const toggleTrashed = () => {
    showTrashed.value = !showTrashed.value
    loadTags()
}

const handleRestore = async (tag) => {
    try {
        const res = await restoreTag(tag.id)
        if (res.data.success) {
            toast.add({ severity: 'success', summary: 'Dipulihkan', detail: res.data.message, life: 3000 })
            await loadTags()
        }
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Gagal',
            detail: e.response?.data?.message || 'Tidak dapat memulihkan tag.',
            life: 4000
        })
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
