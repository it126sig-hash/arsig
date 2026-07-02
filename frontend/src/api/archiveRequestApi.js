import api from '@/services/api'

export function fetchArchiveRequests() {
    return api.get('/archive-requests')
}

export function fetchMyArchiveRequests() {
    return api.get('/archive-requests/my-requests')
}

export function approveArchiveRequest(id) {
    return api.post(`/archive-requests/${id}/approve`)
}

export function rejectArchiveRequest(id) {
    return api.post(`/archive-requests/${id}/reject`)
}

export function fetchDownloadHistory(params = {}) {
    return api.get('/archive-downloads/history', { params })
}
