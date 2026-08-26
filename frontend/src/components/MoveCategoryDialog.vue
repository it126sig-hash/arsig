<template>
    <Dialog 
        :visible="modelValue" 
        @update:visible="$emit('update:modelValue', $event)" 
        header="Pindah Kategori Arsip" 
        :modal="true" 
        class="w-full max-w-lg"
    >
        <div class="flex flex-col gap-4 mt-2" v-if="archive">
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl mb-2">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-white shadow-sm border border-slate-100">
                        <i :class="[getFileIcon(archive?.file_type), getFileColor(archive?.file_type), 'text-xl']"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-800 text-sm">{{ archive.name }}</span>
                        <span class="text-xs text-slate-500 font-mono mt-0.5">{{ archive.file_number || 'Tidak ada nomor' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold text-slate-700">Pilih PT / Perusahaan</label>
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

            <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold text-slate-700">Pilih Sub-kategori</label>
                <TreeSelect 
                    v-model="selectedCategoryKey" 
                    :options="categories" 
                    placeholder="— Pilih Sub-kategori —" 
                    class="w-full"
                    :disabled="!selectedCompanyId"
                    :loading="isLoadingCategories"
                    filter
                    filterPlaceholder="Cari kategori..."
                />
            </div>
        </div>

        <template #footer>
            <Button label="Batal" icon="pi pi-times" class="p-button-text" @click="$emit('update:modelValue', false)" />
            <Button 
                label="Simpan Pindah" 
                icon="pi pi-check" 
                severity="primary" 
                :loading="isSaving" 
                :disabled="!isValid"
                @click="save" 
            />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { fetchCompanies } from '@/api/companyApi'
import { fetchCategoryTree } from '@/api/categoryApi'
import { moveArchiveCategory } from '@/api/archiveApi'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Select from 'primevue/select'
import TreeSelect from 'primevue/treeselect'

const props = defineProps({
    modelValue: Boolean,
    archive: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['update:modelValue', 'moved'])

const isSaving = ref(false)
const isLoadingCompanies = ref(false)
const isLoadingCategories = ref(false)

const companies = ref([])
const categories = ref([])
const selectedCompanyId = ref(null)
const selectedCategoryKey = ref(null)

const isValid = computed(() => {
    return selectedCompanyId.value && selectedCategoryKey.value && Object.keys(selectedCategoryKey.value).length > 0
})

const loadCompanies = async () => {
    isLoadingCompanies.value = true
    try {
        const res = await fetchCompanies()
        companies.value = res.data.data
    } catch (err) {
        console.error('Failed to load companies', err)
    } finally {
        isLoadingCompanies.value = false
    }
}

const formatTreeData = (data, parentPath = '') => {
    if (!data) return []
    return data.map(node => {
        const name = node.label || node.name
        const fullPath = parentPath ? `${parentPath} > ${name}` : name
        return {
            key: node.key || (node.id ? node.id.toString() : Math.random().toString()),
            label: name,
            data: node.data || node,
            children: node.children ? formatTreeData(node.children, fullPath) : []
        }
    })
}

const loadCategories = async (companyId) => {
    isLoadingCategories.value = true
    try {
        const res = await fetchCategoryTree(companyId)
        categories.value = formatTreeData(res.data.data)
    } catch (err) {
        console.error('Failed to load categories', err)
        categories.value = []
    } finally {
        isLoadingCategories.value = false
    }
}

const onCompanyChange = () => {
    selectedCategoryKey.value = null
    if (selectedCompanyId.value) {
        loadCategories(selectedCompanyId.value)
    } else {
        categories.value = []
    }
}

const resetForm = () => {
    selectedCompanyId.value = props.archive?.company_id || null
    if (selectedCompanyId.value) {
        loadCategories(selectedCompanyId.value).then(() => {
            if (props.archive?.category_id) {
                selectedCategoryKey.value = { [props.archive.category_id.toString()]: true }
            }
        })
    } else {
        selectedCategoryKey.value = null
        categories.value = []
    }
}

watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        if (!companies.value.length) {
            loadCompanies().then(resetForm)
        } else {
            resetForm()
        }
    }
})

const save = async () => {
    if (!isValid.value) return

    const categoryId = Object.keys(selectedCategoryKey.value)[0]
    
    isSaving.value = true
    try {
        const payload = {
            company_id: selectedCompanyId.value,
            category_id: categoryId
        }
        await moveArchiveCategory(props.archive.id, payload)
        emit('moved')
    } catch (error) {
        console.error('Failed to move category', error)
        // Let parent handle toast error or we could emit it
    } finally {
        isSaving.value = false
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
