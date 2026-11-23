<template>
  <header class="public-header">
    <BrandLockup />

    <div class="public-header-actions">
      <button
        class="theme-toggle"
        type="button"
        :aria-label="isDark ? 'Switch to light theme' : 'Switch to dark theme'"
        @click="toggleTheme"
      >
        <v-icon :icon="isDark ? 'mdi-white-balance-sunny' : 'mdi-weather-night'" />
      </button>
      <p v-if="prompt && linkText && linkTo" class="public-member-link">
        {{ prompt }}
        <RouterLink :to="linkTo">
          {{ linkText }}
          <span class="public-header-arrow" aria-hidden="true">↗</span>
        </RouterLink>
      </p>
    </div>
  </header>
</template>

<script setup>
import BrandLockup from './BrandLockup.vue'
import { useTheme } from '../composables/useTheme.js'

const { isDark, toggleTheme } = useTheme()

defineProps({
  prompt: {
    type: String,
    default: '',
  },
  linkText: {
    type: String,
    default: '',
  },
  linkTo: {
    type: String,
    default: '',
  },
})
</script>

<style scoped>
.public-header {
  display: flex;
  width: 100%;
  max-width: 1180px;
  min-height: 72px;
  align-items: center;
  justify-content: space-between;
  margin: 0 auto;
  border-bottom: 1px solid var(--color-border);
}

.public-header-actions {
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 14px;
}

.theme-toggle {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: transparent;
  color: var(--color-primary);
  cursor: pointer;
}

.theme-toggle:hover {
  background: var(--color-chip);
}

.public-member-link {
  display: flex;
  flex: 0 0 auto;
  flex-wrap: nowrap;
  align-items: center;
  gap: 6px;
  margin: 0;
  color: var(--color-text);
  font-size: var(--text-small);
  white-space: nowrap;
}

.public-member-link a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--color-primary);
  font-weight: 900;
  text-decoration: none;
}

.public-member-link a:hover {
  text-decoration: underline;
}

.public-header-arrow {
  display: inline-grid;
  width: 20px;
  height: 20px;
  place-items: center;
  border-radius: 5px;
  background: rgba(7, 94, 84, 0);
  color: var(--color-primary);
  font-size: 15px;
  line-height: 1;
}

@media (max-width: 600px) {
  .public-header {
    min-height: 64px;
  }

  .public-header-actions {
    gap: 8px;
  }

  .public-member-link {
    gap: 4px;
    font-size: var(--text-caption);
  }

  .public-header-arrow {
    width: 18px;
    height: 18px;
    font-size: 13px;
  }
}

@media (max-width: 360px) {
  .public-member-link {
    gap: 4px;
    font-size: 0.75rem;
  }
}
</style>
