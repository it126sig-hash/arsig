<template>
  <!-- App Topbar — White header with breadcrumb and user menu -->
  <header class="app-topbar h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 flex-shrink-0 shadow-sm z-30">
    <!-- Left: Breadcrumb -->
    <div class="flex items-center gap-2 text-sm">
      <i class="pi pi-home text-slate-400 text-xs"></i>
      <template v-for="(crumb, index) in breadcrumbs" :key="index">
        <i class="pi pi-angle-right text-slate-300 text-xs"></i>
        <span
          :class="[
            index === breadcrumbs.length - 1
              ? 'text-slate-800 font-semibold'
              : 'text-slate-500'
          ]"
        >
          {{ crumb }}
        </span>
      </template>
    </div>

    <!-- Right: User Menu -->
    <div class="flex items-center gap-4">
      <!-- Notification Bell (placeholder) -->
      <button class="relative w-9 h-9 rounded-full hover:bg-slate-100 flex items-center justify-center transition-colors duration-150">
        <i class="pi pi-bell text-slate-500"></i>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-blue-500 rounded-full"></span>
      </button>

      <!-- User Avatar + Dropdown -->
      <div class="relative" ref="userMenuRef">
        <button
          @click="toggleUserMenu"
          class="flex items-center gap-2.5 px-3 py-1.5 rounded-full hover:bg-slate-100 transition-colors duration-150"
        >
          <!-- Avatar circle -->
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
            {{ userInitial }}
          </div>
          <span class="text-sm font-medium text-slate-700 hidden sm:block">{{ userName }}</span>
          <i class="pi pi-angle-down text-slate-400 text-xs hidden sm:block"></i>
        </button>

        <!-- Dropdown -->
        <transition name="dropdown">
          <div
            v-if="showUserMenu"
            class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50"
          >
            <div class="px-4 py-2.5 border-b border-slate-100">
              <p class="text-sm font-semibold text-slate-800">{{ userName }}</p>
              <p class="text-xs text-slate-500 truncate">{{ userEmail }}</p>
            </div>
            <button
              @click="handleLogout"
              class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-150"
            >
              <i class="pi pi-sign-out"></i>
              <span>Logout</span>
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()

// User menu state
const showUserMenu = ref(false)
const userMenuRef  = ref(null)

// Manual click-outside handler
const handleClickOutside = (event) => {
  if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
    showUserMenu.value = false
  }
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
}

// User info from auth store
const userName    = computed(() => auth.user?.name    || 'Admin')
const userEmail   = computed(() => auth.user?.email   || '')
const userInitial = computed(() => (auth.user?.name?.[0] || 'A').toUpperCase())

// Build breadcrumbs from current route
const routeLabelMap = {
  '/':              'Dashboard',
  '/floors':        'Floors',
  '/rooms':         'Rooms',
  '/cabinets':      'Cabinets',
  '/cabinet-slots': 'Cabinet Slots',
}

const breadcrumbs = computed(() => {
  const label = routeLabelMap[route.path]
  if (!label || route.path === '/') return ['Dashboard']
  return ['Dashboard', label]
})

// Logout handler
const handleLogout = async () => {
  showUserMenu.value = false
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
/* Dropdown animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
