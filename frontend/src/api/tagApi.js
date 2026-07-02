import api from '@/services/api'

export function fetchTags() {
    return api.get('/tags')
}

export function createTag(data) {
    return api.post('/tags', data)
}

export function updateTag(id, data) {
    return api.put(`/tags/${id}`, data)
}

export function deleteTag(id) {
    return api.delete(`/tags/${id}`)
}

export function fetchTrashedTags() {
    return api.get('/tags/trashed')
}

export function restoreTag(id) {
    return api.post(`/tags/${id}/restore`)
}
