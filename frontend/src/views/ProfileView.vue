<template>
  <div class="max-w-4xl mx-auto p-4 md:p-6">
    <!-- Profile Header -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden mb-6">
      <div class="h-32 bg-gradient-to-r from-blue-600 to-indigo-700"></div>
      <div class="px-6 pb-6">
        <div class="relative flex flex-col md:flex-row md:items-end gap-4 -mt-12">
          <div class="w-24 h-24 rounded-2xl bg-white p-1 shadow-lg">
            <div class="w-full h-full rounded-xl bg-slate-100 flex items-center justify-center text-slate-400">
              <i class="pi pi-user text-4xl"></i>
            </div>
          </div>
          <div class="flex-1">
            <h1 class="text-2xl font-bold text-slate-800">{{ authStore.user?.name }}</h1>
            <p class="text-slate-500 font-medium">{{ authStore.user?.email }}</p>
          </div>
          <div class="flex gap-2">
            <Tag :value="authStore.user?.role?.toUpperCase()" severity="info" class="!px-3 !py-1 !rounded-lg" />
            <Tag v-if="authStore.user?.department" :value="authStore.user.department.name" severity="secondary" class="!px-3 !py-1 !rounded-lg" />
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
      <!-- User Info Details -->
      <div class="md:col-span-7 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="pi pi-info-circle text-blue-500"></i>
            Informasi Akun
          </h3>
          <div class="space-y-4">
            <div class="grid grid-cols-3 gap-4">
              <span class="text-sm text-slate-400 font-medium">ID User</span>
              <span class="col-span-2 text-sm text-slate-700 font-semibold">#{{ authStore.user?.id }}</span>
            </div>
            <div class="grid grid-cols-3 gap-4">
              <span class="text-sm text-slate-400 font-medium">Username/Email</span>
              <span class="col-span-2 text-sm text-slate-700 font-semibold">{{ authStore.user?.email }}</span>
            </div>
            <div class="grid grid-cols-3 gap-4">
              <span class="text-sm text-slate-400 font-medium">Role</span>
              <span class="col-span-2 text-sm text-slate-700 font-semibold capitalize">{{ authStore.user?.role }}</span>
            </div>
            <div class="grid grid-cols-3 gap-4">
              <span class="text-sm text-slate-400 font-medium">Bergabung Pada</span>
              <span class="col-span-2 text-sm text-slate-700 font-semibold">{{ formatDate(authStore.user?.created_at) }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="pi pi-lock text-amber-500"></i>
            Keamanan
          </h3>
          <p class="text-sm text-slate-500 mb-4">Ubah kata sandi Anda secara berkala untuk menjaga keamanan akun.</p>
          <Button label="Ubah Password" icon="pi pi-key" severity="warning" outlined class="!rounded-xl" @click="showPasswordDialog = true" />
        </div>
      </div>

      <!-- Actions Sidebar -->
      <div class="md:col-span-5">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col gap-3">
          <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-2">Aksi Cepat</h3>
          <Button label="Dashboard" icon="pi pi-home" severity="secondary" text class="!justify-start !text-slate-600" @click="router.push('/')" />
          <Button label="File Explorer" icon="pi pi-folder" severity="secondary" text class="!justify-start !text-slate-600" @click="router.push('/file-explorer')" />
          <div class="border-t border-slate-100 my-2"></div>
          <Button label="Logout" icon="pi pi-sign-out" severity="danger" class="!rounded-xl !py-3 font-bold" @click="confirmLogout" />
        </div>
      </div>
    </div>

    <!-- Password Change Dialog (Placeholder) -->
    <Dialog v-model:visible="showPasswordDialog" header="Ubah Password" :modal="true" class="w-full max-w-sm">
      <div class="flex flex-col gap-4 mt-2">
        <div class="flex flex-col gap-1">
          <label class="text-xs font-bold text-slate-400 uppercase">Password Saat Ini</label>
          <Password v-model="passForm.current" toggleMask :feedback="false" inputClass="w-full !rounded-xl" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-xs font-bold text-slate-400 uppercase">Password Baru</label>
          <Password v-model="passForm.new" toggleMask class="w-full" inputClass="w-full !rounded-xl" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-xs font-bold text-slate-400 uppercase">Konfirmasi Password Baru</label>
          <Password v-model="passForm.confirm" toggleMask :feedback="false" inputClass="w-full !rounded-xl" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Batal" text severity="secondary" @click="showPasswordDialog = false" />
        <Button label="Simpan Perubahan" icon="pi pi-check" class="!rounded-xl" @click="handlePasswordChange" />
      </template>
    </Dialog>

    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Password from 'primevue/password'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const showPasswordDialog = ref(false)
const passForm = reactive({
  current: '',
  new: '',
  confirm: ''
})

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const confirmLogout = () => {
  confirm.require({
    message: 'Apakah Anda yakin ingin keluar dari sistem?',
    header: 'Konfirmasi Logout',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      await authStore.logout()
      router.push('/login')
    }
  })
}

const handlePasswordChange = () => {
  // Logic change password API call here
  toast.add({ severity: 'info', summary: 'Fitur Belum Tersedia', detail: 'Fungsi ubah password sedang dalam pengembangan.', life: 3000 })
  showPasswordDialog.value = false
}
</script>
