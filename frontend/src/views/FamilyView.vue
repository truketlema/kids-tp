<template>
  <main class="family-page account-page-main">
    <PagePrintDecoration />

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
            <section class="family-reward-settings">
              <span class="family-setting-icon"><v-icon icon="mdi-trophy-outline" /></span>
              <div>
                <h3>Reward Settings</h3>
                <p>Manage rewards and special achievements for your family.</p>
                <small>Reward configuration is not available yet.</small>
              </div>
              <span class="family-coming-soon">Coming soon</span>
            </section>
          </aside>
        </div>

        <section class="family-budget-section" aria-labelledby="family-budget-title">
          <header class="family-budget-section-heading">
            <div>
              <h2 id="family-budget-title">Children’s Budget Allocations</h2>
              <p>Set how each child’s Trust Points are divided across their goals.</p>
            </div>
          </header>
          <p v-if="!children.length" class="family-budget-empty">
            Add a child to set up a budget allocation.
          </p>
          <div v-else class="family-budget-grid">
            <article v-for="child in children" :key="child.id" class="family-budget-card">
              <header>
                <div class="family-budget-child">
                  <span class="family-member-avatar">
                    <v-icon v-if="avatarIcon(child.avatar)" :icon="avatarIcon(child.avatar)" />
                    <template v-else>{{ initials(child.name) }}</template>
                  </span>
                  <span><strong>{{ child.name }}</strong><small>Budget allocation</small></span>
                </div>
                <button
                  type="button"
                  :disabled="isSavingAllocation"
                  @click="allocationEditingId === child.id ? cancelAllocation() : editAllocation(child)"
                >
                  {{ allocationEditingId === child.id ? 'Cancel' : 'Edit allocation' }}
                </button>
              </header>
              <template v-if="allocationEditingId !== child.id">
                <div class="family-budget-content">
                  <div class="family-budget-chart" :style="allocationChartStyle(child)" aria-hidden="true">
                    <span>Allocation<strong>100%</strong></span>
                  </div>
                  <ul>
                    <li v-for="item in allocationFor(child)" :key="item.key" :style="{ '--allocation-color': item.color }">
                      <span class="family-budget-dot"></span><span>{{ item.label }}</span><strong>{{ item.percentage }}%</strong>
                    </li>
                  </ul>
                </div>
                <p class="family-budget-note">
                  {{ child.budget_allocation ? 'Custom allocation saved for this child.' : 'Using the fixed default allocation.' }}
                </p>
              </template>
              <form v-else class="family-budget-editor" @submit.prevent="saveAllocation(child)">
                <p>Percentages must total 100%.</p>
                <label v-for="item in defaultBudgetAllocation" :key="item.key">
                  <span class="family-budget-dot" :style="{ '--allocation-color': item.color }"></span>
                  <span>{{ item.label }}</span>
                  <input v-model.number="budgetDraft[item.key]" type="number" min="0" max="100" step="1" />
                  <span>%</span>
                </label>
                <strong :class="{ 'family-budget-total-invalid': allocationTotal !== 100 }">Total: {{ allocationTotal }}%</strong>
                <p v-if="allocationError" class="family-notice-error" role="alert">{{ allocationError }}</p>
                <div class="family-budget-actions">
                  <button type="button" class="family-budget-reset" :disabled="isSavingAllocation" @click="saveAllocation(child, true)">Restore defaults</button>
                  <span></span>
                  <button type="submit" :disabled="isSavingAllocation || allocationTotal !== 100">{{ isSavingAllocation ? 'Saving…' : 'Save allocation' }}</button>
                </div>
              </form>
            </article>
          </div>
        </section>
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
      <RouterLink class="family-nav-current" to="/family">
        <v-icon icon="mdi-account-group-outline" /><span>Family</span>
      </RouterLink>
      <RouterLink to="/activities">
        <v-icon icon="mdi-format-list-checks" /><span>Activities</span>
      </RouterLink>
      <RouterLink to="/history">
        <v-icon icon="mdi-history" /><span>History</span>
      </RouterLink>
      <RouterLink to="/profile">
        <v-icon icon="mdi-account-outline" /><span>Profile</span>
      </RouterLink>
    </nav>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PagePrintDecoration from '../components/PagePrintDecoration.vue'
import {
  ApiError,
  clearAuthToken,
  fetchChildren,
  getCurrentUser,
  inviteParent,
  updateChildAllocation,
  updateFamily,
} from '../services/auth.js'

const defaultBudgetAllocation = [
  { key: 'long_term_savings', label: 'Long-term Savings', percentage: 40, color: '#264f3a' },
  { key: 'planned_purchase', label: 'Planned Purchase', percentage: 25, color: '#7bd16b' },
  { key: 'discretionary_spending', label: 'Discretionary Spending', percentage: 20, color: '#f5c64c' },
  { key: 'reserve', label: 'Reserve', percentage: 15, color: '#fa987d' },
]
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
const allocationEditingId = ref(null)
const isSavingAllocation = ref(false)
const allocationError = ref('')
const budgetDraft = reactive(Object.fromEntries(defaultBudgetAllocation.map(({ key, percentage }) => [key, percentage])))
const allocationTotal = computed(() =>
  Object.values(budgetDraft).reduce((total, percentage) => total + Number(percentage || 0), 0),
)
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

function allocationFor(child) {
  return defaultBudgetAllocation.map((item) => ({
    ...item,
    percentage: Number(child.budget_allocation?.[item.key] ?? item.percentage),
  }))
}

function allocationChartStyle(child) {
  let start = 0
  const segments = allocationFor(child).map(({ percentage, color }) => {
    const end = start + percentage
    const segment = `${color} ${start}% ${end}%`
    start = end
    return segment
  })

  return { background: `conic-gradient(${segments.join(', ')})` }
}

function editAllocation(child) {
  allocationError.value = ''
  allocationEditingId.value = child.id
  for (const item of allocationFor(child)) {
    budgetDraft[item.key] = item.percentage
  }
}

function cancelAllocation() {
  allocationEditingId.value = null
  allocationError.value = ''
}

async function saveAllocation(child, restoreDefaults = false) {
  if (!restoreDefaults && allocationTotal.value !== 100) {
    allocationError.value = 'The allocation percentages must total 100%.'
    return
  }

  isSavingAllocation.value = true
  allocationError.value = ''
  try {
    const allocation = restoreDefaults
      ? null
      : Object.fromEntries(defaultBudgetAllocation.map(({ key }) => [key, Number(budgetDraft[key])]))
    const result = await updateChildAllocation(child.id, allocation)
    if (!Object.prototype.hasOwnProperty.call(result, 'allocation')) {
      throw new Error('The API response did not include the saved budget allocation.')
    }
    children.value = children.value.map((item) =>
      item.id === child.id ? { ...item, budget_allocation: result.allocation } : item,
    )
    allocationEditingId.value = null
    notice.value = restoreDefaults ? 'The fixed budget allocation was restored.' : 'Budget allocation saved.'
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }
    allocationError.value = error instanceof Error ? error.message : 'Unable to save the budget allocation.'
  } finally {
    isSavingAllocation.value = false
  }
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
