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
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Perusahaan (PT)</label>
                    <Select
                        v-model="selectedCompanyId"
                        :options="companies"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="— Pilih PT —"
                        class="w-full"
                        :loading="isLoadingCompanies"
                        @change="onCompanyChange"
                    />
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
                    <Tree
                        v-else
                        v-model:selectionKeys="selectedKey"
                        :value="categoryStore.categoryTree"
                        selectionMode="single"
                        :filter="true"
                        :filterValue="filterText"
                        filterMode="lenient"
                        class="w-full border-none p-0 overflow-x-auto custom-compact-tree"
                        @node-select="onNodeSelect"
                        @node-unselect="onNodeUnselect"
                    >
                        <template #default="slotProps">
                            <div class="flex items-center gap-2 text-sm whitespace-nowrap">
                                <span>{{ slotProps.node.label }}</span>
                            </div>
                        </template>
                    </Tree>
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 flex flex-col gap-2 border-t pt-4">
                    <Button
                        label="Tambah Sub-kategori"
                        icon="pi pi-plus"
                        class="p-button-outlined p-button-sm w-full"
                        :disabled="!categoryStore.selectedCategory || !selectedCompanyId"
                        @click="openAddSubcategory"
                    />
                    <Button
                        label="Tambah Kategori Root"
                        icon="pi pi-plus-circle"
                        class="p-button-outlined p-button-sm w-full"
                        :disabled="!selectedCompanyId"
                        @click="openAddRoot"
                    />
                    <Button
                        label="Upload Arsip"
                        icon="pi pi-upload"
                        class="p-button-primary w-full mt-1"
                        :disabled="!categoryStore.selectedCategory"
                        @click="showUploadDialog = true"
                    />
                </div>
            </div>
        </div>

        <!-- Main Content Panel -->
        <div class="col-span-12 md:col-span-8 lg:col-span-7">
            <div class="card p-4 h-full bg-white shadow-sm border border-slate-200 rounded-lg flex flex-col">
                <div v-if="categoryStore.selectedCategory" class="w-full h-full flex flex-col">
                    <div class="flex items-center justify-between mb-4 border-b pb-4">
                        <div>
                            <p class="text-xs text-slate-400 uppercase tracking-wide">
                                {{ selectedCompanyName }}
                            </p>
                            <h3 class="text-xl font-semibold text-slate-800 flex items-center gap-2">
                                <i class="pi pi-folder text-yellow-500"></i>
                                {{ categoryStore.selectedCategory.label }}
                            </h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                label="Edit Kategori"
                                icon="pi pi-pencil"
                                size="small"
                                severity="secondary"
                                @click="openEditCategory"
                            />
                            <Button
                                label="Upload Arsip"
                                icon="pi pi-upload"
                                size="small"
                                @click="showUploadDialog = true"
                            />
                        </div>
                    </div>
                    <div class="flex-1 overflow-auto">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-semibold text-slate-700">Sub-kategori</h4>
                                </div>

                                <div v-if="subcategories.length" class="flex flex-wrap gap-2">
                                    <button
                                        v-for="child in subcategories"
                                        :key="child.key"
                                        type="button"
                                        class="px-3 py-2 rounded border border-slate-200 bg-white hover:bg-slate-50 text-sm text-slate-700 flex items-center gap-2"
                                        @click="selectCategoryNode(child)"
                                    >
                                        <i class="pi pi-folder text-yellow-500"></i>
                                        <span class="truncate max-w-[240px]">{{ child.label }}</span>
                                    </button>
                                </div>
                                <div v-else class="text-sm text-slate-400 bg-slate-50 border border-slate-200 rounded p-3">
                                    Tidak ada sub-kategori.
                                </div>
                            </div>

                            <div class="col-span-12">
                                <div class="flex items-center justify-between mb-2 mt-2">
                                    <h4 class="text-sm font-semibold text-slate-700">Arsip</h4>
                                    <Button
                                        label="Refresh"
                                        icon="pi pi-refresh"
                                        size="small"
                                        severity="secondary"
                                        :loading="isLoadingArchives"
                                        @click="loadArchives"
                                    />
                                </div>

                                <div v-if="isLoadingArchives" class="text-center py-8 text-slate-400">
                                    <i class="pi pi-spin pi-spinner text-2xl"></i>
                                </div>
                                <div v-else-if="!archives.length" class="text-center py-10 text-slate-400 bg-slate-50 rounded border border-slate-200">
                                    <i class="pi pi-folder-open text-4xl mb-3 block opacity-20"></i>
                                    <p class="text-sm">Belum ada arsip di kategori ini.</p>
                                </div>
                                <div v-else class="overflow-auto rounded border border-slate-200">
                                    <table class="w-full text-sm">
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
                                            <tr v-for="a in archives" :key="a.id" class="border-t border-slate-200 hover:bg-slate-50">
                                                <td class="p-3 text-slate-800">
                                                    <div class="flex items-center gap-2">
                                                        <i :class="[getFileIcon(a.file_type), getFileColor(a.file_type), 'text-lg']"></i>
                                                        <div class="flex flex-col">
                                                            <div class="font-medium">{{ a.name }}</div>
                                                            <div v-if="a.file_type" class="text-[9px] font-bold text-slate-400 uppercase tracking-tight">.{{ a.file_type }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-3 text-slate-500 italic max-w-[200px] truncate">
                                                    {{ a.keterangan || '—' }}
                                                </td>
                                                <td class="p-3 text-slate-600">{{ a.file_number || '—' }}</td>
                                                <td class="p-3 text-slate-600">{{ formatDateDisplay(a.issue_date) }}</td>
                                                <td class="p-3 text-slate-600">
                                                    <Tag :value="getArchiveTypeLabel(a.archive_type)" :severity="getArchiveTypeSeverity(a.archive_type)" />
                                                </td>
                                                <td class="p-3">
                                                    <Button 
                                                        v-if="a.pic_user_id === currentUserId" 
                                                        icon="pi pi-pencil" 
                                                        size="small" 
                                                        severity="secondary" 
                                                        text 
                                                        @click="openEditArchive(a)" 
                                                        v-tooltip.top="'Edit Arsip'" 
                                                    />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="flex-1 flex items-center justify-center text-slate-400">
                    <div class="text-center">
                        <i class="pi pi-folder text-6xl mb-4 block opacity-20"></i>
                        <p class="text-lg">Pilih kategori untuk melihat konten</p>
                        <p class="text-sm mt-1" v-if="!selectedCompanyId">Mulai dengan memilih perusahaan di panel kiri.</p>
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
                <div v-if="categoryStore.selectedCategory && !isRoot" class="p-3 bg-slate-50 rounded text-sm text-slate-600">
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
                    <InputText id="edit-cat-name" v-model="editCategoryName" autofocus @keyup.enter="saveEditCategory" />
                </div>
            </div>
            <template #footer>
                <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="showEditCategoryDialog = false" />
                <Button label="Simpan" icon="pi pi-check" :loading="isUpdating" @click="saveEditCategory" />
            </template>
        </Dialog>

        <!-- Archive Upload Dialog -->
        <ArchiveUploadDialog
            v-model:visible="showUploadDialog"
            :preselectedCategory="categoryStore.selectedCategory"
            :companyId="selectedCompanyId"
            @upload-success="onUploadSuccess"
        />

        <ArchiveEditDialog
            v-model:visible="showEditArchiveDialog"
            :archive="selectedArchive"
            @edit-success="onEditSuccess"
        />

        <Toast />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCategoryStore } from '@/store/category'
import { createCategory, updateCategory } from '@/api/categoryApi'
import { fetchCompanies } from '@/api/companyApi'
import { fetchArchives } from '@/api/archiveApi'
import { useAuthStore } from '@/store/auth'
import { useToast } from 'primevue/usetoast'
import Tree from 'primevue/tree'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import Tag from 'primevue/tag'
import Tooltip from 'primevue/tooltip'
import ArchiveUploadDialog from '@/components/ArchiveUploadDialog.vue'
import ArchiveEditDialog from '@/components/ArchiveEditDialog.vue'

const vTooltip = Tooltip

const categoryStore = useCategoryStore()
const authStore = useAuthStore()
const toast = useToast()

// State
const companies = ref([])
const isLoadingCompanies = ref(false)
const selectedCompanyId = ref(categoryStore.selectedCompanyId)
const selectedKey = ref(null)
const filterText = ref('')
const showAddCategoryDialog = ref(false)
const showEditCategoryDialog = ref(false)
const showUploadDialog = ref(false)
const showEditArchiveDialog = ref(false)
const selectedArchive = ref(null)
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
    await loadCompanies()

    // Auto-load tree if company was previously selected
    if (selectedCompanyId.value) {
        await categoryStore.loadCategoryTree(selectedCompanyId.value)
    }
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

const selectCategoryNode = (node) => {
    categoryStore.setSelectedCategory(node)
    selectedKey.value = { [String(node.key)]: true }
    loadArchives()
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
