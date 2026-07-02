<template>
  <!-- App Sidebar — Dark navy sidebar with collapsible support -->
  <aside
    :class="[
      'app-sidebar flex flex-col h-screen bg-slate-900 text-white transition-all duration-300 ease-in-out flex-shrink-0 z-40',
      collapsed ? 'w-16' : 'w-64'
    ]"
  >
    

    <!-- Navigation Menu -->
    <nav class="flex-1 py-4 overflow-y-auto overflow-x-hidden">
      <div v-show="!collapsed" class="px-4 mb-2 text-xs font-semibold uppercase tracking-widest text-slate-500">
        Menu
      </div>

      <ul class="space-y-1 px-2">
        <li v-for="item in menuItems" :key="item.to">
          <router-link
            :to="item.to"
            :class="[
              'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 group',
              isActive(item.to)
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20'
                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
            ]"
            :title="collapsed ? item.label : ''"
            @click="$emit('menu-click')"
          >
            <i :class="['flex-shrink-0 text-lg', item.icon]"></i>
            <span
              v-show="!collapsed"
              class="text-sm font-medium whitespace-nowrap transition-opacity duration-200"
            >
              {{ item.label }}
            </span>
          </router-link>
        </li>
      </ul>

      <!-- Separator -->
      <div class="my-4 mx-4 border-t border-slate-700" v-show="!collapsed"></div>
      <div class="my-4 mx-2 border-t border-slate-700" v-show="collapsed"></div>

      <!-- Location sub-items -->
      <div v-show="!collapsed && locationItems.length" class="px-4 mb-2 text-xs font-semibold uppercase tracking-widest text-slate-500">
        Lokasi Fisik
      </div>
      <ul class="space-y-1 px-2">
        <li v-for="item in locationItems" :key="item.to">
          <router-link
            :to="item.to"
            :class="[
              'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150',
              isActive(item.to)
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20'
                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
            ]"
            :title="collapsed ? item.label : ''"
          >
            <i :class="['flex-shrink-0 text-lg', item.icon]"></i>
            <span
              v-show="!collapsed"
              class="text-sm font-medium whitespace-nowrap"
            >
              {{ item.label }}
            </span>
          </router-link>
        </li>
      </ul>
      <!-- History sub-items (always visible) -->
      <div v-show="!collapsed" class="px-4 mb-2 mt-4 text-xs font-semibold uppercase tracking-widest text-slate-500">
        Riwayat
      </div>
      <ul class="space-y-1 px-2">
        <li v-for="item in historyItems" :key="item.to">
          <router-link
            :to="item.to"
            :class="[
              'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150',
              isActive(item.to)
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20'
                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
            ]"
            :title="collapsed ? item.label : ''"
          >
            <i :class="['flex-shrink-0 text-lg', item.icon]"></i>
            <span
              v-show="!collapsed"
              class="text-sm font-medium whitespace-nowrap"
            >
              {{ item.label }}
            </span>
          </router-link>
        </li>
      </ul>
    </nav>

    <!-- Collapse Toggle Button -->
    <div class="border-t border-slate-700 p-3">
      <button
        @click="$emit('toggle-collapse')"
        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition-all duration-150"
        :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
      >
        <i :class="['pi text-lg transition-transform duration-300', collapsed ? 'pi-angle-right' : 'pi-angle-left']"></i>
        <span v-show="!collapsed" class="text-sm font-medium">Collapse</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { computed } from 'vue'
import { useAuthStore } from '@/store/auth'

// Props & Emits
defineProps({
  collapsed: {
    type: Boolean,
    default: false
  }
})
const route = useRoute()
const authStore = useAuthStore()
const emit = defineEmits(['toggle-collapse', 'menu-click'])

// Check if a route is active (exact match or starts with for nested)
const isActive = (path) => {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}

// Main navigation items — always-visible items plus module-gated master data
const menuItems = computed(() => {
  const items = [
    { to: '/', label: 'Dashboard', icon: 'pi pi-home' },
    { to: '/file-explorer', label: 'Upload File', icon: 'pi pi-folder' },
    { to: '/tags', label: 'Tag (Hashtag)', icon: 'pi pi-hashtag' },
    { to: '/approvals', label: 'Persetujuan Akses', icon: 'pi pi-check-square' },
  ]

  if (authStore.canModule('companies', 'view')) {
    items.push({ to: '/companies', label: 'Perusahaan (PT)', icon: 'pi pi-building' })
  }
  if (authStore.canModule('departments', 'view')) {
    items.push({ to: '/departments', label: 'Departemen', icon: 'pi pi-sitemap' })
  }
  if (authStore.canModule('users', 'view')) {
    items.push({ to: '/users', label: 'User', icon: 'pi pi-users' })
  }
  if (authStore.user?.role === 'admin' || authStore.user?.role === 'root') {
    items.push({ to: '/role-permissions', label: 'Matriks Izin', icon: 'pi pi-shield' })
  }

  return items
})

// Physical-location master data — admin-gated
const locationItems = computed(() => {
  const items = []
  if (authStore.canModule('floors', 'view')) {
    items.push({ to: '/floors', label: 'Floors (Lantai)', icon: 'pi pi-building' })
  }
  if (authStore.canModule('rooms', 'view')) {
    items.push({ to: '/rooms', label: 'Rooms (Ruangan)', icon: 'pi pi-table' })
  }
  if (authStore.canModule('cabinets', 'view')) {
    items.push({ to: '/cabinets', label: 'Cabinets (Lemari)', icon: 'pi pi-server' })
  }
  if (authStore.canModule('cabinet_slots', 'view')) {
    items.push({ to: '/cabinet-slots', label: 'Cabinet Slots', icon: 'pi pi-inbox' })
  }
  return items
})

// History items — always visible regardless of role
const historyItems = [
  { to: '/location-histories', label: 'Riwayat Lokasi', icon: 'pi pi-history' },
  { to: '/download-history', label: 'Riwayat Download', icon: 'pi pi-download' },
]
</script>
