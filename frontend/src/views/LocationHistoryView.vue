<template>
    <div class="p-4 lg:p-6 max-w-[1600px] mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Riwayat Lokasi Fisik</h1>
                <p class="text-slate-500 text-sm mt-1">Lacak setiap pergerakan fisik arsip di seluruh gudang.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Filters -->
            <div class="p-4 bg-slate-50/50 border-b border-slate-100 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Cari Arsip</label>
                    <IconField>
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="filters.q" placeholder="Nama arsip atau No. Berkas..." @input="debouncedSearch" />
                    </IconField>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Dari Tanggal</label>
                    <DatePicker v-model="filters.date_from" dateFormat="yy-mm-dd" showIcon iconDisplay="input" placeholder="Pilih Tanggal" @change="loadHistories" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sampai Tanggal</label>
                    <DatePicker v-model="filters.date_to" dateFormat="yy-mm-dd" showIcon iconDisplay="input" placeholder="Pilih Tanggal" @change="loadHistories" />
                </div>
            </div>

            <!-- Table -->
            <DataTable 
                :value="histories" 
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
                        <i class="pi pi-history text-4xl mb-3 opacity-20"></i>
                        <p>Belum ada riwayat pemindahan ditemukan.</p>
                    </div>
                </template>

                <Column field="created_at" header="Waktu Pemindahan" style="width: 180px">
                    <template #body="{ data }">
                        <div class="flex flex-col">
                            <span class="font-semibold text-slate-700">{{ formatDateTime(data.created_at) }}</span>
                            <span class="text-[10px] text-slate-400">{{ timeAgo(data.created_at) }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="Arsip">
                    <template #body="{ data }">
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-800">{{ data.archive?.name }}</span>
                            <span class="text-xs text-slate-500">{{ data.archive?.file_number }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="Lokasi Lama">
                    <template #body="{ data }">
                        <div v-if="data.old_floor" class="flex flex-col text-xs">
                            <span class="text-slate-600">{{ data.old_floor?.name }} > {{ data.old_room?.name }}</span>
                            <span class="text-slate-400">Lemari {{ data.old_cabinet?.name }}</span>
                            <span v-if="data.old_cabinet_slot" class="text-slate-400">Slot: {{ data.old_cabinet_slot?.name }}</span>
                        </div>
                        <span v-else class="text-slate-300 italic text-[10px] flex items-center gap-1">
                            <i class="pi pi-plus-circle text-[9px]"></i>
                            Penempatan Pertama
                        </span>
                    </template>
                </Column>

                <Column header="Lokasi Baru">
                    <template #body="{ data }">
                        <div class="flex flex-col text-xs">
                            <span class="text-blue-600 font-medium">{{ data.new_floor?.name }} > {{ data.new_room?.name }}</span>
                            <span class="text-slate-500">Lemari {{ data.new_cabinet?.name }}</span>
                            <span v-if="data.new_cabinet_slot" class="text-slate-500">Slot: {{ data.new_cabinet_slot?.name }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="PIC & Keterangan">
                    <template #body="{ data }">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-1.5 mb-1">
                                <i class="pi pi-user text-[10px] text-slate-400"></i>
                                <span class="text-xs font-semibold text-slate-700">{{ data.moved_by?.name }}</span>
                            </div>
                            <p v-if="data.notes" class="text-xs text-slate-500 italic bg-slate-50 p-2 rounded border border-slate-100 max-w-xs">
                                "{{ data.notes }}"
                            </p>
                            <span v-else class="text-[10px] text-slate-300 italic">Tanpa keterangan</span>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import DatePicker from 'primevue/datepicker'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import { fetchLocationHistories } from '@/api/locationHistoryApi'
import { debounce } from 'lodash-es'

const histories = ref([])
const totalRecords = ref(0)
const loading = ref(false)

const filters = reactive({
    q: '',
    date_from: null,
    date_to: null,
    page: 1
})

const loadHistories = async () => {
    loading.value = true
    try {
        const params = {
            ...filters,
            date_from: filters.date_from ? formatDate(filters.date_from) : null,
            date_to: filters.date_to ? formatDate(filters.date_to) : null,
        }
        const res = await fetchLocationHistories(params)
        histories.value = res.data.data.data
        totalRecords.value = res.data.data.total
    } catch (err) {
        console.error('Failed to load histories', err)
    } finally {
        loading.value = false
    }
}

const onPage = (event) => {
    filters.page = event.page + 1
    loadHistories()
}

const debouncedSearch = debounce(() => {
    filters.page = 1
    loadHistories()
}, 500)

onMounted(() => {
    loadHistories()
})

// Helpers
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

const timeAgo = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffInSeconds = Math.floor((now - date) / 1000)
    
    if (diffInSeconds < 60) return 'Baru saja'
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} menit yang lalu`
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} jam yang lalu`
    return `${Math.floor(diffInSeconds / 86400)} hari yang lalu`
}
</script>
