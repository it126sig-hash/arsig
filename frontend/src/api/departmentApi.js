import api from '@/services/api'

export function fetchDepartments() {
    return api.get('/departments')
}

export function createDepartment(data) {
    return api.post('/departments', data)
}

export function updateDepartment(id, data) {
    return api.put(`/departments/${id}`, data)
}

export function deleteDepartment(id) {
    return api.delete(`/departments/${id}`)
}
