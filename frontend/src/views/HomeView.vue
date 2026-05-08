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
              
              <!-- PIC Info for restricted access -->
              <div v-if="!hasAccess(data)" class="mt-2 p-2 bg-red-50/50 border border-red-100/50 rounded-lg max-w-sm">
                <p class="text-[10px] text-red-600 italic">
                  Hubungi <span class="font-bold">{{ data.pic?.name || 'PIC' }}</span> untuk mendapatkan berkas.
                </p>
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
          <Column header="Aksi" style="width: 120px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <template v-if="hasAccess(data)">
                  <Button icon="pi pi-eye" v-tooltip="'View Detail'" severity="info" text rounded @click="viewDetail(data)" />
                </template>
                <Button 
                    icon="pi pi-ellipsis-v" 
                    :severity="!hasAccess(data) ? 'danger' : 'secondary'" 
                    text 
                    rounded 
                    @click="toggleActionMenu($event, data)" 
                    aria-haspopup="true" 
                />
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
            <Tag v-if="hasAccess(archive)" :value="archive.privacy_type?.toUpperCase()" :severity="getPrivacySeverity(archive.privacy_type)" :class="{ 'opacity-50': isMuted(archive) }" />
            <i v-else class="pi pi-lock text-red-400"></i>
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

          <div v-if="hasAccess(archive)" class="flex flex-wrap gap-1 mb-4" :class="{ 'opacity-50': isMuted(archive) }">
            <Tag v-for="tag in archive.tags?.slice(0, 3)" :key="tag.id" :value="tag.nama" severity="secondary" rounded />
            <span v-if="archive.tags?.length > 3" class="text-xs text-slate-400 self-center">+{{ archive.tags.length - 3 }} lainnya</span>
          </div>

          <div class="border-t border-slate-100 pt-3">
            <div v-if="!hasAccess(archive)" class="mt-2 p-2 bg-red-50/50 border border-red-100/50 rounded-lg">
              <p class="text-[10px] text-red-600 italic text-center">
                Hubungi <span class="font-bold">{{ archive.pic?.name || 'PIC' }}</span> untuk mendapatkan berkas.
              </p>
            </div>
            <div class="flex justify-around gap-2 mt-3">
              <Button v-if="hasAccess(archive)" label="View" icon="pi pi-eye" severity="info" text size="small" @click="viewDetail(archive)" />
              <Button 
                :label="hasAccess(archive) ? 'Options' : 'Request Akses'" 
                :icon="hasAccess(archive) ? 'pi pi-ellipsis-h' : 'pi pi-lock'" 
                :severity="hasAccess(archive) ? 'secondary' : 'danger'" 
                text 
                size="small" 
                @click="toggleActionMenu($event, archive)" 
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Enhanced Detail Modal -->
    <ArchiveDetailModal v-model="detailDialog" :archive="selectedArchive" :already-unlocked="selectedArchive ? unlockedArchives.has(selectedArchive.id) : false" />

    <!-- Edit Archive Dialog -->
    <ArchiveEditDialog
      v-if="editDialog"
      :visible="editDialog"
      :archive="selectedArchive"
      @update:visible="editDialog = $event"
      @edit-success="onEditSuccess"
    />

    <!-- Move Location Dialog -->
    <MoveLocationDialog 
      v-model="moveLocationDialog" 
      :archive="selectedArchive" 
      @moved="onMoveSuccess" 
    />

    <!-- Action Menu Overlay -->
    <Menu ref="actionMenu" id="overlay_menu" :model="actionMenuItems" :popup="true" />

    <!-- OTP Popover for Restricted Access -->
    <Popover ref="otpPopover">
        <div v-if="selectedArchive" class="p-3 flex flex-col gap-3 min-w-[250px]">
            <div class="flex flex-col gap-1 border-b border-slate-100 pb-2 mb-1">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Akses Terbatas</span>
                <span class="text-[10px] text-slate-500 italic">Hubungi {{ selectedArchive.pic?.name || 'PIC' }}</span>
            </div>
            
            <div class="flex flex-col gap-3">
                <Button 
                    label="Request OTP" 
                    icon="pi pi-send" 
                    severity="danger" 
                    outlined 
                    size="small"
                    class="w-full text-xs"
                    :loading="isRequesting[selectedArchive.id]"
                    @click="handleTableRequestOtp(selectedArchive)"
                />
                
                <div class="flex flex-col gap-1.5 mt-1 pt-3 border-t border-slate-100">
                    <label class="text-[10px] font-semibold text-slate-400 uppercase">Input Kode OTP</label>
                    <div class="flex gap-1">
                        <InputText 
                            v-model="otpInputs[selectedArchive.id]" 
                            placeholder="Kode 6 Digit" 
                            class="flex-1 p-inputtext-sm text-center"
                        />
                        <Button 
                            icon="pi pi-check" 
                            severity="success" 
                            size="small"
                            :loading="isVerifying[selectedArchive.id]"
                            :disabled="!otpInputs[selectedArchive.id]"
                            @click="handleTableVerifyOtp(selectedArchive)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </Popover>

    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue'
import { fetchArchives, downloadArchive as downloadApi, requestOtp, verifyOtp } from '@/api/archiveApi'
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
import Menu from 'primevue/menu'
import Popover from 'primevue/popover'
import Toast from 'primevue/toast'
import ArchiveDetailModal from '@/components/ArchiveDetailModal.vue'
import ArchiveEditDialog from '@/components/ArchiveEditDialog.vue'
import MoveLocationDialog from '@/components/MoveLocationDialog.vue'
import { useToast } from 'primevue/usetoast'

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

const toast = useToast()
const dateRange = ref(null)
const detailDialog = ref(false)
const editDialog = ref(false)
const moveLocationDialog = ref(false)
const actionMenu = ref(null)
const otpPopover = ref(null)
const selectedArchive = ref(null)

// OTP States
const otpInputs = ref({}) // { archiveId: '123456' }
const isRequesting = ref({}) // { archiveId: true }
const isVerifying = ref({}) // { archiveId: true }
const unlockedArchives = ref(new Set()) // Set of archive IDs

const actionMenuItems = computed(() => {
    const archive = selectedArchive.value
    const user = authStore.user
    const isPicOrAdmin = archive && user && (user.role === 'admin' || archive.pic_user_id === user.id)

    const items = [
        { label: 'Pindah Lokasi File Fisik', icon: 'pi pi-arrows-alt', command: () => { moveLocationDialog.value = true } },
        { label: 'Ubah Status Keluar/Kembali', icon: 'pi pi-sync', command: () => { toast.add({ severity: 'info', summary: 'Info', detail: 'Fitur status segera hadir.' }) } }
    ]

    if (isPicOrAdmin) {
        items.unshift({ label: 'Ubah Archive', icon: 'pi pi-pencil', command: () => { editDialog.value = true } })
    }

    return [{ label: 'Opsi Arsip', items }]
})

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

  // Check if manually unlocked via OTP
  if (unlockedArchives.value.has(archive.id)) return true

  return false
}

const handleTableRequestOtp = async (archive) => {
    isRequesting.value[archive.id] = true
    try {
        const res = await requestOtp(archive.id)
        toast.add({ severity: 'info', summary: 'Permintaan Terkirim', detail: res.data?.message || 'Permintaan OTP telah dikirim ke PIC.', life: 5000 })
    } catch (err) {
        const msg = err.response?.data?.message || 'Gagal mengirim permintaan OTP.'
        toast.add({ severity: 'warn', summary: 'Gagal', detail: msg, life: 6000 })
    } finally {
        isRequesting.value[archive.id] = false
    }
}

const handleTableVerifyOtp = async (archive) => {
    const otp = otpInputs.value[archive.id]
    if (!otp || String(otp).length < 6) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Masukkan 6 digit kode OTP.', life: 3000 })
        return
    }

    isVerifying.value[archive.id] = true
    try {
        await verifyOtp(archive.id, otp)
        
        // Force reactivity by re-assigning the Set
        const newSet = new Set(unlockedArchives.value)
        newSet.add(archive.id)
        unlockedArchives.value = newSet

        toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Akses terbuka! Anda sekarang dapat melihat detail berkas.', life: 3000 })
        
        // Clear input
        delete otpInputs.value[archive.id]

        // Close popover
        if (otpPopover.value) {
            otpPopover.value.hide()
        }

        // Automatically open the detail modal
        viewDetail(archive)
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Kode OTP tidak valid.', life: 3000 })
    } finally {
        isVerifying.value[archive.id] = false
    }
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

const onEditSuccess = async () => {
  editDialog.value = false
  toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Arsip berhasil diperbarui.', life: 3000 })
  await search()
}

const toggleActionMenu = (event, archive) => {
  selectedArchive.value = archive
  
  if (hasAccess(archive)) {
    actionMenu.value.toggle(event)
  } else {
    otpPopover.value.toggle(event)
  }
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

const onMoveSuccess = async () => {
  moveLocationDialog.value = false
  toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Lokasi fisik arsip berhasil diperbarui.', life: 3000 })
  await search()
}
</script>

<style scoped>
:deep(.p-datatable .p-datatable-tbody > tr.opacity-60) {
  background: #f8fafc;
}
</style>
