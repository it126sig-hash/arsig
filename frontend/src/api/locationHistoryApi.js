import api from '@/services/api'

export function fetchLocationHistories(params = {}) {
    return api.get('/location-histories', { params })
}
