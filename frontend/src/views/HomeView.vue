<template>
  <div class="flex flex-col min-h-screen bg-slate-50/50">
    <!-- Main Content Area -->
    <main class="p-4 md:p-6 flex-1 flex flex-col gap-6 max-w-[1600px] mx-auto w-full">
      
      <!-- EXPIRY ALERT -->
      <div v-if="dummyStats.expiring > 0" class="bg-orange-50 border border-orange-200 border-l-4 border-l-orange-500 rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="bg-orange-100 p-2 rounded-full">
          <i class="pi pi-exclamation-triangle text-orange-600 text-xl"></i>
        </div>
        <div class="flex-1">
          <p class="text-sm text-orange-800">
            <span class="font-bold">{{ dummyStats.expiring }} dokumen akan kadaluarsa</span> dalam waktu dekat.
            <a href="#" class="font-bold underline ml-2 hover:text-orange-900" @click.prevent="filterByExpiring">Lihat dokumen &rarr;</a>
          </p>
        </div>
      </div>

      <!-- STATS ROW -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 cursor-pointer hover:border-blue-300 transition-colors" @click="resetFilters">
          <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
            <i class="pi pi-file text-xl"></i>
          </div>
          <div>
            <div class="text-2xl font-bold text-slate-800">{{ dummyStats.total.toLocaleString() }}</div>
            <div class="text-xs text-slate-500 font-medium uppercase tracking-wider">Total Arsip</div>
          </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 cursor-pointer hover:border-green-300 transition-colors" @click="filterByInCabinet">
          <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center text-green-600">
            <i class="pi pi-check-circle text-xl"></i>
          </div>
          <div>
            <div class="text-2xl font-bold text-slate-800">{{ dummyStats.inCabinet.toLocaleString() }}</div>
            <div class="text-xs text-slate-500 font-medium uppercase tracking-wider">Ada di Lemari</div>
          </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 cursor-pointer hover:border-red-300 transition-colors" @click="filterByBorrowed">
          <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-600">
            <i class="pi pi-external-link text-xl"></i>
          </div>
          <div>
            <div class="text-2xl font-bold text-slate-800">{{ dummyStats.borrowed.toLocaleString() }}</div>
            <div class="text-xs text-slate-500 font-medium uppercase tracking-wider">Sedang Keluar</div>
          </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 cursor-pointer hover:border-orange-300 transition-colors" @click="filterByExpiring">
          <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center text-orange-600">
            <i class="pi pi-clock text-xl"></i>
          </div>
          <div>
            <div class="text-2xl font-bold text-slate-800">{{ dummyStats.expiring.toLocaleString() }}</div>
            <div class="text-xs text-slate-500 font-medium uppercase tracking-wider">Segera Kadaluarsa</div>
          </div>
        </div>
      </div>

      <!-- SEARCH HERO (Sticky on mobile) -->
      <div class="sticky top-0 z-30 -mx-4 px-4 py-2 md:static md:mx-0 md:px-0 md:py-0 bg-slate-50/80 backdrop-blur-md md:bg-transparent">
        <div class="bg-gradient-to-br from-primary-900 via-primary-700 to-primary-500 rounded-2xl md:rounded-3xl p-4 md:p-8 relative overflow-hidden shadow-lg shadow-blue-900/10">
          <!-- Abstract Decorations -->
          <div class="absolute -top-20 -right-20 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
          <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-primary-400/10 rounded-full blur-3xl"></div>
          
          <div class="relative z-10 flex flex-col items-center text-center max-w-3xl mx-auto">
            <h1 class="hidden md:block text-2xl md:text-3xl font-extrabold text-white mb-6 leading-tight">
              Temukan Dokumen <span class="text-blue-300">dengan Cepat</span>
            </h1>
            
            <div class="w-full bg-white/10 backdrop-blur-md border border-white/20 rounded-xl md:rounded-2xl p-1 md:p-1.5 flex flex-col md:flex-row gap-2 focus-within:ring-2 focus-within:ring-blue-400/50 transition-all">
              <div class="flex-1 flex items-center px-3 md:px-4 py-1.5 md:py-2">
                <i class="pi pi-search text-white/40 mr-2 md:mr-3 text-sm md:text-base"></i>
                <input 
                  v-model="filters.q" 
                  type="text" 
                  placeholder="Cari nama, nomor, atau hashtag..." 
                  class="bg-transparent border-none outline-none text-white placeholder:text-white/40 w-full text-sm md:text-base"
                  @input="debouncedSearch"
                  @keyup.enter="search"
                />
              </div>
              <Button 
                label="Cari" 
                icon="pi pi-search" 
                class="!bg-white !text-primary-800 !border-none !rounded-lg md:!rounded-xl !px-6 md:!px-8 !font-bold hover:!bg-blue-50 transition-colors hidden md:flex"
                :loading="loading"
                @click="search"
              />
            </div>

            <div class="hidden md:flex flex-wrap justify-center gap-4 mt-6 text-white/50 text-[10px] uppercase tracking-widest">
              <div class="flex items-center gap-1.5"><i class="pi pi-building"></i> <strong>{{ companies.length }}</strong> PT</div>
              <div class="flex items-center gap-1.5"><i class="pi pi-tags"></i> <strong>{{ tags.length }}</strong> Hashtags</div>
              <div class="flex items-center gap-1.5"><i class="pi pi-history"></i> Update 5m ago</div>
            </div>
          </div>
        </div>
      </div>

      <!-- FILTER BAR (Horizontal Scroll on Mobile) -->
      <div class="bg-white border border-slate-200 rounded-2xl p-2 md:p-3 shadow-sm flex items-center gap-2 overflow-x-auto no-scrollbar">
        <div class="flex items-center gap-2 px-3 border-r border-slate-100 hidden md:flex shrink-0">
          <i class="pi pi-filter text-slate-400"></i>
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Filter</span>
        </div>
        
        <Select 
          v-model="filters.company_id" 
          :options="companies" 
          optionLabel="name" 
          optionValue="id" 
          placeholder="PT" 
          class="shrink-0 w-[120px] md:flex-1 md:min-w-[150px] p-select-sm" 
          showClear
          @change="onCompanyChange"
        />

        <TreeSelect 
          v-model="filters.category_id" 
          :options="categories" 
          placeholder="Kategori" 
          class="shrink-0 w-[140px] md:flex-1 md:min-w-[200px] p-select-sm" 
          :disabled="!filters.company_id"
          showClear
          filter
          @change="search"
        />

        <Select 
          v-model="filters.archive_type" 
          :options="archiveTypes" 
          optionLabel="label" 
          optionValue="value" 
          placeholder="Tipe" 
          class="shrink-0 w-[120px] md:flex-1 md:min-w-[150px] p-select-sm" 
          showClear
          @change="search"
        />

        <MultiSelect 
          v-model="filters.tag_ids" 
          :options="tags" 
          optionLabel="nama" 
          optionValue="id" 
          placeholder="Tag" 
          :maxSelectedLabels="1" 
          class="shrink-0 w-[100px] md:flex-1 md:min-w-[150px] p-select-sm"
          filter
          @change="search"
        />

        <div class="flex gap-1 ml-auto shrink-0 pl-2">
          <Button icon="pi pi-refresh" severity="secondary" text @click="resetFilters" size="small" />
        </div>
      </div>

      <!-- RESULTS SECTION -->
      <div class="flex-1 flex flex-col gap-4">
        <!-- Results Header -->
        <div class="flex items-center justify-between px-2">
          <div v-if="hasSearched" class="text-sm text-slate-500">
            Menampilkan <span class="font-bold text-slate-800">{{ archives.length }}</span> hasil
          </div>
          <div v-else></div>
          <div class="flex items-center gap-3">
             <!-- Placeholder for view switcher or sort if needed -->
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex-1 flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200">
          <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
          <p class="mt-4 text-slate-500 font-medium">Mencari arsip...</p>
        </div>

        <!-- Search-First Prompt -->
        <div v-else-if="!hasSearched" class="flex-1 flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200 text-center px-4">
          <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-6">
            <i class="pi pi-search text-3xl text-slate-300"></i>
          </div>
          <h3 class="text-xl font-bold text-slate-700">Mulai pencarian</h3>
          <p class="text-slate-500 max-w-sm mt-2">Ketik kata kunci, pilih kategori, atau gunakan filter untuk menemukan arsip.</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="archives.length === 0" class="flex-1 flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200 text-center px-4">
          <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-6">
            <i class="pi pi-search text-3xl text-slate-300"></i>
          </div>
          <h3 class="text-xl font-bold text-slate-700">Tidak ada arsip ditemukan</h3>
          <p class="text-slate-500 max-w-sm mt-2">Coba sesuaikan kata kunci atau filter pencarian Anda.</p>
          <Button label="Bersihkan Semua Filter" class="mt-6" text @click="resetFilters" />
        </div>

        <!-- Data Display -->
        <div v-else class="flex-1">
          <!-- Desktop View (DataTable) -->
          <div class="hidden md:block bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <DataTable :value="archives" responsiveLayout="scroll" class="p-datatable-sm" :rowClass="getRowClass">
              <Column field="name" header="NAMA DOKUMEN">
                <template #body="{ data }">
                  <div class="flex items-center gap-3" :class="{ 'opacity-50': isMuted(data) }">
                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center bg-slate-50', getFileColor(data.file_type)]">
                      <i :class="[getFileIcon(data.file_type), 'text-lg']"></i>
                    </div>
                    <div class="flex flex-col">
                      <span class="font-bold text-slate-800 leading-tight">{{ data.name }}</span>
                      <span class="text-[10px] font-mono text-slate-500 mt-1 uppercase tracking-tighter">{{ data.file_number }}</span>
                      <span class="text-[9px] text-blue-500 font-bold uppercase mt-1">PIC: {{ data.pic?.name || '-' }}</span>
                    </div>
                  </div>
                  
                  <!-- PIC Info for restricted access -->
                  <div v-if="!hasAccess(data)" class="mt-2 ml-12 p-2 bg-red-50 border border-red-100 rounded-lg max-w-xs">
                    <p class="text-[10px] text-red-600 italic">
                      <i class="pi pi-lock mr-1"></i>
                      Hubungi <span class="font-bold">{{ data.pic?.name || 'PIC' }}</span> untuk akses.
                    </p>
                  </div>
                </template>
              </Column>
              
              <Column header="PT / DIVISI">
                <template #body="{ data }">
                  <div class="flex flex-col" :class="{ 'opacity-50': isMuted(data) }">
                    <span class="font-bold text-slate-700 text-xs">{{ data.company?.name }}</span>
                    <span class="text-[10px] text-slate-400 font-medium uppercase mt-0.5">{{ getCategoryPath(data) }}</span>
                  </div>
                </template>
              </Column>

              <Column header="TGL TERBIT" style="width: 140px">
                <template #body="{ data }">
                  <span class="text-xs font-medium text-slate-600" :class="{ 'opacity-50': isMuted(data) }">{{ formatDate(data.issue_date) }}</span>
                </template>
              </Column>

              <Column header="PRIVACY" style="width: 120px">
                <template #body="{ data }">
                  <div class="flex flex-col gap-1 items-start" :class="{ 'opacity-50': isMuted(data) }">
                    <Tag :value="data.privacy_type?.toUpperCase()" :severity="getPrivacySeverity(data.privacy_type)" class="!text-[9px] !px-2" />
                    <Tag v-if="data.is_confidential" value="CONFIDENTIAL" severity="warn" class="!text-[9px] !px-2" />
                  </div>
                </template>
              </Column>

              <Column header="HASHTAG">
                <template #body="{ data }">
                  <div class="flex flex-wrap gap-1" :class="{ 'opacity-50': isMuted(data) }">
                    <Tag v-for="tag in data.tags?.slice(0, 2)" :key="tag.id" :value="'#' + tag.nama" severity="secondary" rounded class="!text-[9px] !bg-slate-100 !text-slate-600 !border-none" />
                    <span v-if="data.tags?.length > 2" class="text-[9px] text-slate-400 self-center font-bold ml-1">+{{ data.tags.length - 2 }}</span>
                  </div>
                </template>
              </Column>

              <Column header="STATUS KELUAR" style="width: 160px">
                <template #body="{ data }">
                  <div v-if="data.is_checked_out" class="flex flex-col gap-0.5">
                    <Tag value="Sedang di Luar" severity="danger" class="!text-[9px] !px-2 w-fit" />
                    <span class="text-[10px] text-slate-600 font-medium">{{ data.last_checkout?.borrower_name || '-' }}</span>
                    <span class="text-[10px] text-slate-400">Kembali: {{ formatDate(data.last_checkout?.planned_return_date) }}</span>
                  </div>
                  <Tag v-else value="Tersedia" severity="success" class="!text-[9px] !px-2" />
                </template>
              </Column>

              <Column header="AKSI" style="width: 100px" alignFrozen="right" frozen>
                <template #body="{ data }">
                  <div class="flex gap-1 justify-end">
                    <Button v-if="hasAccess(data)" icon="pi pi-eye" v-tooltip.top="'View Detail'" severity="info" text rounded @click="viewDetail(data)" />
                    <Button 
                        icon="pi pi-ellipsis-v" 
                        :severity="!hasAccess(data) ? 'danger' : 'secondary'" 
                        text 
                        rounded 
                        @click="toggleActionMenu($event, data)" 
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
              class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm"
              :class="{ 'bg-slate-50 border-dashed': isMuted(archive) }"
            >
              <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3" :class="{ 'opacity-50': isMuted(archive) }">
                  <div :class="['w-10 h-10 rounded-xl flex items-center justify-center bg-slate-50', getFileColor(archive.file_type)]">
                    <i :class="[getFileIcon(archive.file_type), 'text-lg']"></i>
                  </div>
                  <div class="flex flex-col">
                    <span class="font-bold text-slate-800 leading-tight">{{ archive.name }}</span>
                    <span class="text-[10px] font-mono text-slate-500 uppercase">{{ archive.file_number }}</span>
                    <span class="text-[9px] text-blue-500 font-bold uppercase mt-1">PIC: {{ archive.pic?.name || '-' }}</span>
                  </div>
                </div>
                <div v-if="hasAccess(archive)" class="flex flex-col gap-1 items-end">
                  <Tag :value="archive.privacy_type?.toUpperCase()" :severity="getPrivacySeverity(archive.privacy_type)" class="!text-[9px]" />
                  <Tag v-if="archive.is_confidential" value="CONFIDENTIAL" severity="warn" class="!text-[9px]" />
                </div>
                <i v-else class="pi pi-lock text-red-400"></i>
              </div>

              <div class="grid grid-cols-2 gap-y-4 gap-x-2 mb-4 text-xs" :class="{ 'opacity-50': isMuted(archive) }">
                <div class="flex flex-col">
                  <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">PT / Divisi</span>
                  <span class="text-slate-700 font-semibold">{{ archive.company?.name }}</span>
                  <span class="text-[10px] text-slate-500">{{ getCategoryPath(archive) }}</span>
                </div>
                <div class="flex flex-col">
                  <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Tgl Terbit</span>
                  <span class="text-slate-700 font-semibold">{{ formatDate(archive.issue_date) }}</span>
                </div>
              </div>

              <div v-if="!hasAccess(archive)" class="mb-4 p-3 bg-red-50 border border-red-100 rounded-xl text-center">
                <p class="text-[10px] text-red-600 italic">
                  Hubungi <span class="font-bold">{{ archive.pic?.name || 'PIC' }}</span> untuk akses.
                </p>
              </div>

              <div class="flex gap-2">
                <Button v-if="hasAccess(archive)" label="Detail" icon="pi pi-eye" severity="info" class="flex-1 !rounded-xl" size="small" @click="viewDetail(archive)" />
                <Button 
                  :label="hasAccess(archive) ? 'Opsi' : 'Minta Akses'" 
                  :icon="hasAccess(archive) ? 'pi pi-ellipsis-h' : 'pi pi-key'" 
                  :severity="hasAccess(archive) ? 'secondary' : 'danger'" 
                  outlined
                  class="flex-1 !rounded-xl"
                  size="small" 
                  @click="toggleActionMenu($event, archive)" 
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- OVERLAYS & DIALOGS -->
    <ArchiveDetailModal v-model="detailDialog" :archive="selectedArchive" :already-unlocked="selectedArchive ? unlockedArchives.has(selectedArchive.id) : false" />
    
    <ArchiveEditDialog v-if="editDialog" :visible="editDialog" :archive="selectedArchive" @update:visible="editDialog = $event" @edit-success="onEditSuccess" />
    
    <MoveLocationDialog v-model="moveLocationDialog" :archive="selectedArchive" @moved="onMoveSuccess" />
    <MoveCategoryDialog v-model="moveCategoryDialog" :archive="selectedArchive" @moved="onMoveCategorySuccess" />
    <CheckoutDialog v-model="checkoutDialog" :archive="selectedArchive" @checked-out="onCheckoutSuccess" />
    
    <ConfirmDialog />
    
    <Menu ref="actionMenu" id="overlay_menu" :model="actionMenuItems" :popup="true" />

    <Popover ref="otpPopover">
        <div v-if="selectedArchive" class="p-4 flex flex-col gap-4 min-w-[280px]">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3 mb-1">
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500">
                  <i class="pi pi-lock"></i>
                </div>
                <div class="flex flex-col">
                  <span class="text-sm font-bold text-slate-800 uppercase tracking-wider">Akses Terbatas</span>
                  <span class="text-[10px] text-slate-500 italic">PIC: {{ selectedArchive.pic?.name || 'Admin' }}</span>
                </div>
            </div>
            
            <div class="flex flex-col gap-4">
                <Button 
                    label="Request Kode OTP" 
                    icon="pi pi-send" 
                    severity="danger" 
                    size="small"
                    class="w-full !rounded-xl"
                    :loading="isRequesting[selectedArchive.id]"
                    @click="handleTableRequestOtp(selectedArchive)"
                />
                
                <div class="flex flex-col gap-2 pt-3 border-t border-slate-100">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Verifikasi OTP</label>
                    <div class="flex gap-2">
                        <InputText 
                            v-model="otpInputs[selectedArchive.id]" 
                            placeholder="Kode 6 Digit" 
                            class="flex-1 text-center font-mono !rounded-xl"
                            maxlength="6"
                        />
                        <Button 
                            icon="pi pi-check" 
                            severity="success" 
                            class="!rounded-xl"
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
import { fetchArchives, downloadArchive as downloadApi, requestOtp, verifyOtp, toggleArchiveStatus } from '@/api/archiveApi'
import { fetchCompanies } from '@/api/companyApi'
import { fetchCategoryTree } from '@/api/categoryApi'
import { fetchTags } from '@/api/tagApi'
import { fetchDashboardStats } from '@/api/dashboardApi'
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
import MoveCategoryDialog from '@/components/MoveCategoryDialog.vue'
import CheckoutDialog from '@/components/CheckoutDialog.vue'
import ConfirmDialog from 'primevue/confirmdialog'
import { checkinArchive } from '@/api/archiveCheckoutApi'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip
const authStore = useAuthStore()
const confirm = useConfirm()
const loading = ref(false)
const hasSearched = ref(false)
const archives = ref([])
const companies = ref([])
const categories = ref([])
const tags = ref([])
const archiveTypes = [
  { label: 'Digital & Fisik', value: 'full' },
  { label: 'Hanya Digital', value: 'digital_only' },
  { label: 'Hanya Fisik', value: 'placeholder' }
]

const filters = reactive({
  q: '',
  company_id: null,
  category_id: null,
  archive_type: null,
  date_from: null,
  date_to: null,
  tag_ids: [],
  filter_expiring: false,
  filter_borrowed: false,
  filter_in_cabinet: false
})

const toast = useToast()
const dateRange = ref(null)
const detailDialog = ref(false)
const editDialog = ref(false)
const moveLocationDialog = ref(false)
const moveCategoryDialog = ref(false)
const checkoutDialog = ref(false)
const actionMenu = ref(null)
const otpPopover = ref(null)
const selectedArchive = ref(null)

// OTP States
const otpInputs = ref({}) // { archiveId: '123456' }
const isRequesting = ref({}) // { archiveId: true }
const isVerifying = ref({}) // { archiveId: true }
const unlockedArchives = ref(new Set()) // Set of archive IDs

// Stats
const dummyStats = reactive({
  total: 0,
  inCabinet: 0,
  borrowed: 0,
  expiring: 0
})

const actionMenuItems = computed(() => {
    const archive = selectedArchive.value
    const user = authStore.user
    const isPicOrAdmin = archive && user && (['admin', 'root'].includes(user.role) || archive.pic_user_id == user.id) // tidak menggunakan === karena error ketika di deploy ke hosting
    const canMoveLocation = archive?.can_move_location !== false
    

    const items = [
        { 
            label: archive?.is_checked_out ? 'Tandai Sudah Kembali' : 'Keluarkan Arsip', 
            icon: archive?.is_checked_out ? 'pi pi-check-circle' : 'pi pi-external-link', 
            command: () => { 
                if (archive?.is_checked_out) {
                    confirmCheckin()
                } else {
                    checkoutDialog.value = true
                }
            } 
        }
    ]

    if (canMoveLocation) {
        items.unshift({ label: 'Pindah Lokasi Fisik', icon: 'pi pi-arrows-alt', command: () => { moveLocationDialog.value = true } })
    }

    if (isPicOrAdmin) {
        items.unshift({ label: 'Pindah Kategori', icon: 'pi pi-folder-open', command: () => { moveCategoryDialog.value = true } })
        items.unshift({ label: 'Ubah Arsip', icon: 'pi pi-pencil', command: () => { editDialog.value = true } })
        items.push({ separator: true })
        items.push({ 
            label: 'Non-aktifkan Arsip', 
            icon: 'pi pi-eye-slash', 
            command: () => { confirmToggleStatus(archive) } 
        })
    }

    return [{ label: 'Opsi Arsip', items }]
})

// Initialize
onMounted(async () => {
  await Promise.all([
    loadCompanies(),
    loadTags(),
    loadStats()
  ])
})

const loadStats = async () => {
  try {
    const res = await fetchDashboardStats()
    Object.assign(dummyStats, res.data.data)
  } catch (err) {
    console.error('Failed to load dashboard stats', err)
  }
}

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

const search = async () => {
  loading.value = true
  hasSearched.value = true
  try {
    const params = { ...filters }
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

const resetFilters = (triggerSearch = true) => {
  filters.q = ''
  filters.company_id = null
  filters.category_id = null
  filters.archive_type = null
  filters.date_from = null
  filters.date_to = null
  filters.tag_ids = []
  filters.filter_expiring = false
  filters.filter_borrowed = false
  filters.filter_in_cabinet = false
  dateRange.value = null
  categories.value = []
  if (triggerSearch) search()
}

const filterByExpiring = () => {
  resetFilters(false)
  filters.filter_expiring = true
  search()
}

const filterByBorrowed = () => {
  resetFilters(false)
  filters.filter_borrowed = true
  search()
}

const filterByInCabinet = () => {
  resetFilters(false)
  filters.filter_in_cabinet = true
  search()
}

const hasAccess = (archive) => {
  if (archive.privacy_type === 'public') return true
  
  const user = authStore.user
  if (!user) return false
  
  if (user.role === 'admin') return true
  if (Number(archive.created_by) === Number(user.id)) return true
  if (Number(archive.pic_user_id) === Number(user.id)) return true

  if (archive.privacy_type === 'department') {
    return archive.access_departments?.some(d => Number(d.id) === Number(user.department_id))
  }

  if (archive.privacy_type === 'user') {
    return archive.access_users?.some(u => Number(u.id) === Number(user.id))
  }

  if (unlockedArchives.value.has(archive.id)) return true

  return false
}

const handleTableRequestOtp = async (archive) => {
    isRequesting.value[archive.id] = true
    try {
        const res = await requestOtp(archive.id)
        toast.add({ severity: 'info', summary: 'Terkirim', detail: 'Permintaan OTP telah dikirim ke PIC.', life: 5000 })
    } catch (err) {
        toast.add({ severity: 'warn', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal mengirim OTP.', life: 6000 })
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
        const newSet = new Set(unlockedArchives.value)
        newSet.add(archive.id)
        unlockedArchives.value = newSet
        toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Akses terbuka!', life: 3000 })
        delete otpInputs.value[archive.id]
        if (otpPopover.value) otpPopover.value.hide()
        viewDetail(archive)
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'OTP tidak valid.', life: 3000 })
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

const onMoveSuccess = async () => {
  moveLocationDialog.value = false
  toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Lokasi fisik berhasil diperbarui.', life: 3000 })
  await search()
}

const onMoveCategorySuccess = async () => {
  moveCategoryDialog.value = false
  toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Kategori arsip berhasil dipindahkan.', life: 3000 })
  await search()
}

const confirmToggleStatus = (archive) => {
  confirm.require({
    message: 'Apakah Anda yakin ingin menonaktifkan arsip ini? Arsip tidak akan muncul di pencarian utama.',
    header: 'Konfirmasi Non-aktifkan',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Ya, Non-aktifkan',
    rejectLabel: 'Batal',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await toggleArchiveStatus(archive.id)
        toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Arsip berhasil dinonaktifkan.', life: 3000 })
        await search()
      } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal menonaktifkan arsip.', life: 3000 })
      }
    }
  })
}

const onCheckoutSuccess = async () => {
  checkoutDialog.value = false
  toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Arsip berhasil dikeluarkan.', life: 3000 })
  await search()
}

const confirmCheckin = () => {
  confirm.require({
    message: 'Apakah Anda yakin ingin menandai arsip ini sudah kembali?',
    header: 'Konfirmasi Pengembalian',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Ya, Tandai Kembali',
    rejectLabel: 'Batal',
    accept: () => handleCheckin()
  })
}

const handleCheckin = async () => {
  try {
    await checkinArchive(selectedArchive.value.id)
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Arsip berhasil ditandai kembali.', life: 3000 })
    await search()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal menandai pengembalian.', life: 3000 })
  }
}

const getFileIcon = (type) => {
  if (!type) return 'pi pi-file'
  const t = type.toLowerCase()
  if (t === 'pdf') return 'pi pi-file-pdf'
  if (['doc', 'docx'].includes(t)) return 'pi pi-file-word'
  if (['xls', 'xlsx', 'csv'].includes(t)) return 'pi pi-file-excel'
  if (['jpg', 'jpeg', 'png', 'gif', 'svg'].includes(t)) return 'pi pi-image'
  return 'pi pi-file'
}

const getFileColor = (type) => {
  if (!type) return 'text-slate-400'
  const t = type.toLowerCase()
  if (t === 'pdf') return 'text-red-500'
  if (['doc', 'docx'].includes(t)) return 'text-blue-500'
  if (['xls', 'xlsx', 'csv'].includes(t)) return 'text-green-500'
  if (['jpg', 'jpeg', 'png', 'gif', 'svg'].includes(t)) return 'text-orange-500'
  return 'text-slate-400'
}
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
:deep(.p-datatable .p-datatable-tbody > tr.opacity-60) {
  background: #f8fafc;
}
:deep(.p-select-sm .p-select-label) {
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
}
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: #f8fafc;
  color: #64748b;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.05em;
  padding: 0.75rem 1rem;
}
</style>
