<template>
  <header class="public-header">
    <RouterLink to="/" class="public-brand" aria-label="Kids TP home">
      <span class="public-brand-mark" aria-hidden="true" />
      <span class="public-brand-name">kids <span>tp</span></span>
    </RouterLink>

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

.public-brand {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0;
  color: var(--color-primary-dark);
  text-decoration: none;
}

.public-brand-mark {
  position: relative;
  z-index: 1;
  display: block;
  width: 84px;
  margin-right: -14px;
  aspect-ratio: 390 / 315;
  background: var(--color-primary);
  transform: translateY(-8px);
  -webkit-mask: url('/logo.png') center / contain no-repeat;
  mask: url('/logo.png') center / contain no-repeat;
}

.public-brand-name {
  position: relative;
  z-index: 0;
  font-size: 30px;
  font-weight: 900;
  letter-spacing: -1.2px;
  line-height: 1;
}

.public-brand-name span {
  color: #777F43;
}

.public-member-link {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
  color: #59633A;
  font-size: var(--text-small);
}

.public-member-link a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--color-primary-dark);
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
  background: rgba(57, 67, 44, 0.12);
  color: var(--color-primary-dark);
  font-size: 15px;
  line-height: 1;
}

@media (max-width: 600px) {
  .public-header {
    min-height: 64px;
  }

  .public-brand {
    gap: 0;
  }

  .public-brand-mark {
    width: 66px;
    margin-right: -11px;
    transform: translateY(-6px);
  }

  .public-brand-name {
    font-size: 26px;
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
  .public-brand-mark {
    width: 54px;
    margin-right: -9px;
  }

  .public-brand-name {
    font-size: 23px;
  }

  .public-member-link {
    gap: 4px;
    font-size: 0.75rem;
  }
}
</style>
