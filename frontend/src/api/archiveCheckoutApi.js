import api from '@/services/api'

export function checkoutArchive(archiveId, payload) {
    return api.post(`/archives/${archiveId}/checkout`, payload)
}

export function checkinArchive(archiveId) {
    return api.post(`/archives/${archiveId}/checkin`)
}

export function getCheckoutHistory(archiveId, params = {}) {
    return api.get(`/archives/${archiveId}/checkout-history`, { params })
}
