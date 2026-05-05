import { defineStore } from 'pinia'
import api from '../services/api'

export const useLocationStore = defineStore('location', {
  state: () => ({
    floors: [],
    rooms: [],
    cabinets: [],
    cabinetSlots: [],
    loading: false,
    error: null
  }),

  actions: {
    // --- Floors ---
    async fetchFloors() {
      this.loading = true
      try {
        const response = await api.get('/floors')
        this.floors = response.data.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to fetch floors'
        throw err
      } finally {
        this.loading = false
      }
    },
    async createFloor(data) {
      this.loading = true
      try {
        // use FormData for image upload
        const formData = new FormData()
        formData.append('name', data.name)
        if (data.floor_plan_image) {
          formData.append('floor_plan_image', data.floor_plan_image)
        }
        
        const response = await api.post('/floors', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        this.floors.unshift(response.data.data)
        return response.data
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },
    async updateFloor(id, data) {
      this.loading = true
      try {
        const formData = new FormData()
        // Method spoofing for PUT request with FormData
        formData.append('_method', 'PUT')
        formData.append('name', data.name)
        if (data.floor_plan_image) {
          formData.append('floor_plan_image', data.floor_plan_image)
        }
        
        const response = await api.post(`/floors/${id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        const index = this.floors.findIndex(f => f.id === id)
        if (index !== -1) {
          this.floors[index] = response.data.data
        }
        return response.data
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },
    async deleteFloor(id) {
      this.loading = true
      try {
        await api.delete(`/floors/${id}`)
        this.floors = this.floors.filter(f => f.id !== id)
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },

    // --- Rooms ---
    async fetchRooms() {
      this.loading = true
      try {
        const response = await api.get('/rooms')
        this.rooms = response.data.data
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },
    async createRoom(data) {
      this.loading = true
      try {
        const response = await api.post('/rooms', data)
        this.rooms.unshift(response.data.data)
        return response.data
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },
    async updateRoom(id, data) {
      this.loading = true
      try {
        const response = await api.put(`/rooms/${id}`, data)
        const index = this.rooms.findIndex(r => r.id === id)
        if (index !== -1) {
          this.rooms[index] = response.data.data
        }
        return response.data
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },
    async deleteRoom(id) {
      this.loading = true
      try {
        await api.delete(`/rooms/${id}`)
        this.rooms = this.rooms.filter(r => r.id !== id)
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },

    // --- Cabinets ---
    async fetchCabinets() {
      this.loading = true
      try {
        const response = await api.get('/cabinets')
        this.cabinets = response.data.data
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },
    async createCabinet(data) {
      this.loading = true
      try {
        const response = await api.post('/cabinets', data)
        this.cabinets.unshift(response.data.data)
        return response.data
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },
    async updateCabinet(id, data) {
      this.loading = true
      try {
        const response = await api.put(`/cabinets/${id}`, data)
        const index = this.cabinets.findIndex(c => c.id === id)
        if (index !== -1) {
          this.cabinets[index] = response.data.data
        }
        return response.data
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },
    async deleteCabinet(id) {
      this.loading = true
      try {
        await api.delete(`/cabinets/${id}`)
        this.cabinets = this.cabinets.filter(c => c.id !== id)
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },

    // --- Cabinet Slots ---
    async fetchCabinetSlots() {
      this.loading = true
      try {
        const response = await api.get('/cabinet-slots')
        this.cabinetSlots = response.data.data
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },
    async createCabinetSlot(data) {
      this.loading = true
      try {
        const response = await api.post('/cabinet-slots', data)
        this.cabinetSlots.unshift(response.data.data)
        return response.data
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },
    async updateCabinetSlot(id, data) {
      this.loading = true
      try {
        const response = await api.put(`/cabinet-slots/${id}`, data)
        const index = this.cabinetSlots.findIndex(c => c.id === id)
        if (index !== -1) {
          this.cabinetSlots[index] = response.data.data
        }
        return response.data
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    },
    async deleteCabinetSlot(id) {
      this.loading = true
      try {
        await api.delete(`/cabinet-slots/${id}`)
        this.cabinetSlots = this.cabinetSlots.filter(c => c.id !== id)
      } catch (err) {
        throw err
      } finally {
        this.loading = false
      }
    }
  }
})
