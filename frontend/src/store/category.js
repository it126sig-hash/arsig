import { defineStore } from 'pinia'
import { fetchCategoryTree } from '@/api/categoryApi'

export const useCategoryStore = defineStore('category', {
    state: () => ({
        categoryTree: [],
        selectedCategory: null,
        selectedCompanyId: localStorage.getItem('selected_company_id')
            ? parseInt(localStorage.getItem('selected_company_id'))
            : null,
        isLoading: false
    }),
    actions: {
        async loadCategoryTree(companyId) {
            this.isLoading = true
            try {
                const response = await fetchCategoryTree(companyId)
                if (response.data.success) {
                    this.categoryTree = response.data.data
                }
            } catch (error) {
                console.error('Failed to load category tree:', error)
                this.categoryTree = []
            } finally {
                this.isLoading = false
            }
        },
        setSelectedCategory(node) {
            this.selectedCategory = node
        },
        async setSelectedCompany(companyId) {
            this.selectedCompanyId = companyId
            this.selectedCategory = null // reset selected category when switching company
            localStorage.setItem('selected_company_id', companyId)
            if (companyId) {
                await this.loadCategoryTree(companyId)
            } else {
                this.categoryTree = []
            }
        }
    }
})
