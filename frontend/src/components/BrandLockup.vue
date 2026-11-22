<template>
  <RouterLink :to="to" class="brand-lockup" :aria-label="ariaLabel">
    <span class="brand-mark" aria-hidden="true" />
    <span class="brand-name">kids <span>tp</span></span>
  </RouterLink>
</template>

<script setup>
defineProps({
  to: {
    type: String,
    default: '/',
  },
  ariaLabel: {
    type: String,
    default: 'Kids TP home',
  },
})
</script>

<style scoped>
.brand-lockup {
  position: relative;
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 0;
  color: #075E54;
  text-decoration: none;
  white-space: nowrap;
}

.brand-mark {
  position: relative;
  z-index: 1;
  display: block;
  width: 84px;
  margin-right: -14px;
  aspect-ratio: 390 / 315;
  background: #075E54;
  transform: translateY(-8px);
  -webkit-mask: url('/logo.png') center / contain no-repeat;
  mask: url('/logo.png') center / contain no-repeat;
}

.brand-name {
  position: relative;
  z-index: 0;
  font-size: 30px;
  font-weight: 900;
  letter-spacing: -1.2px;
  line-height: 1;
}

.brand-name span {
  color: #075E54;
}

@media (max-width: 600px) {
  .brand-mark {
    width: 66px;
    margin-right: -11px;
    transform: translateY(-6px);
  }

  .brand-name {
    font-size: 26px;
  }
}

@media (max-width: 360px) {
  .brand-mark {
    width: 54px;
    margin-right: -9px;
  }

  .brand-name {
    font-size: 23px;
  }
}
</style>
