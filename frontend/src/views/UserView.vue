<template>
    <div>
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Manajemen User</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola data user dan assign ke departemen.</p>
            </div>
            <Button label="Tambah User" icon="pi pi-plus" @click="openCreate" />
        </div>

        <!-- Data Table (Desktop) / List (Mobile) -->
        <div class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden">
            <!-- Desktop View -->
            <DataTable
                v-if="!isMobile"
                :value="users"
                :loading="isLoading"
                responsiveLayout="scroll"
                stripedRows
                class="w-full"
            >
                <template #empty>
                    <div class="text-center py-12 text-slate-400">
                        <i class="pi pi-users text-5xl mb-3 block opacity-30"></i>
                        <p>Belum ada data user. Klik "Tambah User" untuk mulai.</p>
                    </div>
                </template>
                <template #loading>
                    <div class="text-center py-8 text-slate-400">Memuat data...</div>
                </template>

                <Column field="id" header="ID" style="width: 80px" />
                <Column field="name" header="Nama" />
                <Column field="email" header="Email" />
                <Column field="department.name" header="Departemen">
                    <template #body="{ data }">
                        <span v-if="data.department" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ data.department.name }}
                        </span>
                        <span v-else class="text-slate-400">—</span>
                    </template>
                </Column>
                <Column field="role" header="Role" style="width: 120px">
                    <template #body="{ data }">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white uppercase tracking-tight"
                            :class="{
                                'bg-red-500': data.role === 'root',
                                'bg-blue-600': data.role === 'admin',
                                'bg-slate-500': data.role === 'user',
                            }"
                        >
                            {{ data.role }}
                        </span>
                    </template>
                </Column>
                <Column field="level" header="Level" style="width: 140px">
                    <template #body="{ data }">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 uppercase">
                            {{ data.level || 'staff' }}
                        </span>
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

            <!-- Mobile View (List) -->
            <div v-else class="flex flex-col divide-y divide-slate-100">
                <div v-if="isLoading" class="p-10 flex flex-col items-center justify-center gap-3">
                    <ProgressSpinner style="width: 30px; height: 30px" />
                    <p class="text-xs text-slate-400">Memuat data...</p>
                </div>
                <div v-else-if="users.length === 0" class="p-10 text-center text-slate-400">
                    <p class="text-sm italic">Belum ada data user.</p>
                </div>
                <div v-for="user in users" :key="user.id" class="p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold">
                            {{ user.name.charAt(0) }}
                        </div>
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-slate-800">{{ user.name }}</span>
                                <span 
                                    class="text-[8px] px-1 py-0.5 rounded-sm font-bold uppercase"
                                    :class="{
                                        'bg-red-100 text-red-700': user.role === 'root',
                                        'bg-blue-100 text-blue-700': user.role === 'admin',
                                        'bg-slate-100 text-slate-700': user.role === 'user',
                                    }"
                                >
                                    {{ user.role }}
                                </span>
                            </div>
                            <span class="text-xs text-slate-400">{{ user.email }}</span>
                            <span v-if="user.department" class="text-[10px] text-blue-500 mt-0.5">{{ user.department.name }}</span>
                            <span class="text-[10px] text-amber-600 mt-0.5 uppercase font-semibold">{{ user.level || 'staff' }}</span>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <Button icon="pi pi-pencil" text rounded severity="secondary" @click="openEdit(user)" />
                        <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDelete(user)" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog: Tambah / Edit -->
        <Dialog
            v-model:visible="showDialog"
            :modal="true"
            class="w-full max-w-lg"
            :maximized="isMobile"
            :showHeader="true"
        >
            <template #header>
                <div class="flex items-center justify-between w-full pr-8 md:pr-0">
                    <h3 class="text-base md:text-xl font-bold text-slate-800">
                        {{ isEditing ? 'Edit User' : 'Tambah User' }}
                    </h3>
                </div>
            </template>

            <div class="flex flex-col gap-4 mt-2 p-2 md:p-0">
                <div class="flex flex-col gap-1">
                    <label for="user-name" class="text-sm font-semibold text-slate-700">
                        Nama <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        id="user-name"
                        v-model="form.name"
                        placeholder="Nama lengkap"
                        autofocus
                        class="!rounded-xl"
                        :class="{ 'p-invalid': formErrors.name }"
                    />
                    <small class="text-red-500" v-if="formErrors.name">{{ formErrors.name }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="user-email" class="text-sm font-semibold text-slate-700">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        id="user-email"
                        v-model="form.email"
                        placeholder="email@contoh.com"
                        class="!rounded-xl"
                        :class="{ 'p-invalid': formErrors.email }"
                    />
                    <small class="text-red-500" v-if="formErrors.email">{{ formErrors.email }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="user-password" class="text-sm font-semibold text-slate-700">
                        Password <span class="text-red-500" v-if="!isEditing">*</span>
                    </label>
                    <Password
                        id="user-password"
                        v-model="form.password"
                        :placeholder="isEditing ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter'"
                        toggleMask
                        :feedback="false"
                        :class="{ 'p-invalid': formErrors.password }"
                        inputClass="w-full !rounded-xl"
                        class="w-full"
                    />
                    <small class="text-red-500" v-if="formErrors.password">{{ formErrors.password }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="user-dept" class="text-sm font-semibold text-slate-700">Departemen</label>
                    <Select
                        id="user-dept"
                        v-model="form.department_id"
                        :options="departmentOptions"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Pilih departemen"
                        showClear
                        class="w-full !rounded-xl"
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label for="user-role" class="text-sm font-semibold text-slate-700">
                        Role <span class="text-red-500">*</span>
                    </label>
                    <Select
                        id="user-role"
                        v-model="form.role"
                        :options="roleOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Pilih role"
                        class="w-full !rounded-xl"
                        :class="{ 'p-invalid': formErrors.role }"
                    />
                    <small class="text-red-500" v-if="formErrors.role">{{ formErrors.role }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="user-level" class="text-sm font-semibold text-slate-700">
                        Level <span class="text-red-500">*</span>
                    </label>
                    <Select
                        id="user-level"
                        v-model="form.level"
                        :options="levelOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Pilih level"
                        class="w-full !rounded-xl"
                        :class="{ 'p-invalid': formErrors.level }"
                    />
                    <small class="text-red-500" v-if="formErrors.level">{{ formErrors.level }}</small>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 p-2 md:p-0">
                    <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="closeDialog" class="hidden md:flex" />
                    <Button
                        :label="isEditing ? 'Simpan' : 'Buat User'"
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
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { fetchUsers, createUser, updateUser, deleteUser } from '@/api/userApi'
import { fetchDepartments } from '@/api/departmentApi'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Select from 'primevue/select'
import ProgressSpinner from 'primevue/progressspinner'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

const toast = useToast()
const confirm = useConfirm()

const users = ref([])
const departmentOptions = ref([])
const isLoading = ref(false)
const showDialog = ref(false)
const isSaving = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const isMobile = ref(window.innerWidth < 768)

const onResize = () => {
    isMobile.value = window.innerWidth < 768
}

const roleOptions = [
    { label: 'Root', value: 'root' },
    { label: 'Admin', value: 'admin' },
    { label: 'User', value: 'user' },
]

const levelOptions = [
    { label: 'Staff', value: 'staff' },
    { label: 'Supervisor', value: 'supervisor' },
    { label: 'Manager', value: 'manager' },
    { label: 'Direksi', value: 'direksi' },
]

const form = reactive({ name: '', email: '', password: '', department_id: null, role: 'user', level: 'staff' })
const formErrors = reactive({ name: '', email: '', password: '', role: '', level: '' })

const loadUsers = async () => {
    isLoading.value = true
    try {
        const res = await fetchUsers()
        if (res.data.success) {
            users.value = res.data.data
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data user', life: 3000 })
    } finally {
        isLoading.value = false
    }
}

const loadDepartments = async () => {
    try {
        const res = await fetchDepartments()
        if (res.data.success) {
            departmentOptions.value = res.data.data
        }
    } catch (e) {
        // silent — departments are optional
    }
}

const resetForm = () => {
    form.name = ''
    form.email = ''
    form.password = ''
    form.department_id = null
    form.role = 'user'
    form.level = 'staff'
    formErrors.name = ''
    formErrors.email = ''
    formErrors.password = ''
    formErrors.role = ''
    formErrors.level = ''
    editingId.value = null
}

const openCreate = () => {
    resetForm()
    isEditing.value = false
    showDialog.value = true
}

const openEdit = (user) => {
    resetForm()
    isEditing.value = true
    editingId.value = user.id
    form.name = user.name
    form.email = user.email
    form.department_id = user.department_id != null ? Number(user.department_id) : null
    form.role = user.role
    form.level = user.level || 'staff'
    showDialog.value = true
}

const closeDialog = () => {
    showDialog.value = false
    resetForm()
}

const handleSubmit = async () => {
    formErrors.name = ''
    formErrors.email = ''
    formErrors.password = ''
    formErrors.role = ''
    formErrors.level = ''

    let hasError = false
    if (!form.name.trim()) { formErrors.name = 'Nama wajib diisi.'; hasError = true }
    if (!form.email.trim()) { formErrors.email = 'Email wajib diisi.'; hasError = true }
    if (!isEditing.value && !form.password) { formErrors.password = 'Password wajib diisi.'; hasError = true }
    if (!form.role) { formErrors.role = 'Role wajib dipilih.'; hasError = true }
    if (!form.level) { formErrors.level = 'Level wajib dipilih.'; hasError = true }
    if (hasError) return

    isSaving.value = true
    try {
        const payload = {
            name: form.name.trim(),
            email: form.email.trim(),
            department_id: form.department_id || null,
            role: form.role,
            level: form.level,
        }
        if (form.password) payload.password = form.password

        let res
        if (isEditing.value) {
            res = await updateUser(editingId.value, payload)
        } else {
            res = await createUser(payload)
        }

        if (res.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Sukses',
                detail: isEditing.value ? 'User berhasil diperbarui.' : 'User berhasil dibuat.',
                life: 3000
            })
            closeDialog()
            await loadUsers()
        }
    } catch (e) {
        if (e.response?.status === 422 && e.response?.data?.errors) {
            const errs = e.response.data.errors
            if (errs.name) formErrors.name = errs.name[0]
            if (errs.email) formErrors.email = errs.email[0]
            if (errs.password) formErrors.password = errs.password[0]
            if (errs.role) formErrors.role = errs.role[0]
            if (errs.level) formErrors.level = errs.level[0]
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Terjadi kesalahan.', life: 4000 })
        }
    } finally {
        isSaving.value = false
    }
}

const confirmDelete = (user) => {
    confirm.require({
        message: `Hapus user "${user.name}"? Tindakan ini tidak dapat dibatalkan.`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                const res = await deleteUser(user.id)
                if (res.data.success) {
                    toast.add({ severity: 'success', summary: 'Dihapus', detail: res.data.message, life: 3000 })
                    await loadUsers()
                }
            } catch (e) {
                toast.add({
                    severity: 'error',
                    summary: 'Gagal',
                    detail: e.response?.data?.message || 'Tidak dapat menghapus user.',
                    life: 4000
                })
            }
        }
    })
}

onMounted(async () => {
    window.addEventListener('resize', onResize)
    await Promise.all([loadUsers(), loadDepartments()])
})

onUnmounted(() => {
    window.removeEventListener('resize', onResize)
})
</script>
