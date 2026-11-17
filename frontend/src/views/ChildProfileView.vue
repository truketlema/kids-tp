<template>
  <main class="child-profile-page">
    <ProfileHeader :profile="profile" />

    <div class="child-profile-content">
      <nav class="child-profile-breadcrumb" aria-label="Breadcrumb">
        <RouterLink to="/dashboard#children">‹ Children</RouterLink>
        <span aria-hidden="true">/</span>
        <span>{{ child?.name || 'Child profile' }}</span>
        <span aria-hidden="true">/</span>
        <span>Profile &amp; Settings</span>
      </nav>

      <div v-if="notice" class="child-profile-notice" role="status" aria-live="polite">
        {{ notice }}
      </div>

      <div v-if="isLoading" class="child-profile-state" role="status">
        Loading child profile...
      </div>
      <div v-else-if="loadError" class="child-profile-state child-profile-state-error" role="alert">
        <p>{{ loadError }}</p>
        <button type="button" @click="loadChildProfile">Try again</button>
      </div>

      <template v-else-if="child">
        <div class="child-profile-title-row">
          <div class="child-profile-summary">
            <span class="child-profile-avatar">
              <v-icon v-if="avatarIcon(child.avatar)" :icon="avatarIcon(child.avatar)" />
              <template v-else>{{ initials(child.name) }}</template>
            </span>
            <div class="child-profile-name">
              <h1>{{ child.name }}</h1>
              <span>{{ childAge }} · Child</span>
              <small><v-icon icon="mdi-calendar-blank-outline" /> Date of birth: {{ formatDate(child.date_of_birth) }}</small>
            </div>
            <button type="button" @click="showUnavailable('Editing child information')">
              <v-icon icon="mdi-pencil-outline" />
              Edit Info
            </button>
          </div>
        </div>

        <div class="child-stat-grid">
          <section class="child-stat-card">
            <span class="child-stat-heading"><v-icon icon="mdi-star-circle-outline" /> Total Points</span>
            <strong>Not available yet</strong>
            <small>Point totals are not currently provided by the API.</small>
          </section>
          <section class="child-stat-card">
            <span class="child-stat-heading"><v-icon icon="mdi-trending-up" /> This Month’s Earnings</span>
            <strong>Not available yet</strong>
            <small>Monthly earnings are not currently provided by the API.</small>
          </section>
        </div>

        <div class="child-profile-grid">
          <section class="child-profile-main-column">
            <button
              class="child-reward-settings"
              type="button"
              @click="showUnavailable('Reward settings')"
            >
              <span class="child-profile-icon"><v-icon icon="mdi-trophy-outline" /></span>
              <span>
                <strong>Reward Settings</strong>
                <small>Manage rewards and special achievements.</small>
              </span>
              <v-icon class="child-profile-chevron" icon="mdi-chevron-right" />
            </button>

            <section class="child-value-card">
              <span class="child-profile-icon child-value-icon">
                <v-icon icon="mdi-diamond-stone" />
              </span>
              <span class="child-value-copy">
                <strong>Point Value</strong>
                <small>How much is each point worth?</small>
              </span>
              <span class="child-value-rate">
                <small>TP → ETB Rate</small>
                <strong>{{ conversionRateLabel }}</strong>
              </span>
              <button
                type="button"
                aria-label="Edit point value"
                @click="showUnavailable('Changing point value')"
              >
                <v-icon icon="mdi-chevron-down" />
              </button>
            </section>

            <section class="child-budget-card">
              <header class="child-budget-heading">
                <div>
                  <h2>Budget Allocation</h2>
                  <p>Allocation details for {{ child.name }}.</p>
                </div>
                <button
                  v-if="!isEditingAllocation"
                  type="button"
                  @click="editBudgetAllocation"
                >
                  <v-icon icon="mdi-pencil-outline" />
                  Edit Allocation
                </button>
              </header>
              <div v-if="!isEditingAllocation" class="child-budget-content">
                <div
                  class="child-budget-chart"
                  :style="allocationChartStyle"
                  role="img"
                  :aria-label="`Budget allocation: ${allocationDescription}`"
                >
                  <span>
                    Allocation
                    <strong>100%</strong>
                  </span>
                </div>
                <ul class="child-budget-list">
                  <li
                    v-for="allocation in budgetAllocation"
                    :key="allocation.key"
                    :style="{ '--allocation-color': allocation.color }"
                  >
                    <span
                      class="child-budget-dot"
                      :style="{ '--allocation-color': allocation.color }"
                      aria-hidden="true"
                    ></span>
                    <span class="child-budget-label">{{ allocation.label }}</span>
                    <strong>{{ allocation.percentage }}%</strong>
                  </li>
                </ul>
                <p class="child-budget-note">
                  {{
                    child.budget_allocation
                      ? 'Custom allocation saved for this child.'
                      : 'Using the fixed default allocation. Edit to set custom percentages.'
                  }}
                </p>
              </div>
              <form
                v-else
                class="child-allocation-editor"
                @submit.prevent="saveBudgetAllocation()"
              >
                <p>Set the percentage for each category. The total must equal 100%.</p>
                <label
                  v-for="allocation in budgetAllocation"
                  :key="allocation.key"
                  class="child-allocation-input"
                >
                  <span
                    class="child-budget-dot"
                    :style="{ '--allocation-color': allocation.color }"
                    aria-hidden="true"
                  ></span>
                  <span>{{ allocation.label }}</span>
                  <input
                    v-model.number="draftAllocation[allocation.key]"
                    type="number"
                    min="0"
                    max="100"
                    step="1"
                    :aria-label="`${allocation.label} percentage`"
                  />
                  <span>%</span>
                </label>
                <p
                  class="child-allocation-total"
                  :class="{ 'child-allocation-total-invalid': allocationDraftTotal !== 100 }"
                  aria-live="polite"
                >
                  Total: {{ allocationDraftTotal }}%
                </p>
                <p v-if="allocationError" class="child-allocation-error" role="alert">
                  {{ allocationError }}
                </p>
                <div class="child-allocation-actions">
                  <button
                    type="button"
                    class="child-allocation-reset"
                    :disabled="isSavingAllocation"
                    @click="saveBudgetAllocation(true)"
                  >
                    Restore fixed defaults
                  </button>
                  <span></span>
                  <button
                    type="button"
                    class="child-allocation-cancel"
                    :disabled="isSavingAllocation"
                    @click="cancelBudgetAllocation"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="isSavingAllocation || allocationDraftTotal !== 100"
                  >
                    {{ isSavingAllocation ? 'Saving…' : 'Save allocation' }}
                  </button>
                </div>
              </form>
            </section>

            <p class="child-profile-info-note">
              This profile shows information currently available for your family.
            </p>
            <button
              class="child-remove-button"
              type="button"
              @click="showUnavailable('Removing a child from the family')"
            >
              <v-icon icon="mdi-delete-outline" />
              Remove Child from Family
              <v-icon icon="mdi-chevron-right" />
            </button>
          </section>

          <aside class="child-financial-card">
            <header>
              <h2>Financial setting</h2>
              <p>Shared family settings</p>
            </header>
            <article class="child-financial-setting">
              <span class="child-profile-icon"><v-icon icon="mdi-swap-horizontal" /></span>
              <div>
                <strong>TP to ETB Conversion Rate</strong>
                <small>Current rate</small>
                <b>{{ conversionRateLabel }}</b>
                <p>Family-wide rate used when calculating ETB equivalents.</p>
              </div>
              <RouterLink to="/family">Edit</RouterLink>
            </article>
            <article class="child-financial-setting">
              <span class="child-profile-icon"><v-icon icon="mdi-shield-outline" /></span>
              <div>
                <strong>Daily Deduction Cap</strong>
                <small>Current limit</small>
                <b>{{ deductionCapLabel }}</b>
                <p>Family-wide limit for daily point deductions.</p>
              </div>
              <RouterLink to="/family">Edit</RouterLink>
            </article>
            <p class="child-financial-note">
              <v-icon icon="mdi-information-outline" />
              These settings are shared across the family.
            </p>
          </aside>
        </div>
      </template>
    </div>

    <footer class="child-profile-footer">
      <span><strong>kids tp</strong> &nbsp;•&nbsp; Ethiopian Family Habit &amp; Trust Management</span>
      <span>Family tools for building trust and responsibility</span>
    </footer>

    <nav class="child-profile-mobile-nav" aria-label="Main navigation">
      <RouterLink to="/dashboard">
        <v-icon icon="mdi-view-dashboard-outline" /><span>Home</span>
      </RouterLink>
      <RouterLink to="/dashboard#children">
        <v-icon icon="mdi-account-group-outline" /><span>Children</span>
      </RouterLink>
      <span class="child-nav-unavailable" aria-disabled="true">
        <v-icon icon="mdi-format-list-checks" /><span>Activities</span>
      </span>
      <span class="child-nav-unavailable" aria-disabled="true">
        <v-icon icon="mdi-history" /><span>History</span>
      </span>
      <RouterLink class="child-nav-current" to="/profile">
        <v-icon icon="mdi-account-outline" /><span>Profile</span>
      </RouterLink>
    </nav>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProfileHeader from '../components/ProfileHeader.vue'
import {
  ApiError,
  clearAuthToken,
  fetchChildren,
  getCurrentUser,
  updateChildAllocation,
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
const route = useRoute()
const router = useRouter()
const profile = ref(null)
const child = ref(null)
const isLoading = ref(true)
const loadError = ref('')
const notice = ref('')
const isEditingAllocation = ref(false)
const isSavingAllocation = ref(false)
const allocationError = ref('')
const draftAllocation = ref({})
const budgetAllocation = computed(() => defaultBudgetAllocation.map((item) => ({
  ...item,
  percentage: Number(child.value?.budget_allocation?.[item.key] ?? item.percentage),
})))
const allocationDraftTotal = computed(() =>
  Object.values(draftAllocation.value).reduce((total, percentage) => total + Number(percentage || 0), 0),
)
const allocationDescription = computed(() =>
  budgetAllocation.value
    .map(({ label, percentage }) => `${label} ${percentage}%`)
    .join(', '),
)
const allocationChartStyle = computed(() => {
  let start = 0
  const segments = budgetAllocation.value.map(({ percentage, color }) => {
    const end = start + percentage
    const segment = `${color} ${start}% ${end}%`
    start = end
    return segment
  })

  return { background: `conic-gradient(${segments.join(', ')})` }
})
const conversionRateLabel = computed(() => {
  const rate = Number(profile.value?.family?.conversion_rate)
  return Number.isFinite(rate) && rate > 0 ? `1 TP = ${rate.toFixed(2)} ETB` : 'Not configured'
})
const deductionCapLabel = computed(() => {
  const cap = Number(profile.value?.family?.daily_deduction_cap)
  return Number.isInteger(cap) && cap >= 0 ? `${cap} TP per day` : 'Not configured'
})
const childAge = computed(() => {
  if (!child.value?.date_of_birth) {
    return 'Age unavailable'
  }

  const birthDate = new Date(`${child.value.date_of_birth}T00:00:00`)

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
})

function initials(name) {
  return (name || 'C')
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

function formatDate(date) {
  const parsedDate = new Date(`${date}T00:00:00`)
  return Number.isNaN(parsedDate.getTime())
    ? 'Unavailable'
    : new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      }).format(parsedDate)
}

function showUnavailable(feature) {
  notice.value = `${feature} is not available yet.`
}

function editBudgetAllocation() {
  draftAllocation.value = Object.fromEntries(
    budgetAllocation.value.map(({ key, percentage }) => [key, percentage]),
  )
  allocationError.value = ''
  isEditingAllocation.value = true
}

function cancelBudgetAllocation() {
  allocationError.value = ''
  isEditingAllocation.value = false
}

async function saveBudgetAllocation(restoreDefaults = false) {
  if (!restoreDefaults && allocationDraftTotal.value !== 100) {
    allocationError.value = 'The allocation percentages must total 100%.'
    return
  }

  isSavingAllocation.value = true
  allocationError.value = ''

  try {
    const allocation = restoreDefaults
      ? null
      : Object.fromEntries(
          defaultBudgetAllocation.map(({ key }) => [key, Number(draftAllocation.value[key])]),
        )
    const result = await updateChildAllocation(child.value.id, allocation)

    if (!Object.prototype.hasOwnProperty.call(result, 'allocation')) {
      throw new Error('The API response did not include the saved budget allocation.')
    }

    child.value = { ...child.value, budget_allocation: result.allocation }
    isEditingAllocation.value = false
    notice.value = restoreDefaults
      ? 'The fixed budget allocation has been restored.'
      : 'Budget allocation saved.'
  } catch (error) {
    allocationError.value = error instanceof Error
      ? error.message
      : 'Unable to save the budget allocation.'
  } finally {
    isSavingAllocation.value = false
  }
}

async function loadChildProfile() {
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
    child.value = childrenResult.children.find(
      (item) => String(item.id) === String(route.params.childId),
    ) || null

    if (!child.value) {
      loadError.value = 'This child was not found in your family.'
    }
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    loadError.value = error instanceof Error ? error.message : 'Unable to load this child profile.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadChildProfile)
</script>

<style scoped src="../assets/styles/child-profile.css"></style>
