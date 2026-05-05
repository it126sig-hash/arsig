<template>
  <!-- App Layout — Shell wrapper: Sidebar + Topbar + Content -->
  <div class="flex h-screen bg-slate-50 overflow-hidden">
    <!-- Sidebar -->
    <AppSidebar :collapsed="sidebarCollapsed" @toggle-collapse="sidebarCollapsed = !sidebarCollapsed" />

    <!-- Main Area -->
    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
      <!-- Topbar -->
      <AppTopbar />

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto">
        <div class="p-6">
          <!-- Router View renders the current page -->
          <router-view v-slot="{ Component }">
            <transition name="page" mode="out-in">
              <component :is="Component" :key="$route.path" />
            </transition>
          </router-view>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppSidebar from '../components/AppSidebar.vue'
import AppTopbar  from '../components/AppTopbar.vue'

const route = useRoute()

// Sidebar collapse state (persisted in localStorage)
const sidebarCollapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true')

// Watch collapse changes and persist to localStorage
watch(sidebarCollapsed, (val) => {
  localStorage.setItem('sidebar_collapsed', String(val))
})
</script>

<style scoped>
/* Page transition */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.page-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.page-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
