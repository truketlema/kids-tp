<template>
  <header class="public-header">
    <BrandLockup />

    <p v-if="prompt && linkText && linkTo" class="public-member-link">
      {{ prompt }}
      <RouterLink :to="linkTo">
        {{ linkText }}
        <span class="public-header-arrow" aria-hidden="true">↗</span>
      </RouterLink>
    </p>
  </header>
</template>

<script setup>
import BrandLockup from './BrandLockup.vue'

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

.public-member-link {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
  color: #111B21;
  font-size: var(--text-small);
}

.public-member-link a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #075E54;
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
  color: #075E54;
  font-size: 15px;
  line-height: 1;
}

@media (max-width: 600px) {
  .public-header {
    min-height: 64px;
  }

  .public-member-link {
    gap: 6px;
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
