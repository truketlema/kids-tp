<template>
  <main class="child-setup-page account-page-main">
    <div class="child-setup-content">
      <nav class="child-setup-breadcrumb" aria-label="Breadcrumb">
        <button type="button" @click="requestExit">‹ Back</button>
        <span aria-hidden="true">/</span>
        <span>{{ child?.name || 'New child' }}</span>
      </nav>

      <ol class="child-setup-steps" aria-label="Child setup progress">
        <li class="child-setup-step-complete">
          <span><v-icon icon="mdi-check" /></span>
          <strong>Child Details</strong>
        </li>
        <li aria-hidden="true" class="child-setup-step-connector"></li>
        <li class="child-setup-step-current" aria-current="step">
          <span>2</span>
          <strong>Categories &amp; Activities</strong>
        </li>
      </ol>

      <section class="child-setup-intro">
        <strong>STEP 2 OF 2: BEHAVIORAL FRAMEWORK</strong>
        <p>
          Choose activities for {{ child?.name || 'your child' }}. Set a personal TP reward for each one;
          you can change these selections later.
        </p>
      </section>

      <p v-if="isLoading" class="child-setup-state" role="status">Loading categories and activities…</p>
      <section v-else-if="loadError" class="child-setup-state child-setup-error" role="alert">
        <p>{{ loadError }}</p>
        <button type="button" @click="loadSetup">Try again</button>
      </section>
      <template v-else-if="child">
        <div class="child-setup-heading">
          <div>
            <h1>Categories</h1>
            <span>{{ categories.length }} available</span>
          </div>
          <RouterLink to="/activities">Manage activities</RouterLink>
        </div>

        <div class="child-setup-layout">
          <div class="child-setup-category-list">
            <section
              v-for="category in categories"
              :key="category.id"
              class="child-setup-category"
            >
              <header>
                <button
                  type="button"
                  class="child-setup-category-toggle"
                  :aria-expanded="expandedCategoryIds.includes(String(category.id))"
                  :aria-controls="`setup-category-${category.id}`"
                  @click="toggleCategory(category.id)"
                >
                  <span class="child-setup-category-icon">
                    <v-icon :icon="category.icon || 'mdi-format-list-checks'" />
                  </span>
                  <span class="child-setup-category-copy">
                    <strong>{{ category.name }}</strong>
                    <small>{{ category.description }}</small>
                    <span>{{ selectedCountForCategory(category) }} selected</span>
                  </span>
                  <v-icon
                    class="child-setup-category-chevron"
                    :icon="expandedCategoryIds.includes(String(category.id))
                      ? 'mdi-chevron-up'
                      : 'mdi-chevron-right'"
                  />
                </button>
                <button
                  type="button"
                  class="child-setup-select-all"
                  :disabled="!selectableActivities(category).length"
                  @click="toggleCategorySelection(category)"
                >
                  {{ isCategoryFullySelected(category) ? 'Clear selection' : 'Select all' }}
                </button>
              </header>

              <ul
                v-if="expandedCategoryIds.includes(String(category.id))"
                :id="`setup-category-${category.id}`"
              >
                <li
                  v-for="activity in category.activities"
                  :key="activity.id"
                  class="child-setup-activity"
                  :class="{
                    'child-setup-activity-selected': isActivitySelected(activity),
                    'child-setup-activity-assigned': isActivityAssigned(activity),
                  }"
                >
                  <label class="child-setup-activity-choice">
                    <input
                      type="checkbox"
                      :checked="isActivitySelected(activity)"
                      :disabled="isActivityAssigned(activity)"
                      @change="setActivitySelected(activity, $event.target.checked)"
                    />
                    <span class="child-setup-activity-icon">
                      <v-icon icon="mdi-check" />
                    </span>
                    <span class="child-setup-activity-copy">
                      <strong>{{ activity.title }}</strong>
                      <small>
                        Suggested deduction:
                        {{ activity.suggested_deduction ? `−${activity.suggested_deduction} TP` : 'None' }}
                        <template v-if="isActivityAssigned(activity)"> · Already assigned</template>
                      </small>
                    </span>
                  </label>
                  <label class="child-setup-reward">
                    <span>TP reward</span>
                    <span class="child-setup-reward-input">
                      <input
                        type="number"
                        min="1"
                        max="4294967295"
                        :aria-label="`TP reward for ${activity.title}`"
                        :value="activityTpValue(activity)"
                        :disabled="isActivityAssigned(activity)"
                        @input="updateActivityTp(activity, $event.target.value)"
                      />
                      <small>TP</small>
                    </span>
                  </label>
                </li>
              </ul>
            </section>
          </div>

          <aside class="child-setup-summary">
            <h2>Selected Commitments</h2>
            <div class="child-setup-summary-count">
              <span>
                <small>Selected activities</small>
                <strong>{{ selectedActivities.length }} activities</strong>
              </span>
              <span>{{ selectedCategoryCount }} categories</span>
            </div>
            <div class="child-setup-summary-reward">
              <small>Potential TP rewards</small>
              <strong>+{{ totalSelectedTp }} TP</strong>
              <span>If all selected activities are completed</span>
            </div>
            <div class="child-setup-deduction-cap">
              <span>Daily deduction cap</span>
              <strong>{{ deductionCapLabel }}</strong>
            </div>
            <section class="child-setup-advice">
              <strong><v-icon icon="mdi-lightbulb-outline" /> Parent Steward Advice</strong>
              <p>
                Start with a manageable set of regular activities. You can adjust the selections and
                rewards as your child builds new habits.
              </p>
            </section>
            <p v-if="saveError" class="child-setup-error" role="alert">{{ saveError }}</p>
            <button
              type="button"
              class="child-setup-complete"
              :disabled="isCompleting || selectedActivities.length === 0"
              @click="completeSetup"
            >
              {{ isCompleting ? 'Saving selections…' : 'Save & Complete Setup' }}
              <v-icon icon="mdi-arrow-right" />
            </button>
            <small v-if="selectedActivities.length === 0" class="child-setup-summary-note">
              Select at least one activity to complete setup.
            </small>
            <small class="child-setup-summary-note">
              Activities and rewards can be edited at any time.
            </small>
          </aside>
        </div>
      </template>
      <div v-if="isExitConfirmationOpen" class="child-setup-exit-backdrop" @click.self="keepSettingUp">
        <section
          class="child-setup-exit-dialog"
          role="alertdialog"
          aria-modal="true"
          aria-labelledby="child-setup-exit-title"
        >
          <h2 id="child-setup-exit-title">Leave child setup?</h2>
          <p>
            <template v-if="child?.id">
              {{ child.name }} was saved, but setup did not finish. Leaving now may keep the child
              and any activity assignments already saved.
            </template>
            <template v-else>
              {{ child?.name }} has not been added yet. Leaving now will discard this child’s details
              and activity selections.
            </template>
          </p>
          <div>
            <button type="button" @click="keepSettingUp">Continue setup</button>
            <button type="button" class="child-setup-exit-confirm" @click="discardSetup">Discard setup</button>
          </div>
        </section>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { onBeforeRouteLeave, useRouter } from 'vue-router'
import '../assets/styles/child-activity-setup.css'
import {
  ApiError,
  addChild,
  assignChildActivity,
  clearAuthToken,
  fetchCategories,
  getAuthToken,
  getCurrentUser,
  removeChild,
} from '../services/auth.js'

const router = useRouter()
const child = ref(null)
const childDraft = ref(null)
const profile = ref(null)
const categories = ref([])
const selectedActivityIds = ref([])
const assignedActivityIds = ref([])
const expandedCategoryIds = ref([])
const tpDrafts = ref({})
const isLoading = ref(true)
const isCompleting = ref(false)
const isExitConfirmationOpen = ref(false)
const loadError = ref('')
const saveError = ref('')
let allowRouteLeave = false
let resolveRouteLeave = null

const selectedActivities = computed(() => categories.value.flatMap((category) =>
  category.activities
    .filter((activity) => selectedActivityIds.value.includes(String(activity.id)))
    .map((activity) => ({ ...activity, categoryId: category.id })),
))
const selectedCategoryCount = computed(() => new Set(
  selectedActivities.value.map((activity) => String(activity.categoryId)),
).size)
const totalSelectedTp = computed(() => selectedActivities.value.reduce(
  (total, activity) => total + activityTpValue(activity),
  0,
))
const deductionCapLabel = computed(() => {
  const cap = Number(profile.value?.family?.daily_deduction_cap)
  return Number.isInteger(cap) && cap >= 0 ? `${cap} TP max` : 'Not configured'
})

function isActivityAssigned(activity) {
  return assignedActivityIds.value.includes(String(activity.id))
}

function isActivitySelected(activity) {
  return selectedActivityIds.value.includes(String(activity.id))
}

function selectedCountForCategory(category) {
  return category.activities.filter(isActivitySelected).length
}

function selectableActivities(category) {
  return category.activities.filter((activity) => !isActivityAssigned(activity))
}

function isCategoryFullySelected(category) {
  const selectable = selectableActivities(category)
  return selectable.length > 0 && selectable.every(isActivitySelected)
}

function toggleCategory(categoryId) {
  const id = String(categoryId)
  expandedCategoryIds.value = expandedCategoryIds.value.includes(id)
    ? expandedCategoryIds.value.filter((expandedId) => expandedId !== id)
    : [...expandedCategoryIds.value, id]
}

function toggleCategorySelection(category) {
  const selectable = selectableActivities(category)
  const selectableIds = selectable.map((activity) => String(activity.id))
  const nextIds = new Set(selectedActivityIds.value)

  if (isCategoryFullySelected(category)) {
    selectableIds.forEach((id) => nextIds.delete(id))
  } else {
    selectableIds.forEach((id) => nextIds.add(id))
  }

  selectedActivityIds.value = [...nextIds]
}

function setActivitySelected(activity, isSelected) {
  if (isActivityAssigned(activity)) {
    return
  }

  const id = String(activity.id)
  selectedActivityIds.value = isSelected
    ? [...new Set([...selectedActivityIds.value, id])]
    : selectedActivityIds.value.filter((selectedId) => selectedId !== id)
}

function activityTpValue(activity) {
  return Number(tpDrafts.value[String(activity.id)] ?? activity.default_tp)
}

function updateActivityTp(activity, value) {
  tpDrafts.value = {
    ...tpDrafts.value,
    [String(activity.id)]: value,
  }
}

function requestExit() {
  isExitConfirmationOpen.value = true
}

function keepSettingUp() {
  isExitConfirmationOpen.value = false
  resolveRouteLeave?.(false)
  resolveRouteLeave = null
}

async function discardSetup() {
  sessionStorage.removeItem('kids-tp:child-setup-draft')
  isExitConfirmationOpen.value = false
  allowRouteLeave = true

  if (resolveRouteLeave) {
    resolveRouteLeave(true)
    resolveRouteLeave = null
    return
  }

  await router.replace({ name: 'dashboard' })
}

async function loadSetup() {
  isLoading.value = true
  loadError.value = ''

  try {
    const rawDraft = sessionStorage.getItem('kids-tp:child-setup-draft')
    if (!rawDraft) {
      throw new Error('No child setup is in progress. Start by adding a child from the dashboard.')
    }

    const draft = JSON.parse(rawDraft)
    if (!draft || typeof draft.name !== 'string' || !draft.name || !draft.date_of_birth) {
      throw new Error('The saved child details are incomplete. Return to the dashboard and try again.')
    }

    const [userResult, categoryResult] = await Promise.all([
      getCurrentUser(),
      fetchCategories(),
    ])

    if (!Array.isArray(categoryResult.categories)) {
      throw new Error('The API returned invalid child setup data.')
    }

    childDraft.value = draft
    child.value = { ...draft }
    for (const category of categoryResult.categories) {
      if (!Array.isArray(category.activities)) {
        throw new Error(`The API returned invalid activities for ${category.name}.`)
      }
    }

    profile.value = userResult
    categories.value = categoryResult.categories
    assignedActivityIds.value = []
    selectedActivityIds.value = []
    tpDrafts.value = Object.fromEntries(categories.value.flatMap((category) =>
      category.activities.map((activity) => {
        return [String(activity.id), Number(activity.default_tp)]
      }),
    ))
    expandedCategoryIds.value = []
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      allowRouteLeave = true
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    loadError.value = error instanceof Error ? error.message : 'Unable to load child setup.'
  } finally {
    isLoading.value = false
  }
}

async function completeSetup() {
  if (!child.value || isCompleting.value) {
    return
  }

  if (!selectedActivities.value.length) {
    saveError.value = 'Select at least one activity to complete setup.'
    return
  }

  saveError.value = ''
  const pendingActivities = selectedActivities.value.filter(
    (activity) => !assignedActivityIds.value.includes(String(activity.id)),
  )

  for (const activity of pendingActivities) {
    const tp = activityTpValue(activity)
    if (!Number.isInteger(tp) || tp < 1 || tp > 4294967295) {
      saveError.value = `Enter a whole TP reward between 1 and 4,294,967,295 for ${activity.title}.`
      return
    }
  }

  isCompleting.value = true
  let createdChild = null

  try {
    const childResult = await addChild(childDraft.value)
    createdChild = childResult.child
    if (!createdChild) {
      throw new Error('The API did not return the new child.')
    }

    child.value = createdChild

    for (const activity of pendingActivities) {
      await assignChildActivity(createdChild.id, activity.id, {
        tp_override: activityTpValue(activity),
      })
      assignedActivityIds.value = [...assignedActivityIds.value, String(activity.id)]
    }

    sessionStorage.removeItem('kids-tp:child-setup-draft')
    allowRouteLeave = true
    await router.replace({
      name: 'child-profile',
      params: { childId: createdChild.id },
    })
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Unable to save child activities.'
    let cleanupMessage = ''

    if (createdChild) {
      try {
        await removeChild(createdChild.id)
        child.value = { ...childDraft.value }
        assignedActivityIds.value = []
      } catch (cleanupError) {
        cleanupMessage = cleanupError instanceof Error
          ? cleanupError.message
          : 'Unable to remove the unfinished child profile.'
      }
    }

    if (error instanceof ApiError && error.status === 401) {
      allowRouteLeave = true
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    saveError.value = cleanupMessage
      ? `${message} The unfinished child could not be removed: ${cleanupMessage}`
      : message
  } finally {
    isCompleting.value = false
  }
}

onBeforeRouteLeave(() => {
  if (!getAuthToken()) {
    sessionStorage.removeItem('kids-tp:child-setup-draft')
    return true
  }

  if (allowRouteLeave) {
    return true
  }

  isExitConfirmationOpen.value = true
  return new Promise((resolve) => {
    resolveRouteLeave = resolve
  })
})

onMounted(loadSetup)
</script>
