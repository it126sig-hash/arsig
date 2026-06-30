<template>
    <div class="p-4 lg:p-6 max-w-[1600px] mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Riwayat Download</h1>
                <p class="text-slate-500 text-sm mt-1">Pantau siapa saja yang mengunduh arsip. Arsip confidential hanya terlihat oleh PIC dan kepala departemen terkait.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <DataTable
                :value="logs"
                :loading="loading"
                paginator
                :rows="15"
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

                <Column field="created_at" header="Waktu Download" style="width: 180px">
                    <template #body="{ data }">
                        <span class="font-semibold text-slate-700">{{ formatDateTime(data.created_at) }}</span>
                    </template>
                </Column>

                <Column header="Arsip">
                    <template #body="{ data }">
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-800">{{ data.archive?.name }}</span>
                            <span class="text-xs text-slate-500">{{ data.archive?.file_number }}</span>
                            <span v-if="data.archive?.is_confidential" class="text-[10px] text-red-500 font-semibold uppercase mt-0.5">Confidential</span>
                        </div>
                    </template>
                </Column>

                <Column header="Diunduh Oleh">
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
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { fetchDownloadHistory } from '@/api/archiveRequestApi'

const logs = ref([])
const loading = ref(false)

const loadHistory = async () => {
    loading.value = true
    try {
        const res = await fetchDownloadHistory()
        logs.value = res.data.data
    } catch (err) {
        console.error('Failed to load download history', err)
    } finally {
        loading.value = false
    }
}

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    })
}

onMounted(() => {
    loadHistory()
})
</script>
