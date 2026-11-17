<template>
  <main class="dashboard-page" :class="{ 'adding-child': isAddChildOpen }">
    <header class="dashboard-header">
      <div class="dashboard-header-inner">
        <RouterLink class="dashboard-brand" to="/dashboard" aria-label="Kids TP dashboard">
          <img src="/Logo.png" alt="" />
          <span>kids tp</span>
        </RouterLink>

        <nav class="desktop-nav" aria-label="Main navigation">
          <RouterLink class="nav-current" to="/dashboard">Dashboard</RouterLink>
          <a href="#children">Children</a>
          <span aria-disabled="true">Activities</span>
          <span aria-disabled="true">History</span>
        </nav>

        <div class="desktop-account">
          <span class="date-chip">
            <span class="date-dot" aria-hidden="true"></span>
            {{ todayLabel }}
          </span>

          <span class="notification-icon" aria-label="Notifications">
            <v-icon icon="mdi-bell-outline" />
          </span>

          <RouterLink class="profile-summary" to="/profile" aria-label="Open profile">
            <span class="profile-avatar">{{ initials(profile?.name) }}</span>
            <span class="profile-copy">
              <strong>{{ profile?.name || 'Parent' }}</strong>
              <small>{{ profile?.family?.family_name || 'Family account' }}</small>
            </span>
          </RouterLink>

          <button class="sign-out" type="button" @click="signOut">
            Sign out
          </button>
        </div>

        <div class="mobile-brand">
          <RouterLink
            class="dashboard-brand"
            to="/dashboard"
            aria-label="Kids TP dashboard"
          >
            <img src="/Logo.png" alt="" />
            <span>kids tp</span>
          </RouterLink>
          <span class="notification-icon" aria-label="Notifications">
            <v-icon icon="mdi-bell-outline" />
          </span>
        </div>
      </div>
    </header>

    <div class="dashboard-content">
      <span class="date-chip page-date-chip">
        <span class="date-dot" aria-hidden="true"></span>
        {{ todayLabel }}
      </span>

      <section id="children" class="children-section" aria-labelledby="children-title">
        <div class="section-heading">
          <div>
            <h1 id="children-title">Your children</h1>
            <p>Manage your family’s children and their progress.</p>
          </div>

          <span class="manage-label">Manage family registry <span aria-hidden="true">→</span></span>
        </div>

        <div v-if="isLoading" class="dashboard-message" role="status">
          Loading your family...
        </div>

        <div v-else-if="loadError" class="dashboard-message error-message" role="alert">
          <p>{{ loadError }}</p>
          <button type="button" @click="loadDashboard">Try again</button>
        </div>

        <template v-else-if="children.length">
          <div class="children-list">
            <RouterLink
              v-for="child in children"
              :key="child.id"
              class="child-card"
              :to="{ name: 'child-profile', params: { childId: child.id } }"
            >
              <span class="child-avatar">
                <v-icon
                  v-if="avatarIcon(child.avatar)"
                  :icon="avatarIcon(child.avatar)"
                />
                <template v-else>{{ initials(child.name) }}</template>
              </span>

              <span class="child-copy">
                <strong>{{ child.name }}</strong>
                <small class="child-age">
                  {{ childAge(child.date_of_birth) }}
                </small>
              </span>

              <span class="child-family-label">Family member</span>
            </RouterLink>
          </div>

          <button class="add-child-button" type="button" @click="openAddChild">
            <v-icon icon="mdi-plus-circle" />
            Add a child
          </button>
        </template>

        <div v-else class="empty-children">
          <h2>No children added yet</h2>
          <p>When you add children to your family, they’ll appear here.</p>
          <button class="add-child-button" type="button" @click="openAddChild">
            <v-icon icon="mdi-plus-circle" />
            Add your first child
          </button>
        </div>
      </section>

      <section
        v-if="!isLoading && !loadError && children.length"
        id="family-overview"
        class="family-overview"
        aria-labelledby="overview-title"
      >
        <h2 id="overview-title">Family Stewardship Overview</h2>
        <div class="overview-grid">
          <article class="overview-card">
            <div class="overview-card-heading">
              <span class="overview-kicker">FAMILY MEMBERS</span>
              <span class="overview-icon" aria-hidden="true">
                <v-icon icon="mdi-account-group-outline" />
              </span>
            </div>
            <strong>{{ children.length }}</strong>
            <p>Children in your family registry</p>
            <span class="overview-meter" aria-hidden="true"></span>
          </article>

          <article class="overview-card">
            <div class="overview-card-heading">
              <span class="overview-kicker">TRUST POINTS</span>
              <span class="overview-icon" aria-hidden="true">
                <v-icon icon="mdi-star-outline" />
              </span>
            </div>
            <strong>Getting started</strong>
            <p>Points will appear as you record family activity.</p>
            <span class="overview-meter" aria-hidden="true"></span>
          </article>

          <article class="overview-card">
            <div class="overview-card-heading">
              <span class="overview-kicker">FAMILY ACCOUNT</span>
              <span class="overview-icon" aria-hidden="true">
                <v-icon icon="mdi-shield-check-outline" />
              </span>
            </div>
            <strong>Ready</strong>
            <p>{{ profile?.family?.family_name || 'Your family' }} is set up.</p>
            <span class="overview-meter" aria-hidden="true"></span>
          </article>
        </div>
      </section>
    </div>

    <div class="dashboard-print-decoration" aria-hidden="true">
      <img class="dashboard-print dashboard-print-one" src="/print.png" alt="" />
      <img class="dashboard-print dashboard-print-two" src="/print.png" alt="" />
    </div>

    <nav class="mobile-bottom-nav" aria-label="Dashboard navigation">
      <a class="bottom-nav-current" href="#children">
        <v-icon icon="mdi-view-dashboard-outline" />
        <span>Home</span>
      </a>
      <a href="#children">
        <v-icon icon="mdi-account-group-outline" />
        <span>Children</span>
      </a>
      <span class="nav-unavailable" aria-disabled="true">
        <v-icon icon="mdi-format-list-checks" />
        <span>Activities</span>
      </span>
      <span class="nav-unavailable" aria-disabled="true">
        <v-icon icon="mdi-history" />
        <span>History</span>
      </span>
      <RouterLink to="/profile">
        <v-icon icon="mdi-account-outline" />
        <span>Profile</span>
      </RouterLink>
    </nav>

    <div
      v-if="isAddChildOpen"
      class="dialog-backdrop"
      @click.self="closeAddChild"
    >
      <section
        class="child-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-child-title"
      >
        <p class="dialog-kicker">NEW MEMBER</p>

        <header class="dialog-heading">
          <div>
            <h2 id="add-child-title">Add a child</h2>
            <p>
              Create a profile to start tracking habits, chore streaks, and
              points stewardship.
            </p>
          </div>
          <button
            class="dialog-close"
            type="button"
            aria-label="Close"
            :disabled="isAddingChild"
            @click="closeAddChild"
          >
            <v-icon icon="mdi-close" />
          </button>
        </header>

        <div class="dialog-divider"></div>

        <form class="child-form" @submit.prevent="submitAddChild">
          <label>
            <span>Child’s full name <b>*</b></span>
            <input
              v-model.trim="childForm.name"
              name="childName"
              placeholder="Child’s full name"
              autocomplete="off"
              maxlength="255"
              required
            />
            <small>Full legal or home name for trust records &amp; activity logs.</small>
          </label>

          <label>
            <span>Date of birth <b>*</b></span>
            <input
              v-model="childForm.dateOfBirth"
              name="dateOfBirth"
              type="date"
              :max="todayInputValue"
              required
            />
            <small>The child’s age will be calculated automatically.</small>
          </label>

          <div class="avatar-field">
            <span class="avatar-field-label">Profile picture (optional)</span>
            <div class="avatar-picker">
              <span
                class="avatar-preview"
                :class="selectedAvatarOption?.colorClass"
                aria-hidden="true"
              >
                <v-icon
                  :icon="selectedAvatarOption?.icon || 'mdi-account-outline'"
                />
              </span>
              <div class="avatar-description">
                <strong>
                  {{ selectedAvatarOption?.label || 'Choose avatar' }}
                </strong>
                <small>Use initials or select an illustration</small>
              </div>
              <button
                class="avatar-browse"
                type="button"
                :aria-expanded="isAvatarPickerOpen"
                @click="isAvatarPickerOpen = !isAvatarPickerOpen"
              >
                <v-icon icon="mdi-image-outline" />
                Browse / Select
              </button>
            </div>

            <div v-if="isAvatarPickerOpen" class="avatar-options">
              <button
                v-for="avatar in avatarOptions"
                :key="avatar.id"
                class="avatar-option"
                type="button"
                :aria-pressed="childForm.avatar === avatar.id"
                @click="selectAvatar(avatar.id)"
              >
                <span class="avatar-option-image" :class="avatar.colorClass">
                  <v-icon :icon="avatar.icon" />
                </span>
                <span>{{ avatar.label }}</span>
              </button>
              <button
                class="avatar-option avatar-initials-option"
                type="button"
                :aria-pressed="!childForm.avatar"
                @click="selectAvatar('')"
              >
                <span class="avatar-option-image avatar-initials">
                  {{ initials(childForm.name) }}
                </span>
                <span>Use initials</span>
              </button>
            </div>
          </div>

          <div class="dialog-divider"></div>

          <p v-if="childFormError" class="dialog-error" role="alert">
            {{ childFormError }}
          </p>

          <div class="dialog-actions">
            <button class="dialog-submit" type="submit" :disabled="isAddingChild">
              {{ isAddingChild ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  addChild,
  ApiError,
  clearAuthToken,
  fetchChildren,
  getCurrentUser,
} from '../services/auth.js'

const avatarOptions = [
  { id: 'bear', label: 'Bear', icon: 'mdi-teddy-bear', colorClass: 'avatar-0' },
  { id: 'cat', label: 'Cat', icon: 'mdi-cat', colorClass: 'avatar-1' },
  { id: 'dog', label: 'Dog', icon: 'mdi-dog', colorClass: 'avatar-2' },
  { id: 'flower', label: 'Flower', icon: 'mdi-flower-tulip', colorClass: 'avatar-0' },
  { id: 'rocket', label: 'Rocket', icon: 'mdi-rocket-launch-outline', colorClass: 'avatar-1' },
  { id: 'star', label: 'Star', icon: 'mdi-star-outline', colorClass: 'avatar-2' },
]
const avatarIcons = Object.fromEntries(
  avatarOptions.map(({ id, icon }) => [id, icon]),
)

const router = useRouter()
const profile = ref(null)
const children = ref([])
const isLoading = ref(true)
const loadError = ref('')
const isAddChildOpen = ref(false)
const isAddingChild = ref(false)
const isAvatarPickerOpen = ref(false)
const childFormError = ref('')

const childForm = reactive({
  name: '',
  dateOfBirth: '',
  avatar: '',
})
const selectedAvatarOption = computed(() =>
  avatarOptions.find(({ id }) => id === childForm.avatar),
)

const today = new Date()
const todayLabel = new Intl.DateTimeFormat(undefined, {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric',
}).format(today)
const todayInputValue = [
  today.getFullYear(),
  String(today.getMonth() + 1).padStart(2, '0'),
  String(today.getDate()).padStart(2, '0'),
].join('-')

function initials(name) {
  return (name || 'P')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('')
}

function avatarIcon(avatar) {
  return avatarIcons[avatar] || ''
}

function childAge(dateOfBirth) {
  const birthDate = new Date(`${dateOfBirth}T00:00:00`)

  if (Number.isNaN(birthDate.getTime())) {
    return 'Age unavailable'
  }

  const now = new Date()
  let age = now.getFullYear() - birthDate.getFullYear()
  const birthdayHasPassed =
    now.getMonth() > birthDate.getMonth() ||
    (now.getMonth() === birthDate.getMonth() && now.getDate() >= birthDate.getDate())

  if (!birthdayHasPassed) {
    age -= 1
  }

  if (age < 0) {
    return 'Age unavailable'
  }

  return `Age ${age}`
}

function getErrorMessage(error, fallback) {
  return error instanceof Error ? error.message : fallback
}

async function handleExpiredSession(error) {
  if (!(error instanceof ApiError) || error.status !== 401) {
    return false
  }

  clearAuthToken()
  await router.replace({ name: 'login' })
  return true
}

async function loadDashboard() {
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
    if (await handleExpiredSession(error)) {
      return
    }

    loadError.value = getErrorMessage(error, 'Unable to load your family dashboard.')
  } finally {
    isLoading.value = false
  }
}

function openAddChild() {
  childFormError.value = ''
  isAddChildOpen.value = true
}

function closeAddChild() {
  if (isAddingChild.value) {
    return
  }

  isAddChildOpen.value = false
  childFormError.value = ''
  childForm.name = ''
  childForm.dateOfBirth = ''
  childForm.avatar = ''
  isAvatarPickerOpen.value = false
}

function selectAvatar(avatar) {
  childForm.avatar = avatar
  isAvatarPickerOpen.value = false
}

async function submitAddChild() {
  childFormError.value = ''
  isAddingChild.value = true

  try {
    const result = await addChild({
      name: childForm.name,
      date_of_birth: childForm.dateOfBirth,
      avatar: childForm.avatar || null,
    })

    if (!result.child) {
      throw new Error('The API did not return the new child.')
    }

    children.value = [...children.value, result.child]
      .sort((first, second) => first.name.localeCompare(second.name))
    isAddChildOpen.value = false
    childForm.name = ''
    childForm.dateOfBirth = ''
    childForm.avatar = ''
    isAvatarPickerOpen.value = false
  } catch (error) {
    if (await handleExpiredSession(error)) {
      return
    }

    childFormError.value = getErrorMessage(error, 'Unable to add this child.')
  } finally {
    isAddingChild.value = false
  }
}

async function signOut() {
  clearAuthToken()
  await router.replace({ name: 'login' })
}

onMounted(loadDashboard)
</script>

<style scoped src="../assets/styles/dashboard.css"></style>
