<template>
  <main class="profile-page">
    <ProfileHeader :profile="profile" />

    <div class="profile-content">
      <div class="profile-title-row">
        <div>
          <p class="profile-eyebrow">YOUR ACCOUNT</p>
          <h1>Profile</h1>
          <p class="profile-subtitle">Manage your personal and family account.</p>
        </div>
        <span class="profile-date profile-content-date">
          <span aria-hidden="true"></span>
          {{ todayLabel }}
        </span>
      </div>

      <p v-if="notice" class="profile-notice" role="status" aria-live="polite">
        {{ notice }}
      </p>

      <div v-if="isLoading" class="profile-state" role="status">
        Loading your profile...
      </div>

      <div v-else-if="loadError" class="profile-state profile-state-error" role="alert">
        <p>{{ loadError }}</p>
        <button type="button" @click="loadProfile">Try again</button>
      </div>

      <div v-else class="profile-grid">
        <div class="profile-left-column">
          <section class="profile-card profile-person-card" aria-label="Personal profile">
            <div class="profile-person-top">
              <span class="profile-large-avatar">{{ initials(profile?.name) }}</span>
              <div class="profile-person-name">
                <h2>{{ profile?.name }}</h2>
                <span class="profile-parent-badge">Parent</span>
              </div>
              <button
                class="profile-action-button"
                type="button"
                @click="showUnavailable('Profile editing')"
              >
                <v-icon icon="mdi-pencil-outline" />
                Edit Profile
              </button>
            </div>

            <div class="profile-contact-list">
              <p>
                <v-icon icon="mdi-email-outline" />
                <span>{{ profile?.email || 'No email on file' }}</span>
              </p>
              <p>
                <v-icon icon="mdi-phone-outline" />
                <span>{{ profile?.phone || 'No phone number on file' }}</span>
              </p>
            </div>
          </section>

          <section class="profile-card profile-summary-card">
            <span class="profile-round-icon"><v-icon icon="mdi-home-variant-outline" /></span>
            <div class="profile-summary-copy">
              <h2>My Family</h2>
              <p>{{ profile?.family?.family_name || 'Family name unavailable' }}</p>
              <small>{{ children.length }} {{ children.length === 1 ? 'child' : 'children' }}</small>
            </div>
            <RouterLink class="profile-summary-action" to="/family">
              Manage Family
            </RouterLink>
            <v-icon class="profile-chevron" icon="mdi-chevron-right" />
          </section>

          <section class="profile-card profile-summary-card rewards-card">
            <span class="profile-round-icon rewards-icon">
              <v-icon icon="mdi-diamond-stone" />
            </span>
            <div class="profile-summary-copy">
              <h2>Points &amp; Rewards</h2>
              <p>TP → ETB Rate</p>
              <strong>{{ conversionRateLabel }}</strong>
            </div>
            <button type="button" @click="showUnavailable('Rewards settings')">
              Manage Settings
            </button>
            <v-icon class="profile-chevron" icon="mdi-chevron-right" />
          </section>
        </div>

        <div class="profile-right-column">
          <section class="profile-card profile-settings-card">
            <header class="profile-section-heading">
              <h2>Account Settings</h2>
              <p>Manage your personal information and account preferences.</p>
            </header>

            <button
              v-for="setting in accountSettings"
              :key="setting.label"
              class="profile-setting-row"
              type="button"
              @click="showUnavailable(setting.label)"
            >
              <span class="profile-round-icon"><v-icon :icon="setting.icon" /></span>
              <span class="profile-setting-copy">
                <strong>{{ setting.label }}</strong>
                <small>{{ setting.description }}</small>
              </span>
              <v-icon class="profile-chevron" icon="mdi-chevron-right" />
            </button>
          </section>

          <section class="profile-card profile-settings-card profile-support-card">
            <header class="profile-section-heading">
              <h2>Support &amp; Account Actions</h2>
              <p>Help and options for your account.</p>
            </header>

            <button
              v-for="action in supportActions"
              :key="action.label"
              class="profile-setting-row"
              :class="{ 'profile-delete-row': action.danger }"
              type="button"
              @click="action.action === 'logout'
                ? signOut()
                : showUnavailable(action.label)"
            >
              <span class="profile-round-icon" :class="{ 'profile-danger-icon': action.danger }">
                <v-icon :icon="action.icon" />
              </span>
              <span class="profile-setting-copy">
                <strong>{{ action.label }}</strong>
                <small>{{ action.description }}</small>
              </span>
              <v-icon class="profile-chevron" icon="mdi-chevron-right" />
            </button>
          </section>
        </div>
      </div>
    </div>

    <footer class="profile-footer">
      <span><strong>kids tp</strong> &nbsp;•&nbsp; Ethiopian Family Habit &amp; Trust Management</span>
      <span>Family tools for building trust and responsibility</span>
    </footer>

    <nav class="profile-mobile-nav" aria-label="Main navigation">
      <RouterLink to="/dashboard">
        <v-icon icon="mdi-view-dashboard-outline" />
        <span>Home</span>
      </RouterLink>
      <RouterLink to="/dashboard#children">
        <v-icon icon="mdi-account-group-outline" />
        <span>Children</span>
      </RouterLink>
      <span class="profile-nav-unavailable" aria-disabled="true">
        <v-icon icon="mdi-format-list-checks" />
        <span>Activities</span>
      </span>
      <span class="profile-nav-unavailable" aria-disabled="true">
        <v-icon icon="mdi-history" />
        <span>History</span>
      </span>
      <RouterLink class="profile-nav-current" to="/profile">
        <v-icon icon="mdi-account-outline" />
        <span>Profile</span>
      </RouterLink>
    </nav>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import ProfileHeader from '../components/ProfileHeader.vue'
import {
  ApiError,
  clearAuthToken,
  fetchChildren,
  getCurrentUser,
} from '../services/auth.js'

const accountSettings = [
  {
    icon: 'mdi-account-outline',
    label: 'Personal Information',
    description: 'View and manage your parent account details.',
  },
  {
    icon: 'mdi-lock-outline',
    label: 'Change Password',
    description: 'Update the password used to sign in.',
  },
  {
    icon: 'mdi-account-group-outline',
    label: 'Family Settings',
    description: 'Manage family details and shared access.',
  },
  {
    icon: 'mdi-bell-outline',
    label: 'Notifications',
    description: 'Choose how you receive account updates.',
  },
  {
    icon: 'mdi-shield-outline',
    label: 'Privacy & Security',
    description: 'Review your account security options.',
  },
]

const supportActions = [
  {
    icon: 'mdi-help-circle-outline',
    label: 'Help & Support',
    description: 'Get help with your account.',
  },
  {
    icon: 'mdi-information-outline',
    label: 'About kids tp',
    description: 'Learn more about the app.',
  },
  {
    icon: 'mdi-logout',
    label: 'Log Out',
    description: 'Sign out of this account on this device.',
    action: 'logout',
  },
  {
    icon: 'mdi-delete-outline',
    label: 'Delete Account',
    description: 'Account deletion is not currently available.',
    danger: true,
  },
]

const router = useRouter()
const profile = ref(null)
const children = ref([])
const isLoading = ref(true)
const loadError = ref('')
const notice = ref('')
const todayLabel = new Intl.DateTimeFormat(undefined, {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric',
}).format(new Date())
const conversionRateLabel = computed(() => {
  const rate = Number(profile.value?.family?.conversion_rate)
  return Number.isFinite(rate) && rate > 0
    ? `1 TP = ${rate} ETB`
    : 'Not configured'
})

function initials(name) {
  return (name || 'P')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('')
}

function showUnavailable(feature) {
  notice.value = `${feature} is not available yet.`
}

async function loadProfile() {
  isLoading.value = true
  loadError.value = ''

  try {
    const [userResult, childrenResult] = await Promise.all([
      getCurrentUser(),
      fetchChildren(),
    ])

    if (!Array.isArray(childrenResult.children)) {
      throw new Error('The API returned an invalid children list.')
    }

    profile.value = userResult
    children.value = childrenResult.children
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    loadError.value = error instanceof Error
      ? error.message
      : 'Unable to load your profile.'
  } finally {
    isLoading.value = false
  }
}

async function signOut() {
  clearAuthToken()
  await router.replace({ name: 'home' })
}

onMounted(loadProfile)
</script>

<style scoped src="../assets/styles/profile.css"></style>
