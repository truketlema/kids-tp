<template>
  <div class="activity-editor-fields">
    <div class="activity-form-fields">
      <label class="activity-form-field-wide">
        <span>Activity name <span class="activity-required">*</span></span>
        <input :value="modelValue.title" required maxlength="255" @input="updateField('title', $event.target.value)" />
      </label>
      <label class="activity-form-field-wide">
        <span>Description <span class="activity-optional">(optional)</span></span>
        <textarea
          :value="modelValue.description"
          rows="2"
          maxlength="2000"
          @input="updateField('description', $event.target.value)"
        />
      </label>
    </div>

    <div class="activity-form-fields">
      <label v-if="!hideDefaultTp">
        <span>Default TP reward <span class="activity-required">*</span></span>
        <input
          :value="modelValue.default_tp"
          type="number"
          min="1"
          max="4294967295"
          placeholder="e.g. 5"
          required
          @input="updateField('default_tp', numericValue($event.target.value))"
        />
      </label>
      <label>
        <span>Suggested deduction <span class="activity-optional">(optional)</span></span>
        <input
          :value="modelValue.suggested_deduction"
          type="number"
          min="0"
          max="4294967295"
          placeholder="e.g. 1"
          @input="updateField('suggested_deduction', numericValue($event.target.value))"
        />
      </label>
      <label class="activity-form-field-wide">
        Weight multiplier
        <select
          :value="modelValue.weight_multiplier"
          @change="updateField('weight_multiplier', Number($event.target.value))"
        >
          <option :value="1">1.0 (default)</option>
          <option :value="0.5">0.5× — light activity</option>
          <option :value="1.5">1.5× — extra effort</option>
          <option :value="2">2.0× — significant effort</option>
          <option :value="3">3.0× — major achievement</option>
          <option :value="5">5.0× — exceptional achievement</option>
        </select>
      </label>
    </div>

    <section class="activity-child-assignment" aria-label="Assign to children">
      <header>
        <strong>Assign to children</strong>
        <button type="button" class="activity-select-all" @click="toggleAllChildren">
          {{ allChildrenSelected ? 'Deselect All' : 'Select All' }}
        </button>
      </header>
      <p v-if="!familyChildren.length" class="activity-no-children">
        Add children to your family before assigning activities.
      </p>
      <label v-for="child in familyChildren" :key="child.id" class="activity-child-option">
        <input
          type="checkbox"
          :checked="isChildSelected(child.id)"
          @change="toggleChild(child.id, $event.target.checked)"
        />
        <span class="activity-child-avatar" aria-hidden="true">{{ childInitials(child.name) }}</span>
        <span class="activity-child-name">{{ child.name }}</span>
        <span class="activity-child-age">{{ childAge(child.date_of_birth) }}</span>
      </label>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  familyChildren: {
    type: Array,
    default: () => [],
  },
  hideDefaultTp: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

const allChildrenSelected = computed(
  () =>
    props.familyChildren.length > 0 &&
    props.familyChildren.every((child) => isChildSelected(child.id)),
)

function updateField(field, value) {
  emit('update:modelValue', { ...props.modelValue, [field]: value })
}

function isChildSelected(childId) {
  return (props.modelValue.child_ids || []).some((id) => String(id) === String(childId))
}

function toggleChild(childId, selected) {
  const childIds = (props.modelValue.child_ids || []).filter(
    (id) => String(id) !== String(childId),
  )

  if (selected) {
    childIds.push(childId)
  }

  updateField('child_ids', childIds)
}

function toggleAllChildren() {
  updateField(
    'child_ids',
    allChildrenSelected.value ? [] : props.familyChildren.map((child) => child.id),
  )
}

function childInitials(name) {
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

function childAge(dateOfBirth) {
  if (!dateOfBirth) {
    return 'Age unavailable'
  }

  const birthDate = new Date(`${dateOfBirth}T00:00:00`)
  if (Number.isNaN(birthDate.getTime())) {
    return 'Age unavailable'
  }

  const today = new Date()
  let age = today.getFullYear() - birthDate.getFullYear()
  const birthdayHasPassed =
    today.getMonth() > birthDate.getMonth() ||
    (today.getMonth() === birthDate.getMonth() && today.getDate() >= birthDate.getDate())

  if (!birthdayHasPassed) {
    age -= 1
  }

  return age >= 0 ? `Age ${age}` : 'Age unavailable'
}

function numericValue(value) {
  return value === '' ? '' : Number(value)
}
</script>

<style scoped>
.activity-editor-fields {
  display: grid;
  gap: 11px;
  min-width: 0;
}

.activity-form-fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 11px;
  min-width: 0;
}

.activity-form-field-wide {
  grid-column: 1 / -1;
}

.activity-form-fields label {
  display: grid;
  gap: 5px;
  min-width: 0;
  color: var(--color-text-muted);
  font-size: var(--text-caption);
  font-weight: 700;
}

.activity-form-fields input,
.activity-form-fields textarea,
.activity-form-fields select {
  width: 100%;
  min-width: 0;
  min-height: 38px;
  padding: 8px 10px;
  border: 1px solid var(--color-border-strong);
  border-radius: 8px;
  background: var(--color-input);
  color: var(--color-text);
  font: inherit;
  font-size: var(--text-caption);
}

.activity-form-fields textarea {
  resize: vertical;
}

.activity-form-fields input:focus,
.activity-form-fields textarea:focus,
.activity-form-fields select:focus {
  border-color: var(--color-primary);
  outline: 2px solid var(--color-primary-focus);
}

.activity-child-assignment {
  display: grid;
  gap: 5px;
  padding: 9px 10px;
  border: 1px solid var(--color-border-strong);
  border-radius: 9px;
  background: var(--color-chip);
}

.activity-child-assignment > header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 0 2px 4px;
}

.activity-child-assignment > header strong {
  color: var(--color-text-muted);
  font-size: 9px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.activity-select-all {
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--color-primary);
  font: inherit;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
}

.activity-child-option {
  display: flex;
  align-items: center;
  gap: 7px;
  min-height: 31px;
  padding: 4px 2px;
  border-top: 1px solid var(--color-border-strong);
  cursor: pointer;
}

.activity-child-option input {
  width: 14px;
  height: 14px;
  flex: 0 0 auto;
  accent-color: var(--color-primary);
}

.activity-child-avatar {
  display: grid;
  width: 22px;
  height: 22px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 50%;
  background: var(--color-chip);
  color: var(--color-primary);
  font-size: 9px;
  font-weight: 800;
}

.activity-child-name {
  overflow: hidden;
  color: var(--color-text);
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.activity-child-age {
  margin-left: auto;
  padding: 2px 7px;
  border-radius: 999px;
  background: var(--color-chip);
  color: var(--color-primary);
  font-size: 9px;
  font-weight: 700;
  white-space: nowrap;
}

.activity-no-children {
  margin: 0;
  color: var(--color-text-muted);
  font-size: 10px;
}

@media (max-width: 760px) {
  .activity-editor-fields,
  .activity-form-fields {
    gap: 9px;
  }

  .activity-form-fields label {
    font-size: 11px;
  }

  .activity-form-fields input,
  .activity-form-fields textarea,
  .activity-form-fields select {
    min-height: 36px;
    padding: 7px 9px;
  }
}
</style>
