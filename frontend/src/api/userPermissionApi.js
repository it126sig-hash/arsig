import api from '../services/api'

export const getUserPermissions = (userId) => api.get(`/users/${userId}/permissions`)
export const updateUserPermissions = (userId, permissions) => api.put(`/users/${userId}/permissions`, { permissions })
export const resetUserPermissions = (userId) => api.delete(`/users/${userId}/permissions`)
