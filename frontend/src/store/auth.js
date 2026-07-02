import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    permissions: JSON.parse(localStorage.getItem('permissions')) || {},
    loading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    getUser: (state) => state.user,
    canModule: (state) => (module, action) => {
      if (state.user?.role === 'root') return true
      return !!state.permissions?.[module]?.[`can_${action}`]
    }
  },

  actions: {
    async login(credentials) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post('/auth/login', credentials)
        const { user, token } = response.data.data

        this.user = user
        this.token = token

        localStorage.setItem('user', JSON.stringify(user))
        localStorage.setItem('token', token)

        await this.fetchPermissions()

        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Login failed'
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchPermissions() {
      if (this.user?.role === 'root') {
        this.permissions = {}
        return
      }
      try {
        const res = await api.get('/my-permissions')
        this.permissions = res.data.data || {}
        localStorage.setItem('permissions', JSON.stringify(this.permissions))
      } catch (err) {
        console.error('Failed to fetch permissions:', err)
      }
    },

    async logout() {
      this.loading = true
      try {
        await api.post('/auth/logout')
      } catch (err) {
        console.error('Logout error:', err)
      } finally {
        this.user = null
        this.token = null
        this.permissions = {}
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        localStorage.removeItem('permissions')
        this.loading = false
      }
    }
  }
})
