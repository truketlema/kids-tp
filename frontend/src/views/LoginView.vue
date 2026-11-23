<template>
  <main class="login-page">
    <PublicHeader
      prompt="New to Kids TP?"
      link-text="Register"
      link-to="/register"
    />

    <div class="login-layout">
      <section class="login-form-panel" aria-labelledby="login-title">
        <form
          class="login-form"
          :aria-busy="isSubmitting"
          @submit.prevent="submitForm"
        >
          <p class="login-badge">PARENT LOGIN</p>
          <h1 id="login-title">Welcome Back</h1>

          <label class="login-field">
            <span>Parent name *</span>
            <input
              v-model.trim="form.name"
              type="text"
              name="name"
              autocomplete="name"
              placeholder="Your full name"
              required
            />
          </label>

          <label class="login-field">
            <span>Email address *</span>
            <input
              v-model.trim="form.email"
              type="email"
              name="email"
              autocomplete="email"
              placeholder="you@example.com"
              required
            />
          </label>

          <label class="login-field">
            <span>Password *</span>
            <input
              v-model="form.password"
              type="password"
              name="password"
              autocomplete="current-password"
              placeholder="Your password"
              required
            />
          </label>

          <label class="remember">
            <input v-model="form.remember" type="checkbox" name="remember" />
            <span>Remember me</span>
          </label>

          <button class="login-submit" type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? 'Signing in...' : 'Log in' }}
          </button>

          <p
            v-if="status"
            class="login-status"
            :class="{ 'login-status-error': statusType === 'error' }"
            :role="statusType === 'error' ? 'alert' : 'status'"
            aria-live="polite"
          >
            {{ status }}
          </p>

          <p class="login-footer">
            Don’t have an account?
            <RouterLink to="/register">Register</RouterLink>
          </p>
        </form>
      </section>
    </div>
  </main>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PublicHeader from '../components/PublicHeader.vue'
import { loginAccount } from '../services/auth.js'

const router = useRouter()

const form = reactive({
  name: '',
  email: '',
  password: '',
  remember: false,
})

const status = ref('')
const statusType = ref('success')
const isSubmitting = ref(false)

async function submitForm() {
  status.value = ''
  statusType.value = 'success'
  isSubmitting.value = true

  try {
    const result = await loginAccount({
      name: form.name,
      email: form.email,
      password: form.password,
    }, form.remember)

    status.value = result.message
    await router.replace({ name: 'dashboard' })
  } catch (error) {
    status.value = error instanceof Error
      ? error.message
      : 'Sign-in failed due to an unexpected error.'
    statusType.value = 'error'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped src="../assets/styles/login.css"></style>
