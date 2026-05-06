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
