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
      <!-- Notification Bell -->
      <div class="relative" ref="notifMenuRef">
        <button
          @click="toggleNotifMenu"
          class="relative w-9 h-9 rounded-full hover:bg-slate-100 flex items-center justify-center transition-colors duration-150"
        >
          <i class="pi pi-bell text-slate-500"></i>
          <span
            v-if="unreadCount > 0"
            class="absolute top-0.5 right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
          >
            {{ unreadCount > 9 ? '9+' : unreadCount }}
          </span>
        </button>

        <transition name="dropdown">
          <div
            v-if="showNotifMenu"
            class="absolute right-0 top-full mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50"
          >
            <div class="px-4 py-2.5 border-b border-slate-100 flex items-center justify-between">
              <p class="text-sm font-semibold text-slate-800">Notifikasi</p>
              <button
                v-if="unreadCount > 0"
                @click="handleMarkAllRead"
                class="text-xs text-blue-600 hover:underline"
              >
                Tandai semua dibaca
              </button>
            </div>
            <div class="max-h-96 overflow-y-auto">
              <div v-if="notifications.length === 0" class="px-4 py-6 text-center text-sm text-slate-400">
                Belum ada notifikasi.
              </div>
              <button
                v-for="item in notifications"
                :key="item.id"
                @click="handleNotifClick(item)"
                class="w-full text-left px-4 py-3 border-b border-slate-50 hover:bg-slate-50 transition-colors duration-150"
                :class="!item.read_at ? 'bg-blue-50/50' : ''"
              >
                <p class="text-sm text-slate-700">{{ item.data.message }}</p>
                <p class="text-[10px] text-slate-400 mt-1">{{ timeAgo(item.created_at) }}</p>
              </button>
            </div>
          </div>
        </transition>
      </div>

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
import { fetchNotifications, markNotificationAsRead, markAllNotificationsAsRead } from '@/api/notificationApi'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()

// User menu state
const showUserMenu = ref(false)
const userMenuRef  = ref(null)

// Notification menu state
const showNotifMenu = ref(false)
const notifMenuRef  = ref(null)
const notifications = ref([])
const unreadCount    = ref(0)

// Manual click-outside handler
const handleClickOutside = (event) => {
  if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
    showUserMenu.value = false
  }
  if (notifMenuRef.value && !notifMenuRef.value.contains(event.target)) {
    showNotifMenu.value = false
  }
}
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  loadNotifications()
})
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
}

const loadNotifications = async () => {
  try {
    const res = await fetchNotifications()
    notifications.value = res.data.data.data
    unreadCount.value = notifications.value.filter(n => !n.read_at).length
  } catch (err) {
    console.error('Failed to load notifications', err)
  }
}

const toggleNotifMenu = () => {
  showNotifMenu.value = !showNotifMenu.value
}

const handleNotifClick = async (item) => {
  if (!item.read_at) {
    await markNotificationAsRead(item.id)
    item.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  }
  showNotifMenu.value = false
  if (item.data?.link) router.push(item.data.link)
}

const handleMarkAllRead = async () => {
  await markAllNotificationsAsRead()
  notifications.value.forEach(n => { n.read_at = n.read_at || new Date().toISOString() })
  unreadCount.value = 0
}

const timeAgo = (dateString) => {
  const diffInSeconds = Math.floor((new Date() - new Date(dateString)) / 1000)
  if (diffInSeconds < 60) return 'Baru saja'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} menit yang lalu`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} jam yang lalu`
  return `${Math.floor(diffInSeconds / 86400)} hari yang lalu`
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
