<template>
    <div class="file-explorer grid grid-cols-12 gap-4 h-full">
        <!-- Sidebar Panel: Category Tree -->
        <div class="col-span-12 md:col-span-4 lg:col-span-5">
            <div class="card p-4 flex flex-col h-full bg-white shadow-sm border border-slate-200 rounded-lg">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800">File Explorer</h2>
                </div>

                <!-- Dropdown Pilih Perusahaan -->
                <div class="mb-3">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Perusahaan
                        (PT)</label>
                    <Select v-model="selectedCompanyId" :options="companies" optionLabel="name" optionValue="id"
                        placeholder="— Pilih PT —" class="w-full" :loading="isLoadingCompanies"
                        @change="onCompanyChange" />
                </div>
                <!-- Category Tree -->
                <div class="flex-1 overflow-auto min-h-[300px]">
                    <div v-if="!selectedCompanyId" class="text-center py-8 text-slate-400">
                        <i class="pi pi-building text-3xl mb-2 block opacity-30"></i>
                        <p class="text-sm">Pilih PT terlebih dahulu</p>
                    </div>
                    <div v-else-if="categoryStore.isLoading" class="text-center py-8 text-slate-400">
                        <i class="pi pi-spin pi-spinner text-2xl"></i>
                    </div>
                    <div v-else-if="!categoryStore.categoryTree.length" class="text-center py-8 text-slate-400">
                        <i class="pi pi-folder text-3xl mb-2 block opacity-30"></i>
                        <p class="text-sm">Belum ada kategori</p>
                    </div>
                    <Tree v-else v-model:selectionKeys="selectedKey" v-model:expandedKeys="expandedKeys"
                        :value="categoryStore.categoryTree" selectionMode="single" :filter="true" filterMode="lenient"
                        class="w-full border-none p-0 overflow-x-auto custom-compact-tree" @node-select="onNodeSelect"
                        @node-unselect="onNodeUnselect" @filter="onTreeFilter">
                        <template #default="slotProps">
                            <div class="flex items-center gap-2 text-sm whitespace-nowrap">
                                <span>{{ slotProps.node.label }}</span>
                            </div>
                        </template>
                    </Tree>
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 flex flex-col gap-2 border-t pt-4">
                    <div class="grid grid-cols-2 gap-2">
                        <Button label="Tambah Kategori" icon="pi pi-plus"
                            class="p-button-outlined p-button-sm w-full" severity="secondary"
                            :disabled="!categoryStore.selectedCategory || !selectedCompanyId"
                            @click="openAddSubcategory" />
                        <Button label="Tambah PT" icon="pi pi-plus-circle"
                            class="p-button-outlined p-button-sm w-full" severity="info"
                            :disabled="!selectedCompanyId" @click="openAddRoot" />
                    </div>
                    <Button label="Upload Arsip" icon="pi pi-upload" class="p-button-primary w-full mt-1 hidden md:flex"
                        :disabled="!categoryStore.selectedCategory" @click="showUploadDialog = true" />
                </div>
            </div>
        </div>

        <!-- Main Content Panel -->
        <div class="col-span-12 md:col-span-8 lg:col-span-7">
            <div class="card p-4 h-full bg-white shadow-sm border border-slate-200 rounded-lg flex flex-col">
                <div v-if="categoryStore.selectedCategory" class="w-full h-full flex flex-col">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 border-b pb-4 gap-4">
                        <!-- Title Section -->
                        <div class="flex items-center gap-3">
                            <!-- Back Button (Mobile Only) -->
                            <Button v-if="categoryStore.selectedCategory" icon="pi pi-chevron-left" text rounded
                                severity="secondary" class="md:hidden !w-10 !h-10 bg-slate-50" @click="goBack"
                                v-tooltip.bottom="'Kembali'" />
                            <div class="flex flex-col">
                                <p class="text-[10px] md:text-xs text-slate-400 uppercase font-bold tracking-wider">
                                    {{ selectedCompanyName }}
                                </p>
                                <h3 class="text-lg md:text-xl font-bold text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-folder text-yellow-500 hidden md:block"></i>
                                    {{ categoryStore.selectedCategory.label }}
                                </h3>
                            </div>
                        </div>

                        <!-- Actions (Desktop Only) -->
                        <div class="hidden md:flex items-center gap-2">
                            <Button label="Edit Kategori" icon="pi pi-pencil" size="small" severity="secondary"
                                @click="openEditCategory" />
                            <Button label="Upload Arsip" icon="pi pi-upload" size="small"
                                @click="showUploadDialog = true" />
                        </div>
                    </div>
                    <div class="flex-1 overflow-auto">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-semibold text-slate-700">Sub-kategori</h4>
                                </div>

                                <div v-if="subcategories.length" class="flex flex-wrap gap-2">
                                    <button v-for="child in subcategories" :key="child.key" type="button"
                                        class="px-3 py-2 rounded border border-slate-200 bg-white hover:bg-slate-50 text-sm text-slate-700 flex items-center gap-2"
                                        @click="selectCategoryNode(child)">
                                        <i class="pi pi-folder text-yellow-500"></i>
                                        <span class="truncate max-w-[240px]">{{ child.label }}</span>
                                    </button>
                                </div>
                                <div v-else
                                    class="text-sm text-slate-400 bg-slate-50 border border-slate-200 rounded p-3">
                                    Tidak ada sub-kategori.
                                </div>
                            </div>

                            <div class="col-span-12">
                                <div class="flex items-center justify-between mb-2 mt-2">
                                    <h4 class="text-sm font-semibold text-slate-700">Arsip</h4>
                                    <Button label="Refresh" icon="pi pi-refresh" size="small" severity="secondary"
                                        :loading="isLoadingArchives" @click="loadArchives" />
                                </div>

                                <div v-if="isLoadingArchives" class="text-center py-8 text-slate-400">
                                    <i class="pi pi-spin pi-spinner text-2xl"></i>
                                </div>
                                <div v-else-if="!archives.length"
                                    class="text-center py-10 text-slate-400 bg-slate-50 rounded border border-slate-200">
                                    <i class="pi pi-folder-open text-4xl mb-3 block opacity-20"></i>
                                    <p class="text-sm">Belum ada arsip di kategori ini.</p>
                                </div>
                                <div v-else class="overflow-auto md:rounded md:border md:border-slate-200">
                                    <table class="w-full text-sm hidden md:table">
                                        <thead class="bg-slate-50 text-slate-600">
                                            <tr>
                                                <th class="text-left p-3 font-semibold">Nama</th>
                                                <th class="text-left p-3 font-semibold">Keterangan</th>
                                                <th class="text-left p-3 font-semibold">No. File</th>
                                                <th class="text-left p-3 font-semibold">Tgl Terbit</th>
                                                <th class="text-left p-3 font-semibold">Tipe</th>
                                                <th class="text-left p-3 font-semibold">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="a in archives" :key="a.id"
                                                class="border-t border-slate-200 hover:bg-slate-50">
                                                <td class="p-3 text-slate-800">
                                                    <div class="flex items-center gap-2">
                                                        <i
                                                            :class="[getFileIcon(a.file_type), getFileColor(a.file_type), 'text-lg']"></i>
                                                        <div class="flex flex-col">
                                                            <div class="font-medium">{{ a.name }}</div>
                                                            <div v-if="a.file_type"
                                                                class="text-[9px] font-bold text-slate-400 uppercase tracking-tight">
                                                                .{{ a.file_type }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-3 text-slate-500 italic max-w-[200px] truncate">
                                                    {{ a.keterangan || '—' }}
                                                </td>
                                                <td class="p-3 text-slate-600">{{ a.file_number || '—' }}</td>
                                                <td class="p-3 text-slate-600">{{ formatDateDisplay(a.issue_date) }}
                                                </td>
                                                <td class="p-3 text-slate-600">
                                                    <Tag :value="getArchiveTypeLabel(a.archive_type)"
                                                        :severity="getArchiveTypeSeverity(a.archive_type)" />
                                                </td>
                                                <td class="p-3">
                                                    <Button icon="pi pi-ellipsis-v" severity="secondary" text rounded
                                                        @click="toggleActionMenu($event, a)" />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <!-- Mobile Grid View -->
                                    <div class="grid grid-cols-1 gap-3 md:hidden pb-4">
                                        <div v-for="a in archives" :key="a.id" class="border border-slate-200 rounded-xl p-3 bg-white shadow-sm flex flex-col gap-3">
                                            <div class="flex justify-between items-start gap-2">
                                                <div class="flex items-center gap-3 overflow-hidden">
                                                    <div class="w-10 h-10 rounded bg-slate-50 flex items-center justify-center shrink-0 border border-slate-100">
                                                        <i :class="[getFileIcon(a.file_type), getFileColor(a.file_type), 'text-xl']"></i>
                                                    </div>
                                                    <div class="flex flex-col overflow-hidden">
                                                        <div class="font-medium text-slate-800 truncate text-sm" :title="a.name">{{ a.name }}</div>
                                                        <div v-if="a.file_type" class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">
                                                            .{{ a.file_type }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <Button icon="pi pi-ellipsis-v" severity="secondary" text rounded class="!w-8 !h-8 shrink-0"
                                                    @click="toggleActionMenu($event, a)" />
                                            </div>
                                            
                                            <div v-if="a.keterangan" class="text-xs text-slate-500 italic line-clamp-2 bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                {{ a.keterangan }}
                                            </div>
                                            
                                            <div class="flex flex-wrap gap-y-3 justify-between items-center pt-2 border-t border-slate-100">
                                                <div class="flex flex-col w-[45%]">
                                                    <span class="text-[10px] text-slate-400 uppercase font-semibold">No. File</span>
                                                    <span class="text-xs text-slate-700 font-medium truncate">{{ a.file_number || '—' }}</span>
                                                </div>
                                                <div class="flex flex-col w-[45%]">
                                                    <span class="text-[10px] text-slate-400 uppercase font-semibold">Tgl Terbit</span>
                                                    <span class="text-xs text-slate-700 font-medium">{{ formatDateDisplay(a.issue_date) }}</span>
                                                </div>
                                                <div class="w-full mt-1">
                                                    <Tag :value="getArchiveTypeLabel(a.archive_type)"
                                                        :severity="getArchiveTypeSeverity(a.archive_type)" class="!text-[10px]" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="flex-1 flex items-center justify-center text-slate-400">
                    <div class="text-center">
                        <i class="pi pi-folder text-6xl mb-4 block opacity-20"></i>
                        <p class="text-lg">Pilih kategori untuk melihat konten</p>
                        <p class="text-sm mt-1" v-if="!selectedCompanyId">Mulai dengan memilih perusahaan di panel kiri.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog: Tambah Kategori -->
        <Dialog v-model:visible="showAddCategoryDialog" header="Tambah Kategori" :modal="true" class="w-full max-w-sm">
            <div class="flex flex-col gap-4 mt-2">
                <div class="flex flex-col gap-1">
                    <label for="cat-name" class="text-sm font-medium text-slate-700">Nama Kategori</label>
                    <InputText id="cat-name" v-model="newCategoryName" autofocus @keyup.enter="saveCategory" />
                </div>
                <div v-if="categoryStore.selectedCategory && !isRoot"
                    class="p-3 bg-slate-50 rounded text-sm text-slate-600">
                    Sub-kategori dari: <strong>{{ categoryStore.selectedCategory.label }}</strong>
                </div>
                <div v-else class="p-3 bg-slate-50 rounded text-sm text-slate-600">
                    Kategori root di bawah <strong>{{ selectedCompanyName }}</strong>
                </div>
            </div>
            <template #footer>
                <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="showAddCategoryDialog = false" />
                <Button label="Simpan" icon="pi pi-check" :loading="isSaving" @click="saveCategory" />
            </template>
        </Dialog>

        <Dialog v-model:visible="showEditCategoryDialog" header="Edit Kategori" :modal="true" class="w-full max-w-sm">
            <div class="flex flex-col gap-4 mt-2">
                <div class="flex flex-col gap-1">
                    <label for="edit-cat-name" class="text-sm font-medium text-slate-700">Nama Kategori</label>
                    <InputText id="edit-cat-name" v-model="editCategoryName" autofocus
                        @keyup.enter="saveEditCategory" />
                </div>
            </div>
            <template #footer>
                <Button label="Batal" icon="pi pi-times" class="p-button-text"
                    @click="showEditCategoryDialog = false" />
                <Button label="Simpan" icon="pi pi-check" :loading="isUpdating" @click="saveEditCategory" />
            </template>
        </Dialog>

        <!-- Archive Upload Dialog -->
        <ArchiveUploadDialog v-model:visible="showUploadDialog" :preselectedCategory="categoryStore.selectedCategory"
            :companyId="selectedCompanyId" @upload-success="onUploadSuccess" />

        <ArchiveEditDialog v-model:visible="showEditArchiveDialog" :archive="selectedArchive"
            @edit-success="onEditSuccess" />

        <!-- Mobile Sticky Bottom Bar -->
        <div v-if="categoryStore.selectedCategory"
            class="md:hidden fixed bottom-16 left-0 right-0 p-4 bg-white/80 backdrop-blur-md border-t border-slate-200 flex gap-2 z-40 shadow-[0_-4px_12px_rgba(0,0,0,0.05)]">
            <Button label="Edit Kategori" icon="pi pi-pencil" severity="secondary" outlined
                class="flex-1 !rounded-xl !text-xs !py-3" @click="openEditCategory" />
            <Button label="Upload Arsip" icon="pi pi-upload" class="flex-1 !rounded-xl !text-xs !py-3"
                @click="showUploadDialog = true" />
        </div>
        <!-- Dialogs from Action Menu -->
        <Menu ref="actionMenu" id="overlay_menu" :model="actionMenuItems" :popup="true" />
        <ArchiveDetailModal v-model="detailDialog" :archive="selectedArchive" />
        <MoveLocationDialog v-model="moveLocationDialog" :archive="selectedArchive" @moved="onMoveSuccess" />
        <MoveCategoryDialog v-model="moveCategoryDialog" :archive="selectedArchive" @moved="onMoveCategorySuccess" />
        <CheckoutDialog v-model="checkoutDialog" :archive="selectedArchive" @checked-out="onCheckoutSuccess" />
        <ConfirmDialog />

        <Toast />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCategoryStore } from '@/store/category'
import { createCategory, updateCategory } from '@/api/categoryApi'
import { fetchCompanies } from '@/api/companyApi'
import { fetchArchives, toggleArchiveStatus } from '@/api/archiveApi'
import { useAuthStore } from '@/store/auth'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Tree from 'primevue/tree'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import Tag from 'primevue/tag'
import Menu from 'primevue/menu'
import ConfirmDialog from 'primevue/confirmdialog'
import Tooltip from 'primevue/tooltip'
import ArchiveUploadDialog from '@/components/ArchiveUploadDialog.vue'
import ArchiveEditDialog from '@/components/ArchiveEditDialog.vue'
import ArchiveDetailModal from '@/components/ArchiveDetailModal.vue'
import MoveLocationDialog from '@/components/MoveLocationDialog.vue'
import MoveCategoryDialog from '@/components/MoveCategoryDialog.vue'
import CheckoutDialog from '@/components/CheckoutDialog.vue'
import { checkinArchive } from '@/api/archiveCheckoutApi'

const vTooltip = Tooltip

const categoryStore = useCategoryStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

// State
const companies = ref([])
const isLoadingCompanies = ref(false)
const selectedCompanyId = ref(categoryStore.selectedCompanyId)
const selectedKey = ref(null)
const expandedKeys = ref({})
const showAddCategoryDialog = ref(false)
const showEditCategoryDialog = ref(false)
const showUploadDialog = ref(false)
const showEditArchiveDialog = ref(false)
const selectedArchive = ref(null)
const detailDialog = ref(false)
const moveLocationDialog = ref(false)
const moveCategoryDialog = ref(false)
const checkoutDialog = ref(false)
const actionMenu = ref(null)
const newCategoryName = ref('')
const editCategoryName = ref('')
const isSaving = ref(false)
const isUpdating = ref(false)
const isRoot = ref(false)
const archives = ref([])
const isLoadingArchives = ref(false)

// Computed
const currentUserId = computed(() => authStore.user?.id)
const selectedCompanyName = computed(() => {
    const found = companies.value.find(c => c.id === selectedCompanyId.value)
    return found?.name || '—'
})

const subcategories = computed(() => {
    return categoryStore.selectedCategory?.children || []
})

// Lifecycle
onMounted(async () => {
    const loadTasks = [loadCompanies()]

    // Auto-load tree if company was previously selected
    if (selectedCompanyId.value) {
        loadTasks.push(categoryStore.loadCategoryTree(selectedCompanyId.value))
    }

    await Promise.all(loadTasks)
})

const loadCompanies = async () => {
    isLoadingCompanies.value = true
    try {
        const res = await fetchCompanies()
        if (res.data.success) {
            companies.value = res.data.data
        }
    } catch (e) {
        console.error('Failed to load companies', e)
    } finally {
        isLoadingCompanies.value = false
    }
}

// Event Handlers
const onCompanyChange = async () => {
    selectedKey.value = null
    await categoryStore.setSelectedCompany(selectedCompanyId.value)
    archives.value = []
}

const onNodeSelect = (node) => {
    categoryStore.setSelectedCategory(node)
    loadArchives()
}

const onNodeUnselect = () => {
    categoryStore.setSelectedCategory(null)
    archives.value = []
}

// Expand ancestors of every node whose label matches the search term, so results inside sub-categories are visible
const collectMatchAncestors = (nodes, term, acc) => {
    if (!Array.isArray(nodes)) return false
    let anyMatch = false
    for (const node of nodes) {
        const label = String(node.label || '').toLowerCase()
        const childMatch = collectMatchAncestors(node.children, term, acc)
        if (label.includes(term) || childMatch) {
            anyMatch = true
            if (childMatch) acc[node.key] = true
        }
    }
    return anyMatch
}

const onTreeFilter = ({ value }) => {
    const term = (value || '').trim().toLowerCase()
    if (!term) {
        expandedKeys.value = {}
        return
    }
    const acc = {}
    collectMatchAncestors(categoryStore.categoryTree, term, acc)
    expandedKeys.value = acc
}

const selectCategoryNode = (node) => {
    categoryStore.setSelectedCategory(node)
    selectedKey.value = { [String(node.key)]: true }
    loadArchives()
}

const findParentNode = (nodes, targetKey, parent = null) => {
    if (!Array.isArray(nodes)) return null
    for (const node of nodes) {
        if (String(node.key) === String(targetKey)) {
            return parent
        }
        if (node.children) {
            const found = findParentNode(node.children, targetKey, node)
            if (found) return found
        }
    }
    return null
}

const goBack = () => {
    const current = categoryStore.selectedCategory
    if (!current) return

    const parent = findParentNode(categoryStore.categoryTree, current.key)
    if (parent) {
        selectCategoryNode(parent)
    } else {
        // If no parent, it's a root category, so we unselect to go back to "Choose Company" state or root list
        onNodeUnselect()
        selectedKey.value = null
    }
}

const loadArchives = async () => {
    if (!categoryStore.selectedCategory?.data?.id) return
    if (!selectedCompanyId.value) return

    isLoadingArchives.value = true
    try {
        const res = await fetchArchives({
            company_id: selectedCompanyId.value,
            category_id: categoryStore.selectedCategory.data.id
        })
        if (res.data.success) {
            archives.value = res.data.data || []
        }
    } catch (e) {
        console.error('Failed to load archives', e)
        archives.value = []
    } finally {
        isLoadingArchives.value = false
    }
}

const formatDateDisplay = (dateStr) => {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    if (Number.isNaN(d.getTime())) return String(dateStr)
    return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: '2-digit' })
}

const getArchiveTypeLabel = (type) => {
    const labels = {
        full: 'Digital + Fisik',
        physical_only: 'Hanya Fisik',
        digital_only: 'Hanya Digital',
        placeholder: 'Placeholder'
    }
    return labels[type] || type
}

const getArchiveTypeSeverity = (type) => {
    const severities = {
        full: 'success',
        physical_only: 'info',
        digital_only: 'warn',
        placeholder: 'secondary'
    }
    return severities[type] || null
}

const openAddSubcategory = () => {
    isRoot.value = false
    newCategoryName.value = ''
    showAddCategoryDialog.value = true
}

const openAddRoot = () => {
    isRoot.value = true
    newCategoryName.value = ''
    showAddCategoryDialog.value = true
}

const saveCategory = async () => {
    if (!newCategoryName.value.trim()) return

    isSaving.value = true
    try {
        const payload = {
            name: newCategoryName.value.trim(),
            company_id: selectedCompanyId.value,
            parent_id: isRoot.value ? null : (categoryStore.selectedCategory?.data?.id ?? null)
        }

        const response = await createCategory(payload)
        if (response.data.success) {
            toast.add({ severity: 'success', summary: 'Sukses', detail: 'Kategori berhasil ditambahkan', life: 3000 })
            await categoryStore.loadCategoryTree(selectedCompanyId.value)
            showAddCategoryDialog.value = false
            newCategoryName.value = ''
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Gagal menyimpan kategori', life: 3000 })
    } finally {
        isSaving.value = false
    }
}

const findNodeByKey = (nodes, key) => {
    if (!Array.isArray(nodes)) return null
    for (const node of nodes) {
        if (String(node.key) === String(key)) return node
        const found = findNodeByKey(node.children, key)
        if (found) return found
    }
    return null
}

const openEditCategory = () => {
    if (!categoryStore.selectedCategory) return
    editCategoryName.value = categoryStore.selectedCategory?.data?.name || categoryStore.selectedCategory?.label || ''
    showEditCategoryDialog.value = true
}

const saveEditCategory = async () => {
    if (!categoryStore.selectedCategory?.data?.id) return
    if (!editCategoryName.value.trim()) return

    isUpdating.value = true
    try {
        const categoryId = categoryStore.selectedCategory.data.id
        const response = await updateCategory(categoryId, { name: editCategoryName.value.trim() })
        if (response.data.success) {
            toast.add({ severity: 'success', summary: 'Sukses', detail: 'Kategori berhasil diperbarui', life: 3000 })
            await categoryStore.loadCategoryTree(selectedCompanyId.value)
            selectedKey.value = { [String(categoryId)]: true }
            const updatedNode = findNodeByKey(categoryStore.categoryTree, categoryId)
            if (updatedNode) {
                categoryStore.setSelectedCategory(updatedNode)
            }
            showEditCategoryDialog.value = false
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Gagal memperbarui kategori', life: 3000 })
    } finally {
        isUpdating.value = false
    }
}

const onUploadSuccess = () => {
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Arsip berhasil diupload', life: 3000 })
    showUploadDialog.value = false
    loadArchives()
}

const openEditArchive = (archive) => {
    selectedArchive.value = archive
    showEditArchiveDialog.value = true
}

const onEditSuccess = () => {
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Arsip berhasil diperbarui', life: 3000 })
    showEditArchiveDialog.value = false
    loadArchives()
}

const actionMenuItems = computed(() => {
    const archive = selectedArchive.value
    const user = authStore.user
    const isPicOrAdmin = archive && user && (['admin', 'root'].includes(user.role) || archive.pic_user_id == user.id)
    const canMoveLocation = archive?.can_move_location !== false

    const items = [
        { 
            label: 'Lihat Detail', 
            icon: 'pi pi-eye', 
            command: () => { detailDialog.value = true } 
        },
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

    if (canMoveLocation && isPicOrAdmin) {
        items.splice(1, 0, { label: 'Pindah Lokasi Fisik', icon: 'pi pi-arrows-alt', command: () => { moveLocationDialog.value = true } })
    }

    if (isPicOrAdmin) {
        items.splice(1, 0, { label: 'Ubah Arsip', icon: 'pi pi-pencil', command: () => { 
            showEditArchiveDialog.value = true 
        } })
        items.splice(1, 0, { label: 'Pindah Kategori', icon: 'pi pi-folder-open', command: () => { moveCategoryDialog.value = true } })
        
        items.push({ separator: true })
        items.push({ 
            label: 'Non-aktifkan Arsip', 
            icon: 'pi pi-eye-slash', 
            command: () => { confirmToggleStatus(archive) } 
        })
    }

    return [{ label: 'Opsi Arsip', items }]
})

const toggleActionMenu = (event, archive) => {
    selectedArchive.value = archive
    actionMenu.value.toggle(event)
}

const onMoveSuccess = () => {
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Lokasi arsip berhasil dipindahkan', life: 3000 })
    moveLocationDialog.value = false
    loadArchives()
}

const onMoveCategorySuccess = () => {
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Kategori arsip berhasil dipindahkan', life: 3000 })
    moveCategoryDialog.value = false
    loadArchives()
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
                loadArchives()
            } catch (err) {
                toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal menonaktifkan arsip.', life: 3000 })
            }
        }
    })
}

const onCheckoutSuccess = () => {
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Status checkout berhasil diperbarui', life: 3000 })
    checkoutDialog.value = false
    loadArchives()
}

const confirmCheckin = () => {
    confirm.require({
        message: 'Apakah Anda yakin arsip ini sudah kembali ke lokasi fisik?',
        header: 'Konfirmasi Pengembalian',
        icon: 'pi pi-exclamation-triangle',
        accept: async () => {
            try {
                const res = await checkinArchive(selectedArchive.value.id)
                if (res.data.success) {
                    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Arsip berhasil dikembalikan', life: 3000 })
                    loadArchives()
                }
            } catch (err) {
                toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal mengembalikan arsip', life: 3000 })
            }
        }
    })
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
.file-explorer {
    min-height: calc(100vh - 120px);
}

/* Custom styling for compact Tree */
:deep(.custom-compact-tree) {
    padding: 0 !important;
}

:deep(.custom-compact-tree .p-tree-container) {
    padding: 0 !important;
    margin: 0 !important;
}

/* PrimeVue 3/4 class names compatibility */
:deep(.custom-compact-tree .p-treenode) {
    padding: 0.125rem 0 !important;
}

:deep(.custom-compact-tree .p-tree-node) {
    padding: 0.125rem 0 !important;
}

:deep(.custom-compact-tree .p-treenode-content) {
    padding: 0.25rem 0.5rem !important;
    border-radius: 0.25rem;
}

:deep(.custom-compact-tree .p-tree-node-content) {
    padding: 0.25rem 0.5rem !important;
    border-radius: 0.25rem;
}

:deep(.custom-compact-tree .p-tree-toggler) {
    width: 1.5rem !important;
    height: 1.5rem !important;
}
</style>
