import api from '@/services/api'

export function fetchDashboardStats() {
    return api.get('/dashboard/stats')
}
