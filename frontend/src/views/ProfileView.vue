<template>
  <main class="profile-page account-page-main">

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
                @click="openProfileDialog('personal')"
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
            <RouterLink class="profile-summary-action" to="/family">
              Manage Settings
            </RouterLink>
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
              @click="openSetting(setting.key)"
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
              @click="runSupportAction(action.key)"
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

      <div
        v-if="activeDialog"
        class="profile-dialog-backdrop"
        @click.self="closeProfileDialog"
      >
        <section
          class="profile-dialog"
          :class="{ 'profile-delete-dialog': activeDialog === 'delete' }"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="`profile-dialog-${activeDialog}-title`"
          @keydown.esc.stop="closeProfileDialog"
        >
          <header class="profile-dialog-heading">
            <div>
              <span class="profile-dialog-kicker">ACCOUNT SETTINGS</span>
              <h2 :id="`profile-dialog-${activeDialog}-title`">{{ dialogTitle }}</h2>
              <p>{{ dialogDescription }}</p>
            </div>
            <button
              type="button"
              class="profile-dialog-close"
              aria-label="Close dialog"
              :disabled="isSavingProfile || isChangingPassword || isDeletingAccount"
              @click="closeProfileDialog"
            >
              <v-icon icon="mdi-close" />
            </button>
          </header>

          <form v-if="activeDialog === 'personal'" class="profile-dialog-form" @submit.prevent="saveProfile">
            <label>
              <span>Full name</span>
              <input v-model.trim="profileDraft.name" autocomplete="name" maxlength="255" required />
            </label>
            <label>
              <span>Phone number</span>
              <input v-model.trim="profileDraft.phone" autocomplete="tel" maxlength="20" required />
            </label>
            <label>
              <span>Email address</span>
              <input v-model.trim="profileDraft.email" type="email" autocomplete="email" maxlength="255" required />
              <small>A verification code will be sent if you change your email.</small>
            </label>
            <p v-if="dialogError" class="profile-dialog-error" role="alert">{{ dialogError }}</p>
            <div class="profile-dialog-actions">
              <button type="button" class="profile-secondary-button" :disabled="isSavingProfile" @click="closeProfileDialog">Cancel</button>
              <button type="submit" class="profile-primary-button" :disabled="isSavingProfile">
                {{ isSavingProfile ? 'Saving…' : 'Save changes' }}
              </button>
            </div>
          </form>

          <form
            v-if="activeDialog === 'personal' && pendingEmail"
            class="profile-dialog-form profile-email-verification"
            @submit.prevent="verifyPendingEmail"
          >
            <p>A six-digit verification code was sent to <strong>{{ pendingEmail }}</strong>.</p>
            <label>
              <span>Verification code</span>
              <input v-model.trim="emailVerificationCode" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required />
            </label>
            <p v-if="dialogError" class="profile-dialog-error" role="alert">{{ dialogError }}</p>
            <div class="profile-dialog-actions">
              <button type="submit" class="profile-primary-button" :disabled="isVerifyingEmail">
                {{ isVerifyingEmail ? 'Verifying…' : 'Verify email' }}
              </button>
            </div>
          </form>

          <form v-else-if="activeDialog === 'password'" class="profile-dialog-form" @submit.prevent="savePassword">
            <label>
              <span>Current password</span>
              <input v-model="passwordDraft.current_password" type="password" autocomplete="current-password" required />
            </label>
            <label>
              <span>New password</span>
              <input v-model="passwordDraft.password" type="password" autocomplete="new-password" minlength="8" required />
            </label>
            <label>
              <span>Confirm new password</span>
              <input v-model="passwordDraft.password_confirmation" type="password" autocomplete="new-password" minlength="8" required />
            </label>
            <p v-if="dialogError" class="profile-dialog-error" role="alert">{{ dialogError }}</p>
            <div class="profile-dialog-actions">
              <button type="button" class="profile-secondary-button" :disabled="isChangingPassword" @click="closeProfileDialog">Cancel</button>
              <button type="submit" class="profile-primary-button" :disabled="isChangingPassword">
                {{ isChangingPassword ? 'Updating…' : 'Change password' }}
              </button>
            </div>
          </form>

          <section v-else-if="activeDialog === 'notifications'" class="profile-notification-settings">
            <p v-if="isLoadingNotifications" class="profile-dialog-status" role="status">Loading notifications…</p>
            <p v-else-if="notificationError" class="profile-dialog-error" role="alert">{{ notificationError }}</p>
            <template v-else>
              <h3>Notify me about</h3>
              <label v-for="preference in notificationPreferenceItems" :key="preference.key" class="profile-notification-toggle">
                <span>
                  <strong>{{ preference.label }}</strong>
                  <small>{{ preference.description }}</small>
                </span>
                <input
                  type="checkbox"
                  :checked="notificationPreferences[preference.key]"
                  :disabled="savingNotificationKey === preference.key"
                  @change="saveNotificationPreference(preference.key, $event.target.checked)"
                />
              </label>
              <div class="profile-notification-list-heading">
                <h3>Recent notifications</h3>
                <button
                  v-if="unreadNotificationCount"
                  type="button"
                  :disabled="isMarkingAllRead"
                  @click="markAllRead"
                >
                  {{ isMarkingAllRead ? 'Updating…' : 'Mark all read' }}
                </button>
              </div>
              <ul v-if="notifications.length" class="profile-notification-list">
                <li v-for="notification in notifications" :key="notification.id" :class="{ unread: !notification.read_at }">
                  <span class="profile-notification-dot" aria-hidden="true"></span>
                  <span>
                    <strong>{{ notification.data.title }}</strong>
                    <small>{{ notification.data.message }}</small>
                    <time>{{ formatNotificationDate(notification.created_at) }}</time>
                  </span>
                  <button
                    v-if="!notification.read_at"
                    type="button"
                    :aria-label="`Mark ${notification.data.title} as read`"
                    @click="markNotificationAsRead(notification)"
                  >
                    Mark read
                  </button>
                </li>
              </ul>
              <p v-else class="profile-dialog-status">You don’t have any notifications yet.</p>
            </template>
          </section>

          <section v-else-if="activeDialog === 'security'" class="profile-info-panel">
            <p>Your account is protected by your sign-in password. You can update it at any time using your current password.</p>
            <p>Email changes are not applied until the new address is verified.</p>
            <div class="profile-dialog-actions">
              <button type="button" class="profile-primary-button" @click="openProfileDialog('password')">Change password</button>
              <button type="button" class="profile-secondary-button" @click="openProfileDialog('delete')">Delete account</button>
            </div>
          </section>

          <section v-else-if="activeDialog === 'help'" class="profile-info-panel">
            <h3>How can we help?</h3>
            <p>Use your Profile to update parent details and security, Family to manage shared settings and access, and each child’s profile to manage activities and Trust Points.</p>
            <p>Account support is not configured for this installation. Please contact your family administrator for help with shared access.</p>
          </section>

          <section v-else-if="activeDialog === 'about'" class="profile-info-panel">
            <h3>Kids TP</h3>
            <p>Kids TP helps families build habits, responsibility, and trust by assigning activities and recording Trust Points together.</p>
            <p>Family conversion settings show an estimate in ETB; Trust Points are managed within your family account.</p>
          </section>

          <form v-else-if="activeDialog === 'delete'" class="profile-dialog-form" @submit.prevent="confirmAccountDeletion">
            <p v-if="isLastParent" class="profile-delete-warning">
              You are the only parent in this family. Deleting your account will also permanently delete the family, child profiles, activities, and point history.
            </p>
            <p v-else class="profile-delete-warning">
              Your parent account will be removed. Other parents will keep access to the shared family, children, activities, and point history.
            </p>
            <label>
              <span>Confirm with your password</span>
              <input v-model="deletePassword" type="password" autocomplete="current-password" required />
            </label>
            <label class="profile-delete-acknowledgement">
              <input v-model="confirmAccountDeletionChecked" type="checkbox" required />
              <span>I understand this action cannot be undone.</span>
            </label>
            <p v-if="dialogError" class="profile-dialog-error" role="alert">{{ dialogError }}</p>
            <div class="profile-dialog-actions">
              <button type="button" class="profile-secondary-button" :disabled="isDeletingAccount" @click="closeProfileDialog">Cancel</button>
              <button
                type="submit"
                class="profile-danger-button"
                :disabled="isDeletingAccount || !deletePassword.trim() || !confirmAccountDeletionChecked"
              >
                {{ isDeletingAccount ? 'Deleting account…' : 'Delete account' }}
              </button>
            </div>
          </form>
        </section>
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
      <RouterLink to="/family">
        <v-icon icon="mdi-account-group-outline" />
        <span>Family</span>
      </RouterLink>
      <RouterLink to="/activities">
        <v-icon icon="mdi-format-list-checks" />
        <span>Activities</span>
      </RouterLink>
      <RouterLink to="/history">
        <v-icon icon="mdi-history" />
        <span>History</span>
      </RouterLink>
      <RouterLink class="profile-nav-current" to="/profile">
        <v-icon icon="mdi-account-outline" />
        <span>Profile</span>
      </RouterLink>
    </nav>
  </main>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageActivation } from '../composables/usePageActivation.js'
import {
  ApiError,
  changePassword,
  clearAuthToken,
  deleteAccount,
  fetchChildren,
  signOutAccount,
  fetchNotificationPreferences,
  fetchNotifications,
  getCurrentUser,
  markAllNotificationsRead,
  markNotificationRead,
  updateNotificationPreferences,
  updateProfile,
  verifyEmailChange,
} from '../services/auth.js'

const accountSettings = [
  {
    key: 'personal',
    icon: 'mdi-account-outline',
    label: 'Personal Information',
    description: 'View and manage your parent account details.',
  },
  {
    key: 'password',
    icon: 'mdi-lock-outline',
    label: 'Change Password',
    description: 'Update the password used to sign in.',
  },
  {
    key: 'family',
    icon: 'mdi-account-group-outline',
    label: 'Family Settings',
    description: 'Manage family details and shared access.',
  },
  {
    key: 'notifications',
    icon: 'mdi-bell-outline',
    label: 'Notifications',
    description: 'Choose how you receive account updates.',
  },
  {
    key: 'security',
    icon: 'mdi-shield-outline',
    label: 'Privacy & Security',
    description: 'Review your account security options.',
  },
]

const supportActions = [
  {
    key: 'help',
    icon: 'mdi-help-circle-outline',
    label: 'Help & Support',
    description: 'Get help with your account.',
  },
  {
    key: 'about',
    icon: 'mdi-information-outline',
    label: 'About kids tp',
    description: 'Learn more about the app.',
  },
  {
    key: 'logout',
    icon: 'mdi-logout',
    label: 'Log Out',
    description: 'Sign out of this account on this device.',
  },
  {
    key: 'delete',
    icon: 'mdi-delete-outline',
    label: 'Delete Account',
    description: 'Permanently remove your parent account.',
    danger: true,
  },
]

const notificationPreferenceItems = [
  {
    key: 'points_recorded',
    label: 'Points awarded or deducted',
    description: 'When another parent records Trust Points for a child.',
  },
  {
    key: 'activity_assignment_changed',
    label: 'Activity assignments',
    description: 'When another parent assigns or updates a child activity.',
  },
  {
    key: 'family_access_changed',
    label: 'Family access',
    description: 'When a parent joins or leaves your family.',
  },
]
const defaultNotificationPreferences = {
  points_recorded: true,
  activity_assignment_changed: true,
  family_access_changed: true,
}
const route = useRoute()
const router = useRouter()
const profile = ref(null)
const children = ref([])
const isLoading = ref(true)
const loadError = ref('')
const notice = ref('')
const activeDialog = ref('')
const dialogError = ref('')
const isSavingProfile = ref(false)
const isVerifyingEmail = ref(false)
const isChangingPassword = ref(false)
const isDeletingAccount = ref(false)
const profileDraft = reactive({ name: '', phone: '', email: '' })
const pendingEmail = ref('')
const emailVerificationCode = ref('')
const passwordDraft = reactive({ current_password: '', password: '', password_confirmation: '' })
const deletePassword = ref('')
const confirmAccountDeletionChecked = ref(false)
const notificationPreferences = reactive({ ...defaultNotificationPreferences })
const notifications = ref([])
const unreadNotificationCount = ref(0)
const isLoadingNotifications = ref(false)
const notificationError = ref('')
const savingNotificationKey = ref('')
const isMarkingAllRead = ref(false)
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
const isLastParent = computed(() => (profile.value?.family?.users?.length ?? 0) <= 1)
const dialogTitle = computed(() => ({
  personal: 'Personal information',
  password: 'Change password',
  notifications: 'Notifications',
  security: 'Privacy & security',
  help: 'Help & support',
  about: 'About Kids TP',
  delete: 'Delete account?',
}[activeDialog.value] || 'Account settings'))
const dialogDescription = computed(() => ({
  personal: 'Keep your parent account details up to date.',
  password: 'Use a strong password that you do not use elsewhere.',
  notifications: 'Manage in-app updates from other parents in your family.',
  security: 'Review how your account and family data are protected.',
  help: 'Guidance for managing your Kids TP family account.',
  about: 'A family tool for habits, responsibility, and trust.',
  delete: 'Review what will happen before confirming account deletion.',
}[activeDialog.value] || 'Manage your account preferences.'))

function initials(name) {
  return (name || 'P')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('')
}

function openSetting(key) {
  if (key === 'family') {
    router.push({ name: 'family' })
    return
  }

  openProfileDialog(key)
}

function runSupportAction(key) {
  if (key === 'logout') {
    activeDialog.value = ''
    signOut()
    return
  }

  openProfileDialog(key)
}

function openProfileDialog(dialog) {
  activeDialog.value = dialog
  dialogError.value = ''

  if (dialog === 'personal') {
    profileDraft.name = profile.value?.name || ''
    profileDraft.phone = profile.value?.phone || ''
    profileDraft.email = profile.value?.email || ''
    pendingEmail.value = profile.value?.pending_email || ''
    emailVerificationCode.value = ''
  } else if (dialog === 'password') {
    passwordDraft.current_password = ''
    passwordDraft.password = ''
    passwordDraft.password_confirmation = ''
  } else if (dialog === 'delete') {
    deletePassword.value = ''
    confirmAccountDeletionChecked.value = false
  } else if (dialog === 'notifications') {
    loadNotificationCenter()
  }
}

function closeProfileDialog() {
  if (isSavingProfile.value || isVerifyingEmail.value || isChangingPassword.value || isDeletingAccount.value) {
    return
  }

  activeDialog.value = ''
  dialogError.value = ''
}

async function handleDialogError(error, fallback) {
  if (error instanceof ApiError && error.status === 401) {
    clearAuthToken()
    await router.replace({ name: 'login' })
    return true
  }

  dialogError.value = error instanceof Error ? error.message : fallback
  return false
}

async function saveProfile() {
  isSavingProfile.value = true
  dialogError.value = ''

  try {
    const result = await updateProfile(profileDraft)
    profile.value = { ...profile.value, ...result.user }
    window.dispatchEvent(new CustomEvent('profile-updated', { detail: profile.value }))
    pendingEmail.value = result.pending_email || ''

    if (pendingEmail.value) {
      notice.value = result.message
      return
    }

    notice.value = result.message
    activeDialog.value = ''
  } catch (error) {
    await handleDialogError(error, 'Unable to update your profile.')
  } finally {
    isSavingProfile.value = false
  }
}

async function verifyPendingEmail() {
  isVerifyingEmail.value = true
  dialogError.value = ''

  try {
    const result = await verifyEmailChange(emailVerificationCode.value)
    profile.value = { ...profile.value, ...result.user }
    window.dispatchEvent(new CustomEvent('profile-updated', { detail: profile.value }))
    pendingEmail.value = ''
    emailVerificationCode.value = ''
    notice.value = result.message
    activeDialog.value = ''
  } catch (error) {
    await handleDialogError(error, 'Unable to verify this email address.')
  } finally {
    isVerifyingEmail.value = false
  }
}

async function savePassword() {
  isChangingPassword.value = true
  dialogError.value = ''

  try {
    const result = await changePassword(passwordDraft)
    activeDialog.value = ''
    notice.value = result.message
  } catch (error) {
    await handleDialogError(error, 'Unable to change your password.')
  } finally {
    isChangingPassword.value = false
  }
}

async function confirmAccountDeletion() {
  isDeletingAccount.value = true
  dialogError.value = ''

  try {
    await deleteAccount(deletePassword.value)
    clearAuthToken()
    await router.replace({ name: 'home' })
  } catch (error) {
    await handleDialogError(error, 'Unable to delete your account.')
  } finally {
    isDeletingAccount.value = false
  }
}

async function loadNotificationCenter() {
  isLoadingNotifications.value = true
  notificationError.value = ''

  try {
    const [preferenceResult, notificationResult] = await Promise.all([
      fetchNotificationPreferences(),
      fetchNotifications(),
    ])
    Object.assign(notificationPreferences, defaultNotificationPreferences, preferenceResult.preferences)
    notifications.value = notificationResult.notifications
    unreadNotificationCount.value = notificationResult.unread_count
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    notificationError.value = error instanceof Error ? error.message : 'Unable to load notifications.'
  } finally {
    isLoadingNotifications.value = false
  }
}

async function saveNotificationPreference(key, enabled) {
  savingNotificationKey.value = key
  notificationError.value = ''

  try {
    const result = await updateNotificationPreferences({ [key]: enabled })
    Object.assign(notificationPreferences, result.preferences)
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    notificationError.value = error instanceof Error ? error.message : 'Unable to save this preference.'
  } finally {
    savingNotificationKey.value = ''
  }
}

async function markNotificationAsRead(notification) {
  try {
    await markNotificationRead(notification.id)
    notification.read_at = new Date().toISOString()
    unreadNotificationCount.value = Math.max(0, unreadNotificationCount.value - 1)
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    notificationError.value = error instanceof Error ? error.message : 'Unable to update this notification.'
  }
}

async function markAllRead() {
  isMarkingAllRead.value = true
  notificationError.value = ''

  try {
    await markAllNotificationsRead()
    const readAt = new Date().toISOString()
    notifications.value = notifications.value.map((notification) => ({
      ...notification,
      read_at: notification.read_at || readAt,
    }))
    unreadNotificationCount.value = 0
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    notificationError.value = error instanceof Error ? error.message : 'Unable to update notifications.'
  } finally {
    isMarkingAllRead.value = false
  }
}

function formatNotificationDate(value) {
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? 'Date unavailable'
    : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}

async function loadProfile({ background = false } = {}) {
  const hasExistingData = Boolean(profile.value)

  if (!background || !hasExistingData) {
    isLoading.value = true
  }

  loadError.value = ''

  try {
    const [userResult, childrenResult] = await Promise.all([
      getCurrentUser({ bypassCache: background }),
      fetchChildren({ bypassCache: background }),
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

    if (background && hasExistingData) {
      console.error('Unable to refresh profile details in the background.', error)
    } else {
      loadError.value = error instanceof Error
        ? error.message
        : 'Unable to load your profile.'
    }
  } finally {
    isLoading.value = false
  }
}

function signOut() {
  signOutAccount()
}

usePageActivation(
  () => loadProfile(),
  () => loadProfile({ background: true }),
)
watch(
  () => route.query.panel,
  (panel) => {
    if (panel === 'notifications') {
      openProfileDialog('notifications')
    }
  },
  { immediate: true },
)
</script>

<style scoped src="../assets/styles/profile.css"></style>
