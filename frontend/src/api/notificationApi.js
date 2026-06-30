import api from '@/services/api'

export function fetchNotifications(params = {}) {
    return api.get('/notifications', { params })
}

export function markNotificationAsRead(id) {
    return api.post(`/notifications/${id}/read`)
}

export function markAllNotificationsAsRead() {
    return api.post('/notifications/read-all')
}
