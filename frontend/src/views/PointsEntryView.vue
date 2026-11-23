<template>
  <main class="points-entry-page account-page-main">
    <div class="points-entry-content">
      <nav class="points-breadcrumb">
        <RouterLink to="/dashboard">Dashboard</RouterLink><span>›</span>
        <RouterLink to="/activities">Activities</RouterLink><span>›</span>
        <strong>{{ isDeduction ? 'Deduct Points' : 'Award Points' }}</strong>
      </nav>

      <section v-if="isLoading" class="points-state" role="status">Loading point details…</section>
      <section v-else-if="loadError" class="points-state points-error" role="alert">
        <p>{{ loadError }}</p><button type="button" @click="loadData">Try again</button>
      </section>
      <template v-else>
        <h1 class="points-title">{{ isDeduction ? 'Deduct Points' : 'Award Points' }}</h1>
        <div class="points-layout">
          <form class="points-form" @submit.prevent="submit">
            <label class="points-assignee">
              <span class="points-assignee-icon"><v-icon :icon="isDeduction ? 'mdi-minus' : 'mdi-plus'" /></span>
              <span class="points-assignee-copy"><strong>{{ isDeduction ? 'Deducting from' : 'Awarding to' }}: {{ child.name }}</strong><small>Current Balance: {{ balance }} TP</small></span>
            </label>

            <section class="points-picker">
              <h2>{{ isDeduction ? 'Select the reason' : 'Choose an activity' }}</h2>
              <p v-if="!eligibleCategories.length" class="points-no-activities" role="status">
                No activities are assigned to {{ child.name }} yet.
                <RouterLink :to="{ name: 'child-activities', params: { childId: child.id } }">Assign activities</RouterLink>
                before recording points.
              </p>
              <template v-else>
                <label><span>Category</span>
                  <select v-model="categoryId" required @change="selectFirstActivity">
                    <option value="" disabled>Select a category</option>
                    <option v-for="category in eligibleCategories" :key="category.id" :value="String(category.id)">{{ category.name }}</option>
                  </select>
                </label>
                <label><span>{{ isDeduction ? 'Activity / infraction' : 'Activity' }}</span>
                  <select v-model="activityId" required @change="syncSuggestedAmount">
                    <option value="" disabled>Select an activity</option>
                    <option v-for="activity in eligibleActivities" :key="activity.id" :value="String(activity.id)">
                      {{ activity.title }} · {{ isDeduction ? `−${activity.suggested_deduction || 0}` : `+${effectiveActivityTp(activity)}` }} TP
                    </option>
                  </select>
                </label>
              </template>
            </section>

            <section class="points-amount-section">
              <label class="points-amount-label">{{ isDeduction ? 'Deduction amount' : 'Award amount' }}</label>
              <div class="points-amount-control">
                <button type="button" aria-label="Decrease points" :disabled="amount <= 1" @click="amount = Math.max(1, amount - 1)">−</button>
                <strong :class="{ 'points-amount-negative': isDeduction }">
                  <span class="points-amount-value">
                    <span aria-hidden="true">{{ isDeduction ? '−' : '+' }}</span>
                    <input
                      v-model.number="amount"
                      class="points-amount-input"
                      type="number"
                      min="1"
                      max="2147483647"
                      required
                      :style="{ width: `${Math.max(1, String(amount ?? '').replace(/\D/g, '').length)}ch` }"
                      :aria-label="isDeduction ? 'Deduction amount in Trust Points' : 'Award amount in Trust Points'"
                    />
                    <span class="points-amount-unit">TP</span>
                  </span>
                </strong>
                <button type="button" aria-label="Increase points" @click="amount = Number(amount || 0) + 1">+</button>
              </div>
              <small>You can adjust the suggested points before {{ isDeduction ? 'deducting' : 'awarding' }}.</small>
            </section>

            <label class="points-note"><span>Note (optional)</span><textarea v-model.trim="remarks" rows="2" maxlength="2000" :placeholder="isDeduction ? 'Add context for this deduction…' : 'Add a note about this achievement…'"></textarea></label>

            <section class="points-confirmation">
              <h2>Confirmation summary</h2>
              <div><span>Child</span><strong>{{ child.name }}</strong></div>
              <div><span>{{ isDeduction ? 'Reason' : 'Activity' }}</span><strong>{{ selectedActivity?.title || 'Choose an activity' }}</strong></div>
              <div><span>Points to {{ isDeduction ? 'deduct' : 'award' }}</span><strong :class="{ 'points-amount-negative': isDeduction }">{{ isDeduction ? '−' : '+' }}{{ amount || 0 }} TP</strong></div>
              <div><span>Current balance</span><strong>{{ balance }} TP</strong></div>
              <div class="points-new-balance"><span>New balance after {{ isDeduction ? 'deduction' : 'award' }}</span><strong>{{ balanceAfter }} TP</strong></div>
            </section>
            <p v-if="formError" class="points-form-error" role="alert">{{ formError }}</p>
            <div class="points-actions">
              <RouterLink class="points-cancel" :to="{ name: 'child-profile', params: { childId: child.id } }">Cancel</RouterLink>
              <button class="points-primary" type="submit" :disabled="isSaving || !selectedActivity">
                {{ isSaving ? 'Saving…' : `Confirm ${isDeduction ? 'Deduction' : 'Award'}` }}
              </button>
            </div>
          </form>

          <aside v-if="isDeduction" class="points-safeguards">
            <h2><v-icon icon="mdi-shield-check-outline" /> Safeguard Controls</h2>
            <p>The platform automatically enforces safety thresholds to protect your child’s point balance.</p>
            <div class="points-cap-meter"><span :style="{ width: `${deductionProgress}%` }"></span></div>
            <strong>Daily deduction limit <b>{{ summary.deducted_today }} / {{ familyCap }} TP</b></strong>
            <small>Remaining today: {{ remainingCap }} TP</small>
            <hr />
            <h3>Stewardship Principles</h3>
            <p>“Discipline is instruction, not retribution.”</p>
            <ul><li>Keep deductions consistent with the family limit.</li><li>Deductions may bring the balance below zero.</li></ul>
          </aside>
        </div>
      </template>
      <div v-if="success" class="points-success-backdrop">
        <section
          class="points-success-dialog"
          role="dialog"
          aria-modal="true"
          aria-labelledby="points-success-title"
        >
          <span class="points-success-icon" :class="{ 'points-success-icon-deduction': isDeduction }">
            <v-icon :icon="isDeduction ? 'mdi-minus' : 'mdi-check'" />
          </span>
          <h1 id="points-success-title">{{ isDeduction ? 'Deduction Recorded' : 'Awarded Successfully' }}</h1>
          <p v-if="isDeduction">
            {{ Math.abs(Number(savedLedgerEntry?.tp_amount || 0)) }} TP deducted from {{ child.name }}’s balance for {{ selectedActivity?.title }}.
          </p>
          <p v-else>
            Points successfully awarded to {{ child.name }} for {{ selectedActivity?.title }}.
          </p>
          <div class="points-success-details">
            <div><span>Recipient</span><strong>{{ child.name }}</strong></div>
            <div><span>Activity &amp; Category</span><strong>{{ selectedActivity?.title }} · {{ selectedCategory?.name }}</strong></div>
            <div>
              <span>{{ isDeduction ? 'Points Deducted' : 'Points Credited' }}</span>
              <strong class="points-success-amount" :class="{ 'points-success-amount-negative': isDeduction }">
                {{ savedLedgerEntry?.tp_amount > 0 ? '+' : '' }}{{ savedLedgerEntry?.tp_amount }} TP
              </strong>
            </div>
            <div><span>Previous Balance</span><strong>{{ previousBalance }} TP</strong></div>
            <div v-if="isDeduction"><span>Daily Deduction Cap</span><strong>{{ summary.deducted_today }} / {{ familyCap }} TP</strong></div>
            <div class="points-success-total">
              <span>Updated Total Balance</span>
              <strong>{{ savedBalance }} TP<small>≈ {{ formatCurrency(savedBalanceEtb) }} ETB Trust Value</small></strong>
            </div>
            <footer>
              <span>Recorded by {{ savedLedgerEntry?.recorded_by || profile?.name || 'Parent' }}</span>
              <time>{{ formatDateTime(savedLedgerEntry?.created_at) }}</time>
            </footer>
          </div>
          <div class="points-success-actions">
            <button type="button" class="points-secondary" @click="startAnother">
              {{ isDeduction ? 'Deduct more points' : 'Award more points' }}
            </button>
            <button
              type="button"
              class="points-primary"
              @click="router.push({ name: 'child-profile', params: { childId: child.id } })"
            >
              Done
            </button>
          </div>
        </section>
      </div>
    </div>
    <footer class="points-footer"><span><strong>kids tp</strong> · Ethiopian Family Habit &amp; Trust Management</span><span>Governed Family Custody · End-to-End Encrypted Ledger</span></footer>
    <nav class="points-mobile-nav" aria-label="Main navigation">
      <RouterLink to="/dashboard"><v-icon icon="mdi-home-outline" /><span>Home</span></RouterLink>
      <RouterLink to="/family"><v-icon icon="mdi-account-group-outline" /><span>Family</span></RouterLink>
      <RouterLink class="points-nav-current" to="/activities"><v-icon icon="mdi-format-list-checks" /><span>Activities</span></RouterLink>
      <RouterLink :to="{ name: 'child-profile', params: { childId } }"><v-icon icon="mdi-star-circle-outline" /><span>Points</span></RouterLink>
      <RouterLink to="/history"><v-icon icon="mdi-history" /><span>History</span></RouterLink>
    </nav>
  </main>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageActivation } from '../composables/usePageActivation.js'
import { addLedgerEntry, ApiError, clearAuthToken, fetchCategories, fetchChildLedger, getCurrentUser } from '../services/auth.js'

const route = useRoute()
const router = useRouter()
const isDeduction = computed(() => route.params.type === 'deduct')
const childId = computed(() => String(route.params.childId))
const child = ref(null)
const profile = ref(null)
const categories = ref([])
const summary = ref({ current_balance: 0, deducted_today: 0 })
const categoryId = ref('')
const activityId = ref('')
const amount = ref(1)
const remarks = ref('')
const isLoading = ref(true)
const isSaving = ref(false)
const loadError = ref('')
const formError = ref('')
const success = ref(false)
const savedBalance = ref(0)
const savedLedgerEntry = ref(null)
const eligibleCategories = computed(() => categories.value
  .map((category) => ({
    ...category,
    activities: category.activities.filter((activity) =>
      Array.isArray(activity.child_ids) && activity.child_ids.some((id) => String(id) === childId.value),
    ),
  }))
  .filter((category) => category.activities.length))
const eligibleActivities = computed(() =>
  eligibleCategories.value.find((category) => String(category.id) === categoryId.value)?.activities || [],
)
const selectedActivity = computed(() =>
  eligibleActivities.value.find((activity) => String(activity.id) === activityId.value),
)
const selectedCategory = computed(() =>
  eligibleCategories.value.find((category) => String(category.id) === categoryId.value),
)
const balance = computed(() => Number(summary.value.current_balance || 0))
const previousBalance = computed(() =>
  savedBalance.value - Number(savedLedgerEntry.value?.tp_amount || 0),
)
const savedBalanceEtb = computed(() => savedBalance.value * Number(
  savedLedgerEntry.value?.conversion_rate_snapshot ?? profile.value?.family?.conversion_rate ?? 0,
))
const balanceAfter = computed(() => success.value
  ? savedBalance.value
  : balance.value + (isDeduction.value ? -Number(amount.value || 0) : Number(amount.value || 0)))
const familyCap = computed(() => Number(profile.value?.family?.daily_deduction_cap || 0))
const remainingCap = computed(() => Math.max(0, familyCap.value - Number(summary.value.deducted_today || 0)))
const deductionProgress = computed(() => familyCap.value ? Math.min(100, (summary.value.deducted_today / familyCap.value) * 100) : 100)

function effectiveActivityTp(activity) {
  const override = activity.child_tp_overrides?.find(
    (item) => String(item.child_id) === childId.value,
  )

  return Number(override?.tp_override ?? activity.default_tp)
}

function selectFirstActivity() {
  const first = eligibleCategories.value.find((category) => String(category.id) === categoryId.value)?.activities[0]
  activityId.value = first ? String(first.id) : ''
  syncSuggestedAmount()
}
function syncSuggestedAmount() {
  const selected = eligibleActivities.value.find((activity) => String(activity.id) === activityId.value)
  amount.value = selected
    ? Number(isDeduction.value ? selected.suggested_deduction || 1 : effectiveActivityTp(selected))
    : 1
}
function formatCurrency(value) {
  return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(Number(value) || 0)
}
function formatDateTime(value) {
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? 'Date unavailable'
    : new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
      }).format(date)
}
function setSelectedChild(result) {
  child.value = result.child
  summary.value = result.summary
}
async function loadData({ background = false } = {}) {
  const hasExistingData = Boolean(profile.value)

  if (!background || !hasExistingData) {
    isLoading.value = true
  }

  loadError.value = ''
  try {
    const [user, categoryResult, childResult] = await Promise.all([
      getCurrentUser({ bypassCache: background }),
      fetchCategories({ bypassCache: background }),
      fetchChildLedger(childId.value, { bypassCache: background }),
    ])
    categories.value = categoryResult.categories
    profile.value = user
    setSelectedChild(childResult)
    if (!selectedActivity.value && eligibleCategories.value.length) {
      categoryId.value = String(eligibleCategories.value[0].id)
      selectFirstActivity()
    }
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }
    if (background && hasExistingData) {
      console.error('Unable to refresh point details in the background.', error)
    } else {
      loadError.value = error instanceof Error ? error.message : 'Unable to load point details.'
    }
  } finally {
    isLoading.value = false
  }
}
async function loadSelectedChild() {
  const result = await fetchChildLedger(childId.value)
  setSelectedChild(result)
}
watch(childId, async () => {
  try {
    await loadSelectedChild()
    categoryId.value = ''
    activityId.value = ''
    if (eligibleCategories.value.length) {
      categoryId.value = String(eligibleCategories.value[0].id)
      selectFirstActivity()
    }
  } catch (error) {
    formError.value = error instanceof Error ? error.message : 'Unable to change child.'
  }
})
async function submit() {
  if (!selectedActivity.value || !Number.isInteger(Number(amount.value)) || Number(amount.value) < 1) return
  isSaving.value = true
  formError.value = ''
  try {
    const result = await addLedgerEntry(childId.value, {
      type: isDeduction.value ? 'deduction' : 'earning',
      commitment_id: selectedActivity.value.id,
      tp_amount: Number(amount.value),
      remarks: remarks.value || null,
    })
    savedBalance.value = result.current_balance
    savedLedgerEntry.value = result.ledger
    summary.value.current_balance = result.current_balance
    if (isDeduction.value) summary.value.deducted_today += Number(amount.value)
    success.value = true
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }
    formError.value = error instanceof Error ? error.message : 'Unable to record this point activity.'
  } finally {
    isSaving.value = false
  }
}
function startAnother() {
  success.value = false
  remarks.value = ''
  syncSuggestedAmount()
}
usePageActivation(
  () => loadData(),
  () => loadData({ background: true }),
)
</script>

<style scoped>
.points-entry-page{position:relative;isolation:isolate;min-height:100vh;min-height:100dvh;background:var(--app-page-background);color:var(--color-text)}
.points-entry-content{width:min(100%,1120px);min-height:calc(100vh - 128px);margin:auto;padding:20px 24px 44px}
.points-breadcrumb{display:flex;gap:8px;margin-bottom:16px;color:var(--color-text-muted);font-size:12px}.points-breadcrumb a{color:var(--color-primary);text-decoration:none}.points-breadcrumb strong{color:var(--color-text)}
.points-title{margin:0 0 15px;font-size:22px}
.points-layout{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(270px,.8fr);align-items:start;gap:18px}
.points-form{display:grid;gap:12px;padding:18px;border:1px solid var(--color-border-strong);border-radius:14px;background:var(--color-surface)}
.points-assignee{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:11px;background:var(--color-chip)}.points-assignee-icon{display:grid;width:30px;height:30px;place-items:center;border:1px solid var(--color-primary);border-radius:50%;color:var(--color-primary)}.points-assignee-copy{display:grid;gap:3px;margin-right:auto}.points-assignee-copy strong{font-size:13px}.points-assignee-copy small{color:var(--color-text-muted);font-size:11px}.points-assignee select{max-width:140px;padding:6px;border:1px solid var(--color-border-strong);border-radius:7px;background:var(--color-input);color:var(--color-text);font:inherit;font-size:11px}
.points-picker{display:grid;gap:10px;padding:12px;border:1px solid var(--color-border-strong);border-radius:12px}.points-picker h2,.points-confirmation h2{margin:0 0 2px;font-size:13px}.points-picker label,.points-note{display:grid;gap:5px;color:var(--color-text-muted);font-size:11px}.points-picker label span,.points-note span{font-weight:700}.points-picker select,.points-note textarea{width:100%;min-height:38px;padding:8px 10px;border:1px solid var(--color-border-strong);border-radius:8px;background:var(--color-input);color:var(--color-text);font:inherit}
.points-no-activities{margin:0;color:var(--color-text-muted);font-size:var(--text-caption);line-height:1.5}.points-no-activities a{color:var(--color-primary);font-weight:800;text-decoration:underline}
.points-amount-section{display:grid;justify-items:center;gap:8px;padding:6px}.points-amount-label{justify-self:start;color:var(--color-text);font-size:12px;font-weight:800}.points-amount-control{display:flex;align-items:center;gap:12px}.points-amount-control strong{display:grid;flex:0 0 auto;place-items:center;width:96px;height:96px;padding:0;border:1px solid var(--color-primary);border-radius:50%;background:var(--color-chip);color:var(--color-primary);font-size:20px;line-height:1;box-sizing:border-box}.points-amount-control strong:focus-within{box-shadow:0 0 0 3px var(--color-primary-focus)}.points-amount-value{display:inline-flex;align-items:center;justify-content:center;max-width:88%;line-height:1;white-space:nowrap}.points-amount-unit{margin-left:.18em}.points-amount-control .points-amount-negative{border-color:var(--color-danger-border);background:var(--color-danger-soft);color:var(--color-danger)}.points-amount-control button{width:27px;height:27px;border:1px solid var(--color-border-strong);border-radius:50%;background:var(--color-surface);color:var(--color-text-muted);font-size:17px;cursor:pointer}.points-amount-control button:disabled{opacity:.5}.points-amount-section>small{color:var(--color-text-muted);font-size:10px}.points-amount-input{width:1ch;min-width:1ch;max-width:8ch;height:1em;min-height:0;margin:0;padding:0;border:0;background:transparent;color:inherit;font:inherit;line-height:1;text-align:right;box-sizing:content-box;outline:none;-moz-appearance:textfield;appearance:textfield}.points-amount-input::-webkit-outer-spin-button,.points-amount-input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.points-note textarea{resize:vertical}.points-confirmation{display:grid;gap:9px;padding:12px;border-radius:10px;background:var(--color-chip)}.points-confirmation>div{display:flex;justify-content:space-between;gap:12px;color:var(--color-text-muted);font-size:11px}.points-confirmation strong{color:var(--color-text);text-align:right}.points-confirmation .points-new-balance{padding-top:8px;border-top:1px solid var(--color-border-strong);font-weight:800}.points-new-balance strong{padding:3px 8px;border-radius:999px;background:var(--color-chip)}
.points-actions{display:flex;justify-content:flex-end;gap:9px}.points-primary,.points-secondary,.points-cancel{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:0 15px;border:0;border-radius:999px;background:var(--color-chip);color:var(--color-primary);font:inherit;font-size:12px;font-weight:800;text-decoration:none;cursor:pointer}.points-primary{background:var(--color-primary);color: var(--color-on-primary)}.points-secondary{background:var(--color-chip)}.points-cancel{border:1px solid var(--color-border-strong);background:var(--color-surface)}.points-primary:disabled{opacity:.6;cursor:not-allowed}
.points-safeguards{display:grid;gap:11px;padding:15px;border:1px solid var(--color-border-strong);border-radius:12px;background:var(--color-surface)}.points-safeguards h2,.points-safeguards h3{margin:0;color:var(--color-primary);font-size:13px}.points-safeguards h2{display:flex;gap:6px;align-items:center}.points-safeguards p,.points-safeguards li{margin:0;color:var(--color-text-muted);font-size:11px;line-height:1.55}.points-safeguards>strong{display:flex;justify-content:space-between;gap:8px;color:var(--color-text-muted);font-size:10px}.points-safeguards>strong b{color:var(--color-danger)}.points-safeguards>small{color:var(--color-text-muted);font-size:10px}.points-safeguards hr{width:100%;border:0;border-top:1px solid var(--color-border-strong)}.points-safeguards ul{display:grid;gap:5px;margin:0;padding-left:17px}.points-cap-meter{height:7px;overflow:hidden;border-radius:9px;background:var(--color-danger-soft)}.points-cap-meter span{display:block;height:100%;background:var(--color-danger-button)}
.points-state,.points-success{max-width:560px;margin:50px auto;padding:24px;border:1px solid var(--color-border-strong);border-radius:15px;background:var(--color-surface);text-align:center}.points-error{color:var(--color-danger)}.points-error button{padding:8px 14px;border:0;border-radius:99px;background:var(--color-primary-light)}.points-success{display:grid;justify-items:center;gap:12px;padding:36px 26px}.points-success-icon{display:grid;width:58px;height:58px;place-items:center;border-radius:50%;background:var(--color-chip);color:var(--color-primary);font-size:28px}.points-success h1{margin:0;font-size:22px}.points-success p{max-width:340px;margin:0;color:var(--color-text-muted);font-size:13px;line-height:1.5}.points-success-summary{display:flex;justify-content:space-between;width:min(100%,350px);padding:12px;border-radius:9px;background:var(--color-chip);color:var(--color-text-muted);font-size:12px}.points-success-summary strong{color:var(--color-text)}.points-form-error{margin:0;color:var(--color-danger);font-size:12px}
.points-footer{display:flex;justify-content:space-between;gap:15px;padding:14px max(24px,calc((100% - 1080px)/2));border-top:1px solid var(--color-border-strong);background:var(--color-surface);color:var(--color-text-muted);font-size:10px}.points-footer strong{color:var(--color-primary)}
.points-mobile-nav,.points-print{display:none}
@media(max-width:760px){.points-entry-content{min-height:calc(100dvh - 120px);padding:17px 14px 90px}.points-breadcrumb{font-size:10px}.points-title{font-size:20px}.points-layout{grid-template-columns:1fr;gap:10px}.points-form{gap:10px;padding:11px}.points-assignee{gap:7px;padding:8px}.points-assignee-copy strong{font-size:11px}.points-picker{padding:10px}.points-amount-section{padding:1px}.points-amount-control strong{width:82px;height:82px;font-size:16px}.points-actions{display:grid;grid-template-columns:1fr 1fr}.points-footer{display:none}.points-print{position:fixed;bottom:40px;left:-88px;z-index:0;display:block;width:220px;opacity:.94;pointer-events:none}.points-print img{width:100%;height:auto}.points-entry-content{position:relative;z-index:1}.points-mobile-nav{position:fixed;right:0;bottom:0;left:0;z-index:10;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));min-height:60px;padding:5px 5px max(5px,env(safe-area-inset-bottom));border-top:1px solid var(--color-border-strong);background:var(--color-header)}.points-mobile-nav a{display:grid;justify-items:center;align-content:center;gap:2px;color:var(--color-text-muted);text-decoration:none}.points-mobile-nav .v-icon{font-size:19px}.points-mobile-nav span{font-size:10px}.points-mobile-nav .points-nav-current{margin-inline:8px;border-radius:99px;background:var(--color-primary);color: var(--color-on-primary)}}

.points-entry-page .points-title,
.points-entry-page .points-success h1 {
  font-size: var(--text-heading);
}

.points-entry-page .points-breadcrumb,
.points-entry-page .points-assignee-copy strong,
.points-entry-page .points-picker h2,
.points-entry-page .points-confirmation h2,
.points-entry-page .points-amount-label,
.points-entry-page .points-safeguards h2,
.points-entry-page .points-safeguards h3 {
  font-size: var(--text-small);
}

.points-entry-page .points-assignee-copy small,
.points-entry-page .points-picker label,
.points-entry-page .points-note,
.points-entry-page .points-confirmation > div,
.points-entry-page .points-safeguards p,
.points-entry-page .points-safeguards li,
.points-entry-page .points-safeguards > strong,
.points-entry-page .points-safeguards > small,
.points-entry-page .points-amount-section > small,
.points-entry-page .points-success p,
.points-entry-page .points-success-summary,
.points-entry-page .points-form-error {
  font-size: var(--text-caption);
}

.points-entry-page select {
  min-height: var(--button-height);
  font-size: var(--text-small);
}

.points-entry-page .points-amount-control .points-amount-input {
  height: 1em;
  min-height: 0;
  padding: 0;
  font-size: inherit;
  line-height: 1;
}

.points-entry-page textarea {
  font-size: var(--text-small);
}

.points-entry-page .points-cancel {
  min-width: var(--button-height);
  min-height: var(--button-height);
  padding: 0 var(--button-padding-inline);
  font-size: var(--text-small);
}

.points-entry-page .points-actions > * {
  min-height: var(--button-height);
  font-size: var(--text-small);
}

@media (max-width: 760px) {
  .points-entry-page .points-title,
  .points-entry-page .points-success h1 {
    font-size: var(--text-heading);
  }

  .points-entry-page .points-actions > * {
    width: 100%;
  }
}

.points-success-backdrop {
  position: fixed;
  inset: 0;
  z-index: 300;
  display: grid;
  place-items: center;
  padding: 20px;
  background: var(--color-overlay-strong);
  backdrop-filter: blur(5px);
}

.points-success-dialog {
  display: grid;
  justify-items: center;
  gap: 12px;
  width: min(100%, 520px);
  max-height: calc(100dvh - 40px);
  overflow: auto;
  padding: 26px;
  border: 1px solid var(--color-border-strong);
  border-radius: 14px;
  background: var(--color-surface);
  box-shadow: var(--app-card-shadow);
  text-align: center;
}

.points-success-dialog .points-success-icon {
  width: 52px;
  height: 52px;
  background: var(--color-chip);
  color: var(--color-primary);
  font-size: 26px;
}

.points-success-dialog .points-success-icon-deduction {
  background: var(--color-danger-soft);
  color: var(--color-danger);
}

.points-success-dialog h1 {
  margin: 0;
  color: var(--color-text);
  font-size: var(--text-large);
}

.points-success-dialog > p {
  max-width: 390px;
  margin: 0;
  color: var(--color-text-muted);
  font-size: var(--text-caption);
  line-height: 1.5;
}

.points-success-details {
  display: grid;
  width: 100%;
  margin-top: 3px;
  padding: 10px 13px;
  border-radius: 9px;
  background: var(--color-chip);
  text-align: left;
}

.points-success-details > div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  min-height: 31px;
  color: var(--color-text-muted);
  font-size: var(--text-caption);
}

.points-success-details > div > strong {
  color: var(--color-text);
  text-align: right;
}

.points-success-amount {
  padding: 3px 8px;
  border-radius: 999px;
  background: var(--color-chip);
}

.points-success-amount-negative {
  background: var(--color-danger-soft);
  color: var(--color-danger) !important;
}

.points-success-details .points-success-total {
  min-height: 48px;
  margin-top: 5px;
  padding-top: 7px;
  border-top: 1px solid var(--color-border-strong);
  color: var(--color-text);
  font-weight: 800;
}

.points-success-total > strong {
  display: grid;
  gap: 2px;
  color: var(--color-primary) !important;
}

.points-success-total small {
  color: var(--color-text-muted);
  font-size: 10px;
  font-weight: 500;
}

.points-success-details > footer {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-top: 5px;
  padding-top: 7px;
  border-top: 1px solid var(--color-border-strong);
  color: var(--color-text-muted);
  font-size: 10px;
}

.points-success-actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  width: 100%;
  margin-top: 2px;
}

.points-success-actions > button {
  min-height: var(--button-height);
  font-size: var(--text-caption);
}

@media (max-width: 600px) {
  .points-success-backdrop {
    padding: 12px;
  }

  .points-success-dialog {
    gap: 10px;
    max-height: calc(100dvh - 24px);
    padding: 18px 14px;
  }

  .points-success-details {
    padding: 8px 9px;
  }

  .points-success-details > div {
    gap: 8px;
  }

  .points-success-details > footer {
    flex-direction: column;
  }

  .points-success-actions {
    gap: 7px;
  }
}
</style>
