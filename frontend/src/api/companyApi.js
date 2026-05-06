import api from '@/services/api'

export function fetchCompanies() {
    return api.get('/companies')
}

export function createCompany(data) {
    return api.post('/companies', data)
}

export function updateCompany(id, data) {
    return api.put(`/companies/${id}`, data)
}

export function deleteCompany(id) {
    return api.delete(`/companies/${id}`)
}
