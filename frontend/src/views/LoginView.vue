<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../store/auth'
import { useRouter } from 'vue-router'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import logoUrl from '@/assets/logo.svg'
import heroUrl from '@/assets/hero.png'

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
    error.value = err.response?.data?.message || 'Login gagal. Periksa email dan password Anda.'
  }
}
</script>

<template>
  <main class="login-page">
    <section class="login-shell" aria-label="ARSIG Login">
      <div class="brand-panel">
        <div class="brand-panel__top">
          <img :src="logoUrl" alt="ARSIG" class="brand-logo" />
          <div class="brand-kicker">Sanggar Indah Group</div>
        </div>

        <div class="brand-visual" aria-hidden="true">
          <div class="archive-stack">
            <div class="archive-sheet archive-sheet--back"></div>
            <div class="archive-sheet archive-sheet--middle"></div>
            <div class="archive-sheet archive-sheet--front">
              <span></span>
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
          <img :src="heroUrl" alt="" class="hero-layer" />
        </div>

        <div class="brand-panel__bottom">
          <p>ARSIG</p>
          <span>Archive Management System</span>
        </div>
      </div>

      <div class="form-panel">
        <div class="mobile-brand">
          <div class="mobile-brand__mark" aria-hidden="true">
            <i class="pi pi-file"></i>
          </div>
          <div>
            <strong>ARSIG</strong>
            <span>Sanggar Indah Group</span>
          </div>
        </div>

        <div class="form-heading">
          <span class="eyebrow">Login</span>
          <h1>Selamat Datang</h1>
          <p>Masuk ke akun ARSIG Anda.</p>
        </div>

        <form @submit.prevent="handleLogin" class="login-form">
          <div class="field-group">
            <label for="login-email">Email</label>
            <div class="control-wrap">
              <i class="pi pi-envelope" aria-hidden="true"></i>
              <InputText
                id="login-email"
                v-model="email"
                type="email"
                placeholder="nama@arsig.id"
                required
                autocomplete="email"
                class="login-control"
              />
            </div>
          </div>

          <div class="field-group">
            <label for="login-password">Password</label>
            <div class="control-wrap control-wrap--password">
              <i class="pi pi-lock" aria-hidden="true"></i>
              <Password
                id="login-password"
                v-model="password"
                placeholder="Masukkan password"
                :feedback="false"
                toggleMask
                autocomplete="current-password"
                inputClass="login-control login-control--password"
                class="password-control"
                :pt="{
                  root: { class: 'password-root' },
                  pcInput: { root: { class: 'login-control login-control--password' } },
                  input: { class: 'login-control login-control--password' },
                  panel: { style: 'display: none;' }
                }"
              />
            </div>
          </div>

          <div v-if="error" class="error-message" role="alert">
            <i class="pi pi-exclamation-circle" aria-hidden="true"></i>
            <span>{{ error }}</span>
          </div>

          <Button
            id="login-submit"
            type="submit"
            label="Masuk"
            icon="pi pi-arrow-right"
            iconPos="right"
            :loading="auth.loading"
            class="submit-button"
          />
        </form>

        <p class="form-footnote">ARSIG v3.0</p>
      </div>
    </section>
  </main>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background:
    linear-gradient(135deg, rgba(14, 165, 233, 0.12), transparent 34%),
    linear-gradient(315deg, rgba(16, 185, 129, 0.1), transparent 32%),
    #f6f8fb;
}

.login-shell {
  width: min(1120px, 100%);
  min-width: 0;
  min-height: min(720px, calc(100vh - 48px));
  display: grid;
  grid-template-columns: minmax(0, 1.08fr) minmax(400px, 0.92fr);
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 28px;
  background: #ffffff;
  box-shadow: 0 28px 90px rgba(15, 23, 42, 0.16);
}

.brand-panel {
  position: relative;
  display: flex;
  min-height: 100%;
  flex-direction: column;
  justify-content: space-between;
  padding: 42px;
  overflow: hidden;
  color: #f8fafc;
  background:
    radial-gradient(circle at 22% 16%, rgba(59, 130, 246, 0.28), transparent 28%),
    linear-gradient(150deg, #0f172a 0%, #164e63 54%, #065f46 100%);
}

.brand-panel::before {
  position: absolute;
  inset: 0;
  content: "";
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.075) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.075) 1px, transparent 1px);
  background-size: 44px 44px;
  mask-image: linear-gradient(135deg, rgba(0, 0, 0, 0.88), transparent 82%);
  pointer-events: none;
}

.brand-panel__top,
.brand-panel__bottom,
.brand-visual {
  position: relative;
  z-index: 1;
}

.brand-panel__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
}

.brand-logo {
  width: min(220px, 46vw);
  height: auto;
  filter: drop-shadow(0 18px 28px rgba(0, 0, 0, 0.28));
}

.brand-kicker {
  max-width: 180px;
  padding-top: 8px;
  color: rgba(226, 232, 240, 0.86);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  line-height: 1.5;
  text-align: right;
  text-transform: uppercase;
}

.brand-visual {
  display: grid;
  min-height: 320px;
  place-items: center;
}

.archive-stack {
  position: relative;
  width: min(340px, 62%);
  aspect-ratio: 1 / 1;
  transform: rotate(-8deg);
}

.archive-sheet {
  position: absolute;
  width: 78%;
  height: 58%;
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 18px;
  box-shadow: 0 24px 54px rgba(2, 6, 23, 0.28);
}

.archive-sheet--back {
  top: 8%;
  left: 5%;
  background: rgba(148, 163, 184, 0.3);
}

.archive-sheet--middle {
  top: 21%;
  left: 15%;
  background: rgba(14, 165, 233, 0.22);
}

.archive-sheet--front {
  top: 34%;
  left: 25%;
  display: flex;
  flex-direction: column;
  gap: 13px;
  padding: 32px 34px;
  background: rgba(248, 250, 252, 0.96);
}

.archive-sheet--front span {
  height: 9px;
  border-radius: 999px;
  background: #cbd5e1;
}

.archive-sheet--front span:first-child {
  width: 46%;
  background: #2563eb;
}

.archive-sheet--front span:nth-child(2) {
  width: 74%;
}

.archive-sheet--front span:nth-child(3) {
  width: 58%;
}

.archive-sheet--front span:nth-child(4) {
  width: 68%;
}

.hero-layer {
  position: absolute;
  right: 8%;
  bottom: 4%;
  width: min(260px, 44%);
  opacity: 0.76;
  filter: drop-shadow(0 24px 40px rgba(30, 41, 59, 0.45));
}

.brand-panel__bottom p {
  margin: 0;
  color: #ffffff;
  font-size: clamp(2.7rem, 6vw, 5rem);
  font-weight: 800;
  line-height: 0.9;
  letter-spacing: 0;
}

.brand-panel__bottom span {
  display: block;
  margin-top: 14px;
  color: rgba(226, 232, 240, 0.78);
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
}

.form-panel {
  display: flex;
  min-width: 0;
  flex-direction: column;
  justify-content: center;
  padding: clamp(32px, 5vw, 64px);
  background:
    linear-gradient(180deg, rgba(248, 250, 252, 0.8), rgba(255, 255, 255, 0) 34%),
    #ffffff;
}

.mobile-brand {
  display: none;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
}

.mobile-brand__mark {
  display: grid;
  width: 42px;
  height: 42px;
  place-items: center;
  border-radius: 14px;
  color: #ffffff;
  background: linear-gradient(135deg, #1d4ed8, #0891b2);
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.18);
}

.mobile-brand strong,
.mobile-brand span {
  display: block;
}

.mobile-brand strong {
  color: #0f172a;
  font-size: 1rem;
  font-weight: 900;
  letter-spacing: 0.08em;
}

.mobile-brand span {
  margin-top: 2px;
  color: #64748b;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.form-heading {
  margin-bottom: 34px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border: 1px solid #dbeafe;
  border-radius: 999px;
  color: #1d4ed8;
  background: #eff6ff;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.form-heading h1 {
  margin: 18px 0 8px;
  color: #0f172a;
  font-size: clamp(2rem, 4vw, 3.1rem);
  font-weight: 800;
  line-height: 1;
  letter-spacing: 0;
}

.form-heading p {
  margin: 0;
  color: #64748b;
  font-size: 1rem;
  line-height: 1.6;
}

.login-form {
  display: flex;
  width: 100%;
  min-width: 0;
  flex-direction: column;
  gap: 20px;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 9px;
}

.field-group label {
  color: #334155;
  font-size: 0.9rem;
  font-weight: 700;
}

.control-wrap {
  position: relative;
  min-width: 0;
}

.control-wrap > .pi {
  position: absolute;
  top: 50%;
  left: 16px;
  z-index: 2;
  color: #64748b;
  transform: translateY(-50%);
}

.control-wrap--password :deep(.p-password),
.control-wrap--password :deep(.p-password-input),
.control-wrap--password :deep(.p-inputtext) {
  width: 100%;
}

.login-control,
.control-wrap :deep(.login-control) {
  box-sizing: border-box;
  width: 100%;
  max-width: 100%;
  min-height: 54px;
  padding: 0 48px;
  border: 1px solid #dbe3ee;
  border-radius: 16px;
  color: #0f172a;
  background: #f8fafc;
  font-size: 0.96rem;
  transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease;
}

.login-control::placeholder,
.control-wrap :deep(.login-control::placeholder) {
  color: #94a3b8;
}

.login-control:enabled:hover,
.control-wrap :deep(.login-control:enabled:hover) {
  border-color: #93c5fd;
  background: #ffffff;
}

.login-control:enabled:focus,
.control-wrap :deep(.login-control:enabled:focus) {
  border-color: #2563eb;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.control-wrap--password :deep(.p-password-toggle-mask) {
  right: 15px;
  color: #64748b;
}

.error-message {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 13px 14px;
  border: 1px solid #fecdd3;
  border-radius: 16px;
  color: #be123c;
  background: #fff1f2;
  font-size: 0.9rem;
  line-height: 1.45;
}

.error-message .pi {
  margin-top: 2px;
}

.submit-button {
  width: 100%;
  min-height: 54px;
  margin-top: 4px;
  border: 0;
  border-radius: 16px;
  background: linear-gradient(135deg, #2563eb, #0891b2);
  font-weight: 800;
  box-shadow: 0 16px 28px rgba(37, 99, 235, 0.22);
}

.submit-button:enabled:hover {
  background: linear-gradient(135deg, #1d4ed8, #0e7490);
  box-shadow: 0 18px 34px rgba(37, 99, 235, 0.28);
}

.form-footnote {
  margin: 28px 0 0;
  color: #94a3b8;
  font-size: 0.82rem;
  font-weight: 600;
  text-align: center;
}

@media (max-width: 900px) {
  .login-page {
    padding: 16px;
  }

  .login-shell {
    width: 100%;
    max-width: 520px;
    min-height: auto;
    grid-template-columns: 1fr;
    border-radius: 24px;
  }

  .brand-panel {
    display: none;
  }

  .form-panel {
    min-height: calc(100vh - 32px);
    padding: 32px 22px;
  }

  .mobile-brand {
    display: flex;
  }
}

@media (max-width: 480px) {
  .login-page {
    padding: 0;
    background: #ffffff;
  }

  .login-shell {
    width: 100%;
    max-width: 100%;
    min-height: 100vh;
    border: 0;
    border-radius: 0;
    box-shadow: none;
  }

  .form-panel {
    min-height: 100vh;
  }

  .login-control,
  .control-wrap :deep(.login-control) {
    min-height: 52px;
    border-radius: 14px;
  }
}
</style>
