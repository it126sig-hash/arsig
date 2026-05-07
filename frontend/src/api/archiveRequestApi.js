import api from '@/services/api'

export function fetchArchiveRequests() {
    return api.get('/archive-requests')
}

export function approveArchiveRequest(id) {
    return api.post(`/archive-requests/${id}/approve`)
}

export function rejectArchiveRequest(id) {
    return api.post(`/archive-requests/${id}/reject`)
}
