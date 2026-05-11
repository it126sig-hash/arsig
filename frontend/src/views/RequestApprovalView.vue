<template>
  <div class="p-4 md:p-6">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-800 mb-1">Persetujuan Akses Arsip</h1>
      <p class="text-slate-500 text-sm">Kelola permintaan akses OTP untuk arsip yang Anda kelola</p>
    </div>

    <!-- Stats / Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center">
          <i class="pi pi-clock text-xl"></i>
        </div>
        <div>
          <p class="text-xs text-slate-400 uppercase font-bold">Pending</p>
          <p class="text-2xl font-bold text-slate-800">{{ stats.pending }}</p>
        </div>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center">
          <i class="pi pi-check-circle text-xl"></i>
        </div>
        <div>
          <p class="text-xs text-slate-400 uppercase font-bold">Disetujui</p>
          <p class="text-2xl font-bold text-slate-800">{{ stats.approved }}</p>
        </div>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-red-100 text-red-600 rounded-lg flex items-center justify-center">
          <i class="pi pi-times-circle text-xl"></i>
        </div>
        <div>
          <p class="text-xs text-slate-400 uppercase font-bold">Ditolak</p>
          <p class="text-2xl font-bold text-slate-800">{{ stats.rejected }}</p>
        </div>
      </div>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6 flex flex-wrap gap-4 items-center justify-between">
      <div class="flex gap-2">
        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" class="w-48" @change="onFilterChange" />
        <Button icon="pi pi-refresh" severity="secondary" outlined @click="loadRequests" :loading="loading" />
      </div>
      <IconField class="w-full md:w-72">
        <InputIcon class="pi pi-search" />
        <InputText v-model="searchQuery" placeholder="Cari pemohon atau arsip..." class="w-full" />
      </IconField>
    </div>

    <!-- Request Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <DataTable 
        :value="filteredRequests" 
        responsiveLayout="scroll" 
        class="p-datatable-sm"
        :loading="loading"
        paginator :rows="10"
      >
        <Column field="id" header="ID" style="width: 70px"></Column>
        <Column header="Pemohon">
          <template #body="{ data }">
            <div class="flex flex-col">
              <span class="font-bold text-slate-800">{{ data.requester?.name }}</span>
              <span class="text-xs text-slate-500">{{ data.requester?.email }}</span>
            </div>
          </template>
        </Column>
        <Column header="Arsip">
          <template #body="{ data }">
            <div class="flex flex-col">
              <span class="font-medium text-slate-700">{{ data.archive?.name }}</span>
              <span class="text-xs text-slate-400">{{ data.archive?.file_number }}</span>
            </div>
          </template>
        </Column>
        <Column header="Waktu Request">
          <template #body="{ data }">
            <span class="text-slate-600">{{ formatDateTime(data.created_at) }}</span>
          </template>
        </Column>
        <Column header="Status">
          <template #body="{ data }">
            <Tag :value="data.status.toUpperCase()" :severity="getStatusSeverity(data.status)" />
          </template>
        </Column>
        <Column header="Aksi" style="width: 150px">
          <template #body="{ data }">
            <div v-if="data.status === 'pending'" class="flex gap-2">
              <Button icon="pi pi-check" severity="success" text rounded v-tooltip="'Setujui (Generate OTP)'" @click="confirmApprove(data)" />
              <Button icon="pi pi-times" severity="danger" text rounded v-tooltip="'Tolak'" @click="confirmReject(data)" />
            </div>
            <div v-else-if="data.status === 'approved'" class="flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-slate-400 uppercase font-bold">OTP: {{ data.otp_code }}</span>
                    <Button icon="pi pi-copy" severity="secondary" text rounded size="small" class="!w-6 !h-6" v-tooltip="'Salin OTP'" @click="copyToClipboard(data.otp_code)" />
                </div>
                <span class="text-[9px] text-slate-400">Oleh: {{ data.reviewer?.name }}</span>
                <span class="text-[9px] text-slate-500 font-medium">{{ formatDateTime(data.updated_at) }}</span>
            </div>
            <div v-else class="flex flex-col">
                <span class="text-xs text-slate-400 italic">Ditolak oleh {{ data.reviewer?.name }}</span>
                <span class="text-[9px] text-slate-500 font-medium">{{ formatDateTime(data.updated_at) }}</span>
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { fetchArchiveRequests, approveArchiveRequest, rejectArchiveRequest } from '@/api/archiveRequestApi'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

const toast = useToast()
const confirm = useConfirm()
const requests = ref([])
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('all')

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        toast.add({ severity: 'success', summary: 'Tersalin', detail: 'OTP berhasil disalin ke clipboard.', life: 2000 })
    }).catch(err => {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal menyalin ke clipboard.', life: 2000 })
    })
}

const statusOptions = [
    { label: 'Semua Status', value: 'all' },
    { label: 'Pending', value: 'pending' },
    { label: 'Disetujui', value: 'approved' },
    { label: 'Ditolak', value: 'rejected' }
]

const stats = computed(() => {
    return {
        pending: requests.value.filter(r => r.status === 'pending').length,
        approved: requests.value.filter(r => r.status === 'approved').length,
        rejected: requests.value.filter(r => r.status === 'rejected').length
    }
})

const filteredRequests = computed(() => {
    let result = requests.value

    if (statusFilter.value !== 'all') {
        result = result.filter(r => r.status === statusFilter.value)
    }

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase()
        result = result.filter(r => 
            r.requester?.name?.toLowerCase().includes(q) || 
            r.archive?.name?.toLowerCase().includes(q) ||
            r.archive?.file_number?.toLowerCase().includes(q)
        )
    }

    return result
})

onMounted(() => {
    loadRequests()
})

const loadRequests = async () => {
    loading.value = true
    try {
        const res = await fetchArchiveRequests()
        requests.value = res.data.data
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat data permintaan.', life: 3000 })
    } finally {
        loading.value = false
    }
}

const confirmApprove = (data) => {
    confirm.require({
        message: `Apakah Anda yakin ingin MENYETUJUI permintaan akses untuk arsip "${data.archive.name}"? Kode OTP akan dibuat otomatis.`,
        header: 'Konfirmasi Persetujuan',
        icon: 'pi pi-exclamation-triangle',
        accept: () => handleApprove(data.id),
        acceptLabel: 'Setujui',
        rejectLabel: 'Batal'
    })
}

const handleApprove = async (id) => {
    try {
        await approveArchiveRequest(id)
        toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Permintaan akses telah disetujui.', life: 3000 })
        loadRequests()
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal menyetujui permintaan.', life: 3000 })
    }
}

const confirmReject = (data) => {
    confirm.require({
        message: `Apakah Anda yakin ingin MENOLAK permintaan akses untuk arsip "${data.archive.name}"?`,
        header: 'Konfirmasi Penolakan',
        icon: 'pi pi-info-circle',
        severity: 'danger',
        accept: () => handleReject(data.id),
        acceptLabel: 'Tolak',
        rejectLabel: 'Batal'
    })
}

const handleReject = async (id) => {
    try {
        await rejectArchiveRequest(id)
        toast.add({ severity: 'info', summary: 'Ditolak', detail: 'Permintaan akses telah ditolak.', life: 3000 })
        loadRequests()
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal menolak permintaan.', life: 3000 })
    }
}

const getStatusSeverity = (status) => {
    switch (status) {
        case 'pending': return 'warn'
        case 'approved': return 'success'
        case 'rejected': return 'danger'
        default: return 'secondary'
    }
}

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    })
}

const onFilterChange = () => {
    // Computed property handled automatically
}
</script>
