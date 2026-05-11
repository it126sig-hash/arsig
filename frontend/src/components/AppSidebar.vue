<template>
  <!-- App Sidebar — Dark navy sidebar with collapsible support -->
  <aside
    :class="[
      'app-sidebar flex flex-col h-screen bg-slate-900 text-white transition-all duration-300 ease-in-out flex-shrink-0 z-40',
      collapsed ? 'w-16' : 'w-64'
    ]"
  >
    <!-- Logo Area -->
    <div class="flex items-center h-16 px-4 border-b border-slate-700 gap-3 overflow-hidden">
      <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
        <i class="pi pi-server text-white text-sm"></i>
      </div>
      <span
        v-show="!collapsed"
        class="font-bold text-lg tracking-tight whitespace-nowrap transition-opacity duration-200"
      >
        ARSIG
      </span>
    </div>

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
      <div v-show="!collapsed" class="px-4 mb-2 text-xs font-semibold uppercase tracking-widest text-slate-500">
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

// Main navigation items
const menuItems = computed(() => {
  const items = [
    { to: '/', label: 'Dashboard', icon: 'pi pi-home' },
    { to: '/file-explorer', label: 'File Explorer', icon: 'pi pi-folder' },
    { to: '/companies', label: 'Perusahaan (PT)', icon: 'pi pi-building' },
    { to: '/departments', label: 'Departemen', icon: 'pi pi-sitemap' },
    { to: '/users', label: 'User', icon: 'pi pi-users' },
    { to: '/tags', label: 'Tag (Hashtag)', icon: 'pi pi-hashtag' },
  ]

  // Add Request Approval for PIC or Admin
  // Assuming all users can see it but only PIC/Admin see actual data, 
  // or we can restrict it here. Let's restrict it if role is known.
  const user = authStore.user
  if (user && (user.role === 'admin' || user.role === 'pic' || true)) { // Added true for now to ensure visibility during development
    items.push({ to: '/approvals', label: 'Persetujuan Akses', icon: 'pi pi-check-square' })
  }

  return items
})

// Location management items
const locationItems = [
  { to: '/floors',        label: 'Floors (Lantai)',  icon: 'pi pi-building' },
  { to: '/rooms',         label: 'Rooms (Ruangan)',  icon: 'pi pi-table' },
  { to: '/cabinets',      label: 'Cabinets (Lemari)', icon: 'pi pi-server' },
  { to: '/cabinet-slots', label: 'Cabinet Slots',    icon: 'pi pi-inbox' },
]
</script>
