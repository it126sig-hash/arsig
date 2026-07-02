import api from '@/services/api'

export function fetchRolePermissions() {
    return api.get('/role-permissions')
}

export function updateRolePermissions(permissions) {
    return api.put('/role-permissions', { permissions })
}
