<template>
  <div
    class="family-orbit"
    :class="`family-orbit--${variant}`"
    role="img"
    aria-label="Kids TP family"
  >
    <span class="orbit orbit-outer" aria-hidden="true" />
    <span class="orbit orbit-inner" aria-hidden="true" />
    <span class="orbit-glow" aria-hidden="true" />
    <span class="family-mark" aria-hidden="true" />
    <span class="sparkle sparkle-one" aria-hidden="true" />
    <span class="sparkle sparkle-two" aria-hidden="true" />
  </div>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'landing',
    validator: (value) => ['landing', 'auth'].includes(value),
  },
})
</script>

<style scoped>
.family-orbit {
  position: relative;
  display: grid;
  width: min(54vw, 420px);
  min-width: 260px;
  aspect-ratio: 1;
  place-items: center;
}

.orbit,
.orbit-glow {
  position: absolute;
  border-radius: 50%;
}

.orbit-outer {
  inset: 2% 5% 0 0;
  border: 1px solid rgba(75, 85, 49, 0.12);
  transform: rotate(-7deg);
}

.orbit-inner {
  inset: 8% 2% 7% 5%;
  border: 1px solid rgba(75, 85, 49, 0.15);
  transform: rotate(5deg);
}

.orbit-glow {
  inset: 9%;
  background: radial-gradient(
    circle at 42% 36%,
    rgba(213, 245, 140, 0.78),
    rgba(213, 245, 140, 0.54) 62%,
    rgba(119, 127, 67, 0.28)
  );
  box-shadow: 14px 20px 30px rgba(57, 67, 44, 0.12);
}

.family-mark {
  position: relative;
  z-index: 1;
  display: block;
  width: 108%;
  aspect-ratio: 390 / 315;
  background: var(--color-primary-dark);
  filter: drop-shadow(0 1px 1px rgba(41, 43, 32, 0.14));
  transform: translateY(2%);
  -webkit-mask: url('/logo.png') center / contain no-repeat;
  mask: url('/logo.png') center / contain no-repeat;
}

.sparkle {
  position: absolute;
  z-index: 2;
  width: 15px;
  height: 15px;
  background: var(--color-primary-light);
  clip-path: polygon(50% 0, 60% 40%, 100% 50%, 60% 60%, 50% 100%, 40% 60%, 0 50%, 40% 40%);
}

.sparkle-one {
  top: 20%;
  left: 18%;
  width: 18px;
  height: 18px;
  background: var(--color-primary);
}

.sparkle-two {
  top: 28%;
  right: 14%;
}

.family-orbit--auth {
  width: min(30vw, 320px);
  min-width: 220px;
}

@media (max-width: 600px) {
  .family-orbit {
    width: min(70vw, 300px);
    min-width: 0;
  }

  .family-orbit--auth {
    width: min(44vw, 180px);
  }
}

@media (min-width: 1200px) {
  .family-orbit {
    width: min(30vw, 420px);
  }
}

@media (max-width: 360px) {
  .family-orbit {
    width: min(70vw, 280px);
  }

  .family-orbit--auth {
    width: min(42vw, 150px);
  }
}

@media (min-width: 601px) and (max-height: 820px) {
  .family-orbit {
    width: min(48vh, 360px);
  }
}

@media (max-height: 600px) {
  .family-orbit {
    width: min(44vh, 300px);
    min-width: 200px;
  }

  .family-orbit--auth {
    width: min(25vh, 160px);
    min-width: 0;
  }
}
</style>
