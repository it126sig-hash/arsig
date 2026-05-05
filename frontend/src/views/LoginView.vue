<template>
  <div class="flex items-center justify-center min-h-screen">
    <div class="p-8 surface-card border-round shadow-2 w-full max-w-md">
      <h2 class="text-2xl font-bold mb-6 text-center">Login to ARSIG</h2>
      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
          <label for="email">Email</label>
          <InputText id="email" v-model="email" />
        </div>
        <div class="flex flex-col gap-2">
          <label for="password">Password</label>
          <InputText id="password" v-model="password" type="password" />
        </div>
        <Button label="Login" @click="handleLogin" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const email = ref('')
const password = ref('')

const handleLogin = () => {
  console.log('Login clicked', email.value)
}
</script>
