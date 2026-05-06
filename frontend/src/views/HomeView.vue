<template>
  <div class="p-4 md:p-6">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-800 mb-1">Cari Arsip</h1>
      <p class="text-slate-500 text-sm">Cari dan temukan dokumen arsip di seluruh PT dan kategori</p>
    </div>

    <!-- Search Form / Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Keyword -->
        <div class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-slate-700">Keyword</label>
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.q" placeholder="Nama, No. Berkas, Keterangan..." class="w-full" @input="debouncedSearch" />
          </IconField>
        </div>

        <!-- Company -->
        <div class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-slate-700">PT</label>
          <Select 
            v-model="filters.company_id" 
            :options="companies" 
            optionLabel="name" 
            optionValue="id" 
            placeholder="Semua PT" 
            class="w-full" 
            showClear
            @change="onCompanyChange"
          />
        </div>

        <!-- Category -->
        <div class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-slate-700">Kategori</label>
          <TreeSelect 
            v-model="filters.category_id" 
            :options="categories" 
            placeholder="Pilih Kategori" 
            class="w-full" 
            :disabled="!filters.company_id"
            showClear
            filter
            @change="search"
          />
        </div>

        <!-- Archive Type -->
        <div class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-slate-700">Tipe File</label>
          <Select 
            v-model="filters.archive_type" 
            :options="archiveTypes" 
            optionLabel="label" 
            optionValue="value" 
            placeholder="Semua Tipe" 
            class="w-full" 
            showClear
            @change="search"
          />
        </div>

        <!-- Date Range -->
        <div class="flex flex-col gap-2 lg:col-span-2">
          <label class="text-sm font-semibold text-slate-700">Rentang Tanggal Terbit</label>
          <DatePicker 
            v-model="dateRange" 
            selectionMode="range" 
            :manualInput="false" 
            placeholder="Pilih rentang tanggal" 
            class="w-full"
            showIcon
            iconDisplay="input"
            showClear
            @hide="onDateRangeChange"
            @clear="onDateRangeChange"
          />
        </div>

        <!-- Tags -->
        <div class="flex flex-col gap-2 lg:col-span-2">
          <label class="text-sm font-semibold text-slate-700">Hashtag</label>
          <MultiSelect 
            v-model="filters.tag_ids" 
            :options="tags" 
            optionLabel="nama" 
            optionValue="id" 
            placeholder="Filter Hashtag" 
            :maxSelectedLabels="3" 
            class="w-full"
            filter
            @change="search"
          />
        </div>
      </div>

      <div class="flex justify-end gap-3 mt-6">
        <Button label="Reset Filter" icon="pi pi-refresh" severity="secondary" outlined @click="resetFilters" />
        <Button label="Cari" icon="pi pi-search" @click="search" :loading="loading" />
      </div>
    </div>

    <!-- Results Section -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl border border-slate-200">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
      <p class="mt-4 text-slate-500">Mencari arsip...</p>
    </div>

    <div v-else-if="archives.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl border border-slate-200 text-center px-4">
      <i class="pi pi-inbox text-5xl text-slate-300 mb-4"></i>
      <h3 class="text-lg font-bold text-slate-700">Tidak ada arsip ditemukan</h3>
      <p class="text-slate-500 max-w-sm">Coba sesuaikan filter pencarian Anda atau reset filter untuk melihat semua data.</p>
    </div>

    <div v-else>
      <!-- Desktop View (DataTable) -->
      <div class="hidden md:block">
        <DataTable :value="archives" responsiveLayout="scroll" class="p-datatable-sm" :rowClass="getRowClass">
          <Column field="name" header="Nama Dokumen">
            <template #body="{ data }">
              <div class="flex flex-col" :class="{ 'opacity-50': isMuted(data) }">
                <span class="font-bold text-slate-800">{{ data.name }}</span>
                <span class="text-xs text-slate-500">{{ data.file_number }}</span>
              </div>
            </template>
          </Column>
          <Column header="PT & Kategori">
            <template #body="{ data }">
              <div class="flex flex-col" :class="{ 'opacity-50': isMuted(data) }">
                <span class="font-bold text-slate-700">{{ data.company?.name }}</span>
                <span class="text-xs text-slate-500">{{ getCategoryPath(data) }}</span>
              </div>
            </template>
          </Column>
          <Column header="Tgl Terbit">
            <template #body="{ data }">
              <span :class="{ 'opacity-50': isMuted(data) }">{{ formatDate(data.issue_date) }}</span>
            </template>
          </Column>
          <Column header="Kadaluarsa">
            <template #body="{ data }">
              <span :class="{ 'opacity-50': isMuted(data) }">{{ data.expire_date ? formatDate(data.expire_date) : '-' }}</span>
            </template>
          </Column>
          <Column header="Privacy">
            <template #body="{ data }">
              <Tag :value="data.privacy_type?.toUpperCase()" :severity="getPrivacySeverity(data.privacy_type)" :class="{ 'opacity-50': isMuted(data) }" />
            </template>
          </Column>
          <Column header="Hashtag">
            <template #body="{ data }">
              <div class="flex flex-wrap gap-1" :class="{ 'opacity-50': isMuted(data) }">
                <Tag v-for="tag in data.tags?.slice(0, 3)" :key="tag.id" :value="tag.nama" severity="secondary" rounded />
                <Tag v-if="data.tags?.length > 3" :value="`+${data.tags.length - 3} lainnya`" severity="secondary" rounded />
              </div>
            </template>
          </Column>
          <Column header="Aksi" style="width: 150px">
            <template #body="{ data }">
              <div v-if="!hasAccess(data)" class="text-xs italic text-red-500 font-medium">
                Hubungi PIC untuk mendapatkan berkas
              </div>
              <div v-else class="flex gap-2">
                <Button icon="pi pi-eye" v-tooltip="'Detail'" severity="info" text rounded @click="viewDetail(data)" />
                <Button v-if="data.archive_type !== 'placeholder'" icon="pi pi-download" v-tooltip="'Download'" severity="success" text rounded @click="downloadArchive(data)" />
                <Button v-if="hasPhysicalLocation(data)" icon="pi pi-map-marker" v-tooltip="'Lokasi Fisik'" severity="help" text rounded @click="viewLocation(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Mobile View (Card View) -->
      <div class="md:hidden flex flex-col gap-4">
        <div 
          v-for="archive in archives" 
          :key="archive.id" 
          class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm"
          :class="{ 'bg-slate-50 border-dashed': isMuted(archive) }"
        >
          <div class="flex justify-between items-start mb-3">
            <div class="flex flex-col" :class="{ 'opacity-50': isMuted(archive) }">
              <span class="font-bold text-slate-800">{{ archive.name }}</span>
              <span class="text-xs text-slate-500">{{ archive.file_number }}</span>
            </div>
            <Tag :value="archive.privacy_type?.toUpperCase()" :severity="getPrivacySeverity(archive.privacy_type)" :class="{ 'opacity-50': isMuted(archive) }" />
          </div>

          <div class="grid grid-cols-2 gap-3 mb-4 text-sm" :class="{ 'opacity-50': isMuted(archive) }">
            <div class="flex flex-col">
              <span class="text-xs text-slate-400 uppercase font-semibold">PT</span>
              <span class="text-slate-700">{{ archive.company?.name }}</span>
            </div>
            <div class="flex flex-col">
              <span class="text-xs text-slate-400 uppercase font-semibold">Kategori</span>
              <span class="text-slate-700 truncate">{{ archive.category?.name }}</span>
            </div>
            <div class="flex flex-col">
              <span class="text-xs text-slate-400 uppercase font-semibold">Tgl Terbit</span>
              <span class="text-slate-700">{{ formatDate(archive.issue_date) }}</span>
            </div>
            <div class="flex flex-col">
              <span class="text-xs text-slate-400 uppercase font-semibold">Tipe</span>
              <span class="text-slate-700 capitalize">{{ archive.archive_type }}</span>
            </div>
          </div>

          <div class="flex flex-wrap gap-1 mb-4" :class="{ 'opacity-50': isMuted(archive) }">
            <Tag v-for="tag in archive.tags?.slice(0, 3)" :key="tag.id" :value="tag.nama" severity="secondary" rounded />
            <span v-if="archive.tags?.length > 3" class="text-xs text-slate-400 self-center">+{{ archive.tags.length - 3 }} lainnya</span>
          </div>

          <div class="border-t border-slate-100 pt-3">
            <div v-if="!hasAccess(archive)" class="text-center text-xs italic text-red-500 font-medium">
              Hubungi PIC untuk mendapatkan berkas
            </div>
            <div v-else class="flex justify-around gap-2">
              <Button label="Detail" icon="pi pi-eye" severity="info" text size="small" @click="viewDetail(archive)" />
              <Button v-if="archive.archive_type !== 'placeholder'" label="Download" icon="pi pi-download" severity="success" text size="small" @click="downloadArchive(archive)" />
              <Button v-if="hasPhysicalLocation(archive)" label="Peta" icon="pi pi-map-marker" severity="help" text size="small" @click="viewLocation(archive)" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Dialog -->
    <Dialog v-model:visible="detailDialog" modal header="Detail Arsip" :style="{ width: '50rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
      <div v-if="selectedArchive" class="flex flex-col gap-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-400 uppercase font-bold tracking-wider">Nama Dokumen</label>
            <div class="text-slate-800 font-medium">{{ selectedArchive.name }}</div>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-400 uppercase font-bold tracking-wider">No. Berkas</label>
            <div class="text-slate-800 font-medium">{{ selectedArchive.file_number }}</div>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-400 uppercase font-bold tracking-wider">PT</label>
            <div class="text-slate-800 font-medium">{{ selectedArchive.company?.name }}</div>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-400 uppercase font-bold tracking-wider">Kategori</label>
            <div class="text-slate-800 font-medium">{{ getCategoryPath(selectedArchive) }}</div>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-400 uppercase font-bold tracking-wider">Tanggal Terbit</label>
            <div class="text-slate-800 font-medium">{{ formatDate(selectedArchive.issue_date) }}</div>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-400 uppercase font-bold tracking-wider">Tanggal Kadaluarsa</label>
            <div class="text-slate-800 font-medium">{{ selectedArchive.expire_date ? formatDate(selectedArchive.expire_date) : '-' }}</div>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-400 uppercase font-bold tracking-wider">Privacy</label>
            <div><Tag :value="selectedArchive.privacy_type?.toUpperCase()" :severity="getPrivacySeverity(selectedArchive.privacy_type)" /></div>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-400 uppercase font-bold tracking-wider">Tipe Arsip</label>
            <div class="capitalize">{{ selectedArchive.archive_type }}</div>
          </div>
        </div>
        
        <div class="flex flex-col gap-1 mt-2">
          <label class="text-xs text-slate-400 uppercase font-bold tracking-wider">Keterangan</label>
          <div class="text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100 text-sm whitespace-pre-wrap">
            {{ selectedArchive.keterangan || 'Tidak ada keterangan.' }}
          </div>
        </div>

        <div class="flex flex-col gap-1 mt-2">
          <label class="text-xs text-slate-400 uppercase font-bold tracking-wider">Hashtag</label>
          <div class="flex flex-wrap gap-2">
            <Tag v-for="tag in selectedArchive.tags" :key="tag.id" :value="tag.nama" severity="secondary" rounded />
            <span v-if="selectedArchive.tags?.length === 0" class="text-slate-400 italic text-sm">Tidak ada hashtag.</span>
          </div>
        </div>

        <!-- Location Info if available -->
        <div v-if="hasPhysicalLocation(selectedArchive)" class="flex flex-col gap-1 mt-2 border-t pt-4">
          <label class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-2">Lokasi Fisik</label>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="flex flex-col">
              <span class="text-[10px] text-slate-400 uppercase">Lantai</span>
              <span class="text-sm font-medium">{{ selectedArchive.floor?.name }}</span>
            </div>
            <div class="flex flex-col">
              <span class="text-[10px] text-slate-400 uppercase">Ruangan</span>
              <span class="text-sm font-medium">{{ selectedArchive.room?.name }}</span>
            </div>
            <div class="flex flex-col">
              <span class="text-[10px] text-slate-400 uppercase">Lemari</span>
              <span class="text-sm font-medium">{{ selectedArchive.cabinet?.name }}</span>
            </div>
            <div class="flex flex-col">
              <span class="text-[10px] text-slate-400 uppercase">Slot</span>
              <span class="text-sm font-medium">Baris {{ selectedArchive.cabinet_slot?.row_position }}, Kolom {{ selectedArchive.cabinet_slot?.column_position }}</span>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Tutup" icon="pi pi-times" @click="detailDialog = false" severity="secondary" text />
        <Button v-if="selectedArchive && selectedArchive.archive_type !== 'placeholder' && hasAccess(selectedArchive)" label="Download Berkas" icon="pi pi-download" @click="downloadArchive(selectedArchive)" />
      </template>
    </Dialog>

    <!-- Location Modal (Visual) -->
    <Dialog v-model:visible="locationDialog" modal header="Visualisasi Lokasi Fisik" :style="{ width: '90vw', maxWidth: '1000px' }">
      <div v-if="selectedArchive" class="flex flex-col items-center">
        <div class="w-full bg-slate-50 rounded-xl border border-slate-200 overflow-hidden relative" style="height: 60vh;">
           <p class="absolute inset-0 flex items-center justify-center text-slate-400 italic">
             [Integrasi Konva.js Floor Plan Visualizer]
           </p>
        </div>
        <div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-lg w-full">
          <div class="flex items-center gap-2 text-blue-800 font-bold mb-1">
            <i class="pi pi-info-circle"></i>
            <span>Informasi Lokasi</span>
          </div>
          <p class="text-sm text-blue-700">
            Arsip berada di <strong>{{ selectedArchive.company?.name }}</strong>, 
            <strong>Lantai {{ selectedArchive.floor?.name }}</strong>, 
            <strong>Ruangan {{ selectedArchive.room?.name }}</strong>, 
            <strong>Lemari {{ selectedArchive.cabinet?.name }}</strong>, 
            <strong>Slot Baris {{ selectedArchive.cabinet_slot?.row_position }} Kolom {{ selectedArchive.cabinet_slot?.column_position }}</strong>.
          </p>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { fetchArchives, downloadArchive as downloadApi } from '@/api/archiveApi'
import { fetchCompanies } from '@/api/companyApi'
import { fetchCategoryTree } from '@/api/categoryApi'
import { fetchTags } from '@/api/tagApi'
import { useAuthStore } from '@/store/auth'
import ProgressSpinner from 'primevue/progressspinner'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import TreeSelect from 'primevue/treeselect'
import DatePicker from 'primevue/datepicker'
import MultiSelect from 'primevue/multiselect'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'

const authStore = useAuthStore()
const loading = ref(false)
const archives = ref([])
const companies = ref([])
const categories = ref([])
const tags = ref([])
const archiveTypes = [
  { label: 'Full (Digital & Fisik)', value: 'full' },
  { label: 'Digital Only', value: 'digital_only' },
  { label: 'Placeholder (Fisik Only)', value: 'placeholder' }
]

const filters = reactive({
  q: '',
  company_id: null,
  category_id: null,
  archive_type: null,
  date_from: null,
  date_to: null,
  tag_ids: []
})

const dateRange = ref(null)
const detailDialog = ref(false)
const locationDialog = ref(false)
const selectedArchive = ref(null)

// Initialize
onMounted(async () => {
  await Promise.all([
    loadCompanies(),
    loadTags(),
    search()
  ])
})

const loadCompanies = async () => {
  try {
    const res = await fetchCompanies()
    companies.value = res.data.data
  } catch (err) {
    console.error('Failed to load companies', err)
  }
}

const loadTags = async () => {
  try {
    const res = await fetchTags()
    tags.value = res.data.data
  } catch (err) {
    console.error('Failed to load tags', err)
  }
}

const onCompanyChange = async () => {
  filters.category_id = null
  if (filters.company_id) {
    try {
      const res = await fetchCategoryTree(filters.company_id)
      categories.value = formatTreeData(res.data.data)
    } catch (err) {
      console.error('Failed to load categories', err)
    }
  } else {
    categories.value = []
  }
  search()
}

const formatTreeData = (data, parentPath = '') => {
  if (!data) return []
  return data.map(node => {
    // The node might come from the backend formatTree method (key, label, children)
    // or from a raw model (id, name, children)
    const name = node.label || node.name
    const fullPath = parentPath ? `${parentPath} > ${name}` : name
    
    return {
      key: node.key || (node.id ? node.id.toString() : Math.random().toString()),
      label: fullPath,
      data: node.data || node,
      children: node.children ? formatTreeData(node.children, fullPath) : []
    }
  })
}

const onDateRangeChange = () => {
  if (dateRange.value && dateRange.value.length === 2) {
    if (dateRange.value[0]) filters.date_from = formatDateForApi(dateRange.value[0])
    if (dateRange.value[1]) filters.date_to = formatDateForApi(dateRange.value[1])
    search()
  } else if (!dateRange.value) {
    filters.date_from = null
    filters.date_to = null
    search()
  }
}

const formatDateForApi = (date) => {
  if (!date) return null
  const d = new Date(date)
  let month = '' + (d.getMonth() + 1)
  let day = '' + d.getDate()
  const year = d.getFullYear()

  if (month.length < 2) month = '0' + month
  if (day.length < 2) day = '0' + day

  return [year, month, day].join('-')
}

const search = async () => {
  loading.value = true
  try {
    const params = { ...filters }
    // Handle TreeSelect value which can be an object
    if (typeof params.category_id === 'object' && params.category_id !== null) {
      params.category_id = Object.keys(params.category_id)[0]
    }
    
    const res = await fetchArchives(params)
    archives.value = res.data.data
  } catch (err) {
    console.error('Search failed', err)
  } finally {
    loading.value = false
  }
}

let searchTimeout = null
const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    search()
  }, 500)
}

const resetFilters = () => {
  filters.q = ''
  filters.company_id = null
  filters.category_id = null
  filters.archive_type = null
  filters.date_from = null
  filters.date_to = null
  filters.tag_ids = []
  dateRange.value = null
  categories.value = []
  search()
}

const hasAccess = (archive) => {
  if (archive.privacy_type === 'public') return true
  
  const user = authStore.user
  if (!user) return false
  
  if (user.role === 'admin') return true
  if (archive.created_by === user.id) return true
  if (archive.pic_user_id === user.id) return true

  if (archive.privacy_type === 'department') {
    return archive.access_departments?.some(d => d.id === user.department_id)
  }

  if (archive.privacy_type === 'user') {
    return archive.access_users?.some(u => u.id === user.id)
  }

  return false
}

const isMuted = (archive) => {
  return archive.archive_type === 'placeholder' || !hasAccess(archive)
}

const getRowClass = (data) => {
  return isMuted(data) ? 'opacity-60 bg-slate-50' : ''
}

const getPrivacySeverity = (type) => {
  switch (type) {
    case 'public': return 'success'
    case 'department': return 'info'
    case 'user': return 'warn'
    case 'private': return 'danger'
    default: return 'secondary'
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

const getCategoryPath = (archive) => {
  if (!archive.category) return '-'
  return archive.category.name
}

const hasPhysicalLocation = (archive) => {
  return archive.floor_id && archive.room_id && archive.cabinet_id
}

const viewDetail = (archive) => {
  selectedArchive.value = archive
  detailDialog.value = true
}

const viewLocation = (archive) => {
  selectedArchive.value = archive
  locationDialog.value = true
}

const downloadArchive = async (archive) => {
  if (!archive.id) return
  try {
    const res = await downloadApi(archive.id)
    
    // Get filename from content-disposition header if available
    let fileName = archive.name
    const contentDisposition = res.headers['content-disposition']
    if (contentDisposition) {
      const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/)
      if (fileNameMatch && fileNameMatch[1]) {
        fileName = fileNameMatch[1]
      }
    }

    const url = window.URL.createObjectURL(res.data)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', fileName)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Download failed', err)
  }
}
</script>

<style scoped>
:deep(.p-datatable .p-datatable-tbody > tr.opacity-60) {
  background: #f8fafc;
}
</style>
