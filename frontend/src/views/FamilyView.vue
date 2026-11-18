<template>
  <main class="family-page">
    <ProfileHeader :profile="profile" />

    <div class="family-content">
      <nav class="family-breadcrumb" aria-label="Breadcrumb">
        <RouterLink to="/profile">← Profile</RouterLink>
        <span aria-hidden="true">/</span>
        <span>Manage Family</span>
      </nav>

      <div class="family-title-row">
        <div>
          <p class="family-eyebrow">YOUR HOUSEHOLD</p>
          <h1>Manage Family</h1>
        </div>
        <span class="family-date">{{ todayLabel }}</span>
      </div>

      <p v-if="notice" class="family-notice" role="status" aria-live="polite">
        {{ notice }}
      </p>
      <p v-if="actionError" class="family-notice family-notice-error" role="alert">
        {{ actionError }}
      </p>

      <div v-if="isLoading" class="family-state" role="status">
        Loading your family...
      </div>
      <div v-else-if="loadError" class="family-state family-state-error" role="alert">
        <p>{{ loadError }}</p>
        <button type="button" @click="loadFamily">Try again</button>
      </div>

      <template v-else>
        <section class="family-summary-card">
          <span class="family-home-icon"><v-icon icon="mdi-home-variant" /></span>
          <div class="family-summary-copy">
            <h2>{{ profile?.family?.family_name || 'Your family' }}</h2>
            <p>{{ children.length }} {{ children.length === 1 ? 'child' : 'children' }}</p>
            <small>Showing your parent account and family children</small>
          </div>
          <form
            v-if="editingFamilyName"
            class="family-inline-form family-name-form"
            @submit.prevent="saveFamilySetting('family_name')"
          >
            <label>
              <span>Family name</span>
              <input v-model.trim="familyDraft.family_name" required maxlength="255" />
            </label>
            <button type="submit" :disabled="isSavingFamily">
              {{ isSavingFamily ? 'Saving…' : 'Save' }}
            </button>
            <button type="button" class="family-form-cancel" :disabled="isSavingFamily" @click="cancelFamilyEdit">
              Cancel
            </button>
          </form>
          <button v-else type="button" @click="editFamilyName">
            <v-icon icon="mdi-pencil-outline" /> Edit Family
          </button>
        </section>

        <div class="family-management-grid">
          <section class="family-members-section" aria-labelledby="family-members-title">
            <header class="family-section-title">
              <div>
                <h2 id="family-members-title">Family Members</h2>
                <span>{{ children.length + familyParents.length }} profiles shown</span>
              </div>
              <small>HOUSEHOLD MEMBERS</small>
            </header>

            <div class="family-member-list">
              <article v-for="parent in familyParents" :key="parent.id" class="family-member-card">
                <span class="family-member-avatar">{{ initials(parent.name) }}</span>
                <div class="family-member-copy">
                  <div class="family-member-name">
                    <strong>{{ parent.name || 'Parent' }}</strong>
                    <span class="family-role parent-role">Parent</span>
                  </div>
                  <small>
                    <v-icon icon="mdi-email-outline" />
                    {{ parent.email || 'No email on file' }}
                  </small>
                  <small v-if="parent.phone">
                    <v-icon icon="mdi-phone-outline" />
                    {{ parent.phone }}
                  </small>
                </div>
                <v-icon class="family-member-chevron" icon="mdi-chevron-right" />
              </article>

              <RouterLink
                v-for="child in children"
                :key="child.id"
                class="family-member-card"
                :to="{ name: 'child-profile', params: { childId: child.id } }"
              >
                <span class="family-member-avatar">
                  <v-icon v-if="avatarIcon(child.avatar)" :icon="avatarIcon(child.avatar)" />
                  <template v-else>{{ initials(child.name) }}</template>
                </span>
                <div class="family-member-copy">
                  <div class="family-member-name">
                    <strong>{{ child.name }}</strong>
                    <span class="family-role child-role">Child</span>
                  </div>
                  <small>{{ childAge(child.date_of_birth) }}</small>
                  <small class="family-points-unavailable">Points summary unavailable</small>
                </div>
                <span class="family-view-profile">View Profile</span>
                <v-icon class="family-member-chevron" icon="mdi-chevron-right" />
              </RouterLink>

              <button
                class="family-invite-card"
                type="button"
                @click="isInvitationFormOpen = !isInvitationFormOpen"
              >
                <span class="family-invite-icon"><v-icon icon="mdi-plus" /></span>
                <span>
                  <strong>Invite Parent</strong>
                  <small>Add another parent or guardian to your family.</small>
                </span>
                <v-icon class="family-member-chevron" icon="mdi-chevron-right" />
              </button>
            </div>

            <form
              v-if="isInvitationFormOpen"
              class="family-invitation-form"
              @submit.prevent="sendInvitation"
            >
              <label>
                <span>Parent or guardian email</span>
                <input
                  v-model.trim="invitationEmail"
                  type="email"
                  autocomplete="email"
                  maxlength="255"
                  required
                />
              </label>
              <button type="submit" :disabled="isSendingInvitation">
                {{ isSendingInvitation ? 'Sending…' : 'Send invitation' }}
              </button>
            </form>

            <p class="family-members-note">
              Children are managed by their parent accounts. Invitations expire after seven days.
            </p>
          </section>

          <aside class="family-financial-card" aria-labelledby="family-financial-title">
            <header>
              <h2 id="family-financial-title">Financial settings</h2>
              <p>Shared settings for this family.</p>
            </header>

            <article class="family-financial-setting">
              <span class="family-setting-icon"><v-icon icon="mdi-swap-horizontal" /></span>
              <div class="family-setting-copy">
                <strong>TP to ETB Conversion Rate</strong>
                <small>Current rate</small>
                <b>{{ conversionRateLabel }}</b>
                <p>This rate is used when translating Trust Points into ETB values.</p>
              </div>
              <form
                v-if="editingFamilyField === 'conversion_rate'"
                class="family-setting-editor"
                @submit.prevent="saveFamilySetting('conversion_rate')"
              >
                <label>
                  <span>ETB per Trust Point</span>
                  <input v-model.number="familyDraft.conversion_rate" type="number" min="0.01" step="0.01" required />
                </label>
                <button type="submit" :disabled="isSavingFamily">{{ isSavingFamily ? 'Saving…' : 'Save' }}</button>
                <button type="button" class="family-form-cancel" :disabled="isSavingFamily" @click="cancelFamilyEdit">Cancel</button>
              </form>
              <button v-else type="button" @click="editFamilySetting('conversion_rate')">Edit</button>
            </article>

            <article class="family-financial-setting">
              <span class="family-setting-icon"><v-icon icon="mdi-shield-outline" /></span>
              <div class="family-setting-copy">
                <strong>Daily Deduction Cap</strong>
                <small>Current limit</small>
                <b>{{ deductionCapLabel }}</b>
                <p>Limits the Trust Points deducted in a day.</p>
              </div>
              <form
                v-if="editingFamilyField === 'daily_deduction_cap'"
                class="family-setting-editor"
                @submit.prevent="saveFamilySetting('daily_deduction_cap')"
              >
                <label>
                  <span>Maximum points deducted per day</span>
                  <input v-model.number="familyDraft.daily_deduction_cap" type="number" min="0" max="1000000" step="1" required />
                </label>
                <button type="submit" :disabled="isSavingFamily">{{ isSavingFamily ? 'Saving…' : 'Save' }}</button>
                <button type="button" class="family-form-cancel" :disabled="isSavingFamily" @click="cancelFamilyEdit">Cancel</button>
              </form>
              <button v-else type="button" @click="editFamilySetting('daily_deduction_cap')">Edit</button>
            </article>

            <p class="family-shared-note">
              <v-icon icon="mdi-information-outline" />
              These settings apply across the family.
            </p>
          </aside>
        </div>
      </template>
    </div>

    <footer class="family-footer">
      <span><strong>kids tp</strong> &nbsp;•&nbsp; Ethiopian Family Habit &amp; Trust Management</span>
      <span>Family tools for building trust and responsibility</span>
    </footer>

    <nav class="family-mobile-nav" aria-label="Main navigation">
      <RouterLink to="/dashboard">
        <v-icon icon="mdi-view-dashboard-outline" /><span>Home</span>
      </RouterLink>
      <RouterLink to="/dashboard#children">
        <v-icon icon="mdi-account-group-outline" /><span>Children</span>
      </RouterLink>
      <span class="family-nav-unavailable" aria-disabled="true">
        <v-icon icon="mdi-format-list-checks" /><span>Activities</span>
      </span>
      <span class="family-nav-unavailable" aria-disabled="true">
        <v-icon icon="mdi-history" /><span>History</span>
      </span>
      <RouterLink class="family-nav-current" to="/profile">
        <v-icon icon="mdi-account-outline" /><span>Profile</span>
      </RouterLink>
    </nav>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import ProfileHeader from '../components/ProfileHeader.vue'
import {
  ApiError,
  clearAuthToken,
  fetchChildren,
  getCurrentUser,
  inviteParent,
  updateFamily,
} from '../services/auth.js'

const avatarIcons = {
  bear: 'mdi-teddy-bear',
  cat: 'mdi-cat',
  dog: 'mdi-dog',
  flower: 'mdi-flower-tulip',
  rocket: 'mdi-rocket-launch-outline',
  star: 'mdi-star-outline',
}
const router = useRouter()
const profile = ref(null)
const children = ref([])
const isLoading = ref(true)
const loadError = ref('')
const notice = ref('')
const actionError = ref('')
const editingFamilyName = ref(false)
const editingFamilyField = ref('')
const isSavingFamily = ref(false)
const familyDraft = reactive({
  family_name: '',
  conversion_rate: '',
  daily_deduction_cap: '',
})
const isInvitationFormOpen = ref(false)
const invitationEmail = ref('')
const isSendingInvitation = ref(false)
const todayLabel = new Intl.DateTimeFormat(undefined, {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric',
}).format(new Date())
const conversionRateLabel = computed(() => {
  const rate = Number(profile.value?.family?.conversion_rate)
  return Number.isFinite(rate) && rate > 0 ? `1 TP = ${rate.toFixed(2)} ETB` : 'Not configured'
})
const deductionCapLabel = computed(() => {
  const cap = Number(profile.value?.family?.daily_deduction_cap)
  return Number.isInteger(cap) && cap >= 0 ? `${cap} TP per day` : 'Not configured'
})
const familyParents = computed(() => {
  const parents = profile.value?.family?.users
  return Array.isArray(parents) && parents.length ? parents : [profile.value].filter(Boolean)
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

  return age >= 0 ? `Age ${age}` : 'Age unavailable'
}

function editFamilyName() {
  familyDraft.family_name = profile.value?.family?.family_name || ''
  actionError.value = ''
  editingFamilyName.value = true
}

function editFamilySetting(field) {
  familyDraft[field] = profile.value?.family?.[field] ?? ''
  actionError.value = ''
  editingFamilyField.value = field
}

function cancelFamilyEdit() {
  editingFamilyName.value = false
  editingFamilyField.value = ''
  actionError.value = ''
}

async function saveFamilySetting(field) {
  isSavingFamily.value = true
  actionError.value = ''

  try {
    const result = await updateFamily({ [field]: familyDraft[field] })
    profile.value = { ...profile.value, family: result.family }
    editingFamilyName.value = false
    editingFamilyField.value = ''
    notice.value = result.message
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    actionError.value = error instanceof Error ? error.message : 'Unable to save family settings.'
  } finally {
    isSavingFamily.value = false
  }
}

async function sendInvitation() {
  isSendingInvitation.value = true
  actionError.value = ''

  try {
    const result = await inviteParent(invitationEmail.value)
    notice.value = result.message
    invitationEmail.value = ''
    isInvitationFormOpen.value = false
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    actionError.value = error instanceof Error ? error.message : 'Unable to send the invitation.'
  } finally {
    isSendingInvitation.value = false
  }
}

async function loadFamily() {
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

    loadError.value = error instanceof Error ? error.message : 'Unable to load your family.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadFamily)
</script>

<style scoped src="../assets/styles/family.css"></style>
