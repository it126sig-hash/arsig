<template>
  <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-50 flex items-center justify-around h-16 px-2 pb-safe shadow-[0_-1px_10px_rgba(0,0,0,0.05)]">
    <router-link
      v-for="item in navItems"
      :key="item.to"
      :to="item.to"
      class="flex flex-col items-center justify-center gap-1 w-full h-full transition-colors duration-200"
      :class="[isActive(item.to) ? 'text-blue-600 font-semibold' : 'text-slate-400']"
    >
      <i :class="[item.icon, 'text-xl']"></i>
      <span class="text-[10px] uppercase tracking-wider">{{ item.label }}</span>
    </router-link>

    <!-- Menu Toggle (More) -->
    <button
      @click="$emit('toggle-menu')"
      class="flex flex-col items-center justify-center gap-1 w-full h-full text-slate-400 hover:text-slate-600 transition-colors duration-200"
    >
      <i class="pi pi-bars text-xl"></i>
      <span class="text-[10px] uppercase tracking-wider">Menu</span>
    </button>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/store/auth'

defineEmits(['toggle-menu'])

const route = useRoute()
const authStore = useAuthStore()

const isActive = (path) => {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}

const navItems = computed(() => {
  const items = [
    { to: '/', label: 'Beranda', icon: 'pi pi-home' },
    { to: '/file-explorer', label: 'Explorer', icon: 'pi pi-folder-open' },
    { to: '/profile', label: 'Profil', icon: 'pi pi-user' },
  ]
  return items
})
</script>

<style scoped>
.router-link-active i {
  transform: translateY(-2px);
  transition: transform 0.2s ease;
}
.pb-safe {
  padding-bottom: env(safe-area-inset-bottom);
}
</style>
