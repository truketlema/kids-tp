<template>
  <main class="child-profile-page account-page-main">
    <PagePrintDecoration />

    <div class="child-profile-content">
      <nav class="child-profile-breadcrumb" aria-label="Breadcrumb">
        <RouterLink to="/dashboard#children">‹ Children</RouterLink>
        <span aria-hidden="true">/</span>
        <span>{{ child?.name || 'Child profile' }}</span>
        <span aria-hidden="true">/</span>
        <span>Profile</span>
      </nav>

      <div v-if="childActionError" class="child-profile-notice child-profile-notice-error" role="alert">
        {{ childActionError }}
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
            <button type="button" @click="toggleChildEdit">
              <v-icon icon="mdi-pencil-outline" />
              {{ isEditingChild ? 'Cancel' : 'Edit Info' }}
            </button>
          </div>
        </div>

        <form
          v-if="isEditingChild"
          class="child-edit-form"
          @submit.prevent="saveChildDetails"
        >
          <label>
            <span>Child name</span>
            <input v-model.trim="childDraft.name" maxlength="255" required />
          </label>
          <label>
            <span>Date of birth</span>
            <input v-model="childDraft.date_of_birth" type="date" :max="todayInput" required />
          </label>
          <label>
            <span>Avatar</span>
            <select v-model="childDraft.avatar">
              <option value="">Initials</option>
              <option v-for="avatar in availableAvatars" :key="avatar" :value="avatar">
                {{ avatar }}
              </option>
            </select>
          </label>
          <label>
            <span>Monthly TP goal</span>
            <input v-model.number="childDraft.monthly_savings_goal" type="number" min="1" required />
          </label>
          <div class="child-edit-actions">
            <button type="submit" :disabled="isSavingChild">
              {{ isSavingChild ? 'Saving…' : 'Save changes' }}
            </button>
            <button type="button" :disabled="isSavingChild" @click="isEditingChild = false">
              Cancel
            </button>
          </div>
        </form>

        <section class="child-points-overview" aria-label="Points overview">
          <article class="child-point-balance"><small>CURRENT BALANCE</small><strong>{{ ledgerSummary.current_balance }} <span>TP</span></strong><span>Verified ledger</span></article>
          <article class="child-point-earned"><small>EARNED THIS WEEK</small><strong>+{{ ledgerSummary.earned_this_week }} <span>TP</span></strong><span>Points awarded</span></article>
          <article class="child-point-deducted"><small>DEDUCTIONS THIS WEEK</small><strong>−{{ ledgerSummary.deducted_this_week }} <span>TP</span></strong><span>Daily limit: {{ deductionCapLabel }}</span></article>
        </section>
        <div class="child-point-actions">
          <button type="button" class="child-deduct-action" @click="openPointDialog('deduct')"><v-icon icon="mdi-minus-circle-outline" /> Deduct Points</button>
          <button type="button" class="child-award-action" @click="openPointDialog('award')"><v-icon icon="mdi-plus-circle-outline" /> Award Points</button>
          <RouterLink
            class="child-activities-action"
            :to="{ name: 'child-activities', params: { childId: child.id } }"
          >
            <v-icon icon="mdi-format-list-checks" />
            <span>View {{ child.name }}’s Activities</span>
          </RouterLink>
        </div>
        <section class="child-responsibility-card">
          <header><span><v-icon icon="mdi-finance" /> Financial Responsibility</span><small>Estimate only</small></header>
          <div class="child-value-estimate">
            <small>Current balance equivalent</small>
            <strong>{{ ledgerSummary.current_balance }} TP = {{ formatCurrency(estimatedValue) }} ETB</strong>
          </div>
          <div class="child-goal-heading"><span>Points earned this month</span><strong>{{ goalProgress }}%</strong></div>
          <div class="child-goal-track"><span :style="{ width: `${goalProgress}%` }"></span></div>
          <small class="child-goal-note">
            {{ ledgerSummary.earned_this_month }} / {{ child.monthly_savings_goal }} TP toward the monthly goal.
          </small>
        </section>
        <section class="child-recent-activity">
          <header><h2>Recent Point Activity</h2><RouterLink :to="{ name: 'history', query: { child_id: child.id } }">View All History ›</RouterLink></header>
          <div class="child-activity-filters">
            <button v-for="filter in pointFilters" :key="filter.value" type="button" :class="{ active: activePointFilter === filter.value }" @click="activePointFilter = filter.value">{{ filter.label }}</button>
          </div>
          <ul v-if="filteredPointEntries.length" class="child-recent-list">
            <li v-for="entry in filteredPointEntries.slice(0, 7)" :key="entry.id">
              <span class="child-recent-icon" :class="`child-recent-${entry.type}`"><v-icon :icon="pointEntryIcon(entry.type)" /></span>
              <span class="child-recent-copy"><strong>{{ entry.activity?.title || entry.remarks || pointTypeLabel(entry.type) }}</strong><small>{{ entry.activity?.category || pointTypeLabel(entry.type) }}</small><small>{{ formatDateTime(entry.created_at) }}</small></span>
              <strong class="child-recent-amount" :class="{ negative: entry.tp_amount < 0 }">{{ entry.tp_amount > 0 ? '+' : '' }}{{ entry.tp_amount }} TP</strong>
            </li>
          </ul>
          <p v-else class="child-recent-empty">No point activity has been recorded yet.</p>
        </section>

        <div class="child-profile-grid">
          <section class="child-profile-main-column">
            <p class="child-profile-info-note">
              This profile shows information currently available for your family.
            </p>
            <button
              class="child-remove-button"
              type="button"
              :disabled="isRemovingChild"
              @click="openRemoveConfirmation"
            >
              <v-icon icon="mdi-delete-outline" />
              {{ isRemovingChild ? 'Removing…' : 'Remove Child from Family' }}
              <v-icon icon="mdi-chevron-right" />
            </button>
          </section>

        </div>
      </template>
      <div
        v-if="isRemoveConfirmationOpen && child"
        class="child-remove-confirmation-backdrop"
        @click.self="cancelRemoveConfirmation"
      >
        <section
          class="child-remove-confirmation-dialog"
          role="alertdialog"
          aria-modal="true"
          aria-labelledby="child-remove-confirmation-title"
          aria-describedby="child-remove-confirmation-description"
          @keydown.esc.stop="cancelRemoveConfirmation"
        >
          <header class="child-remove-confirmation-heading">
            <span class="child-remove-confirmation-icon" aria-hidden="true">
              <v-icon icon="mdi-delete-outline" />
            </span>
            <div>
              <h2 id="child-remove-confirmation-title">Remove child from family?</h2>
              <p>This action cannot be undone.</p>
            </div>
          </header>
          <p id="child-remove-confirmation-description" class="child-remove-confirmation-description">
            This will permanently remove <strong>{{ child.name }}</strong>’s profile from your family.
          </p>
          <p v-if="removeChildError" class="child-remove-confirmation-error" role="alert">
            {{ removeChildError }}
          </p>
          <div class="child-remove-confirmation-actions">
            <button
              type="button"
              class="child-remove-cancel"
              ref="removeConfirmationCancel"
              :disabled="isRemovingChild"
              @click="cancelRemoveConfirmation"
            >
              Cancel
            </button>
            <button
              type="button"
              class="child-remove-confirm"
              :disabled="isRemovingChild"
              @click="removeChildProfile"
            >
              <v-icon icon="mdi-delete-outline" />
              {{ isRemovingChild ? 'Removing…' : 'Remove child' }}
            </button>
          </div>
        </section>
      </div>
    </div>

    <footer class="child-profile-footer">
      <span><strong>kids tp</strong> &nbsp;•&nbsp; Ethiopian Family Habit &amp; Trust Management</span>
      <span>Family tools for building trust and responsibility</span>
    </footer>

    <nav class="child-profile-mobile-nav" aria-label="Main navigation">
      <RouterLink to="/dashboard">
        <v-icon icon="mdi-view-dashboard-outline" /><span>Home</span>
      </RouterLink>
      <RouterLink to="/family">
        <v-icon icon="mdi-account-group-outline" /><span>Family</span>
      </RouterLink>
      <RouterLink to="/activities">
        <v-icon icon="mdi-format-list-checks" /><span>Activities</span>
      </RouterLink>
      <RouterLink to="/history"><v-icon icon="mdi-history" /><span>History</span></RouterLink>
      <RouterLink class="child-nav-current" to="/profile">
        <v-icon icon="mdi-account-outline" /><span>Profile</span>
      </RouterLink>
    </nav>
  </main>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PagePrintDecoration from '../components/PagePrintDecoration.vue'
import { usePageActivation } from '../composables/usePageActivation.js'
import {
  ApiError,
  clearAuthToken,
  fetchChildLedger,
  getCurrentUser,
  removeChild,
  updateChild,
} from '../services/auth.js'

const availableAvatars = ['bear', 'cat', 'dog', 'flower', 'rocket', 'star']
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
const ledgerSummary = ref({ current_balance: 0, earned_this_week: 0, earned_this_month: 0, deducted_this_week: 0 })
const pointEntries = ref([])
const activePointFilter = ref('all')
const pointFilters = [
  { value: 'all', label: 'All' },
  { value: 'earning', label: 'Earned' },
  { value: 'deduction', label: 'Deduction' },
  { value: 'bonus', label: 'Bonus' },
  { value: 'adjustment', label: 'Adjustment' },
]
const isLoading = ref(true)
const loadError = ref('')
const childActionError = ref('')
const isEditingChild = ref(false)
const isSavingChild = ref(false)
const isRemovingChild = ref(false)
const isRemoveConfirmationOpen = ref(false)
const removeChildError = ref('')
const removeConfirmationCancel = ref(null)
const removeChildTrigger = ref(null)
const childDraft = ref({ name: '', date_of_birth: '', avatar: '', monthly_savings_goal: 150 })
const todayInput = new Date().toISOString().slice(0, 10)
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
const filteredPointEntries = computed(() => activePointFilter.value === 'all'
  ? pointEntries.value
  : pointEntries.value.filter((entry) => entry.type === activePointFilter.value))
const goalProgress = computed(() => Math.max(0, Math.min(100, Math.round(
  (Number(ledgerSummary.value.earned_this_month || 0) / Number(child.value?.monthly_savings_goal || 150)) * 100,
))))
const familyConversionRate = computed(() => Number(profile.value?.family?.conversion_rate) || 0)
const estimatedValue = computed(() =>
  Number(ledgerSummary.value.current_balance) * familyConversionRate.value,
)

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
function formatCurrency(value) {
  return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value)
}
function formatDateTime(value) {
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Date unavailable' : new Intl.DateTimeFormat(undefined, {
    month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
  }).format(date)
}
function pointEntryIcon(type) {
  return { earning: 'mdi-plus', deduction: 'mdi-minus', bonus: 'mdi-gift-outline', adjustment: 'mdi-swap-vertical' }[type] || 'mdi-history'
}
function pointTypeLabel(type) {
  return type.charAt(0).toUpperCase() + type.slice(1)
}
function openPointDialog(type) {
  router.push({ name: 'child-points', params: { childId: child.value.id, type } })
}

function toggleChildEdit() {
  childActionError.value = ''

  if (isEditingChild.value) {
    isEditingChild.value = false
    return
  }

  childDraft.value = {
    name: child.value.name,
    date_of_birth: child.value.date_of_birth,
    avatar: child.value.avatar || '',
    monthly_savings_goal: child.value.monthly_savings_goal || 150,
  }
  isEditingChild.value = true
}

async function saveChildDetails() {
  isSavingChild.value = true
  childActionError.value = ''

  try {
    const result = await updateChild(child.value.id, {
      ...childDraft.value,
      avatar: childDraft.value.avatar || null,
      monthly_savings_goal: Number(childDraft.value.monthly_savings_goal),
    })
    child.value = { ...child.value, ...result.child }
    isEditingChild.value = false
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    childActionError.value = error instanceof Error ? error.message : 'Unable to update this child.'
  } finally {
    isSavingChild.value = false
  }
}

async function openRemoveConfirmation(event) {
  removeChildTrigger.value = event.currentTarget
  removeChildError.value = ''
  isRemoveConfirmationOpen.value = true
  await nextTick()
  removeConfirmationCancel.value?.focus()
}

async function cancelRemoveConfirmation() {
  if (isRemovingChild.value) {
    return
  }

  isRemoveConfirmationOpen.value = false
  removeChildError.value = ''
  await nextTick()
  removeChildTrigger.value?.focus()
}

async function removeChildProfile() {
  if (!child.value || isRemovingChild.value) {
    return
  }

  isRemovingChild.value = true
  removeChildError.value = ''

  try {
    await removeChild(child.value.id)
    isRemoveConfirmationOpen.value = false
    await router.replace({ name: 'dashboard' })
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      isRemoveConfirmationOpen.value = false
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    removeChildError.value = error instanceof Error ? error.message : 'Unable to remove this child.'
  } finally {
    isRemovingChild.value = false
  }
}

async function loadChildProfile({ background = false } = {}) {
  const hasExistingData = Boolean(profile.value)

  if (!background || !hasExistingData) {
    isLoading.value = true
  }

  loadError.value = ''

  try {
    const [userResult, ledgerResult] = await Promise.all([
      getCurrentUser({ bypassCache: background }),
      fetchChildLedger(route.params.childId, { bypassCache: background }),
    ])

    if (!ledgerResult.child) {
      throw new Error('The API did not return this child profile.')
    }

    profile.value = userResult
    child.value = ledgerResult.child
    ledgerSummary.value = ledgerResult.summary
    pointEntries.value = ledgerResult.ledger
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    if (background && hasExistingData) {
      console.error('Unable to refresh the child profile in the background.', error)
    } else {
      loadError.value = error instanceof Error ? error.message : 'Unable to load this child profile.'
    }
  } finally {
    isLoading.value = false
  }
}

usePageActivation(
  () => loadChildProfile(),
  () => loadChildProfile({ background: true }),
)
</script>

<style scoped src="../assets/styles/child-profile.css"></style>
