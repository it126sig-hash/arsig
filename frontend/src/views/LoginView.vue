<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../store/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')

const handleLogin = async () => {
  try {
    error.value = ''
    await auth.login({
      email: email.value,
      password: password.value
    })
    router.push('/')
  } catch (err) {
    error.value = err.response?.data?.message || 'Login failed'
  }
}
</script>

<template>
  <!-- Login Page — Full-screen glassmorphism design -->
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-blue-950 p-5">
    <!-- Decorative blobs -->
    <div class="absolute top-0 left-0 w-72 h-72 bg-blue-500 rounded-full opacity-10 blur-3xl -translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-indigo-500 rounded-full opacity-10 blur-3xl translate-x-1/2 translate-y-1/2 pointer-events-none"></div>

    <!-- Login Card -->
    <div class="relative w-full max-w-md bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-10 shadow-2xl">
      <!-- Logo -->
      <div class="flex flex-col items-center mb-8">
        <div class="w-14 h-14 bg-blue-500 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-blue-500/30">
          <i class="pi pi-server text-white text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Selamat Datang</h1>
        <p class="text-slate-400 text-sm mt-1">Masuk ke akun ARSIG Anda</p>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="flex flex-col gap-5">
        <!-- Email Field -->
        <div class="flex flex-col gap-2">
          <label for="login-email" class="text-sm font-medium text-slate-300">Email</label>
          <span class="p-input-icon-left w-full">
            <i class="pi pi-envelope" style="color: #94a3b8;" />
            <InputText
              id="login-email"
              v-model="email"
              type="email"
              placeholder="nama@arsig.id"
              required
              class="w-full"
              :pt="{
                root: { style: 'background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.15); color: white; border-radius: 12px; width: 100%;' }
              }"
            />
          </span>
        </div>

        <!-- Password Field -->
        <div class="flex flex-col gap-2">
          <label for="login-password" class="text-sm font-medium text-slate-300">Password</label>
          <Password
            id="login-password"
            v-model="password"
            placeholder="Masukkan password"
            :feedback="false"
            toggleMask
            inputClass="w-full"
            class="w-full"
            :pt="{
              root: { style: 'width: 100%;' },
              input: { style: 'background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.15); color: white; border-radius: 12px; width: 100%;' },
              panel: { style: 'display: none;' }
            }"
          />
        </div>

        <!-- Error Message -->
        <div v-if="error" class="flex items-center gap-2 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl px-4 py-3">
          <i class="pi pi-exclamation-circle flex-shrink-0"></i>
          <span>{{ error }}</span>
        </div>

        <!-- Submit Button -->
        <Button
          id="login-submit"
          type="submit"
          label="Masuk"
          icon="pi pi-sign-in"
          :loading="auth.loading"
          class="w-full mt-2"
          style="border-radius: 12px; padding: 0.875rem;"
        />
      </form>

      <!-- Footer note -->
      <p class="text-center text-xs text-slate-500 mt-8">
        ARSIG v3.0 &mdash; Sistem Manajemen Arsip Digital
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../store/auth'
import { useRouter } from 'vue-router'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'

const auth   = useAuthStore()
const router = useRouter()

const email    = ref('')
const password = ref('')
const error    = ref('')

const handleLogin = async () => {
  try {
    error.value = ''
    await auth.login({ email: email.value, password: password.value })
    router.push('/')
  } catch (err) {
    error.value = err.response?.data?.message || 'Login gagal. Periksa email dan password Anda.'
  }
}

.login-header h1 {
  color: white;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 8px;
}

.login-header p {
  color: #94a3b8;
  font-size: 0.875rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  color: #e2e8f0;
  font-size: 0.875rem;
  font-weight: 500;
}

.form-group input {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 12px 16px;
  color: white;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-group input:focus {
  outline: none;
  border-color: #3b82f6;
  background: rgba(255, 255, 255, 0.1);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.error-message {
  color: #f87171;
  font-size: 0.875rem;
  background: rgba(248, 113, 113, 0.1);
  border: 1px solid rgba(248, 113, 113, 0.2);
  padding: 12px;
  border-radius: 12px;
  text-align: center;
}

.login-button {
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 12px;
  padding: 14px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 12px;
}

.login-button:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.login-button:active:not(:disabled) {
  transform: translateY(0);
}

.login-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
