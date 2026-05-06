import api from '@/services/api'

export function fetchCategoryTree(companyId) {
    return api.get('/categories', { params: { company_id: companyId } })
}

export function createCategory(data) {
    return api.post('/categories', data)
}

export function updateCategory(id, data) {
    return api.put(`/categories/${id}`, data)
}

export function deleteCategory(id) {
    return api.delete(`/categories/${id}`)
}
