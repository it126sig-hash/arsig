import api from '@/services/api'

export function fetchArchives(params = {}) {
    return api.get('/archives', { params })
}

export function uploadArchive(formData) {
    return api.post('/archives', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
}

export function updateArchive(id, formData) {
    // Laravel requires _method: 'PUT' when sending multipart/form-data via POST
    if (formData instanceof FormData && !formData.has('_method')) {
        formData.append('_method', 'PUT')
    }
    
    return api.post(`/archives/${id}`, formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
}

export function downloadArchive(id) {
    return api.get(`/archives/${id}/download`, {
        responseType: 'blob'
    })
}

export function previewArchive(id) {
    return api.get(`/archives/${id}/preview`, {
        responseType: 'blob'
    })
}

export function requestOtp(id) {
    return api.post(`/archives/${id}/request-otp`)
}

export function verifyOtp(id, otp) {
    return api.post(`/archives/${id}/verify-otp`, { otp })
}

export function fetchArchiveLocationHistories(id) {
    return api.get(`/archives/${id}/location-histories`)
}

export function moveArchiveLocation(id, data) {
    return api.post(`/archives/${id}/move-location`, data)
}
