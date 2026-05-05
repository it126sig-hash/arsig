<template>
  <div>
    <!-- Dashboard Page -->
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-slate-800 mb-1">Dashboard</h1>
      <p class="text-slate-500 text-sm">Selamat datang di ARSIG — Sistem Manajemen Arsip Digital</p>
    </div>

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="relative bg-white rounded-2xl p-6 shadow-sm border border-slate-100 overflow-hidden cursor-pointer hover:shadow-md transition-shadow duration-200"
        @click="stat.to && $router.push(stat.to)"
      >
        <!-- Background Icon (decorative) -->
        <div
          :class="['absolute -right-3 -bottom-3 w-20 h-20 rounded-full flex items-center justify-center opacity-10', stat.bgClass]"
        >
          <i :class="['text-5xl', stat.icon, stat.iconColor]"></i>
        </div>

        <!-- Card Content -->
        <div class="relative z-10">
          <div :class="['inline-flex items-center justify-center w-10 h-10 rounded-xl mb-4', stat.badgeBg]">
            <i :class="['text-lg', stat.icon, stat.iconColor]"></i>
          </div>
          <div class="text-3xl font-extrabold text-slate-800 mb-1 tracking-tight">
            {{ stat.loading ? '...' : stat.value }}
          </div>
          <div class="text-sm font-medium text-slate-500">{{ stat.label }}</div>
        </div>
      </div>
    </div>

    <!-- Bottom Section: Quick Access -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
      <div class="flex items-center justify-between mb-5">
        <h2 class="text-base font-bold text-slate-800">Akses Cepat — Manajemen Lokasi Fisik</h2>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <router-link
          v-for="item in quickLinks"
          :key="item.to"
          :to="item.to"
          class="group flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-slate-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200 text-center"
        >
          <div :class="['w-12 h-12 rounded-xl flex items-center justify-center transition-transform duration-200 group-hover:scale-110', item.iconBg]">
            <i :class="['text-xl', item.icon, item.iconColor]"></i>
          </div>
          <div>
            <p class="text-sm font-semibold text-slate-700">{{ item.label }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ item.description }}</p>
          </div>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useLocationStore } from '../store/location'

const locationStore = useLocationStore()

// Fetch data on mount
onMounted(async () => {
  try {
    await Promise.allSettled([
      locationStore.fetchFloors(),
      locationStore.fetchRooms(),
      locationStore.fetchCabinets(),
    ])
  } catch (e) {
    // silently fail — stats show 0 if API unavailable
  }
})

// Stat cards config — values pulled from store
const stats = ref([
  {
    label:     'Total Lantai',
    value:     0,
    loading:   false,
    icon:      'pi pi-building',
    iconColor: 'text-blue-600',
    badgeBg:   'bg-blue-100',
    bgClass:   'bg-blue-500',
    to:        '/floors',
  },
  {
    label:     'Total Ruangan',
    value:     0,
    loading:   false,
    icon:      'pi pi-table',
    iconColor: 'text-emerald-600',
    badgeBg:   'bg-emerald-100',
    bgClass:   'bg-emerald-500',
    to:        '/rooms',
  },
  {
    label:     'Total Lemari',
    value:     0,
    loading:   false,
    icon:      'pi pi-server',
    iconColor: 'text-orange-600',
    badgeBg:   'bg-orange-100',
    bgClass:   'bg-orange-500',
    to:        '/cabinets',
  },
  {
    label:     'Total Slot Lemari',
    value:     0,
    loading:   false,
    icon:      'pi pi-inbox',
    iconColor: 'text-purple-600',
    badgeBg:   'bg-purple-100',
    bgClass:   'bg-purple-500',
    to:        '/cabinet-slots',
  },
])

// Update stat values when store data changes
import { watch } from 'vue'
watch(() => locationStore.floors,   (v) => { stats.value[0].value = v?.length ?? 0 }, { immediate: true })
watch(() => locationStore.rooms,    (v) => { stats.value[1].value = v?.length ?? 0 }, { immediate: true })
watch(() => locationStore.cabinets, (v) => { stats.value[2].value = v?.length ?? 0 }, { immediate: true })
watch(() => locationStore.cabinetSlots, (v) => { stats.value[3].value = v?.length ?? 0 }, { immediate: true })

// Quick links
const quickLinks = [
  {
    to:          '/floors',
    label:       'Floors (Lantai)',
    description: 'Kelola data lantai gedung',
    icon:        'pi pi-building',
    iconColor:   'text-blue-600',
    iconBg:      'bg-blue-100',
  },
  {
    to:          '/rooms',
    label:       'Rooms (Ruangan)',
    description: 'Kelola data ruangan per lantai',
    icon:        'pi pi-table',
    iconColor:   'text-emerald-600',
    iconBg:      'bg-emerald-100',
  },
  {
    to:          '/cabinets',
    label:       'Cabinets (Lemari)',
    description: 'Kelola data lemari arsip',
    icon:        'pi pi-server',
    iconColor:   'text-orange-600',
    iconBg:      'bg-orange-100',
  },
  {
    to:          '/cabinet-slots',
    label:       'Cabinet Slots',
    description: 'Kelola slot penyimpanan dokumen',
    icon:        'pi pi-inbox',
    iconColor:   'text-purple-600',
    iconBg:      'bg-purple-100',
  },
]
</script>
