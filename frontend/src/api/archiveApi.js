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
