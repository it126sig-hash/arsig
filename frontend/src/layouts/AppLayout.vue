<template>
  <!-- App Layout — Shell wrapper: Sidebar + Topbar + Content -->
  <div class="flex h-screen bg-slate-50 overflow-hidden">
    <!-- Sidebar (Hidden on mobile) -->
    <AppSidebar 
      class="hidden md:flex"
      :collapsed="sidebarCollapsed" 
      @toggle-collapse="sidebarCollapsed = !sidebarCollapsed" 
    />

    <!-- Mobile Sidebar (Drawer) -->
    <Drawer v-model:visible="mobileMenuVisible" header="Menu ARSIG" class="!w-72">
        <template #header>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                    <i class="pi pi-server text-white text-sm"></i>
                </div>
                <span class="font-bold text-lg text-slate-800">ARSIG</span>
            </div>
        </template>
        <AppSidebar 
            :collapsed="false" 
            class="!bg-transparent !text-slate-800"
            @menu-click="mobileMenuVisible = false"
        />
    </Drawer>

    <!-- Main Area -->
    <div class="flex flex-col flex-1 min-w-0 overflow-hidden relative">
      <!-- Topbar -->
      <AppTopbar @toggle-menu="mobileMenuVisible = true" />

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto pb-40 md:pb-0" style="padding-bottom: calc(10rem + env(safe-area-inset-bottom))">
        <div class="p-4 md:p-6">
          <!-- Router View renders the current page -->
          <router-view v-slot="{ Component }">
            <transition name="page" mode="out-in">
              <component :is="Component" :key="$route.path" />
            </transition>
          </router-view>
        </div>
      </main>

      <!-- Mobile Bottom Navigation -->
      <BottomNavigationBar @toggle-menu="mobileMenuVisible = true" />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppSidebar from '../components/AppSidebar.vue'
import AppTopbar  from '../components/AppTopbar.vue'
import BottomNavigationBar from '../components/BottomNavigationBar.vue'
import Drawer from 'primevue/drawer'

const route = useRoute()

// Sidebar collapse state (persisted in localStorage)
const sidebarCollapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true')
const mobileMenuVisible = ref(false)

// Watch collapse changes and persist to localStorage
watch(sidebarCollapsed, (val) => {
  localStorage.setItem('sidebar_collapsed', String(val))
})

// Close mobile menu on route change
watch(() => route.path, () => {
    mobileMenuVisible.value = false
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

/* Override sidebar styles when in drawer */
:deep(.drawer-sidebar .app-sidebar) {
    background: transparent !important;
    color: #334155 !important;
}
</style>
