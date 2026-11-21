<template>
  <main class="activities-page account-page-main">

    <div class="activities-page-content">
      <nav class="activities-breadcrumb" aria-label="Breadcrumb">
        <template v-if="isCategoryDetail">
          <RouterLink to="/activities">‹ Categories</RouterLink>
          <span aria-hidden="true">/</span>
          <span>{{ selectedCategory?.name || 'Activities' }}</span>
        </template>
        <template v-else>
          <RouterLink to="/dashboard">Dashboard</RouterLink>
          <span aria-hidden="true">/</span>
          <span>{{ isChildActivitiesPage ? `${selectedChild?.name || 'Child'}'s Activities` : 'Activities' }}</span>
        </template>
      </nav>

      <header v-if="!isCategoryDetail" class="activities-page-heading">
        <div>
          <h1 v-if="isChildActivitiesPage">{{ selectedChild?.name || 'Child' }}'s Activities</h1>
          <h1 v-else>Categories</h1>
          <p v-if="isChildActivitiesPage">
            Activities assigned specifically to {{ selectedChild?.name || 'this child' }}.
          </p>
          <p v-else>Browse activities by category. Customize TP rewards from each child’s Activities page.</p>
        </div>
        <div class="activities-heading-actions">
          <span class="activities-total">{{ activityCount }} activities</span>
          <button
            v-if="isChildActivitiesPage"
            type="button"
            class="activity-primary-button"
            :aria-expanded="assignmentPanelOpen"
            aria-controls="child-activity-assignment-panel"
            @click="toggleAssignmentPanel"
          >
            {{ assignmentPanelOpen ? 'Close assignments' : 'Assign Activities' }}
          </button>
          <button
            v-if="!isChildActivitiesPage"
            type="button"
            class="activity-primary-button"
            @click="openCategoryForm"
          >
            Add Category
          </button>
        </div>
      </header>

      <div v-if="isLoading" class="activities-state" role="status">
        Loading activities...
      </div>
      <div v-else-if="loadError" class="activities-state activities-state-error" role="alert">
        <p>{{ loadError }}</p>
        <button type="button" @click="loadActivities()">Try again</button>
      </div>
      <div v-else-if="isChildActivitiesPage && !selectedChild" class="activities-state">
        This child could not be found in your family.
        <RouterLink to="/dashboard">Back to dashboard</RouterLink>
      </div>
      <template v-else-if="isCategoryDetail && selectedCategory">
        <header class="category-detail-heading">
          <span class="category-detail-icon" :class="categoryColorClass(selectedCategory.name)">
            <v-icon :icon="categoryIcon(selectedCategory)" />
          </span>
          <span class="category-detail-copy">
            <h1>{{ selectedCategory.name }}</h1>
            <p>{{ selectedCategory.description }}</p>
            <p v-if="!isChildActivitiesPage">
              Customize TP rewards from each child’s Activities page.
            </p>
          </span>
          <button
            v-if="!isChildActivitiesPage && selectedCategory.is_default === false"
            type="button"
            class="activity-delete-button"
            :aria-label="`Delete ${selectedCategory.name} category`"
            :disabled="isDeletingCategoryId === selectedCategory.id"
            @click="requestCategoryDelete(selectedCategory)"
          >
            <v-icon icon="mdi-delete-outline" />
          </button>
        </header>

        <section
          v-if="selectedCategory.activities.length"
          class="category-detail-activities"
          aria-labelledby="category-activities-title"
        >
          <div class="activity-section-heading">
            <h2 id="category-activities-title">Activities</h2>
          </div>
          <ul class="activity-list category-detail-list">
            <li
              v-for="activity in selectedCategory.activities"
              :key="activity.id"
              class="activity-item"
            >
              <div class="activity-row">
                <span class="activity-icon" :class="categoryColorClass(selectedCategory.name)" aria-hidden="true">
                  <v-icon :icon="activityIcon(activity.title, selectedCategory.name)" />
                </span>
                <span class="activity-copy">
                  <strong>{{ activity.title }}</strong>
                  <small>
                    +{{ activity.default_tp }} TP
                    <span class="activity-deduction">
                      Suggested deduction: {{ activity.suggested_deduction ? `−${activity.suggested_deduction} TP` : '0 TP' }}
                    </span>
                  </small>
                </span>
                <button
                  v-if="!isChildActivitiesPage && activity.is_default === false"
                  type="button"
                  class="activity-edit-button"
                  :aria-label="`Edit ${activity.title}`"
                  @click="startEditingActivity(activity, selectedCategory)"
                >
                  <v-icon icon="mdi-pencil-outline" />
                </button>
                <button
                  v-if="!isChildActivitiesPage && activity.is_default === false"
                  type="button"
                  class="activity-delete-button"
                  :aria-label="`Delete ${activity.title}`"
                  :disabled="isDeletingActivityId === activity.id"
                  @click="requestActivityDelete(activity, selectedCategory)"
                >
                  <v-icon icon="mdi-delete-outline" />
                </button>
              </div>
            </li>
          </ul>
          <div v-if="!isChildActivitiesPage" class="category-detail-add-activity">
            <button type="button" class="activity-primary-button" @click="openActivityForm(selectedCategory)">
              <v-icon icon="mdi-plus" /> Add Activity
            </button>
          </div>
        </section>
        <section v-else class="category-empty-state">
          <img class="category-empty-logo" src="/logo.png" alt="Kids TP logo" />
          <h2>No activities yet</h2>
          <p v-if="isChildActivitiesPage">
            No activities in this category are assigned to {{ selectedChild?.name }}.
          </p>
          <template v-else>
            <p>Add activities that fit this category and choose their TP values.</p>
            <button type="button" class="activity-primary-button" @click="openActivityForm(selectedCategory)">
              <v-icon icon="mdi-plus" /> Add Activity
            </button>
          </template>
        </section>
      </template>
      <div v-else-if="isCategoryDetail" class="activities-state">
        This category could not be found.
        <RouterLink to="/activities">Back to categories</RouterLink>
      </div>
      <div v-else-if="visibleCategories.length" class="category-list">
        <article
          v-for="category in visibleCategories"
          :key="category.id"
          class="category-card"
          :class="{
            'category-card-expanded': isChildActivitiesPage
              ? expandedAssignedCategoryIds.includes(category.id)
              : expandedCategoryIds.includes(category.id),
          }"
        >
          <div class="category-heading-row">
            <button
              class="category-toggle"
              type="button"
              :aria-expanded="isChildActivitiesPage
                ? expandedAssignedCategoryIds.includes(category.id)
                : expandedCategoryIds.includes(category.id)"
              :aria-controls="`category-activities-${category.id}`"
              @click="selectCategory(category.id)"
            >
              <span class="category-icon" :class="categoryColorClass(category.name)">
                <v-icon :icon="categoryIcon(category)" />
              </span>
              <span class="category-copy">
                <strong>{{ category.name }}</strong>
                <small>{{ category.description }}</small>
                <span class="category-count">{{ category.activities.length }} activities</span>
              </span>
              <span class="category-action">
                <span>
                  {{
                    (isChildActivitiesPage
                      ? expandedAssignedCategoryIds.includes(category.id)
                      : expandedCategoryIds.includes(category.id))
                      ? 'Hide activities'
                      : 'View activities'
                  }}
                </span>
                <v-icon
                  :icon="(isChildActivitiesPage
                    ? expandedAssignedCategoryIds.includes(category.id)
                    : expandedCategoryIds.includes(category.id))
                    ? 'mdi-chevron-up'
                    : 'mdi-chevron-right'"
                />
              </span>
            </button>
            <button
              v-if="!isChildActivitiesPage && category.is_default === false"
              type="button"
              class="activity-delete-button category-delete-button"
              :aria-label="`Delete ${category.name} category`"
              :disabled="isDeletingCategoryId === category.id"
              @click="requestCategoryDelete(category)"
            >
              <v-icon icon="mdi-delete-outline" />
            </button>
          </div>

          <ul
            v-if="isChildActivitiesPage
              ? expandedAssignedCategoryIds.includes(category.id)
              : expandedCategoryIds.includes(category.id)"
            :id="`category-activities-${category.id}`"
            class="activity-list"
          >
            <li v-for="activity in category.activities" :key="activity.id" class="activity-item">
              <div class="activity-row">
                <span class="activity-icon" :class="categoryColorClass(category.name)" aria-hidden="true">
                  <v-icon :icon="activityIcon(activity.title, category.name)" />
                </span>
                <span class="activity-copy">
                  <strong>{{ activity.title }}</strong>
                  <small>{{ activity.description }}</small>
                </span>
                <span class="activity-values">
                  <strong>+{{ activityPointValue(activity) }} TP</strong>
                  <small>
                    Suggested deduction:
                    {{ activity.suggested_deduction ? `−${activity.suggested_deduction} TP` : 'None' }}
                  </small>
                </span>
                <button
                  v-if="isChildActivitiesPage"
                  type="button"
                  class="activity-edit-button"
                  :aria-label="`Edit ${activity.title} TP for ${selectedChild?.name || 'this child'}`"
                  @click="startEditingChildReward(activity)"
                >
                  <v-icon icon="mdi-pencil-outline" />
                </button>
                <button
                  v-if="!isChildActivitiesPage && activity.is_default === false"
                  type="button"
                  class="activity-edit-button"
                  :aria-label="`Edit ${activity.title}`"
                  @click="startEditingActivity(activity, category)"
                >
                  <v-icon icon="mdi-pencil-outline" />
                </button>
                <button
                  v-if="!isChildActivitiesPage && activity.is_default === false"
                  type="button"
                  class="activity-delete-button"
                  :aria-label="`Delete ${activity.title}`"
                  :disabled="isDeletingActivityId === activity.id"
                  @click="requestActivityDelete(activity, category)"
                >
                  <v-icon icon="mdi-delete-outline" />
                </button>
              </div>
            </li>
          </ul>
          <p
            v-if="(isChildActivitiesPage
              ? expandedAssignedCategoryIds.includes(category.id)
              : expandedCategoryIds.includes(category.id)) && !category.activities.length"
            class="category-empty-inline"
          >
            No activities yet. Add one to get started.
          </p>
          <div
            v-if="expandedCategoryIds.includes(category.id) && !isChildActivitiesPage"
            class="category-add-activity"
          >
            <button
              type="button"
              class="activity-primary-button"
              @click="openActivityForm(category)"
            >
              <v-icon icon="mdi-plus" /> Add Activity
            </button>
          </div>
        </article>
      </div>
      <div v-else class="activities-state">
        <template v-if="isChildActivitiesPage">
          <p>No activities are assigned to {{ selectedChild?.name }} yet.</p>
        </template>
        <template v-else>No categories are available.</template>
      </div>

      <div
        v-if="isChildActivitiesPage && !isLoading && !loadError && selectedChild && !isCategoryDetail && assignmentPanelOpen"
        class="child-activity-assignment-backdrop"
        @click.self="assignmentPanelOpen = false"
      >
        <section
          id="child-activity-assignment-panel"
          class="child-activity-assignment-panel"
          role="dialog"
          aria-modal="true"
          aria-labelledby="child-activity-assignment-title"
          @keydown.esc.stop="assignmentPanelOpen = false"
        >
          <header class="child-activity-assignment-heading">
            <div>
              <h2 id="child-activity-assignment-title">Assign activities to {{ selectedChild.name }}</h2>
              <p>Set this child’s TP reward before assigning each activity.</p>
            </div>
            <button
              type="button"
              class="activity-dialog-close"
              aria-label="Close assignments"
              @click="assignmentPanelOpen = false"
            >
              <v-icon icon="mdi-close" />
            </button>
          </header>
          <p v-if="assignmentError" class="activities-state-error" role="alert">
            {{ assignmentError }}
          </p>
          <div v-if="assignableCategories.length" class="child-activity-assignment-categories">
            <section
              v-for="category in assignableCategories"
              :key="category.id"
              class="child-activity-assignment-category"
            >
              <button
                type="button"
                class="child-activity-assignment-toggle"
                :aria-expanded="expandedAssignmentCategoryIds.includes(category.id)"
                :aria-controls="`child-assignment-activities-${category.id}`"
                @click="toggleAssignmentCategory(category.id)"
              >
                <span>
                  <strong>{{ category.name }}</strong>
                  <small>{{ category.activities.length }} available activities</small>
                </span>
                <v-icon
                  :icon="expandedAssignmentCategoryIds.includes(category.id)
                    ? 'mdi-chevron-up'
                    : 'mdi-chevron-down'"
                />
              </button>
              <ul
                v-if="expandedAssignmentCategoryIds.includes(category.id)"
                :id="`child-assignment-activities-${category.id}`"
              >
                <li v-for="activity in category.activities" :key="activity.id">
                  <span>
                    <strong>{{ activity.title }}</strong>
                    <small>Default: +{{ activity.default_tp }} TP</small>
                  </span>
                  <label class="child-activity-assignment-reward">
                    TP reward
                    <input
                      type="number"
                      min="1"
                      max="4294967295"
                      :aria-label="`TP reward for ${activity.title} for ${selectedChild.name}`"
                      :value="assignmentTpValue(activity)"
                      :disabled="isAssigningActivityId === activity.id"
                      @input="updateAssignmentTpDraft(activity, $event.target.value)"
                    />
                  </label>
                  <button
                    type="button"
                    class="activity-primary-button"
                    :disabled="isAssigningActivityId === activity.id"
                    @click="assignActivityToChild(activity)"
                  >
                    {{ isAssigningActivityId === activity.id ? 'Assigning…' : 'Assign' }}
                  </button>
                </li>
              </ul>
            </section>
          </div>
          <p v-else class="child-activity-assignment-empty">
            All available activities are already assigned to {{ selectedChild.name }}.
          </p>
        </section>
      </div>
      <div
        v-if="editingChildReward && selectedChild"
        class="activity-dialog-backdrop"
        @click.self="closeChildRewardDialog"
      >
        <section
          class="activity-dialog"
          role="dialog"
          aria-modal="true"
          aria-labelledby="child-reward-dialog-title"
        >
          <header class="activity-dialog-header">
            <div>
              <h2 id="child-reward-dialog-title">Edit {{ selectedChild.name }}’s TP reward</h2>
              <p>{{ editingChildReward.title }}</p>
            </div>
            <button
              type="button"
              class="activity-dialog-close"
              aria-label="Close"
              :disabled="isSavingChildReward"
              @click="closeChildRewardDialog"
            >
              <v-icon icon="mdi-close" />
            </button>
          </header>
          <form class="activity-dialog-form" @submit.prevent="saveChildReward">
            <label class="activity-dialog-field">
              Trust Points for {{ selectedChild.name }}
              <input
                v-model.number="childRewardDraft"
                type="number"
                min="1"
                max="4294967295"
                required
              />
            </label>
            <p class="activity-dialog-category-hint">
              This reward changes only for {{ selectedChild.name }}.
            </p>
            <p v-if="childRewardError" class="activity-form-error" role="alert">
              {{ childRewardError }}
            </p>
            <div class="activity-dialog-actions">
              <button
                type="button"
                class="activity-secondary-button"
                :disabled="isSavingChildReward"
                @click="closeChildRewardDialog"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="activity-primary-button"
                :disabled="isSavingChildReward"
              >
                {{ isSavingChildReward ? 'Saving…' : 'Save TP reward' }}
              </button>
            </div>
          </form>
        </section>
      </div>
    </div>

    <div
      v-if="categoryFormOpen"
      class="activity-dialog-backdrop"
      @click.self="closeCategoryForm"
    >
      <section
        class="activity-dialog category-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="category-dialog-title"
      >
        <header class="activity-dialog-header">
          <div>
            <h2 id="category-dialog-title">Add Category</h2>
            <p>Create a category for your family’s activities.</p>
          </div>
          <button type="button" class="activity-dialog-close" aria-label="Close" @click="closeCategoryForm">
            <v-icon icon="mdi-close" />
          </button>
        </header>
        <form class="activity-dialog-form" @submit.prevent="submitCategory">
          <label class="dialog-field-label">Category Symbol &amp; Aesthetic</label>
          <button
            type="button"
            class="category-icon-preview"
            :aria-expanded="isIconPickerOpen"
            @click="isIconPickerOpen = !isIconPickerOpen"
          >
            <span class="category-icon category-icon-selected">
              <v-icon :icon="categoryDraft.icon" />
            </span>
            <span>
              <strong>Choose icon</strong>
              <small>Select an icon that represents this category</small>
            </span>
            <v-icon class="category-icon-preview-chevron" :icon="isIconPickerOpen ? 'mdi-chevron-up' : 'mdi-chevron-down'" />
          </button>
          <div v-if="isIconPickerOpen" class="category-icon-picker" role="group" aria-label="Choose category icon">
            <button
              v-for="icon in categoryIconOptions"
              :key="icon"
              type="button"
              :aria-label="`Choose ${icon.replace('mdi-', '').replaceAll('-', ' ')}`"
              :aria-pressed="categoryDraft.icon === icon"
              @click="categoryDraft.icon = icon"
            >
              <v-icon :icon="icon" />
            </button>
          </div>
          <label class="activity-dialog-field">
            Category name <span class="activity-required">*</span>
            <input v-model.trim="categoryDraft.name" required maxlength="100" placeholder="e.g. Creativity" />
          </label>
          <label class="activity-dialog-field">
            Description <span class="activity-optional">(optional)</span>
            <small class="activity-field-hint">Max 140 characters</small>
            <textarea
              v-model.trim="categoryDraft.description"
              rows="3"
              maxlength="140"
              placeholder="Describe this category..."
            />
          </label>
          <p v-if="categoryError" class="activity-form-error" role="alert">{{ categoryError }}</p>
          <div class="activity-dialog-actions">
            <button type="button" class="activity-secondary-button" @click="closeCategoryForm">Cancel</button>
            <button type="submit" class="activity-primary-button" :disabled="isSavingCategory">
              <v-icon icon="mdi-check" />
              {{ isSavingCategory ? 'Saving...' : 'Save Category' }}
            </button>
          </div>
        </form>
      </section>
    </div>

    <div
      v-if="activityFormCategoryId !== null && activeActivityCategory"
      class="activity-dialog-backdrop"
      @click.self="closeActivityDialog"
    >
      <section
        class="activity-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="activity-dialog-title"
      >
        <header class="activity-dialog-header">
          <div>
            <h2 id="activity-dialog-title">
              {{ activeEditingActivity ? 'Edit Activity' : 'Add Activity' }}
            </h2>
          </div>
          <button type="button" class="activity-dialog-close" aria-label="Close" @click="closeActivityDialog">
            <v-icon icon="mdi-close" />
          </button>
        </header>
        <form class="activity-dialog-form" @submit.prevent="submitActivityDialog">
          <div class="activity-dialog-category">
            <span class="category-icon" :class="categoryColorClass(activeActivityCategory.name)">
              <v-icon :icon="categoryIcon(activeActivityCategory)" />
            </span>
            <span>
              <small>Category</small>
              <strong>{{ activeActivityCategory.name }}</strong>
            </span>
          </div>
          <p v-if="activeActivityCategory.description" class="activity-dialog-category-hint">
            Category is selected automatically.
          </p>
          <ActivityEditorFields
            v-model="activityDraft"
            :family-children="familyChildren"
            :hide-default-tp="Boolean(activeEditingActivity)"
          />
          <p v-if="activityError" class="activity-form-error" role="alert">{{ activityError }}</p>
          <div class="activity-dialog-actions">
            <button type="button" class="activity-secondary-button" @click="closeActivityDialog">Cancel</button>
            <button
              type="submit"
              class="activity-primary-button"
              :disabled="isSavingActivity || isSavingEdit"
            >
              <v-icon :icon="activeEditingActivity ? 'mdi-check' : 'mdi-plus'" />
              {{ isSavingActivity || isSavingEdit ? 'Saving...' : activeEditingActivity ? 'Save Changes' : 'Save Activity' }}
            </button>
          </div>
        </form>
      </section>
    </div>

    <div
      v-if="deleteConfirmation"
      class="activity-dialog-backdrop activity-confirmation-backdrop"
      @click.self="cancelDelete"
    >
      <section
        class="activity-dialog delete-confirmation-dialog"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="delete-confirmation-title"
        aria-describedby="delete-confirmation-description"
        @keydown.esc.stop="cancelDelete"
      >
        <header class="delete-confirmation-header">
          <span class="delete-confirmation-icon" aria-hidden="true">
            <v-icon icon="mdi-delete-outline" />
          </span>
          <div>
            <h2 id="delete-confirmation-title">
              {{ deleteConfirmation.type === 'category' ? 'Delete category?' : 'Delete activity?' }}
            </h2>
            <p>This action cannot be undone.</p>
          </div>
        </header>
        <p id="delete-confirmation-description" class="delete-confirmation-description">
          <template v-if="deleteConfirmation.type === 'category' && deleteConfirmation.activityCount">
            Deleting "{{ deleteConfirmation.name }}" will also delete
            {{ deleteConfirmation.activityCount }}
            {{ deleteConfirmation.activityCount === 1 ? 'activity' : 'activities' }} in this category.
          </template>
          <template v-else-if="deleteConfirmation.type === 'category'">
            Delete the empty category "{{ deleteConfirmation.name }}"?
          </template>
          <template v-else>
            Delete the activity "{{ deleteConfirmation.name }}"?
          </template>
        </p>
        <p class="delete-confirmation-warning">
          Activities with point history are protected and cannot be deleted.
        </p>
        <p v-if="deleteError" class="activity-form-error" role="alert">{{ deleteError }}</p>
        <div class="activity-dialog-actions">
          <button
            type="button"
            class="activity-secondary-button"
            :disabled="isDeletingCategoryId !== null || isDeletingActivityId !== null"
            @click="cancelDelete"
          >
            Cancel
          </button>
          <button
            type="button"
            class="activity-danger-button"
            :disabled="isDeletingCategoryId !== null || isDeletingActivityId !== null"
            @click="confirmDeletion"
          >
            <v-icon icon="mdi-delete-outline" />
            {{ isDeletingCategoryId !== null || isDeletingActivityId !== null ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </section>
    </div>

    <footer class="activities-footer">
      <span><strong>kids tp</strong> &nbsp;•&nbsp; Ethiopian Family Habit &amp; Trust Management</span>
      <span>Governed Family Custody • Family activity categories</span>
    </footer>

    <nav class="activities-mobile-nav" aria-label="Main navigation">
      <RouterLink to="/dashboard">
        <v-icon icon="mdi-view-dashboard-outline" /><span>Home</span>
      </RouterLink>
      <RouterLink to="/family">
        <v-icon icon="mdi-account-group-outline" /><span>Family</span>
      </RouterLink>
      <RouterLink class="activities-nav-current" to="/activities">
        <v-icon icon="mdi-format-list-checks" /><span>{{ isCategoryDetail ? 'Categories' : 'Activities' }}</span>
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
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ActivityEditorFields from '../components/ActivityEditorFields.vue'
import { usePageActivation } from '../composables/usePageActivation.js'
import {
  ApiError,
  addCategory,
  addCategoryActivity,
  assignChildActivity,
  clearAuthToken,
  deleteActivity as deleteActivityRequest,
  deleteCategory as deleteCategoryRequest,
  fetchCategories,
  fetchChildren,
  getCurrentUser,
  updateActivity,
  updateChildActivityTp,
} from '../services/auth.js'

const categoryIconOptions = [
  'mdi-palette-outline',
  'mdi-book-open-variant',
  'mdi-school-outline',
  'mdi-emoticon-happy-outline',
  'mdi-music-note',
  'mdi-sprout',
  'mdi-run',
  'mdi-heart-outline',
  'mdi-home-outline',
  'mdi-lightbulb-outline',
  'mdi-star-outline',
  'mdi-account-group-outline',
]
const router = useRouter()
const route = useRoute()
const profile = ref(null)
const categories = ref([])
const familyChildren = ref([])
const expandedCategoryIds = ref([])
const expandedAssignedCategoryIds = ref([])
const expandedAssignmentCategoryIds = ref([])
const isLoading = ref(true)
const loadError = ref('')
const assignmentPanelOpen = ref(false)
const assignmentError = ref('')
const assignmentTpDrafts = ref({})
const isAssigningActivityId = ref(null)
const editingChildReward = ref(null)
const childRewardDraft = ref(1)
const childRewardError = ref('')
const isSavingChildReward = ref(false)
const categoryFormOpen = ref(false)
const categoryDraft = ref({ name: '', description: '' })
const categoryError = ref('')
const isIconPickerOpen = ref(false)
const isSavingCategory = ref(false)
const activityFormCategoryId = ref(null)
const activityDraft = ref(createActivityDraft())
const activityError = ref('')
const isSavingActivity = ref(false)
const editingActivityId = ref(null)
const isSavingEdit = ref(false)
const deleteError = ref('')
const deleteConfirmation = ref(null)
const isDeletingCategoryId = ref(null)
const isDeletingActivityId = ref(null)
const activityCount = computed(() =>
  visibleCategories.value.reduce((total, category) => total + category.activities.length, 0),
)
const isCategoryDetail = computed(() => Boolean(route.params.categoryId))
const isChildActivitiesPage = computed(() => Boolean(route.params.childId))
const selectedChild = computed(() =>
  familyChildren.value.find((child) => String(child.id) === String(route.params.childId)),
)
const visibleCategories = computed(() => {
  if (!isChildActivitiesPage.value || !selectedChild.value) {
    return categories.value
  }

  return categories.value
    .map((category) => ({
      ...category,
      activities: category.activities.filter((activity) =>
        isActivityAssignedToChild(activity, selectedChild.value.id),
      ),
    }))
    .filter((category) => category.activities.length > 0)
})
const assignableCategories = computed(() => {
  if (!selectedChild.value) {
    return []
  }

  return categories.value
    .map((category) => ({
      ...category,
      activities: category.activities.filter((activity) =>
        !isActivityAssignedToChild(activity, selectedChild.value.id),
      ),
    }))
    .filter((category) => category.activities.length > 0)
})
const activeActivityCategory = computed(() =>
  categories.value.find((category) => String(category.id) === String(activityFormCategoryId.value)),
)
const activeEditingActivity = computed(() =>
  activeActivityCategory.value?.activities.find(
    (activity) => String(activity.id) === String(editingActivityId.value),
  ),
)
const selectedCategory = computed(() =>
  visibleCategories.value.find((category) => String(category.id) === String(route.params.categoryId)),
)

watch(() => route.params.childId, () => {
  expandedAssignedCategoryIds.value = []
  expandedAssignmentCategoryIds.value = []
  assignmentPanelOpen.value = false
  assignmentError.value = ''
  assignmentTpDrafts.value = {}
})

function categoryIcon(categoryOrName) {
  const name = typeof categoryOrName === 'string' ? categoryOrName : categoryOrName?.name
  if (typeof categoryOrName === 'object' && categoryOrName?.icon) {
    return categoryOrName.icon
  }

  const icons = {
    Education: 'mdi-book-open-variant',
    'Personal Care': 'mdi-heart-outline',
    Household: 'mdi-home-outline',
    'Trust & Character': 'mdi-account-heart-outline',
    'Time Management': 'mdi-clock-outline',
    'Digital Responsibility': 'mdi-cellphone-check',
    'Financial Literacy': 'mdi-piggy-bank-outline',
  }

  return icons[name] || 'mdi-format-list-checks'
}

function categoryColorClass(name) {
  return `category-icon-${name.toLowerCase().replace(/[^a-z]+/g, '-')}`
}

function isActivityAssignedToChild(activity, childId) {
  return (activity.child_ids || []).some((id) => String(id) === String(childId))
}

function openAssignmentPanel() {
  assignmentPanelOpen.value = true
}

function toggleAssignmentPanel() {
  if (assignmentPanelOpen.value) {
    assignmentPanelOpen.value = false
    return
  }

  openAssignmentPanel()
}

function activityIcon(title, categoryName) {
  const icons = {
    'Complete homework': 'mdi-pencil',
    'Independent study': 'mdi-book-open-page-variant',
    'Exam preparation': 'mdi-file-document-outline',
    'Exam effort': 'mdi-check',
    'Correct mistakes': 'mdi-lightbulb-outline',
    'Reading habit': 'mdi-book-open-variant',
    'Ask for help': 'mdi-help-circle-outline',
    'Morning hygiene': 'mdi-water-outline',
    'Make the bed': 'mdi-bed-outline',
    'Organize belongings': 'mdi-hanger',
    'Prepare for school': 'mdi-bag-personal-outline',
    'Wash personal items': 'mdi-tshirt-crew-outline',
    'Prepare own food': 'mdi-food-apple-outline',
    'Appropriate eating habits': 'mdi-silverware-fork-knife',
  }

  if (icons[title]) {
    return icons[title]
  }

  return categoryName === 'Education' ? 'mdi-book-open-variant' : categoryIcon(categoryName)
}

function activityPointValue(activity) {
  const override = activity.child_tp_overrides?.find(
    (item) => String(item.child_id) === String(selectedChild.value?.id),
  )

  return Number(override?.tp_override ?? activity.default_tp)
}

function assignmentTpValue(activity) {
  return assignmentTpDrafts.value[activity.id] ?? activity.default_tp
}

function updateAssignmentTpDraft(activity, value) {
  assignmentTpDrafts.value = {
    ...assignmentTpDrafts.value,
    [activity.id]: value,
  }
  assignmentError.value = ''
}

function startEditingChildReward(activity) {
  editingChildReward.value = activity
  childRewardDraft.value = activityPointValue(activity)
  childRewardError.value = ''
}

function closeChildRewardDialog() {
  if (isSavingChildReward.value) {
    return
  }

  editingChildReward.value = null
  childRewardError.value = ''
}

async function saveChildReward() {
  const child = selectedChild.value
  const activity = editingChildReward.value
  const tpOverride = Number(childRewardDraft.value)

  if (!child || !activity) {
    return
  }

  if (!Number.isInteger(tpOverride) || tpOverride < 1 || tpOverride > 4294967295) {
    childRewardError.value = 'Enter a whole TP value between 1 and 4,294,967,295.'
    return
  }

  isSavingChildReward.value = true
  childRewardError.value = ''

  try {
    await updateChildActivityTp(child.id, activity.id, { tp_override: tpOverride })
    await loadActivities(false)
    editingChildReward.value = null
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    childRewardError.value = error instanceof Error
      ? error.message
      : 'Unable to update this child’s TP reward.'
  } finally {
    isSavingChildReward.value = false
  }
}

function selectCategory(categoryId) {
  if (isChildActivitiesPage.value) {
    toggleExpandedCategory(categoryId, expandedAssignedCategoryIds)
    return
  }

  if (window.matchMedia('(max-width: 760px)').matches) {
    router.push({ name: 'category-activities', params: { categoryId } })
    return
  }

  toggleCategory(categoryId)
}

function toggleCategory(categoryId) {
  toggleExpandedCategory(categoryId, expandedCategoryIds)
}

function toggleAssignmentCategory(categoryId) {
  toggleExpandedCategory(categoryId, expandedAssignmentCategoryIds)
}

function toggleExpandedCategory(categoryId, expandedIds) {
  expandedIds.value = expandedIds.value.includes(categoryId)
    ? expandedIds.value.filter((id) => id !== categoryId)
    : [...expandedIds.value, categoryId]
}

function createActivityDraft() {
  return {
    title: '',
    description: '',
    default_tp: '',
    suggested_deduction: '',
    weight_multiplier: 1,
    child_ids: [],
  }
}

function openCategoryForm() {
  categoryFormOpen.value = true
  categoryDraft.value = { name: '', description: '', icon: categoryIconOptions[0] }
  categoryError.value = ''
  isIconPickerOpen.value = false
}

function closeCategoryForm() {
  if (isSavingCategory.value) {
    return
  }

  categoryFormOpen.value = false
  categoryError.value = ''
  isIconPickerOpen.value = false
}

async function submitCategory() {
  isSavingCategory.value = true
  categoryError.value = ''

  try {
    const result = await addCategory(categoryDraft.value)
    categoryFormOpen.value = false
    await loadActivities()
    const createdCategory = categories.value.find(
      (category) => String(category.id) === String(result.category?.id),
    )

    if (createdCategory) {
      if (window.matchMedia('(max-width: 760px)').matches) {
        await router.push({
          name: 'category-activities',
          params: { categoryId: createdCategory.id },
        })
      } else {
        expandedCategoryIds.value = [createdCategory.id]
      }
    }
  } catch (error) {
    categoryError.value = error instanceof Error ? error.message : 'Unable to save this category.'
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
    }
  } finally {
    isSavingCategory.value = false
  }
}

function openActivityForm(category) {
  activityFormCategoryId.value = category.id
  editingActivityId.value = null
  activityDraft.value = createActivityDraft()
  activityError.value = ''
}

function closeActivityDialog(force = false) {
  if (!force && (isSavingActivity.value || isSavingEdit.value)) {
    return
  }

  activityFormCategoryId.value = null
  editingActivityId.value = null
  activityError.value = ''
}

async function submitActivityDialog() {
  const category = activeActivityCategory.value
  if (!category) {
    return
  }

  const isEditing = Boolean(activeEditingActivity.value)
  isSavingActivity.value = !isEditing
  isSavingEdit.value = isEditing
  activityError.value = ''

  try {
    if (activeEditingActivity.value) {
      const {
        title,
        description,
        suggested_deduction,
        weight_multiplier,
        child_ids,
      } = activityDraft.value
      await updateActivity(activeEditingActivity.value.id, {
        title,
        description,
        suggested_deduction,
        weight_multiplier,
        child_ids,
      })
    } else {
      await addCategoryActivity(category.id, activityDraft.value)
    }

    closeActivityDialog(true)
    await refreshCategory(category.name)
  } catch (error) {
    activityError.value = error instanceof Error
      ? error.message
      : 'Unable to save this activity.'
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
    }
  } finally {
    isSavingActivity.value = false
    isSavingEdit.value = false
  }
}

function startEditingActivity(activity, category) {
  activityFormCategoryId.value = category.id
  editingActivityId.value = activity.id
  activityDraft.value = {
    title: activity.title,
    description: activity.description || '',
    default_tp: activity.default_tp,
    suggested_deduction: activity.suggested_deduction,
    weight_multiplier: Number(activity.weight_multiplier) || 1,
    child_ids: activity.child_ids || [],
  }
  activityError.value = ''
}

function requestCategoryDelete(category) {
  if (category.is_default !== false) {
    return
  }

  deleteError.value = ''
  deleteConfirmation.value = {
    type: 'category',
    id: category.id,
    name: category.name,
    activityCount: category.activities.length,
  }
}

function requestActivityDelete(activity, category) {
  if (activity.is_default !== false) {
    return
  }

  deleteError.value = ''
  deleteConfirmation.value = {
    type: 'activity',
    id: activity.id,
    name: activity.title,
    categoryName: category.name,
  }
}

async function assignActivityToChild(activity) {
  const child = selectedChild.value
  if (!child || isAssigningActivityId.value !== null) {
    return
  }

  const tpOverride = Number(assignmentTpValue(activity))
  if (!Number.isInteger(tpOverride) || tpOverride < 1 || tpOverride > 4294967295) {
    assignmentError.value = 'Enter a whole TP value between 1 and 4,294,967,295.'
    return
  }

  assignmentError.value = ''
  isAssigningActivityId.value = activity.id

  try {
    const result = await assignChildActivity(child.id, activity.id, { tp_override: tpOverride })
    if (result.activity_id == null || String(result.child_id) !== String(child.id)) {
      throw new Error('The API returned an invalid activity assignment.')
    }

    categories.value = categories.value.map((category) => ({
      ...category,
      activities: category.activities.map((currentActivity) => {
        if (String(currentActivity.id) !== String(activity.id)) {
          return currentActivity
        }

        const childIds = currentActivity.child_ids || []
        const childTpOverrides = (currentActivity.child_tp_overrides || [])
          .filter((override) => String(override.child_id) !== String(child.id))

        return {
          ...currentActivity,
          id: result.activity_id,
          child_ids: childIds.some((id) => String(id) === String(child.id))
            ? childIds
            : [...childIds, child.id],
          child_tp_overrides: [
            ...childTpOverrides,
            { child_id: child.id, tp_override: tpOverride },
          ],
        }
      }),
    }))

    const nextDrafts = { ...assignmentTpDrafts.value }
    delete nextDrafts[activity.id]
    assignmentTpDrafts.value = nextDrafts
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    assignmentError.value = error instanceof Error
      ? error.message
      : 'Unable to assign this activity.'
  } finally {
    isAssigningActivityId.value = null
  }
}

function cancelDelete() {
  if (isDeletingCategoryId.value !== null || isDeletingActivityId.value !== null) {
    return
  }

  deleteConfirmation.value = null
  deleteError.value = ''
}

async function confirmDeletion() {
  const target = deleteConfirmation.value
  if (!target) {
    return
  }

  deleteError.value = ''

  try {
    if (target.type === 'category') {
      isDeletingCategoryId.value = target.id
      await deleteCategoryRequest(target.id)
      deleteConfirmation.value = null

      if (isCategoryDetail.value) {
        await router.replace({ name: 'activities' })
      }

      await loadActivities()
    } else {
      isDeletingActivityId.value = target.id
      await deleteActivityRequest(target.id)
      deleteConfirmation.value = null
      await refreshCategory(target.categoryName)
    }
  } catch (error) {
    deleteError.value = error instanceof Error
      ? error.message
      : 'Unable to delete this item.'
    if (error instanceof ApiError && error.status === 401) {
      deleteConfirmation.value = null
      clearAuthToken()
      await router.replace({ name: 'login' })
    }
  } finally {
    isDeletingCategoryId.value = null
    isDeletingActivityId.value = null
  }
}

async function refreshCategory(categoryName) {
  await loadActivities()
  const updatedCategory = categories.value.find((category) => category.name === categoryName)

  if (!updatedCategory) {
    return
  }

  if (isCategoryDetail.value) {
    await router.replace({
      name: 'category-activities',
      params: { categoryId: updatedCategory.id },
    })
  } else {
    expandedCategoryIds.value = [updatedCategory.id]
  }
}

async function loadActivities(showLoading = true, background = false) {
  const hasExistingData = Boolean(profile.value)

  if (showLoading) {
    isLoading.value = true
  }
  loadError.value = ''

  try {
    const [userResult, categoryResult, childrenResult] = await Promise.all([
      getCurrentUser({ bypassCache: background }),
      fetchCategories({ bypassCache: background }),
      fetchChildren({ bypassCache: background }),
    ])

    if (!Array.isArray(categoryResult.categories)) {
      throw new Error('The API returned an invalid activity category list.')
    }

    if (!Array.isArray(childrenResult.children)) {
      throw new Error('The API returned an invalid family child list.')
    }

    for (const category of categoryResult.categories) {
      if (!Array.isArray(category.activities)) {
        throw new Error(`The API returned invalid activities for ${category.name}.`)
      }
    }

    profile.value = userResult
    categories.value = categoryResult.categories
    familyChildren.value = childrenResult.children
    expandedCategoryIds.value = []
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }

    if (background && hasExistingData) {
      console.error('Unable to refresh activity categories in the background.', error)
    } else {
      loadError.value = error instanceof Error ? error.message : 'Unable to load activity categories.'
    }
  } finally {
    if (showLoading) {
      isLoading.value = false
    }
  }
}

usePageActivation(
  () => loadActivities(),
  () => loadActivities(false, true),
)
</script>

<style scoped src="../assets/styles/activities.css"></style>
