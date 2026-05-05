<template>
  <div class="p-6">
    <h1 class="text-3xl font-bold mb-4">Welcome to ARSIG v3.0</h1>
    <p class="mb-4">This is the dashboard skeleton.</p>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
      <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow cursor-pointer hover:shadow-md transition" @click="$router.push('/floors')">
        <i class="pi pi-building text-3xl text-blue-500 mb-2"></i>
        <h2 class="text-xl font-bold">Floors</h2>
        <p class="text-gray-500">Manage building floors</p>
      </div>
      <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow cursor-pointer hover:shadow-md transition" @click="$router.push('/rooms')">
        <i class="pi pi-box text-3xl text-green-500 mb-2"></i>
        <h2 class="text-xl font-bold">Rooms</h2>
        <p class="text-gray-500">Manage rooms within floors</p>
      </div>
      <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow cursor-pointer hover:shadow-md transition" @click="$router.push('/cabinets')">
        <i class="pi pi-server text-3xl text-orange-500 mb-2"></i>
        <h2 class="text-xl font-bold">Cabinets</h2>
        <p class="text-gray-500">Manage cabinets in rooms</p>
      </div>
      <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow cursor-pointer hover:shadow-md transition" @click="$router.push('/cabinet-slots')">
        <i class="pi pi-inbox text-3xl text-purple-500 mb-2"></i>
        <h2 class="text-xl font-bold">Cabinet Slots</h2>
        <p class="text-gray-500">Manage specific storage slots</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()
</script>
