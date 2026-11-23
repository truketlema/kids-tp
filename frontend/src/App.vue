<template>
  <v-app>
    <ProfileHeader
      v-if="showAccountHeader"
      :profile="profile"
      :active="activeNav"
    />
    <p v-if="profileError" class="account-profile-error" role="alert">
      {{ profileError }}
    </p>
    <RouterView v-slot="{ Component, route: viewRoute }">
      <KeepAlive :key="viewCacheVersion" :max="12">
        <component
          v-if="viewRoute.meta.keepAlive"
          :is="Component"
          :key="viewRoute.fullPath"
        />
      </KeepAlive>
      <component
        v-if="!viewRoute.meta.keepAlive"
        :is="Component"
        :key="viewRoute.fullPath"
      />
    </RouterView>
  </v-app>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTheme as useVuetifyTheme } from 'vuetify'
import ProfileHeader from './components/ProfileHeader.vue'
import { useTheme } from './composables/useTheme.js'
import { ApiError, clearAuthToken, getCurrentUser } from './services/auth.js'

const route = useRoute()
const router = useRouter()
const vuetifyTheme = useVuetifyTheme()
const { theme } = useTheme()

watch(theme, (value) => {
  vuetifyTheme.global.name.value = value === 'dark' ? 'kidsTpDark' : 'kidsTpLight'
}, { immediate: true })
const profile = ref(null)
const profileError = ref('')
const viewCacheVersion = ref(0)
const showAccountHeader = computed(() => route.meta.requiresAuth)
const activeNav = computed(() => ({
  dashboard: 'dashboard',
  family: 'family',
  activities: 'activities',
  'category-activities': 'activities',
  'child-activities': 'activities',
  'child-points': 'activities',
  history: 'history',
})[route.name] || '')

let profileRequest = 0

function updateHeaderProfile(event) {
  if (event.detail) {
    profile.value = event.detail
  }
}

function clearCachedViews() {
  viewCacheVersion.value += 1
}

onMounted(() => {
  window.addEventListener('profile-updated', updateHeaderProfile)
  window.addEventListener('kids-tp:auth-cleared', clearCachedViews)
})
onBeforeUnmount(() => {
  window.removeEventListener('profile-updated', updateHeaderProfile)
  window.removeEventListener('kids-tp:auth-cleared', clearCachedViews)
})

watch(showAccountHeader, async (shouldShow) => {
  const request = ++profileRequest
  profileError.value = ''

  if (!shouldShow) {
    profile.value = null
    return
  }

  try {
    const user = await getCurrentUser()
    if (request === profileRequest) profile.value = user
  } catch (error) {
    if (request !== profileRequest) return
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }
    profileError.value = error instanceof Error
      ? error.message
      : 'Unable to load your account details.'
  }
}, { immediate: true })
</script>

<style>
.account-profile-error {
  margin: 0;
  padding: 8px 20px;
  background: var(--color-danger-soft);
  color: var(--color-danger);
  font-size: 13px;
  text-align: center;
}

.v-application .account-page-main {
  min-height: calc(100vh - 74px);
  min-height: calc(100dvh - 74px);
}

@media (max-width: 760px) {
  .v-application .account-page-main {
    min-height: calc(100vh - 58px);
    min-height: calc(100dvh - 58px);
  }
}
</style>