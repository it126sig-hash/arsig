<template>
    <div class="p-4 lg:p-6 max-w-[1600px] mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Riwayat Download</h1>
                <p class="text-slate-500 text-sm mt-1">Pantau siapa saja yang melihat & mengunduh arsip. Arsip confidential hanya terlihat oleh PIC dan kepala departemen terkait.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Filters -->
            <div class="p-4 bg-slate-50/50 border-b border-slate-100 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Dari Tanggal</label>
                    <DatePicker v-model="filters.date_from" dateFormat="yy-mm-dd" showIcon iconDisplay="input" placeholder="Pilih Tanggal" @change="onFilterChange" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sampai Tanggal</label>
                    <DatePicker v-model="filters.date_to" dateFormat="yy-mm-dd" showIcon iconDisplay="input" placeholder="Pilih Tanggal" @change="onFilterChange" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Departemen</label>
                    <Select v-model="filters.department_id" :options="departments" optionLabel="name" optionValue="id" placeholder="Semua Departemen" showClear @change="onFilterChange" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status</label>
                    <Select v-model="filters.is_confidential" :options="confidentialOptions" optionLabel="label" optionValue="value" placeholder="Semua Status" showClear @change="onFilterChange" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Diunduh/Dilihat Oleh</label>
                    <Select v-model="filters.user_id" :options="users" optionLabel="name" optionValue="id" placeholder="Semua User" filter showClear @change="onFilterChange" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">PIC</label>
                    <Select v-model="filters.pic_user_id" :options="users" optionLabel="name" optionValue="id" placeholder="Semua PIC" filter showClear @change="onFilterChange" />
                </div>
            </div>

            <!-- Table -->
            <DataTable
                :value="logs"
                lazy
                paginator
                :rows="15"
                :totalRecords="totalRecords"
                :loading="loading"
                @page="onPage($event)"
                class="p-datatable-sm"
                responsiveLayout="stack"
                breakpoint="960px"
            >
                <template #empty>
                    <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                        <i class="pi pi-download text-4xl mb-3 opacity-20"></i>
                        <p>Belum ada riwayat download ditemukan.</p>
                    </div>
                </template>

                <Column field="created_at" header="Waktu" style="width: 170px">
                    <template #body="{ data }">
                        <span class="font-semibold text-slate-700">{{ formatDateTime(data.created_at) }}</span>
                    </template>
                </Column>

                <Column header="Aksi" style="width: 120px">
                    <template #body="{ data }">
                        <Tag :value="data.action === 'view' ? 'Lihat Detail' : 'Download'" :severity="data.action === 'view' ? 'info' : 'success'" />
                    </template>
                </Column>

                <Column header="Arsip">
                    <template #body="{ data }">
                        <button
                            class="flex flex-col text-left hover:underline decoration-blue-400"
                            @click="openDetail(data.archive)"
                        >
                            <span class="font-bold text-slate-800">{{ data.archive?.name }}</span>
                            <span class="text-xs text-slate-500">{{ data.archive?.file_number }}</span>
                            <span v-if="data.archive?.is_confidential" class="text-[10px] text-red-500 font-semibold uppercase mt-0.5">Confidential</span>
                        </button>
                    </template>
                </Column>

                <Column header="Oleh">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1.5">
                            <i class="pi pi-user text-[10px] text-slate-400"></i>
                            <span class="text-sm text-slate-700">{{ data.user?.name }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="PIC Arsip">
                    <template #body="{ data }">
                        <span class="text-sm text-slate-500">{{ data.archive?.pic?.name || '-' }}</span>
                    </template>
                </Column>
            </DataTable>
        </div>

        <ArchiveDetailModal v-model="detailDialog" :archive="selectedArchive" />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Tag from 'primevue/tag'
import { fetchDownloadHistory } from '@/api/archiveRequestApi'
import { fetchUsers } from '@/api/userApi'
import { fetchDepartments } from '@/api/departmentApi'
import ArchiveDetailModal from '@/components/ArchiveDetailModal.vue'

const logs = ref([])
const totalRecords = ref(0)
const loading = ref(false)
const users = ref([])
const departments = ref([])
const detailDialog = ref(false)
const selectedArchive = ref(null)

const confidentialOptions = [
    { label: 'Confidential', value: true },
    { label: 'Non-Confidential', value: false },
]

const filters = reactive({
    date_from: null,
    date_to: null,
    user_id: null,
    pic_user_id: null,
    department_id: null,
    is_confidential: null,
    page: 1,
})

const loadHistory = async () => {
    loading.value = true
    try {
        const params = {
            ...filters,
            date_from: filters.date_from ? formatDate(filters.date_from) : null,
            date_to: filters.date_to ? formatDate(filters.date_to) : null,
        }
        const res = await fetchDownloadHistory(params)
        logs.value = res.data.data.data
        totalRecords.value = res.data.data.total
    } catch (err) {
        console.error('Failed to load download history', err)
    } finally {
        loading.value = false
    }
}

const onPage = (event) => {
    filters.page = event.page + 1
    loadHistory()
}

const onFilterChange = () => {
    filters.page = 1
    loadHistory()
}

const openDetail = (archive) => {
    if (!archive) return
    selectedArchive.value = archive
    detailDialog.value = true
}

const loadUsers = async () => {
    try {
        const res = await fetchUsers()
        users.value = res.data.data
    } catch (err) {
        console.error('Failed to load users', err)
    }
}

const loadDepartments = async () => {
    try {
        const res = await fetchDepartments()
        departments.value = res.data.data
    } catch (err) {
        console.error('Failed to load departments', err)
    }
}

const formatDate = (date) => {
    if (!date) return null
    const d = new Date(date)
    return d.toISOString().split('T')[0]
}

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    })
}

onMounted(() => {
    loadHistory()
    loadUsers()
    loadDepartments()
})
</script>
